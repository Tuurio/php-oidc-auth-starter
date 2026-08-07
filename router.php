<?php

/**
 * Tuurio Auth Studio — PHP built-in dev server router.
 *
 * Usage: php -S localhost:8080 router.php
 *
 * Routes requests to the appropriate handler and serves static
 * assets from the public/ directory with correct MIME types.
 *
 * @author  Tuurio GmbH, Berlin
 * @version 1.0.0 (2026-03-07)
 * @see     https://id.tuurio.com
 */

declare(strict_types=1);

// Load config once so route handling and startup logging use the same values.
$config = require __DIR__ . '/src/config.php';
$webhookPath = $config['webhook_listen_path'] ?? '/webhooks/tuurio';

// Log config once per server process (sanitized) to help verify env setup.
$configPrintedFlag = sys_get_temp_dir() . '/tuurio_auth_samples_config_printed';
if (!file_exists($configPrintedFlag)) {
    $safeConfig = $config;
    if (!empty($safeConfig['client_secret'])) {
        $secret = (string) $safeConfig['client_secret'];
        $safeConfig['client_secret'] = '[set]';
        $safeConfig['client_secret_len'] = strlen($secret);
        $safeConfig['client_secret_sha256_prefix'] = substr(hash('sha256', $secret), 0, 8);
    }
    if (!empty($safeConfig['webhook_api_key'])) {
        $apiKey = (string) $safeConfig['webhook_api_key'];
        $safeConfig['webhook_api_key'] = '[set]';
        $safeConfig['webhook_api_key_len'] = strlen($apiKey);
        $safeConfig['webhook_api_key_sha256_prefix'] = substr(hash('sha256', $apiKey), 0, 8);
    }
    error_log('Tuurio config: ' . json_encode($safeConfig, JSON_UNESCAPED_SLASHES));
    @file_put_contents($configPrintedFlag, "1");
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$publicRoot = __DIR__ . '/public';

if ($path === '/auth/callback') {
    require $publicRoot . '/auth/callback/index.php';
    return true;
}

if ($path === '/' && (isset($_GET['code']) || isset($_GET['error']))) {
    require $publicRoot . '/auth/callback/index.php';
    return true;
}

if ($path === '/login') {
    require $publicRoot . '/login.php';
    return true;
}

if ($path === '/logout') {
    require $publicRoot . '/logout.php';
    return true;
}

if ($path === '/logout/callback') {
    require $publicRoot . '/logout/callback/index.php';
    return true;
}

if ($path === $webhookPath) {
    require $publicRoot . '/webhook.php';
    return true;
}

if ($path === '/' || $path === '' || $path === '/index.php') {
    require $publicRoot . '/home.php';
    return true;
}

$target = realpath($publicRoot . $path);
if ($target && strpos($target, realpath($publicRoot)) === 0 && is_file($target)) {
    $ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));
    $mimeTypes = [
        'css'   => 'text/css',
        'js'    => 'application/javascript',
        'svg'   => 'image/svg+xml',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'json'  => 'application/json',
    ];
    header('Content-Type: ' . ($mimeTypes[$ext] ?? 'application/octet-stream'));
    readfile($target);
    return true;
}

require $publicRoot . '/404.php';
return true;
