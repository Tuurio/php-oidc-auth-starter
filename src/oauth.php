<?php

/**
 * Tuurio Auth Studio — OAuth 2.1 / OpenID Connect helpers.
 *
 * Pure-PHP functions for PKCE generation, authorization URL building,
 * token exchange, discovery fetching, and UserInfo calls.
 * No external dependencies required.
 *
 * @author  Tuurio GmbH, Berlin
 * @version 1.0.0 (2026-03-07)
 * @see     https://id.tuurio.com
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function generate_random_string(int $length = 32): string
{
    return base64url_encode(random_bytes($length));
}

function pkce_challenge(string $verifier): string
{
    return base64url_encode(hash('sha256', $verifier, true));
}

function build_authorize_url(array $config, string $state, string $verifier): string
{
    $params = [
        'client_id' => $config['client_id'],
        'redirect_uri' => $config['redirect_uri'],
        'response_type' => 'code',
        'scope' => $config['scope'],
        'state' => $state,
        'code_challenge' => pkce_challenge($verifier),
        'code_challenge_method' => 'S256',
    ];

    return $config['authorize_endpoint'] . '?' . http_build_query($params);
}

function exchange_code_for_tokens(array $config, string $code, string $verifier): array
{
    $params = [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $config['redirect_uri'],
        'client_id' => $config['client_id'],
        'code_verifier' => $verifier,
    ];

    $clientSecret = $config['client_secret'] ?? '';

    $curlOptions = [];
    if ($clientSecret !== '') {
        $curlOptions[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
        $curlOptions[CURLOPT_USERPWD] = $config['client_id'] . ':' . $clientSecret;
    }

    $payload = http_build_query($params);

    $ch = curl_init($config['token_endpoint']);
    $options = [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
    ];

    if ($curlOptions) {
        $options = $options + $curlOptions;
    }

    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        throw new RuntimeException('Token request failed.');
    }

    if ($status >= 400) {
        throw new RuntimeException('Token request failed.');
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        throw new RuntimeException('Unable to parse token response.');
    }

    return $data;
}

function fetch_discovery(array $config): array
{
    $response = file_get_contents($config['discovery_endpoint']);
    if ($response === false) {
        throw new RuntimeException('Unable to load discovery document.');
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        throw new RuntimeException('Invalid discovery document.');
    }

    return $data;
}

function fetch_userinfo(string $endpoint, string $accessToken): array
{
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ["Authorization: Bearer {$accessToken}"],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        throw new RuntimeException('UserInfo request failed.');
    }

    if ($status >= 400) {
        throw new RuntimeException('UserInfo request failed.');
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        throw new RuntimeException('Unable to parse UserInfo response.');
    }

    return $data;
}

/**
 * Build the OIDC RP-Initiated Logout URL.
 *
 * Fetches the end_session_endpoint from the discovery document and
 * appends the recommended parameters. Falls back to the post-logout
 * redirect URI directly if the IdP endpoint is unavailable.
 *
 * @see https://openid.net/specs/openid-connect-rpinitiated-1_0.html
 */
function build_end_session_url(array $config, ?string $idTokenHint = null): string
{
    $postLogoutRedirectUri = $config['post_logout_redirect_uri'] ?? url('/');

    try {
        $discovery = fetch_discovery($config);
        $endSessionEndpoint = $discovery['end_session_endpoint'] ?? null;
    } catch (Throwable) {
        return $postLogoutRedirectUri;
    }

    if ($endSessionEndpoint === null) {
        return $postLogoutRedirectUri;
    }

    $params = [
        'client_id' => $config['client_id'],
        'post_logout_redirect_uri' => $postLogoutRedirectUri,
    ];

    if ($idTokenHint !== null && $idTokenHint !== '') {
        $params['id_token_hint'] = $idTokenHint;
    }

    return $endSessionEndpoint . '?' . http_build_query($params);
}
