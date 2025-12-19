<?php
$conn = new mysqli('localhost', 'root', '', 'attendance_php');

// Get first student
$result = $conn->query("SELECT id FROM students LIMIT 1");
if ($result->num_rows === 0) {
    echo "No students found in database\n";
    exit;
}

$student = $result->fetch_assoc();
$student_id = $student['id'];

echo "Testing with student_id: $student_id\n\n";

$amount = 500;
$category = 'Monthly Fee';
$description = 'Test payment';

echo "=== Testing save_payment INSERT ===\n";

$stmt = $conn->prepare("INSERT INTO student_payments (student_id, amount, transaction_type, category, description) VALUES (?, ?, 'debit', ?, ?)");

if (!$stmt) {
    echo "ERROR: Prepare failed: " . $conn->error . "\n";
    exit;
}

$stmt->bind_param("idss", $student_id, $amount, $category, $description);

echo "Executing INSERT...\n";
if ($stmt->execute()) {
    echo "✅ INSERT successful!\n";
    echo "Payment ID: " . $stmt->insert_id . "\n";
} else {
    echo "❌ INSERT failed: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();
?>
