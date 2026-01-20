<?php
// org/get_employee_attendance.php
header('Content-Type: application/json');
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'org') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$org_id = $_SESSION['user_id'];
$employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;

if (!$employee_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid Employee ID']);
    exit();
}

// Verify employee belongs to org
$emp_check = $conn->prepare("SELECT id FROM employees WHERE id = ? AND org_id = ?");
$emp_check->bind_param("ii", $employee_id, $org_id);
if (!$emp_check->execute()) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}
$result = $emp_check->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Employee not found']);
    exit();
}
$emp_check->close();

// Fetch attendance
$query = "SELECT date, in_time, out_time 
          FROM employee_attendance 
          WHERE employee_id = ? 
          ORDER BY date DESC 
          LIMIT 30";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $employee_id);
if ($stmt->execute()) {
    $result = $stmt->get_result();
    $attendance = [];
    while ($row = $result->fetch_assoc()) {
        // Check if both in_time and out_time are blank (NULL or empty)
        $in_time = $row['in_time'];
        $out_time = $row['out_time'];
        
        // Determine status: Absent if no in/out times, Present if at least one time exists
        if (empty($in_time) && empty($out_time)) {
            $status = 'Absent';
        } else {
            $status = 'Present';
        }
        
        $attendance[] = [
            'date' => $row['date'],
            'in_time' => $in_time,  // Return actual value (null or time string)
            'out_time' => $out_time,  // Return actual value (null or time string)
            'status' => $status
        ];
    }
    echo json_encode(['success' => true, 'attendance' => $attendance]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error fetching attendance']);
}

$stmt->close();
$conn->close();
?>
