<?php
// add_advance_payment_system.php
// Add advance payment system to existing database

require 'config.php';

$updates = [
    // Add advance_payment column to students table
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS advance_payment DECIMAL(10, 2) DEFAULT 0.00 AFTER admission_amount",
    
    // Create advance_payments table to track advance payment history
    "CREATE TABLE IF NOT EXISTS advance_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        payment_date DATE NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        INDEX idx_student_id (student_id),
        INDEX idx_payment_date (payment_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    
    // Create advance_payment_adjustments table to track deductions from advance payment
    "CREATE TABLE IF NOT EXISTS advance_payment_adjustments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        advance_payment_id INT NOT NULL,
        student_payment_id INT NOT NULL,
        deduction_amount DECIMAL(10, 2) NOT NULL,
        adjustment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (advance_payment_id) REFERENCES advance_payments(id) ON DELETE CASCADE,
        FOREIGN KEY (student_payment_id) REFERENCES student_payments(id) ON DELETE CASCADE,
        INDEX idx_student_id (student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

$success_count = 0;
$error_count = 0;

foreach ($updates as $sql) {
    if ($conn->query($sql)) {
        $success_count++;
        echo "✓ Query executed successfully\n";
    } else {
        $error_count++;
        echo "✗ Error: " . $conn->error . "\n";
    }
}

echo "\n=== Summary ===\n";
echo "Successful updates: $success_count\n";
echo "Errors: $error_count\n";

if ($error_count === 0) {
    echo "\nAdvance Payment System added successfully!\n";
} else {
    echo "\nSome errors occurred. Please check the database manually.\n";
}

$conn->close();
?>
