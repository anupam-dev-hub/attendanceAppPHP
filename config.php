<?php
// config.php
$host = 'localhost';
$user = 'mxaxnxu';
$pass = 'x5NU9~Po!W5!Y#$rW4$$';
$dbname = 'attendance_php';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
