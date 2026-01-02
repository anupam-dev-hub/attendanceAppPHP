<?php
require 'config.php';

echo "Checking organizations table:\n";
$result = $conn->query('SHOW TABLES LIKE "organizations"');
if ($result && $result->num_rows > 0) {
    echo "✓ organizations table exists\n\n";
    echo "Columns:\n";
    $cols = $conn->query('DESCRIBE organizations');
    while($row = $cols->fetch_assoc()) {
        echo "  - " . $row['Field'] . "\n";
    }
} else {
    echo "✗ organizations table does NOT exist\n";
}
?>
