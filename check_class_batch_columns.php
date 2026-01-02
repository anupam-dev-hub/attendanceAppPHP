<?php
require 'config.php';

echo "Classes table columns:\n";
$result = $conn->query('DESCRIBE classes');
if ($result) {
    while($row = $result->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\nBatches table columns:\n";
$result = $conn->query('DESCRIBE batches');
if ($result) {
    while($row = $result->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
