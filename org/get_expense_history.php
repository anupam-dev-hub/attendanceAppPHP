<?php
session_start();
require '../config.php';
require '../functions.php';

if (!isOrg()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$org_id = $_SESSION['user_id'];
$category = $_GET['category'] ?? '';

if (empty($category)) {
    http_response_code(400);
    echo json_encode(['error' => 'Category required']);
    exit();
}

// Get expenses for this category
$stmt = $conn->prepare("SELECT id, title, category, amount, expense_date, notes FROM expenses WHERE org_id = ? AND category = ? ORDER BY expense_date DESC");
$stmt->bind_param("is", $org_id, $category);
$stmt->execute();
$result = $stmt->get_result();

$expenses = [];
$total_amount = 0;

while ($row = $result->fetch_assoc()) {
    $expenses[] = $row;
    $total_amount += $row['amount'];
}

echo json_encode([
    'expenses' => $expenses,
    'total_amount' => $total_amount,
    'total_records' => count($expenses)
]);
?>
