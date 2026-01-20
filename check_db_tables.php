<?php
// check_db_tables.php
// Inspects the current database structure to verify all tables and columns

require 'config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "============================================================\n";
echo "DATABASE STRUCTURE INSPECTION\n";
echo "Database: " . $dbname . "\n";
echo "============================================================\n\n";

// Get all tables
$result = $conn->query("SHOW TABLES");
if (!$result) {
    echo "ERROR: Could not retrieve tables: " . $conn->error . "\n";
    exit(1);
}

$tables = [];
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

if (empty($tables)) {
    echo "No tables found in database.\n";
    echo "Run setup_db.php to create initial schema.\n";
    exit(0);
}

echo "Found " . count($tables) . " tables:\n";
echo "============================================================\n\n";

foreach ($tables as $table) {
    echo "TABLE: $table\n";
    echo str_repeat("-", 60) . "\n";
    
    // Get columns
    $colResult = $conn->query("DESCRIBE `$table`");
    if ($colResult) {
        echo sprintf("%-25s %-25s %-10s %-10s %-10s %s\n", 
            "Field", "Type", "Null", "Key", "Default", "Extra");
        echo str_repeat("-", 60) . "\n";
        
        while ($col = $colResult->fetch_assoc()) {
            echo sprintf("%-25s %-25s %-10s %-10s %-10s %s\n",
                $col['Field'],
                $col['Type'],
                $col['Null'],
                $col['Key'],
                $col['Default'] ?? 'NULL',
                $col['Extra']
            );
        }
    }
    
    // Get row count
    $countResult = $conn->query("SELECT COUNT(*) as cnt FROM `$table`");
    if ($countResult) {
        $count = $countResult->fetch_assoc()['cnt'];
        echo "\nRow count: $count\n";
    }
    
    // Get indexes
    $indexResult = $conn->query("SHOW INDEX FROM `$table`");
    if ($indexResult && $indexResult->num_rows > 0) {
        echo "\nIndexes:\n";
        $indexes = [];
        while ($idx = $indexResult->fetch_assoc()) {
            $key = $idx['Key_name'];
            if (!isset($indexes[$key])) {
                $indexes[$key] = [];
            }
            $indexes[$key][] = $idx['Column_name'];
        }
        foreach ($indexes as $key => $columns) {
            echo "  - $key: " . implode(', ', $columns) . "\n";
        }
    }
    
    echo "\n\n";
}

echo "============================================================\n";
echo "SUMMARY\n";
echo "============================================================\n";
echo "Total tables: " . count($tables) . "\n";
echo "\nTables list:\n";
foreach ($tables as $i => $table) {
    echo ($i + 1) . ". $table\n";
}

echo "\nDone.\n";
?>
