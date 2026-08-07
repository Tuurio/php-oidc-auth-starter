<?php

/**
 * Tuurio Auth Studio — OAuth callback handler.
 *
 * Receives the authorization code from the IdP, validates state,
 * exchanges the code for tokens, fetches the UserInfo profile,
 * and stores everything in the PHP session.
 *
 * @author  Tuurio GmbH, Berlin
 * @version 1.0.0 (2026-03-07)
 * @see     https://id.tuurio.com
 */

declare(strict_types=1);

session_start();

$config = require __DIR__ . '/../../../src/config.php';
require __DIR__ . '/../../../src/oauth.php';

try {
    if (isset($_GET['error'])) {
        $message = $_GET['error_description'] ?? 'Login failed.';
        throw new RuntimeException($message);
    }

    $code = $_GET['code'] ?? null;
    $state = $_GET['state'] ?? null;
    if (!$code) {
        throw new RuntimeException('Missing authorization code.');
    }

    $oauth = $_SESSION['oauth'] ?? null;
    if (!$oauth || ($oauth['state'] ?? null) !== $state) {
        throw new RuntimeException('State mismatch.');
    }

    $tokens = exchange_code_for_tokens($config, $code, $oauth['verifier']);
    $accessToken = $tokens['access_token'] ?? null;
    if (!$accessToken) {
        throw new RuntimeException('Missing access token.');
    }

    $expiresAt = null;
    if (!empty($tokens['expires_in'])) {
        $expiresAt = time() + (int) $tokens['expires_in'];
    } elseif (!empty($tokens['expires_at'])) {
        $expiresAt = (int) $tokens['expires_at'];
    }

    $discovery = fetch_discovery($config);
    $userInfoEndpoint = $discovery['userinfo_endpoint'] ?? null;
    if (!is_string($userInfoEndpoint) || $userInfoEndpoint === '') {
        throw new RuntimeException('UserInfo endpoint missing from discovery metadata.');
    }
    $profile = fetch_userinfo($userInfoEndpoint, $accessToken);
    $profileJson = json_encode($profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    $_SESSION['auth'] = [
        'access_token' => $accessToken,
        'id_token' => $tokens['id_token'] ?? null,
        'scope' => $tokens['scope'] ?? null,
        'expires_at' => $expiresAt,
        'profile_json' => $profileJson,
    ];

    unset($_SESSION['oauth']);

    header('Location: ' . url('/'), true, 302);
    exit;
} catch (Throwable $exception) {
    $_SESSION['error'] = $exception->getMessage();
    header('Location: ' . url('/'), true, 302);
    exit;
}
