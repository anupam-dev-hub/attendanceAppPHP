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

if ($class === '' || $batch === '') {
    echo json_encode(['success' => false, 'message' => 'Class and batch are required']);
    exit;
}

// Fetch all active students matching the class and batch
$stmt = $conn->prepare("SELECT id, name, roll_number, class, batch FROM students WHERE org_id = ? AND class = ? AND batch = ? AND is_active = 1 ORDER BY roll_number ASC, name ASC");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
    exit;
}

$stmt->bind_param('iss', $org_id, $class, $batch);

if ($stmt->execute()) {
    $result = $stmt->get_result();
    $students = [];
    
    while ($row = $result->fetch_assoc()) {
        $students[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'roll_number' => $row['roll_number'],
            'class' => $row['class'],
            'batch' => $row['batch']
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
