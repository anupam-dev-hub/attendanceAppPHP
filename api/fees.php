<?php
// api/fees.php
header('Content-Type: application/json');
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $student_id = $_GET['student_id'] ?? null;

    if (!$student_id) {
        echo json_encode(['status' => 'error', 'message' => 'Student ID required']);
        exit;
    }

    // Fetch Fees
    $sql = "SELECT f.id, f.amount, f.due_date, f.status, 
            (SELECT IFNULL(SUM(amount_paid), 0) FROM fee_payments WHERE fee_id = f.id) as paid_amount
            FROM fees f 
            WHERE f.student_id = $student_id 
            ORDER BY f.due_date DESC";
            
    $result = $conn->query($sql);
    
    $fees = [];
    while($row = $result->fetch_assoc()) {
        // Fetch payments for this fee
        $fee_id = $row['id'];
        $paySql = "SELECT amount_paid, payment_date FROM fee_payments WHERE fee_id = $fee_id";
        $payResult = $conn->query($paySql);
        $payments = [];
        while($p = $payResult->fetch_assoc()) {
            $payments[] = $p;
        }
        
        $row['payments'] = $payments;
        $fees[] = $row;
    }

    echo json_encode(['status' => 'success', 'data' => $fees]);
    exit;
}
?>
