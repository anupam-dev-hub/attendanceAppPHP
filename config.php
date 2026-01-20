<?php
// config.php - Production Ready Configuration

// Load environment variables from .env file (if exists)
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Environment Settings
$app_env = $_ENV['APP_ENV'] ?? 'development';
$app_debug = filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

// Error Reporting (Production vs Development)
if ($app_env === 'production') {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/logs/error.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Set Timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Kolkata');

// Database Configuration
$host = $_ENV['DB_HOST'] ?? 'localhost';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$dbname = $_ENV['DB_NAME'] ?? 'attendance_php';

// Create Database Connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check Connection
if ($conn->connect_error) {
    if ($app_env === 'production') {
        // Log error and show generic message
        error_log("Database connection failed: " . $conn->connect_error);
        die("Database connection error. Please contact administrator.");
    } else {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Set character set to UTF-8
$conn->set_charset("utf8mb4");

// Set MySQL session timezone
$conn->query("SET time_zone = '+05:30'");

// Session Configuration
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? '1' : '0');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', $_ENV['SESSION_LIFETIME'] ?? '7200');

// Application Constants
define('APP_ENV', $app_env);
define('APP_DEBUG', $app_debug);
define('BASE_PATH', __DIR__);
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('MAX_UPLOAD_SIZE', $_ENV['MAX_UPLOAD_SIZE'] ?? 5242880); // 5MB default

// Create necessary directories if they don't exist
$directories = [
    BASE_PATH . '/logs',
    UPLOAD_PATH,
    UPLOAD_PATH . '/students',
    UPLOAD_PATH . '/employees',
    UPLOAD_PATH . '/employees/documents',
    UPLOAD_PATH . '/screenshots'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        // Create .htaccess to prevent direct access to uploads
        if (strpos($dir, 'uploads') !== false) {
            file_put_contents($dir . '/.htaccess', "Options -Indexes\nDeny from all");
        }
    }
}

// Security Headers (if not set by web server)
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}
?>
