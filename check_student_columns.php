<?php
require 'config.php';

$result = $conn->query('DESCRIBE students');
echo "Students table columns:\n";
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
