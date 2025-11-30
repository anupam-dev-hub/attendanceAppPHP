<?php
// org/toggle_student_status.php
session_start();
require '../config.php';
require '../functions.php';

header('Content-Type: application/json');

if (!isOrg()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$org_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
    $new_status = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;
    
    if ($student_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
        exit;
    }
    
    // Verify student belongs to this organization
    $checkStmt = $conn->prepare("SELECT id FROM students WHERE id = ? AND org_id = ?");
    $checkStmt->bind_param("ii", $student_id, $org_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }
    
    // Update status
    $updateStmt = $conn->prepare("UPDATE students SET is_active = ? WHERE id = ? AND org_id = ?");
    $updateStmt->bind_param("iii", $new_status, $student_id, $org_id);
    
    if ($updateStmt->execute()) {
        $status_text = $new_status ? 'activated' : 'deactivated';
        echo json_encode([
            'success' => true, 
            'message' => "Student $status_text successfully",
            'is_active' => $new_status
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
