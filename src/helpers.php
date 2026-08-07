<?php

/**
 * Tuurio Auth Studio — URL helpers.
 *
 * Base-path detection (supports webroot and subdirectory deployments)
 * and URL generation for internal links and asset references.
 *
 * @author  Tuurio GmbH, Berlin
 * @version 1.0.0 (2026-03-07)
 * @see     https://id.tuurio.com
 */

declare(strict_types=1);

/**
 * Detect the base URL path for the application.
 *
 * Uses SCRIPT_NAME (set by Apache mod_rewrite) to derive the URL
 * prefix. This is the standard approach used by Laravel, Symfony,
 * and Slim — it works with Aliases, symlinks, and nested directories.
 *
 * Returns '' for webroot, '/demo' for subdirectory, etc.
 */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    // PHP built-in dev server: router.php handles all URL mapping,
    // so no path prefix is needed.
    if (PHP_SAPI === 'cli-server') {
        $base = '';
        return $base;
    }

    // SCRIPT_NAME reflects the URL path to the front controller.
    // When the whole package is web-accessible, requests are internally routed
    // through /public/index.php. Strip that internal segment so generated URLs
    // stay aligned with the externally visible app path.
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $scriptName = preg_replace('#/public/index\.php$#', '/index.php', $scriptName);
    $base = rtrim(dirname($scriptName), '/');

    return $base;
}

/**
 * Generate a URL path relative to the application root.
 * Prepends the base path for subdirectory deployments.
 *
 * url('/login')  => '/login'       (webroot)
 * url('/login')  => '/demo/login'  (subdirectory)
 */
function url(string $path = '/'): string
{
    $bp = base_path();
    if ($path === '/') {
        return $bp === '' ? '/' : $bp . '/';
    }
    return $bp . '/' . ltrim($path, '/');
}
