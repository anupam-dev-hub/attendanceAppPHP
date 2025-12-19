<?php
// org/deactivate_class_batch.php
session_start();
require '../config.php';
require '../functions.php';

header('Content-Type: application/json');

if (!isOrg()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$org_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$class = isset($_POST['class']) ? trim($_POST['class']) : '';
$batch = isset($_POST['batch']) ? trim($_POST['batch']) : '';

if ($class === '' || $batch === '') {
    echo json_encode(['success' => false, 'message' => 'Class and batch are required']);
    exit;
}

// Deactivate all matching active students for this organization
$stmt = $conn->prepare("UPDATE students SET is_active = 0 WHERE org_id = ? AND class = ? AND batch = ? AND is_active = 1");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
    exit;
}

$stmt->bind_param('iss', $org_id, $class, $batch);

if ($stmt->execute()) {
    $affected = $stmt->affected_rows;
    echo json_encode([
        'success' => true,
        'message' => 'Students set to inactive successfully',
        'updated' => $affected
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update students']);
}

$stmt->close();
$conn->close();
?>
