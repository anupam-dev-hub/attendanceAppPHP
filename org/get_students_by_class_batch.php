<?php
// org/get_students_by_class_batch.php
session_start();
require '../config.php';
require '../functions.php';

header('Content-Type: application/json');

if (!isOrg()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$org_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$class = isset($_GET['class']) ? trim($_GET['class']) : '';
$batch = isset($_GET['batch']) ? trim($_GET['batch']) : '';
$stream = isset($_GET['stream']) ? trim($_GET['stream']) : '';

// Require at least one filter to avoid fetching all rows unintentionally
$types = 'i';
$params = [$org_id];
$conditions = ['org_id = ?', 'is_active = 1'];

if ($class !== '') {
    $conditions[] = 'class = ?';
    $types .= 's';
    $params[] = $class;
}
if ($batch !== '') {
    $conditions[] = 'batch = ?';
    $types .= 's';
    $params[] = $batch;
}
if ($stream !== '') {
    $conditions[] = 'stream = ?';
    $types .= 's';
    $params[] = $stream;
}

if (count($params) === 1) {
    echo json_encode(['success' => false, 'message' => 'Please provide at least one filter (class, batch, or stream).']);
    exit;
}

$whereSql = implode(' AND ', $conditions);
$sql = "SELECT id, name, roll_number, class, batch, stream FROM students WHERE $whereSql ORDER BY roll_number ASC, name ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
    exit;
}

// Bind parameters dynamically
$bindParams = [];
$bindParams[] = & $types;
foreach ($params as $key => $value) {
    $bindParams[] = & $params[$key];
}
call_user_func_array([$stmt, 'bind_param'], $bindParams);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    $students = [];
    
    while ($row = $result->fetch_assoc()) {
        $students[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'roll_number' => $row['roll_number'],
            'class' => $row['class'],
            'batch' => $row['batch'],
            'stream' => $row['stream']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'students' => $students,
        'count' => count($students)
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch students']);
}

$stmt->close();
$conn->close();
?>
