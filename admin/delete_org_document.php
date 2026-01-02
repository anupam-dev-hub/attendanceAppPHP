<?php
// admin/delete_org_document.php
session_start();
require '../config.php';
require '../functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$doc_id = $input['id'] ?? null;

if (!$doc_id) {
    echo json_encode(['success' => false, 'error' => 'Document ID required']);
    exit;
}

// Fetch document path
$stmt = $conn->prepare("SELECT file_path FROM org_documents WHERE id = ?");
$stmt->bind_param("i", $doc_id);
$stmt->execute();
$result = $stmt->get_result();
$doc = $result->fetch_assoc();

if (!$doc) {
    echo json_encode(['success' => false, 'error' => 'Document not found']);
    exit;
}

// Delete file from server
if (file_exists($doc['file_path'])) {
    unlink($doc['file_path']);
}

// Delete from database
$deleteStmt = $conn->prepare("DELETE FROM org_documents WHERE id = ?");
$deleteStmt->bind_param("i", $doc_id);

if ($deleteStmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $deleteStmt->error]);
}

$deleteStmt->close();
$conn->close();
