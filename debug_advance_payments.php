<?php
// Debug script to check advance payments
require 'config.php';

echo "=== Checking advance_payments table ===\n\n";

// Check if table exists
$tables = $conn->query("SHOW TABLES LIKE 'advance_payments'");
if ($tables->num_rows === 0) {
    echo "❌ advance_payments table does NOT exist\n";
    exit;
}
echo "✓ advance_payments table exists\n\n";

// Get all advance payments
$result = $conn->query("SELECT * FROM advance_payments ORDER BY created_at DESC LIMIT 10");
if ($result && $result->num_rows > 0) {
    echo "Found {$result->num_rows} advance payment(s):\n\n";
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}\n";
        echo "Student ID: {$row['student_id']}\n";
        echo "Amount: {$row['amount']}\n";
        echo "Payment Date: {$row['payment_date']}\n";
        echo "Created At: " . ($row['created_at'] ?? 'NULL') . "\n";
        echo "Description: " . ($row['description'] ?? 'NULL') . "\n";
        echo "---\n";
    }
} else {
    echo "No advance payments found in database\n";
}

// Test the UNION query
echo "\n=== Testing UNION query ===\n\n";
$sql = "SELECT id, amount, transaction_type, category, CAST(description AS CHAR) AS description,
               COALESCE(created_at, NOW()) AS created_at
        FROM student_payments
        WHERE student_id = 78559
        UNION ALL
        SELECT id, amount, CAST('debit' AS CHAR) AS transaction_type, 
               CAST('Advance Payment' AS CHAR) AS category,
               CAST(COALESCE(description, 'Advance Payment') AS CHAR) AS description,
               COALESCE(TIMESTAMP(payment_date), created_at, NOW()) AS created_at
        FROM advance_payments
        WHERE student_id = 78559
        ORDER BY created_at DESC";

echo "Query:\n$sql\n\n";

$result = $conn->query($sql);
if ($result) {
    echo "✓ Query executed successfully\n";
    echo "Rows returned: " . $result->num_rows . "\n\n";
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "ID: {$row['id']} | Type: {$row['transaction_type']} | Category: {$row['category']} | Amount: {$row['amount']}\n";
        }
    }
} else {
    echo "❌ Query failed: " . $conn->error . "\n";
}
