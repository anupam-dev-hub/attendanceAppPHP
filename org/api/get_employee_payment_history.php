<?php
// org/api/get_employee_payment_history.php
session_start();
require '../../config.php';
require '../../functions.php';

header('Content-Type: application/json');

if (!isOrg()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$org_id = $_SESSION['user_id'];
$employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;

if ($employee_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
    exit;
}

// Verify employee belongs to org
$check_stmt = $conn->prepare("SELECT id FROM employees WHERE id = ? AND org_id = ?");
$check_stmt->bind_param("ii", $employee_id, $org_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Employee not found']);
    exit;
}

// Fetch payment history
$stmt = $conn->prepare("
    SELECT id, amount, transaction_type, category, description, payment_date, created_at
    FROM employee_payments
    WHERE employee_id = ?
    ORDER BY payment_date DESC, created_at DESC
");

$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

$payments = [];
$total_debits = 0; // Payments made to employee
$total_credits = 0; // Amounts owed/deductions

while ($row = $result->fetch_assoc()) {
    // Normalize legacy values (salary/bonus/advance = debit, deduction = credit)
    if ($row['transaction_type'] === 'credit' || $row['transaction_type'] === 'deduction') {
        $total_credits += $row['amount'];
    } else {
        $total_debits += $row['amount'];
    }
    
    $payments[] = [
        'id' => $row['id'],
        'amount' => $row['amount'],
        'transaction_type' => $row['transaction_type'],
        'category' => $row['category'],
        'description' => $row['description'],
        'payment_date' => $row['payment_date'],
        'created_at' => $row['created_at']
    ];
}

echo json_encode([
    'success' => true,
    'payments' => $payments,
    'total_debits' => $total_debits,
    'total_credits' => $total_credits,
    'net_balance' => $total_credits - $total_debits, // Credits (owed) minus debits (paid)
    // Legacy keys for backward compatibility
    'total_paid' => $total_debits,
    'total_deductions' => $total_credits,
    'net_payment' => $total_debits - $total_credits
]);

$stmt->close();
?>
