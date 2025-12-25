<?php
// org/api/custom_fees.php
// Assign custom (ad-hoc) fees to selected students or a single student

session_start();
require '../../config.php';
require '../../functions.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

if (!isOrg()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$org_id = $_SESSION['user_id'] ?? 0;
$action = $_GET['action'] ?? '';

if ($action !== 'assign') {
    echo json_encode(['success' => false, 'message' => 'Unknown or missing action']);
    exit;
}

// Expect JSON body
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$fee_title = trim($input['fee_title'] ?? '');
$amount = (float)($input['amount'] ?? 0);
$description = trim($input['description'] ?? '');
$student_ids = $input['student_ids'] ?? [];
$due_month = isset($input['due_month']) ? (int)$input['due_month'] : null; // 1..12
$due_year = isset($input['due_year']) ? (int)$input['due_year'] : null;   // e.g., 2025
$skip_existing = isset($input['skip_existing']) ? (bool)$input['skip_existing'] : true;

if ($fee_title === '' || $amount <= 0 || !is_array($student_ids) || count($student_ids) === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'fee_title, amount (>0), and at least one student must be provided'
    ]);
    exit;
}

// Build category text (used for grouping and pending calculations)
$category = $fee_title;
if ($due_month && $due_year) {
    $ts = mktime(0,0,0, $due_month, 1, $due_year);
    $category .= ' - ' . date('F Y', $ts);
}

// Normalize credit amount (amount owed) as negative value
$credit_amount = -abs($amount);

// Sanitize student IDs to integers and unique
$student_ids = array_values(array_unique(array_map('intval', $student_ids)));

if (empty($student_ids)) {
    echo json_encode(['success' => false, 'message' => 'No valid student IDs provided']);
    exit;
}

// Verify these students belong to the org in one query
$placeholders = implode(',', array_fill(0, count($student_ids), '?'));
$params = [];
$types = '';

// First param is org_id, then student ids
$types .= 'i';
$params[] = $org_id;
$types .= str_repeat('i', count($student_ids));
$params = array_merge($params, $student_ids);

$sqlVerify = "SELECT id FROM students WHERE org_id = ? AND id IN ($placeholders)";
$stmt = $conn->prepare($sqlVerify);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
    exit;
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$valid_ids = [];
while ($row = $res->fetch_assoc()) {
    $valid_ids[] = (int)$row['id'];
}
$stmt->close();

if (empty($valid_ids)) {
    echo json_encode(['success' => false, 'message' => 'No valid students found for this organization']);
    exit;
}

// Optional: skip if same category already exists for a student (to avoid duplicates)
$existing = [];
if ($skip_existing) {
    $placeholders2 = implode(',', array_fill(0, count($valid_ids), '?'));
    $types2 = 's' . str_repeat('i', count($valid_ids));
    $sqlExisting = "SELECT student_id FROM student_payments WHERE transaction_type = 'credit' AND category = ? AND student_id IN ($placeholders2)";
    $stmt2 = $conn->prepare($sqlExisting);
    if ($stmt2) {
        // Build bind params: first category (s), then ids (i...)
        $vals = array_merge([$category], $valid_ids);
        $tmp = [];
        foreach ($vals as $k => $v) { $tmp[$k] = &$vals[$k]; }
        array_unshift($tmp, $types2);
        call_user_func_array([$stmt2, 'bind_param'], $tmp);
        $stmt2->execute();
        $r2 = $stmt2->get_result();
        while ($r = $r2->fetch_assoc()) { $existing[(int)$r['student_id']] = true; }
        $stmt2->close();
    }
}

$to_insert = array_values(array_filter($valid_ids, function($id) use ($existing, $skip_existing) {
    return !$skip_existing || !isset($existing[$id]);
}));

if (empty($to_insert)) {
    echo json_encode(['success' => true, 'message' => 'No new records to create (duplicates skipped)', 'created' => 0, 'skipped' => count($valid_ids)]);
    exit;
}

$conn->begin_transaction();
$created = 0; $failed = 0; $errors = [];

$ins = $conn->prepare("INSERT INTO student_payments (student_id, amount, transaction_type, category, description) VALUES (?, ?, 'credit', ?, ?)");
if (!$ins) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
    exit;
}

foreach ($to_insert as $sid) {
    $ins->bind_param('idss', $sid, $credit_amount, $category, $description);
    if ($ins->execute()) {
        $created++;
    } else {
        $failed++;
        $errors[] = ['student_id' => $sid, 'error' => $ins->error];
    }
}

if ($failed > 0 && $created === 0) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to create custom fee entries', 'errors' => $errors]);
    exit;
}

$conn->commit();

echo json_encode([
    'success' => true,
    'message' => 'Custom fee assigned',
    'created' => $created,
    'skipped' => count($valid_ids) - $created,
]);

?>
