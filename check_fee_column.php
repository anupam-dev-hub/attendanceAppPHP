<?php
$conn = new mysqli('localhost', 'root', '', 'attendance_php');

echo "=== Checking for 'fee' column in students table ===\n";
$result = $conn->query("SHOW COLUMNS FROM students LIKE 'fee'");

if ($result->num_rows > 0) {
    echo "FOUND 'fee' column:\n";
    $row = $result->fetch_assoc();
    print_r($row);
    echo "\n⚠️  The 'fee' column still exists in the database!\n";
} else {
    echo "✅ The 'fee' column does NOT exist in the database.\n";
}

$conn->close();
?>
