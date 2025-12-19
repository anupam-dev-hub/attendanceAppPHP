<?php
// org/toggle_employee_status.php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'org') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = intval($_POST['employee_id']);
    $is_active = intval($_POST['is_active']);
    $org_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE employees SET is_active = ? WHERE id = ? AND org_id = ?");
    $stmt->bind_param("iii", $is_active, $employee_id, $org_id);

    if ($stmt->execute()) {
        $status_text = $is_active ? 'activated' : 'deactivated';
        echo json_encode(['success' => true, 'message' => "Employee $status_text successfully"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update employee status']);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
