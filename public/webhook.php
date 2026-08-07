<?php

/**
 * Tuurio Auth Studio — Webhook receiver.
 *
 * Accepts POST requests from Tuurio ID webhook events, validates
 * the API key header, and logs the event payload to error_log.
 *
 * @author  Tuurio GmbH, Berlin
 * @version 1.0.0 (2026-03-07)
 * @see     https://id.tuurio.com
 */

declare(strict_types=1);

$config = require __DIR__ . '/../src/config.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['accepted' => false, 'error' => 'method_not_allowed']);
    return;
}

$headers = function_exists('getallheaders') ? getallheaders() : [];
$readHeader = static function (array $source, string $name): string {
    foreach ($source as $key => $value) {
        if (strcasecmp((string) $key, $name) === 0) {
            return is_array($value) ? (string) reset($value) : (string) $value;
        }
    }
    return '';
};

$configuredApiKey = (string) ($config['webhook_api_key'] ?? '');
$apiKeyHeader = (string) ($config['webhook_api_key_header'] ?? 'X-Tuurio-Webhook-Key');
$providedApiKey = $readHeader($headers, $apiKeyHeader);

if ($configuredApiKey !== '' && $configuredApiKey !== $providedApiKey) {
    error_log('[tuurio-webhook] rejected request with invalid API key header');
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['accepted' => false, 'error' => 'invalid_api_key']);
    return;
}

$rawBody = file_get_contents('php://input') ?: '';
$decoded = json_decode($rawBody, true);
$payload = json_last_error() === JSON_ERROR_NONE ? $decoded : $rawBody;

$record = [
    'webhookId' => ($config['webhook_id'] ?? '') !== '' ? $config['webhook_id'] : null,
    'eventType' => $readHeader($headers, 'X-Tuurio-Event') ?: 'unknown',
    'eventId' => $readHeader($headers, 'X-Tuurio-Event-Id'),
    'signature' => $readHeader($headers, 'X-Tuurio-Signature'),
    'payload' => $payload,
];
error_log('[tuurio-webhook] event received ' . json_encode($record, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

http_response_code(202);
header('Content-Type: application/json');
echo json_encode(['accepted' => true]);
