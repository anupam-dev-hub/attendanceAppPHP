<?php
session_start();
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');

// Check if user is logged in as organization
if (!isOrg()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$org_id = $_SESSION['user_id'];
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

if ($student_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
    exit;
}

// Verify student belongs to this organization
$stmt = $conn->prepare("SELECT id FROM students WHERE id = ? AND org_id = ?");
$stmt->bind_param("ii", $student_id, $org_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

// Fetch student documents
$documents = [];
try {
    $stmt = $conn->prepare("SELECT id, filename, filepath FROM student_documents WHERE student_id = ? ORDER BY id ASC");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $documents[] = [
            'id' => $row['id'],
            'file_name' => $row['filename'],
            'file_path' => $row['filepath']
        ];
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}

echo json_encode([
    'success' => true,
    'documents' => $documents,
    'count' => count($documents)
]);
?>
