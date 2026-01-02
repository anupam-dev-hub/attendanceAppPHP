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

if ($class === '' || $batch === '' || $stream === '') {
    echo json_encode(['success' => false, 'message' => 'Class, batch and stream are required']);
    exit;
}

// Fetch all active students matching the class, batch, and stream
$stmt = $conn->prepare("SELECT id, name, roll_number, class, batch, stream FROM students WHERE org_id = ? AND class = ? AND batch = ? AND stream = ? AND is_active = 1 ORDER BY roll_number ASC, name ASC");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
    exit;
}
$stmt->bind_param('isss', $org_id, $class, $batch, $stream);

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
