<?php
// api/get_qr_token.php
// Get QR Token by Email/Password Authentication
// Returns the organization's QR token for attendance API access

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require '../config.php';
require __DIR__ . '/qr_utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

// Get input data
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) $input = $decoded;
}

$email = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? (string)$input['password'] : '';

// Validate input
if ($email === '' || $password === '') {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Email and password are required'
    ]);
    exit;
}

// Verify organization credentials
$stmt = $conn->prepare('SELECT id, password, name FROM organizations WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if (!($org = $result->fetch_assoc())) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid credentials'
    ]);
    exit;
}

$org_id = (int)$org['id'];
$org_name = $org['name'];
$hashed = $org['password'];

// Verify password
if (!password_verify($password, $hashed)) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid credentials'
    ]);
    exit;
}

// Get or create QR token
$qr_token = get_current_qr_token($conn, $org_id);

if (!$qr_token) {
    // Generate new QR token if none exists
    $qr_token = create_qr_token_for_org($conn, $org_id);
    
    if (!$qr_token) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to generate QR token'
        ]);
        exit;
    }
}

// Return QR token
echo json_encode([
    'status' => 'success',
    'org_id' => $org_id,
    'org_name' => $org_name,
    'qr_token' => $qr_token['token'],
    'created_at' => $qr_token['created_at'],
    'expires_at' => $qr_token['expires_at'],
    'message' => 'QR token retrieved successfully'
]);
?>
