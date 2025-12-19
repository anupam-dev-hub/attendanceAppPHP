<?php
/**
 * Migration: Remove old 'fee' column from students table
 * Run this once to update existing databases
 */

require 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Migration: Remove Old Fee Column</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .box { padding: 20px; border-radius: 5px; margin: 10px 0; }
        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }
        .info { background-color: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <h1>Database Migration Tool</h1>
    <p>This script removes the old 'fee' column from the 'students' table.</p>
";

// Check if column exists before dropping
$checkColumn = "SELECT COUNT(*) as col_count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'students' AND COLUMN_NAME = 'fee' AND TABLE_SCHEMA = 'attendance_php'";
$result = $conn->query($checkColumn);
$row = $result->fetch_assoc();

if ($row['col_count'] > 0) {
    // Column exists, so drop it
    $sql = "ALTER TABLE students DROP COLUMN fee";
    
    if ($conn->query($sql) === TRUE) {
        echo "<div class='box success'>";
        echo "<h2>✓ Migration Successful</h2>";
        echo "<p>The old 'fee' column has been successfully removed from the 'students' table.</p>";
        echo "<p>The new 'fees_json' column is now the only fee storage system.</p>";
        echo "</div>";
    } else {
        echo "<div class='box error'>";
        echo "<h2>✗ Migration Failed</h2>";
        echo "<p>Error: " . $conn->error . "</p>";
        echo "</div>";
    }
} else {
    echo "<div class='box info'>";
    echo "<h2>ℹ No Action Needed</h2>";
    echo "<p>The 'fee' column does not exist in the 'students' table. Your database is already updated.</p>";
    echo "</div>";
}

echo "<p><a href='org/manage_fees.php'>Go to Fee Management</a> | <a href='org/students.php'>Go to Students</a></p>";
echo "</body></html>";
?>
