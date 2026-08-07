<?php

/**
 * Tuurio Auth Studio — Logout handler.
 *
 * Captures the id_token for the logout hint, destroys the local
 * session completely, then redirects to the IdP end_session_endpoint
 * for RP-Initiated Logout (OpenID Connect).
 *
 * @author  Tuurio GmbH, Berlin
 * @version 1.0.0 (2026-03-07)
 * @see     https://id.tuurio.com
 */

declare(strict_types=1);

session_start();

$config = require __DIR__ . '/../src/config.php';
require __DIR__ . '/../src/oauth.php';

// Capture id_token BEFORE destroying the session.
// The OIDC spec recommends id_token_hint so the IdP can identify
// which session to terminate without requiring user interaction.
$idTokenHint = $_SESSION['auth']['id_token'] ?? null;

// ── Destroy local session completely ────────────────────────
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $p['path'],
        $p['domain'],
        $p['secure'],
        $p['httponly'],
    );
}

session_destroy();

// ── Redirect to IdP for SSO logout ─────────────────────────
$logoutUrl = build_end_session_url($config, $idTokenHint);

header('Location: ' . $logoutUrl, true, 302);
exit;
