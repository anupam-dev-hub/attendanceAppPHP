<?php
/**
 * Server Configuration Info
 * Returns server details and QR code data for app configuration
 */

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';

// Try to detect actual server IP/hostname
$host = $_SERVER['HTTP_HOST'] ?? '192.168.56.1';
if (strpos($host, ':') !== false) {
    $host = explode(':', $host)[0];
}

$port = $_SERVER['SERVER_PORT'] ?? 80;
if (($protocol === 'https' && $port == 443) || ($protocol === 'http' && $port == 80)) {
    $baseUrl = "{$protocol}://{$host}";
} else {
    $baseUrl = "{$protocol}://{$host}:{$port}";
}

$qrData = "appUrl_{$baseUrl}";

header('Content-Type: application/json');

$response = [
    'status' => 'success',
    'server' => [
        'protocol' => $protocol,
        'host' => $host,
        'port' => intval($port),
        'baseUrl' => $baseUrl,
    ],
    'app_config' => [
        'format' => 'appUrl_<server_url>',
        'qrData' => $qrData,
        'instructions' => [
            'Step 1: Go to Settings screen in the app',
            'Step 2: Tap "Scan Configuration QR"',
            'Step 3: Point camera at the QR code or manually enter the QR data',
            'Step 4: App will configure and reconnect to this server',
        ],
    ],
    'connectivity_check' => [
        'ping' => "{$baseUrl}/api/ping.php",
        'updates' => "{$baseUrl}/api/updates/check.php",
    ],
    'timestamp' => date('Y-m-d H:i:s'),
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>
