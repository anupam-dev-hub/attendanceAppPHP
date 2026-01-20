<?php
/**
 * setup_db.php
 * Database initialization script for Attendance & Fee Management System
 * Creates all required tables with proper structure and relationships
 * Run this script once during initial setup
 */

require 'config.php';

// Track success/failure
$results = [
    'created' => [],
    'errors' => [],
    'skipped' => []
];

function create_table($conn, $sql, $table_name) {
    global $results;
    
    if ($conn->query($sql)) {
        $results['created'][] = $table_name;
        return true;
    } else {
        if (strpos($conn->error, 'already exists') !== false) {
            $results['skipped'][] = $table_name . ' (already exists)';
        } else {
            $results['errors'][] = "$table_name: " . $conn->error;
        }
        return false;
    }
}

// ============================================================================
// CORE TABLES
// ============================================================================

// Admins table
create_table($conn, "
    CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        contact_email VARCHAR(100),
        contact_phone VARCHAR(20),
        contact_address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'admins');

// Organizations table
create_table($conn, "
    CREATE TABLE IF NOT EXISTS organizations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        email VARCHAR(150) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        city VARCHAR(100),
        state VARCHAR(100),
        zip_code VARCHAR(20),
        country VARCHAR(100),
        website VARCHAR(255),
        logo VARCHAR(500),
        registration_number VARCHAR(100),
        gst_number VARCHAR(100),
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_is_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'organizations');

// Classes/Batches table
create_table($conn, "
    CREATE TABLE IF NOT EXISTS classes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        org_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        capacity INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
        INDEX idx_org_id (org_id),
        UNIQUE KEY unique_org_class (org_id, name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'classes');

// Students table
create_table($conn, "
    CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        org_id INT NOT NULL,
        name VARCHAR(150) NOT NULL,
        email VARCHAR(150),
        phone VARCHAR(20),
        photo VARCHAR(500),
        roll_number VARCHAR(50),
        class VARCHAR(100),
        batch VARCHAR(100),
        stream VARCHAR(100),
        date_of_birth DATE,
        gender ENUM('M', 'F', 'Other'),
        father_name VARCHAR(150),
        mother_name VARCHAR(150),
        native_district VARCHAR(100),
        religion VARCHAR(50),
        community VARCHAR(50),
        nationality VARCHAR(50),
        address TEXT,
        parent_phone VARCHAR(20),
        emergency_contact VARCHAR(20),
        is_active TINYINT(1) DEFAULT 1,
        fees_json JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
        INDEX idx_org_id (org_id),
        INDEX idx_roll_number (org_id, roll_number),
        INDEX idx_is_active (org_id, is_active),
        INDEX idx_class (org_id, class)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'students');

// Employees table
create_table($conn, "
    CREATE TABLE IF NOT EXISTS employees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        org_id INT NOT NULL,
        name VARCHAR(150) NOT NULL,
        email VARCHAR(150),
        phone VARCHAR(20),
        photo VARCHAR(500),
        employee_id VARCHAR(50),
        designation VARCHAR(100),
        department VARCHAR(100),
        date_of_joining DATE,
        date_of_birth DATE,
        gender ENUM('M', 'F', 'Other'),
        address TEXT,
        emergency_contact VARCHAR(20),
        salary_type ENUM('Fixed', 'Hourly', 'Contract'),
        base_salary DECIMAL(12,2),
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
        INDEX idx_org_id (org_id),
        INDEX idx_employee_id (org_id, employee_id),
        INDEX idx_is_active (org_id, is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'employees');

// ============================================================================
// ATTENDANCE TABLES
// ============================================================================

// Student Attendance
create_table($conn, "
    CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        org_id INT NOT NULL,
        date DATE NOT NULL,
        in_time TIME,
        out_time TIME,
        status ENUM('Present', 'Absent', 'Late', 'Leave') DEFAULT 'Absent',
        notes VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
        INDEX idx_student_date (student_id, date),
        INDEX idx_org_date (org_id, date),
        UNIQUE KEY unique_student_date (student_id, date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'attendance');

// Employee Attendance
create_table($conn, "
    CREATE TABLE IF NOT EXISTS employee_attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        org_id INT NOT NULL,
        date DATE NOT NULL,
        in_time TIME,
        out_time TIME,
        status ENUM('Present', 'Absent', 'Late', 'Leave') DEFAULT 'Absent',
        notes VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
        FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
        INDEX idx_employee_date (employee_id, date),
        INDEX idx_org_date (org_id, date),
        UNIQUE KEY unique_employee_date (employee_id, date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'employee_attendance');

// ============================================================================
// FEE & PAYMENT TABLES
// ============================================================================

// Organization Fee Configuration
create_table($conn, "
    CREATE TABLE IF NOT EXISTS org_fees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        org_id INT NOT NULL,
        fee_name VARCHAR(150) NOT NULL,
        fee_type ENUM('One-Time', 'Monthly', 'Annual', 'Custom') DEFAULT 'Monthly',
        amount DECIMAL(12,2),
        is_mandatory TINYINT(1) DEFAULT 1,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
        INDEX idx_org_id (org_id),
        UNIQUE KEY unique_org_fee (org_id, fee_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'org_fees');

// Fees (per student per fee type)
create_table($conn, "
    CREATE TABLE IF NOT EXISTS fees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        org_id INT NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        due_date DATE,
        status ENUM('Pending', 'Partial', 'Paid', 'Overdue') DEFAULT 'Pending',
        fee_name VARCHAR(150),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
        INDEX idx_student_id (student_id),
        INDEX idx_status (status),
        INDEX idx_due_date (due_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'fees');

// Fee Payments
create_table($conn, "
    CREATE TABLE IF NOT EXISTS fee_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fee_id INT NOT NULL,
        student_id INT NOT NULL,
        org_id INT NOT NULL,
        amount_paid DECIMAL(12,2) NOT NULL,
        payment_date DATE NOT NULL,
        payment_method ENUM('Cash', 'Check', 'Bank Transfer', 'Online', 'Card') DEFAULT 'Cash',
        transaction_id VARCHAR(100),
        notes VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (fee_id) REFERENCES fees(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
        INDEX idx_fee_id (fee_id),
        INDEX idx_student_id (student_id),
        INDEX idx_payment_date (payment_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'fee_payments');

// Student Payment Transactions
create_table($conn, "
    CREATE TABLE IF NOT EXISTS student_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        org_id INT NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        transaction_type ENUM('credit', 'debit') NOT NULL,
        category VARCHAR(200),
        notes VARCHAR(255),
        reference_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
        INDEX idx_student_id (student_id),
        INDEX idx_org_id (org_id),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'student_payments');

// Advance Payments
create_table($conn, "
    CREATE TABLE IF NOT EXISTS advance_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        org_id INT NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        amount_used DECIMAL(12,2) DEFAULT 0,
        amount_remaining DECIMAL(12,2),
        payment_date DATE NOT NULL,
        payment_method ENUM('Cash', 'Check', 'Bank Transfer', 'Online', 'Card') DEFAULT 'Cash',
        notes VARCHAR(255),
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
        INDEX idx_student_id (student_id),
        INDEX idx_org_id (org_id),
        INDEX idx_is_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'advance_payments');

// ============================================================================
// EMPLOYEE PAYMENT TABLES
// ============================================================================

// Employee Payments
create_table($conn, "
    CREATE TABLE IF NOT EXISTS employee_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        org_id INT NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        payment_month DATE,
        payment_status ENUM('Pending', 'Partial', 'Paid', 'Overdue') DEFAULT 'Pending',
        payment_date DATE,
        payment_method ENUM('Cash', 'Check', 'Bank Transfer', 'Online', 'Card') DEFAULT 'Bank Transfer',
        transaction_id VARCHAR(100),
        notes VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
        FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
        INDEX idx_employee_id (employee_id),
        INDEX idx_payment_month (payment_month),
        INDEX idx_payment_status (payment_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'employee_payments');

// ============================================================================
// SUBSCRIPTION TABLES
// ============================================================================

// Subscription Plans
create_table($conn, "
    CREATE TABLE IF NOT EXISTS subscription_plans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        description TEXT,
        duration_months INT NOT NULL,
        price DECIMAL(12,2) NOT NULL,
        max_students INT,
        max_employees INT,
        features JSON,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_is_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'subscription_plans');

// Subscriptions
create_table($conn, "
    CREATE TABLE IF NOT EXISTS subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        org_id INT NOT NULL,
        plan_id INT,
        plan_months INT,
        amount DECIMAL(12,2) NOT NULL,
        payment_proof VARCHAR(500),
        status ENUM('Pending', 'Active', 'Expired', 'Cancelled') DEFAULT 'Pending',
        from_date DATE,
        to_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
        FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE SET NULL,
        INDEX idx_org_id (org_id),
        INDEX idx_status (status),
        INDEX idx_to_date (to_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'subscriptions');

// ============================================================================
// AUTHENTICATION & SECURITY TABLES
// ============================================================================

// API Tokens
create_table($conn, "
    CREATE TABLE IF NOT EXISTS api_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        org_id INT NOT NULL,
        token_hash CHAR(64) NOT NULL UNIQUE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        last_used_at DATETIME NULL,
        revoked TINYINT(1) NOT NULL DEFAULT 0,
        FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
        INDEX idx_org_id (org_id),
        INDEX idx_expires_revoked (expires_at, revoked)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'api_tokens');

// QR Tokens
create_table($conn, "
    CREATE TABLE IF NOT EXISTS org_qr_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        org_id INT NOT NULL,
        token_hash CHAR(64) NOT NULL UNIQUE,
        qr_data VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NULL,
        revoked TINYINT(1) NOT NULL DEFAULT 0,
        last_used_at DATETIME NULL,
        FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
        INDEX idx_org_id (org_id),
        INDEX idx_token_hash (token_hash),
        INDEX idx_revoked (revoked)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'org_qr_tokens');

// Password Reset Tokens
create_table($conn, "
    CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        org_id INT NOT NULL,
        token_hash CHAR(64) NOT NULL UNIQUE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) NOT NULL DEFAULT 0,
        FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
        INDEX idx_org_id (org_id),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'password_resets');

// ============================================================================
// DOCUMENTS & FILES TABLES
// ============================================================================

// Organization Documents
create_table($conn, "
    CREATE TABLE IF NOT EXISTS org_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        org_id INT NOT NULL,
        document_type VARCHAR(100),
        file_path VARCHAR(500) NOT NULL,
        file_name VARCHAR(255),
        file_size BIGINT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
        INDEX idx_org_id (org_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'org_documents');

// Student Documents
create_table($conn, "
    CREATE TABLE IF NOT EXISTS student_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        org_id INT NOT NULL,
        document_type VARCHAR(100),
        file_path VARCHAR(500) NOT NULL,
        file_name VARCHAR(255),
        file_size BIGINT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
        INDEX idx_student_id (student_id),
        INDEX idx_org_id (org_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'student_documents');

// Employee Documents
create_table($conn, "
    CREATE TABLE IF NOT EXISTS employee_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        org_id INT NOT NULL,
        document_type VARCHAR(100),
        file_path VARCHAR(500) NOT NULL,
        file_name VARCHAR(255),
        file_size BIGINT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
        FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
        INDEX idx_employee_id (employee_id),
        INDEX idx_org_id (org_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'employee_documents');

// ============================================================================
// MISCELLANEOUS TABLES
// ============================================================================

// Expenses
create_table($conn, "
    CREATE TABLE IF NOT EXISTS expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        org_id INT NOT NULL,
        title VARCHAR(150) NOT NULL,
        category VARCHAR(100) NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        expense_date DATE NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
        INDEX idx_org_date (org_id, expense_date),
        INDEX idx_org_category (org_id, category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'expenses');

// Settings
create_table($conn, "
    CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value LONGTEXT,
        description TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_key (setting_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'settings');

// App Versions (for auto-update)
create_table($conn, "
    CREATE TABLE IF NOT EXISTS app_versions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        version VARCHAR(20) NOT NULL,
        build_number INT NOT NULL,
        release_notes TEXT,
        file_path VARCHAR(500),
        file_size BIGINT,
        checksum VARCHAR(64),
        is_mandatory TINYINT(1) DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_version (version),
        INDEX idx_is_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
", 'app_versions');

// ============================================================================
// OUTPUT RESULTS
// ============================================================================

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup Results</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Database Setup Complete</h1>
            <p class="text-gray-600 mb-8">Attendance & Fee Management System</p>
            
            <?php if (!empty($results['errors'])): ?>
            <div class="mb-8 p-6 bg-red-50 border-l-4 border-red-500 rounded">
                <h2 class="text-2xl font-bold text-red-700 mb-4">Errors (<?php echo count($results['errors']); ?>)</h2>
                <ul class="space-y-2">
                    <?php foreach ($results['errors'] as $error): ?>
                        <li class="text-red-600">⚠️ <?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="p-6 bg-green-50 border-l-4 border-green-500 rounded">
                    <h2 class="text-xl font-bold text-green-700 mb-2">Tables Created</h2>
                    <p class="text-3xl font-bold text-green-600"><?php echo count($results['created']); ?></p>
                    <?php if (!empty($results['created'])): ?>
                        <ul class="mt-4 space-y-1 text-sm text-green-700">
                            <?php foreach ($results['created'] as $table): ?>
                                <li>✓ <?php echo htmlspecialchars($table); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                
                <div class="p-6 bg-blue-50 border-l-4 border-blue-500 rounded">
                    <h2 class="text-xl font-bold text-blue-700 mb-2">Tables Skipped</h2>
                    <p class="text-3xl font-bold text-blue-600"><?php echo count($results['skipped']); ?></p>
                    <?php if (!empty($results['skipped'])): ?>
                        <ul class="mt-4 space-y-1 text-sm text-blue-700">
                            <?php foreach ($results['skipped'] as $table): ?>
                                <li>ℹ️ <?php echo htmlspecialchars($table); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="p-6 bg-gray-50 border-l-4 border-gray-500 rounded mb-8">
                <h3 class="text-lg font-bold text-gray-700 mb-3">Summary</h3>
                <ul class="space-y-2 text-gray-700">
                    <li>📊 <strong><?php echo count($results['created']) + count($results['skipped']); ?></strong> Total tables ready</li>
                    <li>🔧 Core authentication & authorization tables</li>
                    <li>👥 Student & Employee management tables</li>
                    <li>📅 Attendance tracking tables</li>
                    <li>💰 Fee & Payment management tables</li>
                    <li>🔐 Security & API token tables</li>
                    <li>📄 Document management tables</li>
                </ul>
            </div>
            
            <div class="space-y-4">
                <div class="p-4 bg-yellow-50 border border-yellow-200 rounded">
                    <p class="text-yellow-800 text-sm">
                        <strong>ℹ️ Next Steps:</strong>
                    </p>
                    <ol class="list-decimal list-inside text-yellow-800 text-sm mt-2 space-y-1">
                        <li>Create admin account via database INSERT or admin registration</li>
                        <li>Create organization accounts</li>
                        <li>Configure organization settings</li>
                        <li>Add subscription plans if using subscription model</li>
                    </ol>
                </div>
                
                <a href="index.php" class="inline-block bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                    ← Back to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>
