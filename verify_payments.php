<?php
require 'config.php';

$table = 'student_payments';
$result = $conn->query("SHOW TABLES LIKE '$table'");

if ($result->num_rows > 0) {
    echo "Table '$table' exists.\n";
    $columns = $conn->query("SHOW COLUMNS FROM $table");
    echo "Columns:\n";
    while ($row = $columns->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Table '$table' does not exist.\n";
}
?>
