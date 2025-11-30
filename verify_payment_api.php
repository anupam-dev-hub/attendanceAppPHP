<?php
// verify_payment_api.php
require 'config.php';

// Simulate session
session_start();
// Assuming we have a valid org_id in session for testing, or we bypass check for this CLI script
// For CLI test, we'll just check if the file runs and inserts
// But since the API checks session, we might need to mock it or just check the DB insert directly if we were running unit tests.
// Instead, let's just use a direct script to insert a test payment using the same logic as the API to verify the query works.

$student_id = 1; // Assuming student ID 1 exists from seed data
$amount = 500.00;
$category = 'Test Fee';
$description = 'Verification Payment';

echo "Testing payment insertion for Student ID: $student_id\n";

// Check if student exists
$check = $conn->query("SELECT id FROM students WHERE id = $student_id");
if ($check->num_rows > 0) {
    echo "Student found.\n";
    
    $stmt = $conn->prepare("INSERT INTO student_payments (student_id, amount, transaction_type, category, description) VALUES (?, ?, 'debit', ?, ?)");
    $stmt->bind_param("idss", $student_id, $amount, $category, $description);
    
    if ($stmt->execute()) {
        echo "Payment inserted successfully.\n";
        $insert_id = $stmt->insert_id;
        
        // Verify it's there
        $verify = $conn->query("SELECT * FROM student_payments WHERE id = $insert_id");
        if ($row = $verify->fetch_assoc()) {
            echo "Verified Record: ID={$row['id']}, Amount={$row['amount']}, Type={$row['transaction_type']}, Category={$row['category']}\n";
        }
    } else {
        echo "Error inserting payment: " . $stmt->error . "\n";
    }
} else {
    echo "Student ID $student_id not found. Please check seed data.\n";
}
?>
