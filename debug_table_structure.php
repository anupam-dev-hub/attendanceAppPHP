<?php
// Quick debug script
$conn = new mysqli('localhost', 'root', '', 'attendance_php');

echo "=== student_payments table structure ===\n";
$result = $conn->query("DESCRIBE student_payments");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " | " . $row['Type'] . "\n";
}

echo "\n=== students table structure (checking for 'fee' column) ===\n";
$result = $conn->query("DESCRIBE students");
while($row = $result->fetch_assoc()) {
    if (strpos($row['Field'], 'fee') !== false) {
        echo "FOUND: " . $row['Field'] . " | " . $row['Type'] . "\n";
    }
}
$conn->close();
?>
