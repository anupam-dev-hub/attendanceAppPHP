<?php
// api/auth_utils.php
header('Access-Control-Allow-Origin: *');

function ensure_api_tokens_table($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS api_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        org_id INT NOT NULL,
        token_hash CHAR(64) NOT NULL UNIQUE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        last_used_at DATETIME NULL,
        revoked TINYINT(1) NOT NULL DEFAULT 0,
        CONSTRAINT fk_api_tokens_org FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
        INDEX idx_org_id (org_id),
        INDEX idx_expires_revoked (expires_at, revoked)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $conn->query($sql);
}

function generate_token() {
    return bin2hex(random_bytes(32)); // 64 hex chars
}

function hash_token($token) {
    return hash('sha256', $token);
}

function get_authorization_header() {
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        return $_SERVER['HTTP_AUTHORIZATION'];
    }
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) return $headers['Authorization'];
        if (isset($headers['authorization'])) return $headers['authorization'];
    }
    return null;
}

function get_bearer_token_from_request() {
    $authHeader = get_authorization_header();
    if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $m)) {
        return trim($m[1]);
    }
    if (isset($_GET['token'])) return $_GET['token'];
    if (isset($_POST['token'])) return $_POST['token'];
    return null;
}

function find_org_by_token($conn, $token) {
    if (!$token) return false;
    $hash = hash_token($token);
    $stmt = $conn->prepare("SELECT org_id, expires_at, revoked FROM api_tokens WHERE token_hash = ? LIMIT 1");
    $stmt->bind_param('s', $hash);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if ((int)$row['revoked'] === 1) return false;
        if (strtotime($row['expires_at']) <= time()) return false;
        $upd = $conn->prepare("UPDATE api_tokens SET last_used_at = NOW() WHERE token_hash = ?");
        $upd->bind_param('s', $hash);
        $upd->execute();
        return (int)$row['org_id'];
    }
    return false;
}

function create_token_for_org($conn, $org_id, $ttlSeconds = 30*24*60*60) {
    ensure_api_tokens_table($conn);
    $token = generate_token();
    $hash = hash_token($token);
    $expiresAt = date('Y-m-d H:i:s', time() + $ttlSeconds);
    $stmt = $conn->prepare("INSERT INTO api_tokens (org_id, token_hash, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param('iss', $org_id, $hash, $expiresAt);
    if (!$stmt->execute()) return false;
    return [ 'token' => $token, 'expires_at' => $expiresAt ];
}

function require_org_auth($conn) {
    header('Content-Type: application/json');
    $token = get_bearer_token_from_request();
    $orgId = find_org_by_token($conn, $token);
    if (!$orgId) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized: invalid or expired token']);
        exit;
    }
    return $orgId;
}
