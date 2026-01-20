<?php
/**
 * api/updates/upload.php
 * Upload and manage app versions (admin only)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config.php';

// Simple admin authentication (use your existing auth system)
$adminToken = $_GET['admin_token'] ?? $_POST['admin_token'] ?? '';
$expectedToken = 'attendance_admin_token_2026'; // Change this to your actual token

if ($adminToken !== $expectedToken) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Create uploads directory for APKs
    $uploadsDir = '../../uploads/apks';
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }

    // Handle file upload
    if (!isset($_FILES['apk'])) {
        throw new Exception('No APK file provided');
    }

    $file = $_FILES['apk'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $file['error']);
    }

    // Validate file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if ($mimeType !== 'application/vnd.android.package-archive') {
        throw new Exception('Invalid file type. Only APK files are allowed.');
    }

    // Get version info from request
    $version = $_POST['version'] ?? '';
    $versionCode = (int)($_POST['version_code'] ?? 0);
    $platform = $_POST['platform'] ?? 'android';
    $mandatory = isset($_POST['mandatory']) ? (bool)$_POST['mandatory'] : false;
    $changes = $_POST['changes'] ?? '[]'; // JSON string
    $minSdk = isset($_POST['min_sdk_version']) ? (int)$_POST['min_sdk_version'] : 21;

    if (!$version || !$versionCode) {
        throw new Exception('Version and version code are required');
    }

    // Validate version format
    if (!preg_match('/^\d+\.\d+(\.\d+)?$/', $version)) {
        throw new Exception('Invalid version format. Expected: X.Y or X.Y.Z');
    }

    // Generate unique filename
    $fileName = 'attendance_' . $version . '_' . time() . '.apk';
    $filePath = $uploadsDir . '/' . $fileName;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Failed to save APK file');
    }

    $fileSize = filesize($filePath);
    
    // Get full URL for the APK
    $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];
    if (strpos($baseUrl, 'ngrok') === false) {
        // Adjust path based on your server setup
        $apkUrl = $baseUrl . '/uploads/apks/' . $fileName;
    } else {
        $apkUrl = $baseUrl . '/uploads/apks/' . $fileName;
    }

    // Prepare changelog
    $changelogJson = json_encode(json_decode($changes, true) ?? []);

    // Create versions table if not exists
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
        throw new Exception("Database setup failed: " . $conn->error);
    }

    // Check if version already exists
    $checkSql = "SELECT id FROM app_versions WHERE version = ? AND platform = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param('ss', $version, $platform);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        // Update existing version
        $versionId = $checkResult->fetch_assoc()['id'];
        
        // Delete old APK file
        $oldSql = "SELECT apk_url FROM app_versions WHERE id = ?";
        $oldStmt = $conn->prepare($oldSql);
        $oldStmt->bind_param('i', $versionId);
        $oldStmt->execute();
        $oldResult = $oldStmt->get_result();
        if ($oldResult->num_rows > 0) {
            $oldRow = $oldResult->fetch_assoc();
            $oldPath = str_replace('uploads/apks/', $uploadsDir . '/', $oldRow['apk_url']);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
        
        $updateSql = "UPDATE app_versions 
                      SET apk_url = ?, apk_size = ?, changelog = ?, mandatory = ?, 
                          min_sdk_version = ?, updated_at = CURRENT_TIMESTAMP 
                      WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param('sliiii', $apkUrl, $fileSize, $changelogJson, $mandatory, $minSdk, $versionId);
        
        if (!$updateStmt->execute()) {
            throw new Exception("Failed to update version: " . $updateStmt->error);
        }
        $updateStmt->close();
    } else {
        // Insert new version
        $insertSql = "INSERT INTO app_versions 
                      (version, version_code, platform, apk_url, apk_size, changelog, mandatory, min_sdk_version) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param('sisslii', $version, $versionCode, $platform, $apkUrl, $fileSize, $changelogJson, $mandatory, $minSdk);
        
        if (!$insertStmt->execute()) {
            throw new Exception("Failed to create version: " . $insertStmt->error);
        }
        $insertStmt->close();
    }

    $checkStmt->close();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Version uploaded successfully',
        'version' => $version,
        'versionCode' => $versionCode,
        'apkUrl' => $apkUrl,
        'apkSize' => $fileSize,
        'fileName' => $fileName,
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage(),
    ]);
}

$conn->close();
?>
