<?php
/**
 * Migration: Create Fee Management System
 * Creates org_fees table to manage fee types per organization
 * Updates students table to store fees as JSON
 */

require 'config.php';

try {
    // Create org_fees table
    $createFeesTableSQL = "
    CREATE TABLE IF NOT EXISTS org_fees (
        id INT PRIMARY KEY AUTO_INCREMENT,
        org_id INT NOT NULL,
        fee_name VARCHAR(100) NOT NULL,
        fee_type ENUM('Monthly Fee', 'Library Fee', 'Tuition Fee', 'Other') DEFAULT 'Other',
        is_default BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
        UNIQUE KEY unique_org_fee (org_id, fee_name),
        INDEX idx_org_id (org_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $conn->query($createFeesTableSQL);
    echo "✓ Created org_fees table\n";
    
    // Modify students table - change fee to support JSON format
    // First check if we need to update the column type
    $checkColumnSQL = "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
                       WHERE TABLE_NAME = 'students' AND COLUMN_NAME = 'fee' AND TABLE_SCHEMA = DATABASE()";
    $result = $conn->query($checkColumnSQL);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Keep it as DECIMAL for backward compatibility, but we'll store JSON in a new column
        // For now, we'll use a TEXT column to store fee data as JSON
        
        // Add fees_json column to students table
        $addFeesJsonSQL = "ALTER TABLE students ADD COLUMN IF NOT EXISTS fees_json JSON DEFAULT NULL COMMENT 'Stores fees as JSON array'";
        $conn->query($addFeesJsonSQL);
        echo "✓ Added fees_json column to students table\n";
    }
    
    echo "\n✓ Fee system migration completed successfully!\n";
    echo "Note: Monthly Fee is marked as default and should be created when org is added\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
