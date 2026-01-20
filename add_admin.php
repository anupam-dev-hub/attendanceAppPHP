<?php
// add_admin.php
// One-time helper to create an admin without exposing a public page.
// Set the credentials below, run the script once (CLI or browser), then delete this file.

require __DIR__ . '/config.php';

// ----------------------------------------------------------------------------
// Configure the admin credentials here before running
$ADMIN_USERNAME = 'admin';
$ADMIN_PASSWORD = 'admin1234'; // min 8 chars
$ADMIN_EMAIL    = '';
$ADMIN_PHONE    = '';
$ADMIN_ADDRESS  = '';
// ----------------------------------------------------------------------------

function out($message) {
    static $headerSent = false;
    if (php_sapi_name() !== 'cli' && !$headerSent && !headers_sent()) {
        header('Content-Type: text/plain');
        $headerSent = true;
    }
    echo $message . "\n";
}

// Basic validation of placeholders
if ($ADMIN_USERNAME === 'set_admin_username' || $ADMIN_PASSWORD === 'set_secure_password') {
    out('Set $ADMIN_USERNAME and $ADMIN_PASSWORD in add_admin.php before running.');
    exit(1);
}

if (strlen($ADMIN_USERNAME) < 3) {
    out('Username must be at least 3 characters.');
    exit(1);
}
if (strlen($ADMIN_PASSWORD) < 8) {
    out('Password must be at least 8 characters.');
    exit(1);
}

// Ensure admins table exists (safety)
$createSql = "CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    contact_email VARCHAR(100),
    contact_phone VARCHAR(20),
    contact_address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!$conn->query($createSql)) {
    out('Failed to ensure admins table: ' . $conn->error);
    exit(1);
}

// Check for existing username
$check = $conn->prepare('SELECT id FROM admins WHERE username = ? LIMIT 1');
$check->bind_param('s', $ADMIN_USERNAME);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->close();
    out('Username already exists. No changes made.');
    exit(0);
}
$check->close();

$hashed = password_hash($ADMIN_PASSWORD, PASSWORD_BCRYPT);
$stmt = $conn->prepare('INSERT INTO admins (username, password, contact_email, contact_phone, contact_address) VALUES (?, ?, ?, ?, ?)');
$stmt->bind_param('sssss', $ADMIN_USERNAME, $hashed, $ADMIN_EMAIL, $ADMIN_PHONE, $ADMIN_ADDRESS);

if ($stmt->execute()) {
    $stmt->close();
    out('Admin created successfully. Username: ' . $ADMIN_USERNAME);
    out('IMPORTANT: Delete add_admin.php after use.');
    exit(0);
}

$err = $stmt->error;
$stmt->close();
out('Failed to create admin: ' . $err);
exit(1);
