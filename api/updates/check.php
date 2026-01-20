<?php
/**
 * api/updates/check.php
 * Check for available app updates
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config.php';

// Get version management table
$sql = "CREATE TABLE IF NOT EXISTS app_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(20) NOT NULL UNIQUE,
    version_code INT NOT NULL UNIQUE,
    platform VARCHAR(10) NOT NULL,
    release_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    apk_url VARCHAR(500) NOT NULL,
    apk_size BIGINT NOT NULL,
    changelog JSON,
    mandatory BOOLEAN DEFAULT FALSE,
    active BOOLEAN DEFAULT TRUE,
    min_sdk_version INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (!$conn->query($sql)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database setup failed']);
    exit;
}

// Get current app version from request
$currentVersion = $_GET['currentVersion'] ?? '0.0.1';
$platform = $_GET['platform'] ?? 'android';
$buildVersion = (int)($_GET['buildVersion'] ?? 0);

try {
    // Get the latest active version
    $sql = "SELECT * FROM app_versions 
            WHERE platform = ? AND active = TRUE 
            ORDER BY version_code DESC 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param('s', $platform);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Parse changelog
        $changes = [];
        if (!empty($row['changelog'])) {
            $changeData = json_decode($row['changelog'], true);
            $changes = is_array($changeData) ? $changeData : [];
        }
        
        // Return relative path only - client will prepend baseUrl
        $apkUrl = $row['apk_url'];
        
        http_response_code(200);
        echo json_encode([
            'version' => $row['version'],
            'versionCode' => (int)$row['version_code'],
            'releaseDate' => $row['release_date'],
            'changes' => $changes,
            'apkUrl' => $apkUrl,
            'apkSize' => (int)$row['apk_size'],
            'mandatory' => (bool)$row['mandatory'],
            'minSdkVersion' => $row['min_sdk_version'],
            'currentVersion' => $currentVersion,
        ]);
    } else {
        // No updates available
        http_response_code(200);
        echo json_encode([
            'version' => $currentVersion,
            'versionCode' => $buildVersion,
            'releaseDate' => date('Y-m-d H:i:s'),
            'changes' => [],
            'apkUrl' => null,
            'apkSize' => 0,
            'mandatory' => false,
        ]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>
