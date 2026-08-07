<?php

/**
 * Tuurio Auth Studio — Environment file parser and value sanitizers.
 *
 * Reads .env files (KEY=value format) and provides typed sanitizers
 * for the configuration values used by this app. No external
 * dependencies — works with pure PHP.
 *
 * @author  Tuurio GmbH, Berlin
 * @version 1.0.0 (2026-03-07)
 * @see     https://id.tuurio.com
 */

declare(strict_types=1);

function loadEnv(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $values = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return [];
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        $separator = strpos($trimmed, '=');
        if ($separator === false || $separator === 0) {
            continue;
        }

        $key = trim(substr($trimmed, 0, $separator));
        $value = trim(substr($trimmed, $separator + 1));
        $values[$key] = stripEnvQuotes($value);
    }

    return $values;
}

function stripEnvQuotes(string $value): string
{
    if ($value === '') {
        return '';
    }

    $first = $value[0];
    $last = $value[strlen($value) - 1];
    if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
        return substr($value, 1, -1);
    }

    return $value;
}

function envValue(array $env, string $key): ?string
{
    $value = $env[$key] ?? getenv($key);
    if ($value === false) {
        return null;
    }

    $trimmed = trim((string) $value);
    return $trimmed === '' ? null : $trimmed;
}

function normalizeAuthority(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    $parts = parse_url($value);
    if (!is_array($parts)) {
        return null;
    }

    $scheme = $parts['scheme'] ?? null;
    $host = $parts['host'] ?? null;
    if (!in_array($scheme, ['http', 'https'], true) || $host === null) {
        return null;
    }

    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
    return $scheme . '://' . $host . $port;
}

function normalizeUrl(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    $parts = parse_url($value);
    if (!is_array($parts)) {
        return null;
    }

    $scheme = $parts['scheme'] ?? null;
    $host = $parts['host'] ?? null;
    if (!in_array($scheme, ['http', 'https'], true) || $host === null) {
        return null;
    }

    return $value;
}

function sanitizeClientId(?string $value): ?string
{
    if ($value === null || strlen($value) > 120) {
        return null;
    }

    return preg_match('/^[A-Za-z0-9._-]+$/', $value) === 1 ? $value : null;
}

function sanitizeScope(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    $parts = preg_split('/\s+/', trim($value)) ?: [];
    $filtered = [];
    foreach ($parts as $part) {
        if ($part !== '' && preg_match('/^[A-Za-z0-9._:-]+$/', $part) === 1) {
            $filtered[] = $part;
        }
    }

    if ($filtered === []) {
        return null;
    }

    return implode(' ', $filtered);
}

function normalizeWebhookPath(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    $raw = trim($value);
    if ($raw === '' || !str_starts_with($raw, '/') || str_contains($raw, ' ')) {
        return null;
    }

    return $raw;
}

function sanitizeHeaderName(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    if (strlen($value) > 120) {
        return null;
    }

    return preg_match('/^[A-Za-z0-9-]+$/', $value) === 1 ? $value : null;
}
