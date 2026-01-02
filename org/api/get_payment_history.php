<?php
// org/api/get_payment_history.php
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

// Ensure student belongs to this org
$check = $conn->query("SELECT id FROM students WHERE id = $student_id AND org_id = {$_SESSION['user_id']}");
if ($check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

// Fetch current advance balance from students table
$advance_balance = 0.0;
$advance_row = $conn->query("SELECT advance_payment FROM students WHERE id = $student_id");
if ($advance_row && $advance_row->num_rows > 0) {
    $advance_balance = floatval($advance_row->fetch_assoc()['advance_payment'] ?? 0);
}

// Fetch payment history (all transactions from both student_payments and advance_payments)
try {
    // Try to include advance payments if table exists
    // Using BINARY to normalize collations for UNION compatibility
    $sql = "SELECT id, amount, transaction_type, category, CAST(description AS CHAR) AS description,
                   COALESCE(created_at, NOW()) AS created_at
            FROM student_payments
            WHERE student_id = $student_id
            UNION ALL
            SELECT id, amount, CAST('debit' AS CHAR) AS transaction_type, 
                   CAST('Advance Payment' AS CHAR) AS category,
                   CAST(COALESCE(description, 'Advance Payment') AS CHAR) AS description,
                   COALESCE(TIMESTAMP(payment_date), created_at, NOW()) AS created_at
            FROM advance_payments
            WHERE student_id = $student_id
            ORDER BY created_at DESC";
    
    $result = $conn->query($sql);
    
    // If query failed, fallback to student_payments only
    if (!$result) {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    // Fallback if advance_payments table doesn't exist or query fails
    $sql = "SELECT id, amount, transaction_type, category, description,
                   COALESCE(created_at, NOW()) AS created_at
            FROM student_payments
            WHERE student_id = $student_id
            ORDER BY created_at DESC, id DESC";
    
    $result = $conn->query($sql);
}

$payments = [];
$total_debit = 0.0;    // money paid (+)
$total_credit = 0.0;   // money owed (- stored as credit)
$total_advance = 0.0;  // advance payments only
$net_balance = 0.0;    // running balance (paid - owed)

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $type = strtolower($row['transaction_type'] ?? '');
        $raw_amount = (float)$row['amount'];

        // Normalize amounts: credits are stored negative (owed), debits positive (paid)
        $amount = ($type === 'credit') ? -abs($raw_amount) : abs($raw_amount);

        if ($type === 'debit') {
            $total_debit += abs($amount);
            if (strcasecmp($row['category'] ?? '', 'Advance Payment') === 0) {
                $total_advance += abs($amount);
            }
        } elseif ($type === 'credit') {
            $total_credit += abs($amount);
        }

        $net_balance += $amount;

        $payments[] = [
            'id' => (int)$row['id'],
            'date' => date('Y-m-d H:i', strtotime($row['created_at'])),
            'amount' => $amount,
            'transaction_type' => $row['transaction_type'] ?? '',
            'category' => $row['category'] ?? 'General',
            'description' => $row['description'] ?? ''
        ];
    }
}

$balance = $net_balance; // Positive = paid ahead, Negative = amount due

echo json_encode([
    'success' => true,
    'payments' => $payments,
    'totals' => [
        'total_debit' => round($total_debit, 2),
        'total_credit' => round($total_credit, 2),
        'balance' => round($balance, 2),
        'total_advance' => round($total_advance, 2),
        'advance_balance' => round($advance_balance, 2)
    ]
]);
exit;
