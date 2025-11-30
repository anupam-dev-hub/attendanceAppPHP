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
$document_id = isset($_POST['document_id']) ? intval($_POST['document_id']) : 0;

if ($document_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid document ID']);
    exit;
}

// Fetch document details and verify it belongs to a student in this organization
$stmt = $conn->prepare("
    SELECT sd.id, sd.filepath, s.org_id 
    FROM student_documents sd
    JOIN students s ON sd.student_id = s.id
    WHERE sd.id = ?
");
$stmt->bind_param("i", $document_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Document not found']);
    exit;
}

$document = $result->fetch_assoc();

// Verify the student belongs to this organization
if ($document['org_id'] != $org_id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access to this document']);
    exit;
}

// Delete the physical file from filesystem
$file_path = $document['filepath'];
if (file_exists($file_path)) {
    if (!unlink($file_path)) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete file from server']);
        exit;
    }
}

// Delete the database record
$deleteStmt = $conn->prepare("DELETE FROM student_documents WHERE id = ?");
$deleteStmt->bind_param("i", $document_id);

if ($deleteStmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Document deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete document record: ' . $deleteStmt->error]);
}
?>
