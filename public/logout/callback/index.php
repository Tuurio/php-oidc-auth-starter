<?php

/**
 * Tuurio Auth Studio — Logout confirmation page.
 *
 * Landing page after RP-Initiated Logout. Displays proof that
 * the local session has been destroyed and tokens discarded.
 *
 * @author  Tuurio GmbH, Berlin
 * @version 1.0.0 (2026-03-07)
 * @see     https://id.tuurio.com
 */

declare(strict_types=1);

session_start();

$config = require __DIR__ . '/../../../src/config.php';
require __DIR__ . '/../../../src/view.php';

// Defensive cleanup: if a stale session survived the redirect chain,
// clear it so the page always reflects the logged-out state.
if (!empty($_SESSION['auth'])) {
    $_SESSION = [];
    session_regenerate_id(true);
}

// Collect proof that everything has been cleared.
$sessionData = $_SESSION;
$cookieName = session_name();
$hasCookie = isset($_COOKIE[$cookieName]);

$loginUrl = url('/login');

$sessionJson = highlight_json_html(
    escape_html(json_encode($sessionData, JSON_PRETTY_PRINT) ?: '{}')
);
$cookieStatus = $hasCookie
    ? 'Present (will be removed on next request)'
    : 'Removed';

$checkIcon = icon('check-circle', 14);
$logOutIcon = icon('log-out', 18);
$shieldIcon = icon('shield', 18);
$clockIcon = icon('clock', 18);

$status = ['label' => 'Signed out', 'tone' => 'neutral', 'authority' => $config['authority']];
$content = <<<HTML
    <div class="stack">
      <section class="card card-hero">
        <div class="card-header">
          <span class="badge badge-neutral">{$checkIcon} Session ended</span>
          <h2 class="card-title">Successfully signed out</h2>
          <p class="muted">
            Local session destroyed. The identity provider has been
            notified via RP-Initiated Logout.
          </p>
        </div>
        <div class="button-row">
          <a class="button primary" href="{$loginUrl}">
            Sign in again
            <span class="btn-arrow">&rarr;</span>
          </a>
          <span class="helper">Redirects to the identity provider.</span>
        </div>
      </section>

      <section class="card">
        <div class="section-header">
          <div class="section-icon">{$logOutIcon}</div>
          <div>
            <h3 class="section-title">Session state</h3>
            <p class="muted">Proof that all credentials have been cleared.</p>
          </div>
        </div>
        <div class="cleared-grid">
          <div class="cleared-item">
            <span class="cleared-label">Access Token</span>
            <span class="cleared-value cleared-empty">discarded</span>
          </div>
          <div class="cleared-item">
            <span class="cleared-label">ID Token</span>
            <span class="cleared-value cleared-empty">discarded</span>
          </div>
          <div class="cleared-item">
            <span class="cleared-label">Session Cookie ({$cookieName})</span>
            <span class="cleared-value cleared-empty">{$cookieStatus}</span>
          </div>
          <div class="cleared-item">
            <span class="cleared-label">\$_SESSION</span>
            <pre class="code-block">{$sessionJson}</pre>
          </div>
        </div>
      </section>

      <div class="feature-grid">
        <div class="feature-card">
          <div class="feature-icon">{$shieldIcon}</div>
          <h3>Session destroyed</h3>
          <p class="muted">PHP session data cleared and cookie invalidated.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">{$clockIcon}</div>
          <h3>SSO logout</h3>
          <p class="muted">RP-Initiated Logout sent to the OpenID provider.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">{$logOutIcon}</div>
          <h3>Tokens discarded</h3>
          <p class="muted">Access and ID tokens removed from server memory.</p>
        </div>
      </div>
    </div>
HTML;

render_page($status, $content);
