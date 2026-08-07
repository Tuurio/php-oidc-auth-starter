<?php

/**
 * Tuurio Auth Studio — Application configuration.
 *
 * Loads environment variables from .env and returns a normalized
 * configuration array with all OAuth / OIDC endpoints and secrets.
 *
 * @author  Tuurio GmbH, Berlin
 * @version 1.0.0 (2026-03-07)
 * @see     https://id.tuurio.com
 */

declare(strict_types=1);

require_once __DIR__ . '/env.php';

$envFile = __DIR__ . '/../.env';
$env = loadEnv($envFile);
$envExists = is_file($envFile);
$hasAppConfig = false;
foreach (['TUURIO_ISSUER', 'TUURIO_CLIENT_ID', 'TUURIO_CLIENT_SECRET', 'TUURIO_REDIRECT_URI'] as $key) {
    if (envValue($env, $key) !== null) {
        $hasAppConfig = true;
        break;
    }
}

$authority = normalizeAuthority(envValue($env, 'TUURIO_ISSUER')) ?? 'https://your-tenant.id.tuurio.com';
$clientId = sanitizeClientId(envValue($env, 'TUURIO_CLIENT_ID')) ?? 'replace-after-browser-handoff';
$scope = sanitizeScope(envValue($env, 'TUURIO_SCOPE')) ?? 'openid profile email';

return [
    'authority' => $authority,
    'authorize_endpoint' => $authority . '/oauth2/authorize',
    'token_endpoint' => $authority . '/oauth2/token',
    'discovery_endpoint' => $authority . '/.well-known/openid-configuration',
    'client_id' => $clientId,
    'client_secret' => envValue($env, 'TUURIO_CLIENT_SECRET') ?? '',
    'redirect_uri' => normalizeUrl(envValue($env, 'TUURIO_REDIRECT_URI')) ?? 'http://localhost:8080/auth/callback',
    'post_logout_redirect_uri' => normalizeUrl(envValue($env, 'TUURIO_POST_LOGOUT_REDIRECT_URI')) ?? 'http://localhost:8080/logout/callback',
    'scope' => $scope,
    'webhook_id' => envValue($env, 'TUURIO_WEBHOOK_ID') ?? '',
    'webhook_url' => normalizeUrl(envValue($env, 'TUURIO_WEBHOOK_URL')) ?? '',
    'webhook_edit_url' => normalizeUrl(envValue($env, 'TUURIO_WEBHOOK_EDIT_URL')) ?? '',
    'webhook_signing_secret' => envValue($env, 'TUURIO_WEBHOOK_SIGNING_SECRET') ?? '',
    'webhook_listen_path' => normalizeWebhookPath(envValue($env, 'TUURIO_WEBHOOK_LISTEN_PATH')) ?? '/webhooks/tuurio',
    'webhook_api_key_header' => sanitizeHeaderName(envValue($env, 'TUURIO_WEBHOOK_API_KEY_HEADER')) ?? 'X-Tuurio-Webhook-Key',
    'webhook_api_key' => envValue($env, 'TUURIO_WEBHOOK_API_KEY') ?? '',
    '_env_exists' => $envExists,
    '_has_app_config' => $hasAppConfig,
];
