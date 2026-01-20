<?php
/**
 * API Ping Endpoint
 * Simple endpoint to check if server is accessible
 * No authentication required
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

echo json_encode([
    'status' => 'success',
    'message' => 'Server is accessible',
    'timestamp' => time(),
    'server' => 'Attendance System API'
]);
