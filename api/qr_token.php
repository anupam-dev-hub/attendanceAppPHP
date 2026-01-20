<?php
// api/qr_token.php
// App Token Management API
// Handles app token generation, retrieval, and reset

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require '../config.php';
require __DIR__ . '/auth_utils.php';
require __DIR__ . '/qr_utils.php';

// Require Bearer token authentication
$org_id = require_org_auth($conn);

// GET: Retrieve current app token or generate new one
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if org has an active app token
    $current_token = get_current_qr_token($conn, $org_id);
    
    if (!$current_token) {
        // Generate new token if none exists
        $current_token = create_qr_token_for_org($conn, $org_id);
        
        if (!$current_token) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to generate app token'
            ]);
            exit;
        }
    }
    
    // QR code is now generated on frontend using QRCode.js
    // No need to generate it server-side
    
    echo json_encode([
        'status' => 'success',
        'token' => $current_token['token'],
        'created_at' => $current_token['created_at'],
        'expires_at' => $current_token['expires_at']
    ]);
    exit;
}

// POST: Reset app token (revoke old, generate new)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Generate new token (this automatically revokes old ones)
    $new_token = create_qr_token_for_org($conn, $org_id);
    
    if (!$new_token) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to reset app token'
        ]);
        exit;
    }
    
    // QR code is generated on frontend
    
    echo json_encode([
        'status' => 'success',
        'message' => 'App token reset successfully',
        'token' => $new_token['token'],
        'created_at' => $new_token['created_at'],
        'expires_at' => $new_token['expires_at']
    ]);
    exit;
}

// Method not allowed
http_response_code(405);
echo json_encode([
    'status' => 'error',
    'message' => 'Method not allowed'
]);
?>
