<?php
// org/api/save_payment.php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
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
    $check = null;
    $advance_balance = 0.0;
    $student_name = 'Student';
    $class_name = '';
    $batch_name = '';
    
    try {
        // Try with advance_payment column
        $check = $conn->query("SELECT id, advance_payment, name, class, batch FROM students WHERE id = $student_id AND org_id = {$_SESSION['user_id']}");
    } catch (Exception $e) {
        // If advance_payment fails, try without it
        $check = $conn->query("SELECT id, name, class, batch FROM students WHERE id = $student_id AND org_id = {$_SESSION['user_id']}");
    }

    if (!$check || $check->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }

    $student = $check->fetch_assoc();
    $student_name = $student['name'] ?? 'Student';
    $class_id = $student['class'] ?? null;
    $batch_id = $student['batch'] ?? null;
    
    // Try to get advance balance if available
    if (isset($student['advance_payment'])) {
        $advance_balance = floatval($student['advance_payment'] ?? 0);
    }
    
    // Get organization details
    $org_name = 'Educational Institution';
    $org_logo = '';
    try {
        $org_result = $conn->query("SELECT name, logo FROM organizations WHERE id = {$_SESSION['user_id']}");
        if ($org_result && $org_result->num_rows > 0) {
            $org_data = $org_result->fetch_assoc();
            $org_name = $org_data['name'] ?? 'Educational Institution';
            $org_logo = $org_data['logo'] ?? '';
        }
    } catch (Exception $e) {
        // Silently fail
    }
    
    // Use class and batch IDs directly if tables don't exist
    $class_name = $class_id ? "$class_id" : '';
    $batch_name = $batch_id ? "$batch_id" : '';
    
    // Try to get class and batch names from tables if they exist
    try {
        if ($class_id) {
            $c = $conn->query("SELECT class_name, name FROM classes WHERE id = $class_id");
            if ($c && $c->num_rows > 0) {
                $class_row = $c->fetch_assoc();
                $class_name = $class_row['class_name'] ?? $class_row['name'] ?? "$class_id";
            }
        }
        
        if ($batch_id) {
            $b = $conn->query("SELECT batch_name, name FROM batches WHERE id = $batch_id");
            if ($b && $b->num_rows > 0) {
                $batch_row = $b->fetch_assoc();
                $batch_name = $batch_row['batch_name'] ?? $batch_row['name'] ?? "$batch_id";
            }
        }
    } catch (Exception $e) {
        // Silently fail on class/batch retrieval - use IDs as fallback
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get pending fees for this category to distribute payment across months (FIFO)
        $pending_months = [];
        $fee_type = $category;
        if (strpos($category, ' - ') !== false) {
            $fee_type = substr($category, 0, strpos($category, ' - '));
        }
        
        // Fetch unpaid months for this fee category
        $escaped_fee_type = $conn->real_escape_string($fee_type);
        $pending_query = "SELECT amount, category, transaction_type, created_at 
                         FROM student_payments 
                         WHERE student_id = $student_id AND category LIKE '{$escaped_fee_type}%'
                         ORDER BY created_at ASC";
        $pending_result = $conn->query($pending_query);
        
        $months_data = [];
        if ($pending_result && $pending_result->num_rows > 0) {
            while ($row = $pending_result->fetch_assoc()) {
                $type = strtolower($row['transaction_type'] ?? '');
                $raw_amount = (float)$row['amount'];
                $amount_value = ($type === 'credit') ? -abs($raw_amount) : abs($raw_amount);
                
                // Extract month from category
                if (preg_match('/ - ([A-Za-z]+ \d{4})$/', $row['category'], $matches)) {
                    $month_year = $matches[1];
                    if (!isset($months_data[$month_year])) {
                        $months_data[$month_year] = ['amount' => 0, 'paid' => 0];
                    }
                    if ($type === 'credit') {
                        $months_data[$month_year]['amount'] += abs($raw_amount);
                    } else {
                        $months_data[$month_year]['paid'] += abs($raw_amount);
                    }
                }
            }
        }
        
        // Calculate balance for each month and sort by date (oldest first for FIFO)
        foreach ($months_data as $month => &$data) {
            $data['balance'] = $data['amount'] - $data['paid'];
            $data['month'] = $month;
        }
        usort($months_data, function($a, $b) {
            return strtotime($a['month']) - strtotime($b['month']);
        });
        
        // Distribute payment across unpaid months (FIFO - oldest first)
        $remaining_payment = $amount;
        $payment_distribution = [];
        
        foreach ($months_data as $month_data) {
            if ($remaining_payment <= 0) break;
            if ($month_data['balance'] <= 0) continue;
            
            $to_pay = min($remaining_payment, $month_data['balance']);
            $payment_distribution[] = [
                'month' => $month_data['month'],
                'amount' => $to_pay,
                'category' => $fee_type . ' - ' . $month_data['month']
            ];
            $remaining_payment -= $to_pay;
        }
        
        // Insert payment records for each month
        $payment_ids = [];
        foreach ($payment_distribution as $dist) {
            $stmt = $conn->prepare("INSERT INTO student_payments (student_id, amount, transaction_type, category, description) VALUES (?, ?, 'debit', ?, ?)");
            if (!$stmt) {
                throw new Exception("Prepare failed (payment insert): " . $conn->error);
            }
            
            $dist_desc = $description . " (Payment for " . $dist['month'] . ")";
            $stmt->bind_param("idss", $student_id, $dist['amount'], $dist['category'], $dist_desc);
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert payment: " . $stmt->error);
            }
            $payment_ids[] = $conn->insert_id;
            $stmt->close();
        }
        
        // If payment_distribution is empty (no months found), insert single payment
        if (empty($payment_distribution)) {
            $stmt = $conn->prepare("INSERT INTO student_payments (student_id, amount, transaction_type, category, description) VALUES (?, ?, 'debit', ?, ?)");
            if (!$stmt) {
                throw new Exception("Prepare failed (payment insert): " . $conn->error);
            }

            $stmt->bind_param("idss", $student_id, $amount, $category, $description);
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert payment: " . $stmt->error);
            }

            $payment_ids[] = $conn->insert_id;
            $stmt->close();
        }
        
        $payment_id = $payment_ids[0]; // Return first payment ID
        
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
        
        // Calculate remaining pending months after payment
        $remaining_months = [];
        foreach ($months_data as $month_data) {
            $month_paid = 0;
            foreach ($payment_distribution as $dist) {
                if ($dist['month'] === $month_data['month']) {
                    $month_paid += $dist['amount'];
                }
            }
            $new_balance = $month_data['balance'] - $month_paid;
            if ($new_balance > 0.01) { // Only show months with remaining balance
                $remaining_months[] = [
                    'month' => $month_data['month'],
                    'fee_category' => $fee_type,
                    'original_fee' => $month_data['balance'],
                    'paid' => $month_paid,
                    'remaining' => $new_balance
                ];
            }
        }
        
        // Prepare payment distribution for receipt (fallback when no month breakdown)
        $receipt_distribution = [];
        if (!empty($payment_distribution)) {
            foreach ($payment_distribution as $dist) {
                $receipt_distribution[] = [
                    'month' => $dist['month'],
                    'fee_category' => $fee_type,
                    'amount_paid' => $dist['amount']
                ];
            }
        } else {
            // No per-month data; add a single summarized row so receipt shows the payment
            $receipt_distribution[] = [
                'month' => '-',
                'fee_category' => $category,
                'amount_paid' => $amount
            ];
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment recorded successfully',
            'payment_id' => $payment_id,
            'amount' => $amount,
            'fee_category' => $category,
            'payment_mode' => 'Cash',
            'payment_date' => date('Y-m-d H:i:s'),
            'student_name' => $student_name,
            'class_name' => $class_name,
            'batch_name' => $batch_name,
            'org_name' => $org_name,
            'org_logo' => $org_logo,
            'advance_deducted' => min($advance_balance, $amount),
            'remaining_advance' => max(0, $advance_balance - $amount),
            'payment_distribution' => $receipt_distribution,
            'remaining_months' => $remaining_months,
            'total_remaining' => array_sum(array_column($remaining_months, 'remaining'))
        ]);
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage(),
            'error_details' => $e->getTraceAsString()
        ]);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
