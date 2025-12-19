<?php
// org/get_employee_documents.php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'org') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
    exit();
}

$employee_id = intval($_GET['employee_id']);
$org_id = $_SESSION['user_id'];

// Verify employee belongs to this organization
$verify_stmt = $conn->prepare("SELECT id FROM employees WHERE id = ? AND org_id = ?");
$verify_stmt->bind_param("ii", $employee_id, $org_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Employee not found']);
    exit();
}
$verify_stmt->close();

// Fetch documents
$stmt = $conn->prepare("SELECT id, file_name, file_path, document_type FROM employee_documents WHERE employee_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

$documents = [];
while ($row = $result->fetch_assoc()) {
    $documents[] = $row;
}

echo json_encode([
    'success' => true,
    'documents' => $documents
]);

$stmt->close();
$conn->close();
?>
