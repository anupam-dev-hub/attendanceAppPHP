<?php
// org/api/save_payment.php
session_start();
require '../../config.php';
require '../../functions.php';

header('Content-Type: application/json');

// Make mysqli throw exceptions so we can return clear JSON errors
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isOrg()) {
    http_response_code(401);
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
    
    // Verify student belongs to org and get advance payment balance (tolerant if column missing)
    try {
        $check = $conn->query("SELECT id, advance_payment FROM students WHERE id = $student_id AND org_id = {$_SESSION['user_id']}");
    } catch (Exception $e) {
        // If advance_payment column is missing, fall back to zero to avoid fatal error
        if (stripos($e->getMessage(), 'Unknown column') !== false) {
            $check = $conn->query("SELECT id FROM students WHERE id = $student_id AND org_id = {$_SESSION['user_id']}");
            $advance_balance = 0.0;
        } else {
            throw $e;
        }
    }

    if (!$check || $check->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }

    // If we did not already set fallback advance balance, read it
    if (!isset($advance_balance)) {
        $student = $check->fetch_assoc();
        $advance_balance = floatval($student['advance_payment'] ?? 0);
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert Payment (transaction_type 'debit' means money received)
        $stmt = $conn->prepare("INSERT INTO student_payments (student_id, amount, transaction_type, category, description) VALUES (?, ?, 'debit', ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed (payment insert): " . $conn->error);
        }

        $stmt->bind_param("idss", $student_id, $amount, $category, $description);
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert payment: " . $stmt->error);
        }

        $payment_id = $conn->insert_id;
        $stmt->close();
        
        // Check if advance payment is available and deduct it
        if ($advance_balance > 0) {
            $deduction_amount = min($advance_balance, $amount);
            $remaining_advance = $advance_balance - $deduction_amount;
            
            // Get the latest advance payment record
            $advance_result = $conn->query("
                SELECT id FROM advance_payments 
                WHERE student_id = $student_id 
                ORDER BY payment_date DESC 
                LIMIT 1
            ");
            
            if ($advance_result && $advance_result->num_rows > 0) {
                $advance_row = $advance_result->fetch_assoc();
                $advance_payment_id = $advance_row['id'];
                
                // Record the adjustment
                $adj_stmt = $conn->prepare("
                    INSERT INTO advance_payment_adjustments 
                    (student_id, advance_payment_id, student_payment_id, deduction_amount) 
                    VALUES (?, ?, ?, ?)
                ");

                if (!$adj_stmt) {
                    throw new Exception("Prepare failed (advance adjustment): " . $conn->error);
                }

                // types: int, int, int, decimal
                $adj_stmt->bind_param("iiid", $student_id, $advance_payment_id, $payment_id, $deduction_amount);

                if (!$adj_stmt->execute()) {
                    throw new Exception("Failed to record advance payment adjustment: " . $adj_stmt->error);
                }

                $adj_stmt->close();

                // Update advance payment balance
                $update_stmt = $conn->prepare("UPDATE students SET advance_payment = ? WHERE id = ?");

                if (!$update_stmt) {
                    throw new Exception("Prepare failed (advance balance update): " . $conn->error);
                }

                $update_stmt->bind_param("di", $remaining_advance, $student_id);

                if (!$update_stmt->execute()) {
                    throw new Exception("Failed to update advance payment balance: " . $update_stmt->error);
                }

                $update_stmt->close();
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment recorded successfully',
            'payment_id' => $payment_id,
            'advance_deducted' => min($advance_balance, $amount),
            'remaining_advance' => max(0, $advance_balance - $amount)
        ]);
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
