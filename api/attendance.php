<?php
// api/attendance.php - Smart Attendance API
// Auto-detects check-in/out, supports students and employees
// Enforces 5-minute cooldown and daily limits

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, ngrok-skip-browser-warning");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
require '../config.php';
require __DIR__ . '/auth_utils.php';
require __DIR__ . '/qr_utils.php';

// Support both Bearer token and QR token authentication
$org_id = null;

// Try QR token first (from query param or POST data)
$qr_token = isset($_GET['qr_token']) ? $_GET['qr_token'] : (isset($_POST['qr_token']) ? $_POST['qr_token'] : null);

if ($qr_token) {
    $org_id = find_org_by_qr_token($conn, $qr_token);
    if (!$org_id) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired QR token']);
        exit;
    }
} else {
    // Fallback to Bearer token authentication
    $org_id = require_org_auth($conn);
}

// Handle POST request (Record Attendance)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get input
    $input = $_POST;
    if (empty($input)) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) $input = $decoded;
    }
    
    // New format: type (S/E) and id
    $person_type = isset($input['type']) ? strtoupper(trim($input['type'])) : null;
    $person_id = isset($input['id']) ? (int)$input['id'] : null;
    $time = $input['time'] ?? date('H:i:s');
    $date = date('Y-m-d');
    
    // Backward compatibility: support old format
    if (!$person_type && isset($input['student_id'])) {
        $person_type = 'S';
        $person_id = (int)$input['student_id'];
        $manual_type = $input['type'] ?? null; // 'in' or 'out'
    }
    
    // Validate input
    if (!$person_type || !$person_id) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required parameters: type (S/E) and id'
        ]);
        exit;
    }
    
    if ($person_type !== 'S' && $person_type !== 'E') {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid type. Use S for Student or E for Employee'
        ]);
        exit;
    }
    
    // Determine table and column names
    if ($person_type === 'S') {
        $table = 'attendance';
        $id_column = 'student_id';
        $verify_table = 'students';
    } else {
        $table = 'employee_attendance';
        $id_column = 'employee_id';
        $verify_table = 'employees';
    }
    
    // Verify person belongs to the authenticated org
    $stmt = $conn->prepare("SELECT id, name, photo FROM $verify_table WHERE id = ? AND org_id = ?");
    $stmt->bind_param('ii', $person_id, $org_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Forbidden: ' . ($person_type === 'S' ? 'student' : 'employee') . ' not in your organization'
        ]);
        exit;
    }
    
    $person_data = $res->fetch_assoc();
    $person_name = $person_data['name'];
    $person_photo = $person_data['photo'];
    
    // Check today's attendance records
    $check_sql = "SELECT id, in_time, out_time, date FROM $table 
                  WHERE $id_column = ? AND date = ? 
                  ORDER BY id DESC LIMIT 1";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('is', $person_id, $date);
    $check_stmt->execute();
    $today_result = $check_stmt->get_result();
    
    $action = null;
    $error = null;
    
    if ($today_result->num_rows === 0) {
        // No attendance today - this is check-in
        $action = 'in';
    } else {
        $today_record = $today_result->fetch_assoc();
        $record_id = $today_record['id']; // Store record ID for potential update
        
        if (empty($today_record['in_time'])) {
            // Has record but no check-in time - this is check-in (update blank entry)
            $action = 'in';
        } elseif (empty($today_record['out_time'])) {
            // Has check-in but no check-out - validate cooldown
            $check_in_time = strtotime($date . ' ' . $today_record['in_time']);
            $current_time = strtotime($date . ' ' . $time);
            $time_diff_minutes = ($current_time - $check_in_time) / 60;
            
            if ($time_diff_minutes < 5) {
                $wait_minutes = ceil(5 - $time_diff_minutes);
                $earliest_checkout = date('H:i:s', $check_in_time + (5 * 60));
                
                // Cooldown active
                echo json_encode([
                    'status' => 'warning',
                    'message' => "Please wait $wait_minutes more minute(s) before check-out",
                    'check_in_time' => $today_record['in_time'],
                    'earliest_checkout' => $earliest_checkout,
                    'time_elapsed_minutes' => floor($time_diff_minutes),
                    'person' => [
                        'name' => $person_name,
                        'photo' => $person_photo
                    ]
                ]);
                exit;
            }
            
            // Cooldown passed - this is check-out
            $action = 'out';
        } else {
            // Already has both check-in and check-out
            // Already has both check-in and check-out
            echo json_encode([
                'status' => 'warning',
                'message' => 'Daily attendance limit reached',
                'check_in' => $today_record['in_time'],
                'check_out' => $today_record['out_time'],
                'date' => $today_record['date'],
                'person' => [
                    'name' => $person_name,
                    'photo' => $person_photo
                ]
            ]);
            exit;
        }
    }
    
    // Override action if manual type is specified (backward compatibility)
    if (isset($manual_type) && ($manual_type === 'in' || $manual_type === 'out')) {
        $action = $manual_type;
    }
    
    // Execute the action
    if ($action === 'in') {
        if (isset($record_id)) {
            // Update existing record
            $upd = $conn->prepare("UPDATE $table SET in_time = ? WHERE id = ?");
            $upd->bind_param('si', $time, $record_id);
            $upd->execute();
        } else {
            // Insert new record
            $ins = $conn->prepare("INSERT INTO $table ($id_column, date, in_time) VALUES (?, ?, ?)");
            $ins->bind_param('iss', $person_id, $date, $time);
            $ins->execute();
        }
        
        $earliest_checkout = date('H:i:s', strtotime($time) + (5 * 60));
        
        echo json_encode([
            'status' => 'success',
            'action' => 'in',
            'message' => 'Check-in recorded',
            'time' => $time,
            'date' => $date,
            'next_action' => 'Check-out available after ' . $earliest_checkout,
            'person' => [
                'name' => $person_name,
                'photo' => $person_photo
            ]
        ]);
    } else {
        // Check-out
        $upd = $conn->prepare("UPDATE $table SET out_time = ? WHERE id = ?");
        $upd->bind_param('si', $time, $record_id);
        $upd->execute();
        
        // Calculate duration
        $in_timestamp = strtotime($date . ' ' . $today_record['in_time']);
        $out_timestamp = strtotime($date . ' ' . $time);
        $duration_seconds = $out_timestamp - $in_timestamp;
        $hours = floor($duration_seconds / 3600);
        $minutes = floor(($duration_seconds % 3600) / 60);
        
        echo json_encode([
            'status' => 'success',
            'action' => 'out',
            'message' => 'Check-out recorded',
            'time' => $time,
            'date' => $date,
            'check_in_time' => $today_record['in_time'],
            'duration' => "$hours hours $minutes minutes",
            'person' => [
                'name' => $person_name,
                'photo' => $person_photo
            ]
        ]);
    }
    exit;
}

// Handle GET request (History) - unchanged
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if today has any attendance entries (blank or with times)
    if (isset($_GET['action']) && $_GET['action'] === 'check_today_entries') {
        $today = date('Y-m-d');
        
        try {
            // Check student attendance - ANY entries for today
            $stmt1 = $conn->prepare("
                SELECT COUNT(*) as count FROM attendance 
                WHERE date = ?
            ");
            $stmt1->bind_param('s', $today);
            $stmt1->execute();
            $result1 = $stmt1->get_result();
            $row1 = $result1->fetch_assoc();
            $student_count = $row1['count'] ?? 0;
            
            // Check employee attendance - ANY entries for today
            $stmt2 = $conn->prepare("
                SELECT COUNT(*) as count FROM employee_attendance 
                WHERE date = ?
            ");
            $stmt2->bind_param('s', $today);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $row2 = $result2->fetch_assoc();
            $employee_count = $row2['count'] ?? 0;
            
            $total_entries = $student_count + $employee_count;
            
            echo json_encode([
                'status' => 'success',
                'date' => $today,
                'has_entries' => $total_entries > 0,
                'entry_count' => $total_entries
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    // Open day: Create blank entries for all active students and employees
    if (isset($_GET['action']) && $_GET['action'] === 'open_day') {
        $today = date('Y-m-d');
        
        try {
            // Get all active students in the org
            $stmt = $conn->prepare("SELECT id FROM students WHERE org_id = ? AND is_active = 1");
            $stmt->bind_param('i', $org_id);
            $stmt->execute();
            $students = $stmt->get_result();
            
            // Get all active employees in the org
            $stmt_emp = $conn->prepare("SELECT id FROM employees WHERE org_id = ? AND is_active = 1");
            $stmt_emp->bind_param('i', $org_id);
            $stmt_emp->execute();
            $employees = $stmt_emp->get_result();
            
            $created_count = 0;
            
            // Insert blank entries for students
            $ins_stmt = $conn->prepare("INSERT IGNORE INTO attendance (student_id, date, in_time, out_time) VALUES (?, ?, NULL, NULL)");
            while ($student = $students->fetch_assoc()) {
                $ins_stmt->bind_param('is', $student['id'], $today);
                $ins_stmt->execute();
                $created_count++;
            }
            
            // Insert blank entries for employees
            $ins_stmt_emp = $conn->prepare("INSERT IGNORE INTO employee_attendance (employee_id, date, in_time, out_time) VALUES (?, ?, NULL, NULL)");
            while ($employee = $employees->fetch_assoc()) {
                $ins_stmt_emp->bind_param('is', $employee['id'], $today);
                $ins_stmt_emp->execute();
                $created_count++;
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Open day initialized',
                'date' => $today,
                'entries_created' => $created_count
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to initialize open day: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    if (isset($_GET['action']) && $_GET['action'] === 'org_info') {
        $stmt_org = $conn->prepare("SELECT name, logo, camera_type FROM organizations WHERE id = ?");
        $stmt_org->bind_param('i', $org_id);
        $stmt_org->execute();
        $res_org = $stmt_org->get_result();
        
        if ($data = $res_org->fetch_assoc()) {
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'name' => $data['name'],
                    'logo' => $data['logo'], // Ensure this is a full URL or relative path handled by frontend
                    'camera_type' => $data['camera_type'] ?? 'back' // Default to back camera
                ]
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Organization not found']);
        }
        exit;
    }

    if (isset($_GET['action']) && $_GET['action'] === 'verify_password') {
        $password = isset($_GET['password']) ? $_GET['password'] : '';
        if (!$password) {
            echo json_encode(['status' => 'error', 'message' => 'Password required']);
            exit;
        }

        // $org_id is already set at the top of the file via QR token or Bearer token
        if (!$org_id) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        $stmt = $conn->prepare("SELECT password FROM organizations WHERE id = ?");
        $stmt->bind_param('i', $org_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                echo json_encode(['status' => 'success', 'message' => 'Password verified']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid password']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Organization not found']);
        }
        exit;
    }

    $student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;

    if (!$student_id) {
        echo json_encode(['status' => 'error', 'message' => 'Student ID required']);
        exit;
    }

    // Ensure student belongs to org
    $stmt = $conn->prepare("SELECT id FROM students WHERE id = ? AND org_id = ?");
    $stmt->bind_param('ii', $student_id, $org_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden: student not in your organization']);
        exit;
    }

    $stmt2 = $conn->prepare("SELECT date, in_time, out_time FROM attendance WHERE student_id = ? ORDER BY date DESC");
    $stmt2->bind_param('i', $student_id);
    $stmt2->execute();
    $result = $stmt2->get_result();

    $history = [];
    while ($row = $result->fetch_assoc()) {
        // Determine status: Absent if both in_time and out_time are blank
        $in_time = $row['in_time'];
        $out_time = $row['out_time'];
        
        if (empty($in_time) && empty($out_time)) {
            $row['status'] = 'Absent';
        } else {
            $row['status'] = 'Present';
        }
        
        $history[] = $row;
    }

    echo json_encode(['status' => 'success', 'data' => $history]);
    exit;
}
?>
