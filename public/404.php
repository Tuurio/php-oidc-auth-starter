<?php

/**
 * Tuurio Auth Studio — 404 Not Found page.
 *
 * @author  Tuurio GmbH, Berlin
 * @version 1.0.0 (2026-03-07)
 * @see     https://id.tuurio.com
 */

declare(strict_types=1);

session_start();

$config = require __DIR__ . '/../src/config.php';
require __DIR__ . '/../src/view.php';

$homeUrl = url('/');
$xcircleIcon = icon('x-circle', 14);

$status = ['label' => 'Route not found', 'tone' => 'neutral', 'authority' => $config['authority']];
$content = <<<HTML
  <section class="card card-hero">
    <div class="card-header">
      <span class="badge badge-error">{$xcircleIcon} 404</span>
      <h2 class="card-title">Route not found</h2>
      <p class="muted">This path doesn't match any known endpoint.</p>
    </div>
    <div class="button-row">
      <a class="button ghost" href="{$homeUrl}">
        Go home
        <span class="btn-arrow">&rarr;</span>
      </a>
    </div>
  </section>
HTML;

render_page($status, $content);
