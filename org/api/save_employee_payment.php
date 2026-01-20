<?php
// org/api/save_employee_payment.php
session_start();
require '../../config.php';
require '../../functions.php';

header('Content-Type: application/json');

if (!isOrg()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0.00;
    $transaction_type = isset($_POST['transaction_type']) ? trim($_POST['transaction_type']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $payment_date = isset($_POST['payment_date']) ? trim($_POST['payment_date']) : date('Y-m-d');
    
    // Validation
    if ($employee_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
        exit;
    }
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid amount']);
        exit;
    }
    
    if (empty($transaction_type) || !in_array($transaction_type, ['debit', 'credit'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid transaction type']);
        exit;
    }
    
    if (empty($category)) {
        echo json_encode(['success' => false, 'message' => 'Category is required']);
        exit;
    }
    
    // Verify employee belongs to org
    $check = $conn->query("SELECT id FROM employees WHERE id = $employee_id AND org_id = {$_SESSION['user_id']}");
    if ($check->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit;
    }
    
    // Use transaction_type directly (debit or credit)
    $positive_amount = abs($amount); // Always store positive amounts
    
    // Insert Payment
    $stmt = $conn->prepare("INSERT INTO employee_payments (employee_id, amount, transaction_type, category, description, payment_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("idssss", $employee_id, $positive_amount, $transaction_type, $category, $description, $payment_date);
    
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
