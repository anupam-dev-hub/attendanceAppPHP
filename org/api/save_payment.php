<?php
// org/api/save_payment.php
session_start();
require '../../config.php';
require '../../functions.php';

header('Content-Type: application/json');

if (!isOrg()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0.00;
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    
    // Validation
    if ($student_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
        exit;
    }
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid amount']);
        exit;
    }
    
    if (empty($category)) {
        echo json_encode(['success' => false, 'message' => 'Category is required']);
        exit;
    }
    
    // Verify student belongs to org
    $check = $conn->query("SELECT id FROM students WHERE id = $student_id AND org_id = {$_SESSION['user_id']}");
    if ($check->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }
    
    // Insert Payment
    // Transaction type is 'debit' as per requirement (Student paying money)
    $stmt = $conn->prepare("INSERT INTO student_payments (student_id, amount, transaction_type, category, description) VALUES (?, ?, 'debit', ?, ?)");
    $stmt->bind_param("idss", $student_id, $amount, $category, $description);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Payment recorded successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
