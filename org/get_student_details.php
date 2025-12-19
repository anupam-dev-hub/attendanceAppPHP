<?php
// org/get_student_details.php
session_start();
require '../config.php';
require '../functions.php';
require 'StudentStats.php';

header('Content-Type: application/json');

if (!isOrg()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

if ($student_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
    exit;
}

try {
    // Verify student belongs to org and get student details
    $student_query = $conn->query("SELECT id, name, fees_json FROM students WHERE id = $student_id AND org_id = {$_SESSION['user_id']}");
    
    if (!$student_query) {
        throw new Exception('Database query failed: ' . $conn->error);
    }
    
    if ($student_query->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit;
    }
    $student = $student_query->fetch_assoc();

    // Calculate balance (credits - debits)
    $balance_query = $conn->query("
        SELECT 
            COALESCE(SUM(CASE WHEN transaction_type = 'debit' THEN ABS(amount) ELSE -ABS(amount) END), 0) as net_balance,
            COALESCE(SUM(CASE WHEN transaction_type = 'debit' THEN ABS(amount) ELSE 0 END), 0) as total_debits,
            COALESCE(SUM(CASE WHEN transaction_type = 'credit' THEN ABS(amount) ELSE 0 END), 0) as total_credits
        FROM student_payments 
        WHERE student_id = $student_id
    ");
    
    if (!$balance_query) {
        throw new Exception('Balance query failed: ' . $conn->error);
    }
    
    $balance_row = $balance_query->fetch_assoc();
    $balance = $balance_row['net_balance'];

    $stats = new StudentStats($conn);

    $response = [
        'success' => true,
        'student' => [
            'id' => $student['id'],
            'name' => $student['name'],
            'fees_json' => $student['fees_json'],
            'balance' => $balance
        ],
        'attendance_history' => $stats->getAttendanceHistory($student_id),
        'attendance_chart' => $stats->getAttendanceStats($student_id)
    ];

    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
