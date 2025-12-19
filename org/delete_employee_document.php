<?php
// org/delete_employee_document.php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in and is an organization
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'org') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit();
    }

    $org_id = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['document_id'])) {
        $document_id = intval($_POST['document_id']);
        
        // First, verify that this document belongs to an employee of this organization
        $verify_query = "SELECT ed.*, e.org_id 
                         FROM employee_documents ed 
                         INNER JOIN employees e ON ed.employee_id = e.id 
                         WHERE ed.id = ? AND e.org_id = ?";
        $verify_stmt = $conn->prepare($verify_query);
        
        if (!$verify_stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            exit();
        }
        
        $verify_stmt->bind_param("ii", $document_id, $org_id);
        $verify_stmt->execute();
        $result = $verify_stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Document not found or unauthorized']);
            $verify_stmt->close();
            $conn->close();
            exit();
        }
        
        $document = $result->fetch_assoc();
        $verify_stmt->close();
        
        // Delete the file from the server
        $file_path = $document['file_path'];
        
        // Handle both absolute and relative paths
        if (!empty($file_path)) {
            // If path starts with ../, it's relative to the org directory
            if (strpos($file_path, '../') === 0) {
                $full_path = __DIR__ . '/' . $file_path;
            } else {
                $full_path = $file_path;
            }
            
            if (file_exists($full_path)) {
                @unlink($full_path);
            }
        }
        
        // Delete the database record
        $delete_query = "DELETE FROM employee_documents WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        
        if (!$delete_stmt) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
            exit();
        }
        
        $delete_stmt->bind_param("i", $document_id);
        
        if ($delete_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Document deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete document: ' . $delete_stmt->error]);
        }
        
        $delete_stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }

    $conn->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
