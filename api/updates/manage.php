<?php
/**
 * api/updates/manage.php
 * Manage app versions - activate/deactivate, list versions
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config.php';

// Admin authentication
$adminToken = $_GET['admin_token'] ?? $_POST['admin_token'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$expectedToken = 'attendance_admin_token_2026'; // Change this to your actual token

if (empty($adminToken) || strpos($adminToken, $expectedToken) === false) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

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
    http_response_code(500);
    echo json_encode(['error' => 'Database setup failed']);
    exit;
}

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? 'list';

    switch ($action) {
        case 'list':
            // List all versions
            $platform = $_GET['platform'] ?? 'android';
            $sql = "SELECT * FROM app_versions 
                    WHERE platform = ? 
                    ORDER BY version_code DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $platform);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $versions = [];
            while ($row = $result->fetch_assoc()) {
                $versions[] = [
                    'id' => $row['id'],
                    'version' => $row['version'],
                    'versionCode' => (int)$row['version_code'],
                    'releaseDate' => $row['release_date'],
                    'apkSize' => (int)$row['apk_size'],
                    'mandatory' => (bool)$row['mandatory'],
                    'active' => (bool)$row['active'],
                    'minSdkVersion' => $row['min_sdk_version'],
                    'changes' => json_decode($row['changelog'], true) ?? [],
                ];
            }
            $stmt->close();
            
            http_response_code(200);
            echo json_encode($versions);
            break;

        case 'activate':
            // Activate a specific version and deactivate others
            $versionId = (int)$_POST['id'];
            
            if (!$versionId) {
                throw new Exception('Version ID is required');
            }

            // Get platform of the version
            $getPlatformSql = "SELECT platform FROM app_versions WHERE id = ?";
            $getPlatformStmt = $conn->prepare($getPlatformSql);
            $getPlatformStmt->bind_param('i', $versionId);
            $getPlatformStmt->execute();
            $platformResult = $getPlatformStmt->get_result();
            
            if ($platformResult->num_rows === 0) {
                throw new Exception('Version not found');
            }
            
            $platform = $platformResult->fetch_assoc()['platform'];
            $getPlatformStmt->close();

            // Deactivate all versions for this platform
            $deactivateSql = "UPDATE app_versions SET active = FALSE WHERE platform = ?";
            $deactivateStmt = $conn->prepare($deactivateSql);
            $deactivateStmt->bind_param('s', $platform);
            if (!$deactivateStmt->execute()) {
                throw new Exception('Failed to deactivate versions');
            }
            $deactivateStmt->close();

            // Activate the selected version
            $activateSql = "UPDATE app_versions SET active = TRUE WHERE id = ?";
            $activateStmt = $conn->prepare($activateSql);
            $activateStmt->bind_param('i', $versionId);
            if (!$activateStmt->execute()) {
                throw new Exception('Failed to activate version');
            }
            $activateStmt->close();

            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Version activated']);
            break;

        case 'delete':
            // Delete a version
            $versionId = (int)$_POST['id'];
            
            if (!$versionId) {
                throw new Exception('Version ID is required');
            }

            // Get APK URL before deleting
            $getUrlSql = "SELECT apk_url FROM app_versions WHERE id = ?";
            $getUrlStmt = $conn->prepare($getUrlSql);
            $getUrlStmt->bind_param('i', $versionId);
            $getUrlStmt->execute();
            $urlResult = $getUrlStmt->get_result();
            
            if ($urlResult->num_rows > 0) {
                $row = $urlResult->fetch_assoc();
                $apkPath = str_replace('/uploads/apks/', '../../uploads/apks/', $row['apk_url']);
                if (file_exists($apkPath)) {
                    unlink($apkPath);
                }
            }
            $getUrlStmt->close();

            // Delete database record
            $deleteSql = "DELETE FROM app_versions WHERE id = ?";
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->bind_param('i', $versionId);
            if (!$deleteStmt->execute()) {
                throw new Exception('Failed to delete version');
            }
            $deleteStmt->close();

            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Version deleted']);
            break;

        case 'toggle-mandatory':
            // Toggle mandatory flag
            $versionId = (int)$_POST['id'];
            $mandatory = isset($_POST['mandatory']) ? (bool)$_POST['mandatory'] : null;
            
            if (!$versionId || $mandatory === null) {
                throw new Exception('Version ID and mandatory flag are required');
            }

            $sql = "UPDATE app_versions SET mandatory = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $mandatory, $versionId);
            if (!$stmt->execute()) {
                throw new Exception('Failed to update version');
            }
            $stmt->close();

            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Version updated']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action']);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>
