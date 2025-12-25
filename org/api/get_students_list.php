<?php
// org/api/get_students_list.php
// Return all active students for the org (id, name, roll_number, class, batch)

session_start();
require '../../config.php';
require '../../functions.php';

header('Content-Type: application/json');

if (!isOrg()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$org_id = $_SESSION['user_id'] ?? 0;

$stmt = $conn->prepare("SELECT id, name, roll_number, class, batch FROM students WHERE org_id = ? AND is_active = 1 ORDER BY class ASC, roll_number ASC, name ASC");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
    exit;
}
$stmt->bind_param('i', $org_id);
$stmt->execute();
$res = $stmt->get_result();
$students = [];
while ($row = $res->fetch_assoc()) {
    $students[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'roll_number' => $row['roll_number'],
        'class' => $row['class'],
        'batch' => $row['batch'],
    ];
}
$stmt->close();

echo json_encode(['success' => true, 'students' => $students, 'count' => count($students)]);
