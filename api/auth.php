<?php
// api/auth.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require '../config.php';
require __DIR__ . '/auth_utils.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) $input = $decoded;
}

$email = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? (string)$input['password'] : '';
$expires_in = isset($input['expires_in']) ? (int)$input['expires_in'] : 30*24*60*60; // seconds

if ($expires_in < 3600) $expires_in = 3600; // min 1 hour
if ($expires_in > 90*24*60*60) $expires_in = 90*24*60*60; // max 90 days

if ($email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required']);
    exit;
}

$stmt = $conn->prepare('SELECT id, password FROM organizations WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
if (!($org = $result->fetch_assoc())) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid credentials']);
    exit;
}

$org_id = (int)$org['id'];
$hashed = $org['password'];

if (!password_verify($password, $hashed)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid credentials']);
    exit;
}

$tok = create_token_for_org($conn, $org_id, $expires_in);
if (!$tok) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to issue token']);
    exit;
}

echo json_encode([
    'status' => 'success',
    'token_type' => 'Bearer',
    'access_token' => $tok['token'],
    'expires_at' => $tok['expires_at'],
    'org_id' => $org_id
]);
