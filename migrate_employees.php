<?php
// migrate_employees.php
// Script to create employees and employee_documents tables

require_once 'config.php';

echo "<h2>Employees Tables Migration</h2>";

// Read SQL file
$sql_file = __DIR__ . '/migrations/create_employees_tables.sql';

if (!file_exists($sql_file)) {
    die("Error: Migration file not found at $sql_file");
}

$sql = file_get_contents($sql_file);

// Split into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

$success_count = 0;
$error_count = 0;

foreach ($statements as $statement) {
    if (empty($statement)) continue;
    
    if ($conn->query($statement)) {
        $success_count++;
        echo "<p style='color: green;'>✓ Executed successfully</p>";
    } else {
        $error_count++;
        echo "<p style='color: red;'>✗ Error: " . $conn->error . "</p>";
        echo "<pre>" . htmlspecialchars($statement) . "</pre>";
    }
}

echo "<hr>";
echo "<h3>Summary:</h3>";
echo "<p>Successful: $success_count</p>";
echo "<p>Errors: $error_count</p>";

if ($error_count === 0) {
    echo "<p style='color: green; font-weight: bold;'>✓ All tables created successfully!</p>";
    echo "<p>You can now access the Employees page at: <a href='org/employees.php'>org/employees.php</a></p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>✗ Some errors occurred. Please check above.</p>";
}

$conn->close();
?>
