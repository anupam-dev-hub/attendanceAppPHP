<?php
require 'config.php';

echo "Checking advance_payments table:\n";
$result = $conn->query('SHOW TABLES LIKE "advance_payments"');
if ($result && $result->num_rows > 0) {
    echo "✓ advance_payments table exists\n\n";
    echo "Columns:\n";
    $cols = $conn->query('DESCRIBE advance_payments');
    while($row = $cols->fetch_assoc()) {
        echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "✗ advance_payments table does NOT exist\n";
}
?>
