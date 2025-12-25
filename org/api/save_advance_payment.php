<?php
// org/api/save_advance_payment.php
// Save advance payment for a student

session_start();
require '../../config.php';
require '../../functions.php';

header('Content-Type: application/json');

// Enable mysqli exceptions for better error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isOrg()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0.00;
    $description = isset($_POST['description']) ? trim($_POST['description']) : 'Advance Payment';
    
    // Validation
    if ($student_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
        exit;
    }
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid amount']);
        exit;
    }
    
    // Verify student belongs to org (tolerant to missing advance_payment column)
    try {
        $check = $conn->query("SELECT id, advance_payment FROM students WHERE id = $student_id AND org_id = {$_SESSION['user_id']}");
    } catch (Exception $e) {
        // If advance_payment column doesn't exist, try without it
        if (stripos($e->getMessage(), 'Unknown column') !== false) {
            $check = $conn->query("SELECT id FROM students WHERE id = $student_id AND org_id = {$_SESSION['user_id']}");
            $current_advance = 0.0;
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }
    
    if (!$check || $check->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Student not found or not authorized']);
        exit;
    }
    
    // Get current advance if not already set
    if (!isset($current_advance)) {
        $student = $check->fetch_assoc();
        $current_advance = floatval($student['advance_payment'] ?? 0);
    }
    $new_advance = $current_advance + $amount;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check if advance_payments table exists
        $check_table = $conn->query("SHOW TABLES LIKE 'advance_payments'");
        if ($check_table->num_rows == 0) {
            throw new Exception("Advance payments table does not exist. Please contact administrator to run: add_advance_payment_system.php");
        }
        
        // Insert into advance_payments table
        $stmt = $conn->prepare("INSERT INTO advance_payments (student_id, amount, payment_date, description) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $payment_date = date('Y-m-d');
        $stmt->bind_param("idss", $student_id, $amount, $payment_date, $description);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert advance payment: " . $stmt->error);
        }
        
        $stmt->close();
        
        // Update students table
        $update_stmt = $conn->prepare("UPDATE students SET advance_payment = ? WHERE id = ?");
        if (!$update_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $update_stmt->bind_param("di", $new_advance, $student_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update student advance payment: " . $update_stmt->error);
        }
        
        $update_stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Advance payment recorded successfully',
            'advance_payment' => $new_advance
        ]);
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    exit;
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
