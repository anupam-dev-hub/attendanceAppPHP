<?php
// org/api/get_advance_payment.php
// Get advance payment balance for a student

session_start();
require '../../config.php';
require '../../functions.php';

header('Content-Type: application/json');

if (!isOrg()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

if ($student_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
    exit;
}

// Verify student belongs to this org
$check = $conn->query("SELECT id FROM students WHERE id = $student_id AND org_id = {$_SESSION['user_id']}");
if ($check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

// Get student's advance payment balance
$student = $conn->query("SELECT advance_payment FROM students WHERE id = $student_id")->fetch_assoc();
$advance_balance = floatval($student['advance_payment'] ?? 0);

// Get advance payment history
$history = [];
$history_result = $conn->query("
    SELECT id, amount, payment_date, description 
    FROM advance_payments 
    WHERE student_id = $student_id 
    ORDER BY payment_date DESC
");

while ($row = $history_result->fetch_assoc()) {
    $history[] = $row;
}

// Get advance payment adjustments (deductions)
$adjustments = [];
$adjustments_result = $conn->query("
    SELECT ap.id, ap.amount, sp.created_at as adjustment_date, sp.category, spa.deduction_amount
    FROM advance_payment_adjustments spa
    JOIN advance_payments ap ON spa.advance_payment_id = ap.id
    JOIN student_payments sp ON spa.student_payment_id = sp.id
    WHERE spa.student_id = $student_id
    ORDER BY spa.adjustment_date DESC
");

while ($row = $adjustments_result->fetch_assoc()) {
    $adjustments[] = $row;
}

echo json_encode([
    'success' => true,
    'advance_balance' => round($advance_balance, 2),
    'history' => $history,
    'adjustments' => $adjustments
]);
exit;
?>
