<?php
// Test save_payment.php directly
session_start();
$_SESSION['user_id'] = 1; // Simulate org user
$_SESSION['user_role'] = 'org';

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['student_id'] = 1; // Use a test student ID
$_POST['amount'] = 100;
$_POST['category'] = 'Tuition Fee';
$_POST['description'] = 'Test payment';

// Capture output
ob_start();
require 'org/api/save_payment.php';
$output = ob_get_clean();

echo "=== OUTPUT ===\n";
echo $output;
echo "\n=== END ===\n";

// Try to decode
$json = json_decode($output, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "\nJSON Error: " . json_last_error_msg() . "\n";
} else {
    echo "\nJSON decoded successfully!\n";
    print_r($json);
}
?>
