<?php
// org/get_student_details.php
session_start();
require '../config.php';
require '../functions.php';
require 'StudentStats.php';
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

// Verify student belongs to org
$check = $conn->query("SELECT id FROM students WHERE id = $student_id AND org_id = {$_SESSION['user_id']}");
if ($check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

$stats = new StudentStats($conn);

$response = [
    'success' => true,
    'attendance_history' => $stats->getAttendanceHistory($student_id),
    'attendance_chart' => $stats->getAttendanceStats($student_id)
];

echo json_encode($response);
?>
