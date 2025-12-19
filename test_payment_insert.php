<?php
// Quick test of the payment save endpoint
error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli('localhost', 'root', '', 'attendance_php');

if ($conn->connect_error) {
    die('Connection Error: ' . $conn->connect_error);
}

// Simulate the save_payment request
$student_id = 1; // First student
$amount = 500;
$category = 'Monthly Fee';
$description = 'Test payment';

echo "=== Testing save_payment INSERT ===\n";
echo "Data: student_id=$student_id, amount=$amount, category=$category, description=$description\n\n";

try {
    $stmt = $conn->prepare("INSERT INTO student_payments (student_id, amount, transaction_type, category, description) VALUES (?, ?, 'debit', ?, ?)");
    
    if (!$stmt) {
        echo "ERROR: Prepare failed: " . $conn->error . "\n";
        exit;
    }
    
    $stmt->bind_param("idss", $student_id, $amount, $category, $description);
    
    echo "Executing INSERT...\n";
    if ($stmt->execute()) {
        echo "✅ INSERT successful!\n";
        echo "Last Insert ID: " . $stmt->insert_id . "\n";
    } else {
        echo "❌ INSERT failed: " . $stmt->error . "\n";
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}

$conn->close();
?>
