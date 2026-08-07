<?php

/**
 * Tuurio Auth Studio — Login initiator.
 *
 * Generates PKCE verifier + state, stores them in the session,
 * and redirects to the authorization endpoint.
 *
 * @author  Tuurio GmbH, Berlin
 * @version 1.0.0 (2026-03-07)
 * @see     https://id.tuurio.com
 */

declare(strict_types=1);

session_start();

$config = require __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/helpers.php';
require __DIR__ . '/../src/oauth.php';

if (!(bool) ($config['_has_app_config'] ?? false)) {
    $_SESSION['error'] = 'Configuration missing. Copy .env.example to .env or provide the TUURIO_* environment variables before signing in.';
    header('Location: ' . url('/'), true, 302);
    exit;
}

$expectedCallbackPath = url('/auth/callback');
$configuredPath = parse_url($config['redirect_uri'] ?? '', PHP_URL_PATH) ?: '';
if ($configuredPath !== '' && $configuredPath !== $expectedCallbackPath) {
    $_SESSION['error'] = 'Redirect URI mismatch. Update TUURIO_REDIRECT_URI so it ends with ' . $expectedCallbackPath . '.';
    header('Location: ' . url('/'), true, 302);
    exit;
}

$state = generate_random_string(32);
$verifier = generate_random_string(64);

$_SESSION['oauth'] = [
    'state' => $state,
    'verifier' => $verifier,
];

$authorizeUrl = build_authorize_url($config, $state, $verifier);

header('Location: ' . $authorizeUrl, true, 302);
exit;
