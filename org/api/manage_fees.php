<?php
/**
 * API: Manage Organization Fees
 * Endpoints to fetch, add, and manage fees for an organization
 */
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

session_start();
require '../../config.php';
require '../../functions.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Verify org is logged in
if (!isOrg()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - not org']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No user_id in session']);
    exit;
}

$org_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? null;

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit;
}

// Get all fees for this organization
if ($action === 'get_fees') {
    $sql = "SELECT id, fee_name, is_default FROM org_fees WHERE org_id = ? ORDER BY is_default DESC, fee_name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $org_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $fees = [];
    while ($row = $result->fetch_assoc()) {
        $fees[] = $row;
    }
    
    echo json_encode(['success' => true, 'fees' => $fees]);
    exit;
}

// Add new fee type
if ($action === 'add_fee') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['fee_name']) || empty($data['fee_name'])) {
        echo json_encode(['success' => false, 'message' => 'Fee name is required']);
        exit;
    }
    
    $fee_name = $data['fee_name'];
    
    // Try to insert with fee_type first (for backward compatibility)
    // If that fails, insert without it
    $sql = "INSERT INTO org_fees (org_id, fee_name, is_default) VALUES (?, ?, 0)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . $conn->error]);
        exit;
    }
    
    if (!$stmt->bind_param('is', $org_id, $fee_name)) {
        echo json_encode(['success' => false, 'message' => 'Bind Error: ' . $stmt->error]);
        exit;
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Fee added successfully', 'id' => $stmt->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Execute Error: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// Delete fee type
if ($action === 'delete_fee') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data['fee_id']) {
        echo json_encode(['success' => false, 'message' => 'Fee ID is required']);
        exit;
    }
    
    $fee_id = $data['fee_id'];
    
    // Check if fee belongs to this org
    $checkSql = "SELECT id FROM org_fees WHERE id = ? AND org_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param('ii', $fee_id, $org_id);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Fee not found']);
        exit;
    }
    
    $sql = "DELETE FROM org_fees WHERE id = ? AND org_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $fee_id, $org_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Fee deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete fee']);
    }
    exit;
}

// Update fee
if ($action === 'update_fee') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data['fee_id'] || !$data['fee_name']) {
        echo json_encode(['success' => false, 'message' => 'Fee ID and name are required']);
        exit;
    }
    
    $fee_id = $data['fee_id'];
    $fee_name = $data['fee_name'];
    
    // Check if fee belongs to this org
    $checkSql = "SELECT id FROM org_fees WHERE id = ? AND org_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param('ii', $fee_id, $org_id);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Fee not found']);
        exit;
    }
    
    $sql = "UPDATE org_fees SET fee_name = ? WHERE id = ? AND org_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sii', $fee_name, $fee_id, $org_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Fee updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update fee']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
?>