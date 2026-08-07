<?php

/**
 * Tuurio Auth Studio — Home page.
 *
 * Renders the login view (unauthenticated) or the token inspection
 * dashboard (authenticated) with decoded JWT payloads and provider
 * endpoint discovery information.
 *
 * @author  Tuurio GmbH, Berlin
 * @version 1.0.0 (2026-03-07)
 * @see     https://id.tuurio.com
 */

declare(strict_types=1);

session_start();

$config = require __DIR__ . '/../src/config.php';
require __DIR__ . '/../src/oauth.php';
require __DIR__ . '/../src/view.php';

$session = $_SESSION['auth'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

$authority = $config['authority'];

if ($session) {
    $status = ['label' => 'Authenticated', 'tone' => 'good', 'authority' => $authority];
    $content = render_token_view($session, $config);
} else {
    $status = ['label' => 'Signed out', 'tone' => 'neutral', 'authority' => $authority];
    $content = render_login_view($error, $config);
}

render_page($status, $content);

// ── Login view ───────────────────────────────────────────

function render_login_view(?string $error, array $config): string
{
    $errorHtml = $error
        ? '<div class="alert alert-error">' . escape_html($error) . '</div>'
        : '';

    $alertsHtml = '';
    $loginDisabled = false;
    $xIcon = icon('x-circle', 16);
    $expectedCallbackPath = url('/auth/callback');
    $hasAppConfig = (bool) ($config['_has_app_config'] ?? false);

    if (!$hasAppConfig) {
        $loginDisabled = true;
        $alertsHtml .= <<<WARN
        <div class="alert alert-error alert-block">
          <strong>{$xIcon} Configuration missing</strong><br>
          No local <code>.env</code> file or explicit <code>TUURIO_*</code> environment variables were found.<br><br>
          Copy <code>.env.example</code> to <code>.env</code> and fill in your tenant values before signing in.
        </div>
WARN;
    }

    if (!$loginDisabled) {
        $configuredRedirectUri = $config['redirect_uri'] ?? '';
        $configuredPath = parse_url($configuredRedirectUri, PHP_URL_PATH) ?: '';

        if ($configuredPath !== '' && $configuredPath !== $expectedCallbackPath) {
            $loginDisabled = true;
            $safeConfigured = escape_html($configuredPath);
            $safeExpected = escape_html($expectedCallbackPath);
            $safeFullUri = escape_html($configuredRedirectUri);
            $alertsHtml .= <<<WARN
            <div class="alert alert-warning alert-block">
              <strong>{$xIcon} Redirect URI mismatch</strong><br>
              The configured <code>TUURIO_REDIRECT_URI</code> path does not match this deployment.<br><br>
              <strong>Configured:</strong> <code>{$safeConfigured}</code><br>
              <strong>Expected:</strong> <code>{$safeExpected}</code><br><br>
              Update the configured redirect URI so it matches this app path:<br>
              <code>{$safeFullUri}</code>
            </div>
WARN;
        }
    }

    $loginUrl = url('/login');
    $authorityHost = escape_html(
        parse_url($config['authority'], PHP_URL_HOST) ?? 'id.tuurio.com'
    );
    $buttonHtml = $loginDisabled
        ? '<button class="button primary" type="button" disabled>Continue with Tuurio ID <span class="btn-arrow">&rarr;</span></button>'
        : '<a class="button primary" href="' . $loginUrl . '">Continue with Tuurio ID <span class="btn-arrow">&rarr;</span></a>';

    $shieldIcon = icon('shield', 18);
    $clockIcon = icon('clock', 18);
    $serverIcon = icon('server', 18);

    return <<<HTML
    <div class="stack">
      {$alertsHtml}
      <section class="card card-hero">
        <div class="card-header">
          <span class="eyebrow">OAuth 2.0 + OpenID Connect</span>
          <h2 class="card-title">Sign in to continue</h2>
          <p class="muted">
            Authenticate with the authorization code flow and PKCE.
            Tokens are exchanged server-side and stored in the PHP session.
          </p>
        </div>
        <div class="button-row">
          {$buttonHtml}
          <span class="helper">Redirects to {$authorityHost}</span>
        </div>
        {$errorHtml}
      </section>

      <div class="feature-grid">
        <div class="feature-card">
          <div class="feature-icon">{$shieldIcon}</div>
          <h3>PKCE by default</h3>
          <p class="muted">Proof Key for Code Exchange prevents authorization code interception attacks.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">{$clockIcon}</div>
          <h3>Short-lived tokens</h3>
          <p class="muted">Access tokens expire quickly, scoped to openid&nbsp;profile&nbsp;email.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">{$serverIcon}</div>
          <h3>Session aware</h3>
          <p class="muted">Token state lives server-side. Nothing sensitive reaches the browser.</p>
        </div>
      </div>
    </div>
HTML;
}

// ── Authenticated view ───────────────────────────────────

function render_token_view(array $session, array $config): string
{
    $scopeLabel = $session['scope'] ?? 'openid profile email';
    $profileJson = $session['profile_json'] ?? 'No profile data.';
    $logoutUrl = url('/logout');

    // ── Token timing ─────────────────────────────────────
    $now = time();
    $expiresAt = $session['expires_at'] ?? null;
    $timingParts = [];
    if ($expiresAt !== null) {
        $remaining = (int) $expiresAt - $now;
        $timingParts[] = $remaining > 0
            ? format_duration($remaining) . ' remaining'
            : 'expired ' . format_duration(abs($remaining)) . ' ago';
    }
    $timingLabel = $timingParts !== [] ? implode(' &middot; ', $timingParts) : '';
    $expiryLine = $timingLabel !== ''
        ? "<code>{$scopeLabel}</code> &middot; {$timingLabel}"
        : "<code>{$scopeLabel}</code>";

    $userIcon = icon('user', 18);
    $checkIcon = icon('check-circle', 14);

    $profileHighlighted = highlight_json_html(escape_html($profileJson));

    // ── Discovery ────────────────────────────────────────
    $authority = escape_html($config['authority']);
    $discoveryUrl = escape_html($config['discovery_endpoint']);
    $discoverySection = render_discovery_section($authority, $discoveryUrl);

    return <<<HTML
    <div class="stack">
      <section class="card card-hero">
        <div class="card-header">
          <span class="badge badge-success">{$checkIcon} Authenticated</span>
          <h2 class="card-title">Session active</h2>
          <p class="muted">{$expiryLine}</p>
        </div>
        <div class="button-row">
          <a class="button ghost" href="{$logoutUrl}">Log out and end session</a>
          <span class="helper">Destroys tokens and notifies the identity provider.</span>
        </div>
      </section>

      <section class="card">
        <div class="section-header">
          <div class="section-icon">{$userIcon}</div>
          <div>
            <h3 class="section-title">User profile</h3>
            <p class="muted">Claims returned by the UserInfo endpoint.</p>
          </div>
        </div>
        <pre class="code-block">{$profileHighlighted}</pre>
      </section>

      {$discoverySection}
    </div>
HTML;
}

// ── Duration formatter ───────────────────────────────────

function format_duration(int $seconds): string
{
    $seconds = abs($seconds);
    if ($seconds < 60) {
        return "{$seconds}s";
    }
    if ($seconds < 3600) {
        $m = intdiv($seconds, 60);
        $s = $seconds % 60;
        return $s > 0 ? "{$m}m {$s}s" : "{$m}m";
    }
    $h = intdiv($seconds, 3600);
    $m = intdiv($seconds % 3600, 60);
    return $m > 0 ? "{$h}h {$m}m" : "{$h}h";
}

// ── Discovery endpoints ──────────────────────────────────

function render_discovery_section(string $authority, string $discoveryUrl): string
{
    $globeIcon = icon('globe', 18);

    return <<<HTML
      <section class="card">
        <div class="section-header">
          <div class="section-icon">{$globeIcon}</div>
          <div>
            <h3 class="section-title">Provider endpoints</h3>
            <p class="muted">
              Published at
              <a href="{$discoveryUrl}" target="_blank" rel="noopener" class="link">{$discoveryUrl}</a>
            </p>
          </div>
        </div>
        <div class="endpoint-grid">
          <div class="endpoint-item">
            <span class="endpoint-label">Authorization</span>
            <code class="endpoint-value">{$authority}/oauth2/authorize</code>
          </div>
          <div class="endpoint-item">
            <span class="endpoint-label">Token</span>
            <code class="endpoint-value">{$authority}/oauth2/token</code>
          </div>
          <div class="endpoint-item">
            <span class="endpoint-label">UserInfo</span>
            <code class="endpoint-value">{$authority}/userinfo</code>
          </div>
          <div class="endpoint-item">
            <span class="endpoint-label">End Session</span>
            <code class="endpoint-value">{$authority}/oauth2/logout</code>
          </div>
          <div class="endpoint-item">
            <span class="endpoint-label">JWKS</span>
            <code class="endpoint-value">{$authority}/jwks</code>
          </div>
        </div>
      </section>
HTML;
}
