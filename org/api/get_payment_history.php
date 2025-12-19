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

// Fetch payment history (all transactions)
// Assuming table: student_payments(student_id, amount, transaction_type, category, description, created_at)
$sql = "SELECT id, amount, transaction_type, category, description,
               COALESCE(created_at, NOW()) AS created_at
        FROM student_payments
        WHERE student_id = $student_id
        ORDER BY created_at DESC, id DESC";

$result = $conn->query($sql);

$payments = [];
$total_debit = 0.0;    // money paid (+)
$total_credit = 0.0;   // money owed (- stored as credit)
$net_balance = 0.0;    // running balance (paid - owed)

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $type = strtolower($row['transaction_type'] ?? '');
        $raw_amount = (float)$row['amount'];

        // Normalize amounts: credits are stored negative (owed), debits positive (paid)
        $amount = ($type === 'credit') ? -abs($raw_amount) : abs($raw_amount);

        if ($type === 'debit') {
            $total_debit += abs($amount);
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
        'balance' => round($balance, 2)
    ]
]);
exit;
