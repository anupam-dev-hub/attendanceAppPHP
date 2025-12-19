<?php
// update_student_schema.php
require 'config.php';

echo "Adding new fields to students table...\n";

$alterations = [
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS sex ENUM('Male', 'Female', 'Other') DEFAULT NULL",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS sex_other VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS date_of_birth DATE DEFAULT NULL",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS parent_guardian_name VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS parent_contact VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS pin_code VARCHAR(10) DEFAULT NULL",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS native_district VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS nationality VARCHAR(50) DEFAULT 'Indian'",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS mother_tongue VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS religion ENUM('Hindu', 'Muslim', 'Christian', 'Other') DEFAULT NULL",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS religion_other VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS place_of_birth VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS community ENUM('ST', 'SC', 'SC(A)', 'BC', 'General') DEFAULT NULL",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS exam_name VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS exam_total_marks INT DEFAULT NULL",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS exam_marks_obtained DECIMAL(10,2) DEFAULT NULL",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS exam_percentage DECIMAL(5,2) DEFAULT NULL",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS exam_grade VARCHAR(10) DEFAULT NULL"
];

$success_count = 0;
$error_count = 0;

foreach ($alterations as $sql) {
    try {
        if ($conn->query($sql) === TRUE) {
            $success_count++;
            echo "✓ Executed: " . substr($sql, 0, 80) . "...\n";
        } else {
            // Check if error is because column already exists
            if (strpos($conn->error, 'Duplicate column name') !== false) {
                echo "○ Skipped (already exists): " . substr($sql, 0, 80) . "...\n";
            } else {
                $error_count++;
                echo "✗ Error: " . $conn->error . "\n";
            }
        }
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "○ Skipped (already exists): " . substr($sql, 0, 80) . "...\n";
        } else {
            $error_count++;
            echo "✗ Exception: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n--- Summary ---\n";
echo "Successful: $success_count\n";
echo "Errors: $error_count\n";
echo "\nDatabase schema update completed.\n";

$conn->close();
?>
