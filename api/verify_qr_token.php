<?php
// api/verify_qr_token.php
// Verify QR Token Validity
// Checks if a QR token is valid and returns organization information

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require '../config.php';
require __DIR__ . '/qr_utils.php';

// Accept both GET and POST
$qr_token = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $_POST;
    if (empty($input)) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) $input = $decoded;
    }
    $qr_token = isset($input['qr_token']) ? trim($input['qr_token']) : null;
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $qr_token = isset($_GET['qr_token']) ? trim($_GET['qr_token']) : null;
}

// Validate input
if (!$qr_token) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'valid' => false,
        'message' => 'QR token is required'
    ]);
    exit;
}

// Verify QR token
$org_id = find_org_by_qr_token($conn, $qr_token);

if (!$org_id) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'valid' => false,
        'message' => 'Invalid or expired QR token'
    ]);
    exit;
}

// Get organization details
$stmt = $conn->prepare('SELECT id, name, email, phone FROM organizations WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $org_id);
$stmt->execute();
$result = $stmt->get_result();
$org = $result->fetch_assoc();

if (!$org) {
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'valid' => false,
        'message' => 'Organization not found'
    ]);
    exit;
}

// Get token details
$token_details = get_current_qr_token($conn, $org_id);

// Return success with organization info
echo json_encode([
    'status' => 'success',
    'valid' => true,
    'message' => 'QR token is valid',
    'organization' => [
        'id' => (int)$org['id'],
        'name' => $org['name'],
        'email' => $org['email'],
        'phone' => $org['phone']
    ],
    'token_info' => [
        'created_at' => $token_details['created_at'] ?? null,
        'expires_at' => $token_details['expires_at'] ?? null,
        'is_expired' => false
    ]
]);
?>
