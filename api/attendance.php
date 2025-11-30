<?php
// api/attendance.php
header('Content-Type: application/json');
require '../config.php';

// Handle POST request (Record Attendance)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? null;
    $time = $_POST['time'] ?? date('H:i:s'); // Default to current time if not sent
    $type = $_POST['type'] ?? null; // 'in' or 'out'
    $date = date('Y-m-d');

    if (!$student_id || !$type) {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
        exit;
    }

    // Check if student exists
    $check = $conn->query("SELECT id FROM students WHERE id = $student_id");
    if ($check->num_rows == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Student not found']);
        exit;
    }

    // Check if attendance record exists for today
    $attCheck = $conn->query("SELECT id, in_time, out_time FROM attendance WHERE student_id = $student_id AND date = '$date'");
    
    if ($attCheck->num_rows > 0) {
        $row = $attCheck->fetch_assoc();
        if ($type === 'in') {
            if ($row['in_time']) {
                echo json_encode(['status' => 'error', 'message' => 'Already checked in']);
            } else {
                $conn->query("UPDATE attendance SET in_time = '$time' WHERE id = " . $row['id']);
                echo json_encode(['status' => 'success', 'message' => 'Check-in recorded']);
            }
        } elseif ($type === 'out') {
            $conn->query("UPDATE attendance SET out_time = '$time' WHERE id = " . $row['id']);
            echo json_encode(['status' => 'success', 'message' => 'Check-out recorded']);
        }
    } else {
        if ($type === 'in') {
            $stmt = $conn->prepare("INSERT INTO attendance (student_id, date, in_time) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $student_id, $date, $time);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Check-in recorded']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Database error']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Cannot check-out without check-in']);
        }
    }
    exit;
}

// Handle GET request (History)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $student_id = $_GET['student_id'] ?? null;

    if (!$student_id) {
        echo json_encode(['status' => 'error', 'message' => 'Student ID required']);
        exit;
    }

    $sql = "SELECT date, in_time, out_time FROM attendance WHERE student_id = $student_id ORDER BY date DESC";
    $result = $conn->query($sql);
    
    $history = [];
    while($row = $result->fetch_assoc()) {
        $history[] = $row;
    }

    echo json_encode(['status' => 'success', 'data' => $history]);
    exit;
}
?>
