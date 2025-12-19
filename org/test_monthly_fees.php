<?php
// org/test_monthly_fees.php
// Test script for monthly fee initialization system

require '../config.php';
require 'monthly_fee_functions.php';

echo "<h1>Monthly Fee System Test</h1>";
echo "<hr>";

// Test 1: Check if duplicate detection works
echo "<h2>Test 1: Duplicate Detection</h2>";
$test_student_id = 1; // Use an existing student ID
$test_fee = 1000.00;
$current_month = (int)date('n');
$current_year = (int)date('Y');

// Check if fee already exists
$has_fee = hasMonthlyFee($conn, $test_student_id, $current_month, $current_year);
echo "Student #$test_student_id has fee for current month: " . ($has_fee ? 'YES' : 'NO') . "<br>";

// Try to initialize
echo "<h3>Attempting to initialize current month fee...</h3>";
$result = initializeMonthlyFee($conn, $test_student_id, $test_fee, $current_month, $current_year);
echo "Success: " . ($result['success'] ? 'YES' : 'NO') . "<br>";
echo "Message: " . $result['message'] . "<br>";

// Try again (should detect duplicate)
echo "<h3>Attempting to initialize again (should fail with duplicate message)...</h3>";
$result2 = initializeMonthlyFee($conn, $test_student_id, $test_fee, $current_month, $current_year);
echo "Success: " . ($result2['success'] ? 'YES' : 'NO') . "<br>";
echo "Message: " . $result2['message'] . "<br>";

echo "<hr>";

// Test 2: Get fee status
echo "<h2>Test 2: Get Monthly Fee Status</h2>";
$status = getMonthlyFeeStatus($conn, $test_student_id, 6);
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Month</th><th>Has Fee</th><th>Due</th><th>Paid</th><th>Balance</th><th>Status</th></tr>";
foreach ($status as $month) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($month['month_name']) . "</td>";
    echo "<td>" . ($month['has_fee'] ? 'Yes' : 'No') . "</td>";
    echo "<td>₹" . number_format($month['total_due'], 2) . "</td>";
    echo "<td>₹" . number_format($month['total_paid'], 2) . "</td>";
    echo "<td>₹" . number_format($month['balance'], 2) . "</td>";
    echo "<td>" . htmlspecialchars($month['status']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";

// Test 3: Test invalid inputs
echo "<h2>Test 3: Invalid Input Handling</h2>";

echo "<h3>Test with zero fee amount:</h3>";
$result3 = initializeMonthlyFee($conn, $test_student_id, 0, $current_month, $current_year);
echo "Success: " . ($result3['success'] ? 'YES' : 'NO') . "<br>";
echo "Message: " . $result3['message'] . "<br>";

echo "<h3>Test with negative fee amount:</h3>";
$result4 = initializeMonthlyFee($conn, $test_student_id, -500, $current_month, $current_year);
echo "Success: " . ($result4['success'] ? 'YES' : 'NO') . "<br>";
echo "Message: " . $result4['message'] . "<br>";

echo "<hr>";
echo "<p><strong>Tests completed!</strong></p>";
echo "<p><a href='students.php'>Back to Students</a></p>";

$conn->close();
?>
