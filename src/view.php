<?php

/**
 * Tuurio Auth Studio — View layer and HTML rendering.
 *
 * Provides the page shell, SVG icon library, JSON syntax highlighting,
 * copy-to-clipboard script, and shared HTML escape utilities.
 *
 * @author  Tuurio GmbH, Berlin
 * @version 1.0.0 (2026-03-07)
 * @see     https://id.tuurio.com
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function escape_html(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// ── SVG icon library (Lucide-style) ──────────────────────

function icon(string $name, int $size = 18): string
{
    $paths = [
        'shield'       => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'clock'        => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'database'     => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>',
        'user'         => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'key'          => '<path d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/>',
        'id-card'      => '<rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>',
        'globe'        => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
        'code'         => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
        'server'       => '<rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/>',
        'layers'       => '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
        'lock'         => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'check-circle' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
        'x-circle'     => '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
        'arrow-right'  => '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
        'log-out'      => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
    ];

    $inner = $paths[$name] ?? '';
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $inner . '</svg>';
}

// ── JSON syntax highlighting ─────────────────────────────

function highlight_json_html(string $escapedHtml): string
{
    // Keys: "key" followed by :
    $result = preg_replace(
        '/(&quot;)((?:(?!&quot;).)*?)(&quot;)(\s*:)/',
        '<span class="hl-key">$1$2$3</span>$4',
        $escapedHtml
    );

    // String values after :
    $result = preg_replace(
        '/(:\s*)(&quot;)((?:(?!&quot;).)*?)(&quot;)/',
        '$1<span class="hl-str">$2$3$4</span>',
        $result
    );

    // Array string values (inside [ ])
    $result = preg_replace(
        '/([\[,]\s*)(&quot;)((?:(?!&quot;).)*?)(&quot;)/',
        '$1<span class="hl-str">$2$3$4</span>',
        $result
    );

    // Numbers
    $result = preg_replace(
        '/(:\s*)(-?\d+(?:\.\d+)?)([,\s\n\r}])/',
        '$1<span class="hl-num">$2</span>$3',
        $result
    );

    // Booleans and null
    $result = preg_replace(
        '/(:\s*)(true|false|null)\b/',
        '$1<span class="hl-bool">$2</span>',
        $result
    );

    return $result;
}

// ── Page layout ──────────────────────────────────────────

function render_page(array $status, string $content): void
{
    // Security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

    echo '<!doctype html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="utf-8" />';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1" />';
    echo '<title>Tuurio Auth Studio</title>';
    echo '<link rel="icon" type="image/svg+xml" href="' . url('/assets/favicon.svg') . '" />';
    echo '<link rel="stylesheet" href="' . url('/assets/app.css') . '" />';
    echo '</head>';
    echo '<body>';
    echo '<div id="app">';
    echo render_shell($status, $content);
    echo '</div>';
    echo render_copy_script();
    echo '</body>';
    echo '</html>';
}

// ── Copy-to-clipboard script ─────────────────────────────

function render_copy_script(): string
{
    return <<<'JS'
    <script>
    document.querySelectorAll('.token-block, .code-block').forEach(function(pre) {
        var wrap = document.createElement('div');
        wrap.className = 'copy-wrap';
        pre.parentNode.insertBefore(wrap, pre);
        wrap.appendChild(pre);
        var btn = document.createElement('button');
        btn.className = 'copy-btn';
        btn.textContent = 'Copy';
        btn.addEventListener('click', function() {
            navigator.clipboard.writeText(pre.textContent).then(function() {
                btn.textContent = 'Copied!';
                btn.classList.add('copied');
                setTimeout(function() { btn.textContent = 'Copy'; btn.classList.remove('copied'); }, 1500);
            });
        });
        wrap.appendChild(btn);
    });
    </script>
JS;
}

// ── App shell ────────────────────────────────────────────

function render_shell(array $status, string $content): string
{
    $tone = escape_html($status['tone']);
    $label = escape_html($status['label']);
    $authorityHost = escape_html(
        parse_url($status['authority'] ?? '', PHP_URL_HOST) ?? 'id.tuurio.com'
    );

    $codeIcon = icon('code', 16);
    $serverIcon = icon('server', 16);
    $layersIcon = icon('layers', 16);

    return <<<HTML
    <div class="app">
      <aside class="side-panel">
        <div class="brand">
          <div class="logo-mark">tu</div>
          <div>
            <p class="brand-name">Tuurio Auth Studio</p>
            <p class="brand-subtitle">OIDC playground for OAuth 2.0</p>
          </div>
        </div>

        <div class="side-card">
          <h1>Design for<br>secure sign in.</h1>
          <p class="muted">
            A minimal PHP server client that authenticates with OpenID Connect,
            inspects decoded tokens, and handles secure logout.
          </p>
          <div class="status-row">
            <span class="status status-{$tone}">{$label}</span>
            <span class="muted">{$authorityHost}</span>
          </div>
        </div>

        <div class="side-list">
          <div class="side-list-item">
            <span class="side-list-icon">{$codeIcon}</span>
            <div>
              <span class="side-list-label">Architecture</span>
              <span class="side-list-value">Auth code + PKCE</span>
            </div>
          </div>
          <div class="side-list-item">
            <span class="side-list-icon">{$serverIcon}</span>
            <div>
              <span class="side-list-label">Storage</span>
              <span class="side-list-value">Server session</span>
            </div>
          </div>
          <div class="side-list-item">
            <span class="side-list-icon">{$layersIcon}</span>
            <div>
              <span class="side-list-label">Scope</span>
              <span class="side-list-value">openid profile email</span>
            </div>
          </div>
        </div>
      </aside>

      <main class="main-panel">{$content}</main>
    </div>
HTML;
}
