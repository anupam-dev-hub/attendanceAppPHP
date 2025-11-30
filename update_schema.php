<?php
require 'config.php';

// Add new columns to students table
$queries = [
    "ALTER TABLE students ADD COLUMN class VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE students ADD COLUMN batch VARCHAR(50) DEFAULT '2025-2026'",
    "ALTER TABLE students ADD COLUMN roll_number VARCHAR(50) DEFAULT NULL"
];

foreach ($queries as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Column added successfully: $sql<br>";
    } else {
        echo "Error adding column: " . $conn->error . "<br>";
    }
}
?>
