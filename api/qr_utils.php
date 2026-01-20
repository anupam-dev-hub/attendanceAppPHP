<?php
// api/qr_utils.php
// Utility functions for QR token management

/**
 * Ensure org_qr_tokens table exists
 */
function ensure_org_qr_tokens_table($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS org_qr_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        org_id INT NOT NULL,
        token_hash CHAR(64) NOT NULL UNIQUE,
        qr_data VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NULL,
        revoked TINYINT(1) NOT NULL DEFAULT 0,
        last_used_at DATETIME NULL,
        CONSTRAINT fk_org_qr_tokens_org FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
        INDEX idx_org_id (org_id),
        INDEX idx_token_hash (token_hash),
        INDEX idx_revoked (revoked)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $conn->query($sql);
}

/**
 * Generate a unique QR token string
 * Format: qr_[64 hex characters]
 */
function generate_qr_token() {
    return 'qr_' . bin2hex(random_bytes(32)); // qr_ + 64 hex chars
}

/**
 * Hash a QR token for storage
 */
function hash_qr_token($token) {
    return hash('sha256', $token);
}

/**
 * Create a new QR token for an organization
 * Revokes any existing active tokens
 */
function create_qr_token_for_org($conn, $org_id) {
    ensure_org_qr_tokens_table($conn);
    
    // Revoke existing tokens
    revoke_qr_token($conn, $org_id);
    
    // Generate new token
    $token = generate_qr_token();
    $hash = hash_qr_token($token);
    
    // Insert new token
    $stmt = $conn->prepare("INSERT INTO org_qr_tokens (org_id, token_hash, qr_data) VALUES (?, ?, ?)");
    $stmt->bind_param('iss', $org_id, $hash, $token);
    
    if (!$stmt->execute()) {
        return false;
    }
    
    return [
        'token' => $token,
        'created_at' => date('Y-m-d H:i:s'),
        'expires_at' => null
    ];
}

/**
 * Revoke existing QR tokens for an organization
 */
function revoke_qr_token($conn, $org_id) {
    ensure_org_qr_tokens_table($conn);
    $stmt = $conn->prepare("UPDATE org_qr_tokens SET revoked = 1 WHERE org_id = ? AND revoked = 0");
    $stmt->bind_param('i', $org_id);
    return $stmt->execute();
}

/**
 * Find organization by QR token
 * Returns org_id if valid, false otherwise
 */
function find_org_by_qr_token($conn, $qr_token) {
    if (!$qr_token || strpos($qr_token, 'qr_') !== 0) {
        return false;
    }
    
    $hash = hash_qr_token($qr_token);
    $stmt = $conn->prepare("SELECT org_id, expires_at, revoked FROM org_qr_tokens WHERE token_hash = ? LIMIT 1");
    $stmt->bind_param('s', $hash);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        // Check if revoked
        if ((int)$row['revoked'] === 1) {
            return false;
        }
        
        // Check if expired (if expires_at is set)
        if ($row['expires_at'] !== null && strtotime($row['expires_at']) <= time()) {
            return false;
        }
        
        // Update last_used_at
        $upd = $conn->prepare("UPDATE org_qr_tokens SET last_used_at = NOW() WHERE token_hash = ?");
        $upd->bind_param('s', $hash);
        $upd->execute();
        
        return (int)$row['org_id'];
    }
    
    return false;
}

/**
 * Get current active QR token for an organization
 */
function get_current_qr_token($conn, $org_id) {
    ensure_org_qr_tokens_table($conn);
    
    $stmt = $conn->prepare("SELECT qr_data, created_at, expires_at FROM org_qr_tokens WHERE org_id = ? AND revoked = 0 ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param('i', $org_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        return [
            'token' => $row['qr_data'],
            'created_at' => $row['created_at'],
            'expires_at' => $row['expires_at']
        ];
    }
    
    return false;
}
?>
