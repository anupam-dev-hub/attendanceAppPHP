<?php
// setup_db.php
require 'config.php';

$sql = "
-- Disable foreign key checks to allow dropping tables in any order
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS student_documents;
DROP TABLE IF EXISTS fee_transactions;
DROP TABLE IF EXISTS fee_payments;
DROP TABLE IF EXISTS fees;
DROP TABLE IF EXISTS attendance;
DROP TABLE IF EXISTS employee_documents;
DROP TABLE IF EXISTS employee_payments;
DROP TABLE IF EXISTS emp_documents;
DROP TABLE IF EXISTS employees;
DROP TABLE IF EXISTS org_documents;
DROP TABLE IF EXISTS org_fees;
DROP TABLE IF EXISTS subscriptions;
DROP TABLE IF EXISTS advance_payment_adjustments;
DROP TABLE IF EXISTS advance_payments;
DROP TABLE IF EXISTS student_payments;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS organizations;
DROP TABLE IF EXISTS admins;

-- Enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Admins Table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Organizations Table
CREATE TABLE IF NOT EXISTS organizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    principal_name VARCHAR(100),
    owner_name VARCHAR(100),
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    alt_phone VARCHAR(20),
    logo VARCHAR(255),
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Organization Documents
CREATE TABLE IF NOT EXISTS org_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    org_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE
);

-- Subscriptions
CREATE TABLE IF NOT EXISTS subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    org_id INT NOT NULL,
    plan_months INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_proof VARCHAR(255),
    status ENUM('pending', 'active', 'expired') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    from_date DATETIME,
    to_date DATETIME,
    FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE
);

-- Organization Fees
CREATE TABLE IF NOT EXISTS org_fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    org_id INT NOT NULL,
    fee_name VARCHAR(100) NOT NULL,
    is_default BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_org_fee (org_id, fee_name),
    INDEX idx_org_id (org_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Students
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    org_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    class VARCHAR(50) DEFAULT NULL,
    stream VARCHAR(50) DEFAULT NULL,
    batch VARCHAR(50) DEFAULT '2025-2026',
    roll_number VARCHAR(50) DEFAULT NULL,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    photo VARCHAR(255),
    sex ENUM('Male', 'Female', 'Other') DEFAULT NULL,
    sex_other VARCHAR(50) DEFAULT NULL,
    date_of_birth DATE DEFAULT NULL,
    place_of_birth VARCHAR(100) DEFAULT NULL,
    nationality VARCHAR(50) DEFAULT 'Indian',
    mother_tongue VARCHAR(50) DEFAULT NULL,
    religion ENUM('Hindu', 'Muslim', 'Christian', 'Other') DEFAULT NULL,
    religion_other VARCHAR(50) DEFAULT NULL,
    community ENUM('ST', 'SC', 'SC(A)', 'BC', 'General', 'Other') DEFAULT NULL,
    community_other VARCHAR(50) DEFAULT NULL,
    native_district VARCHAR(100) DEFAULT NULL,
    pin_code VARCHAR(10) DEFAULT NULL,
    parent_guardian_name VARCHAR(255) DEFAULT NULL,
    parent_contact VARCHAR(20) DEFAULT NULL,
    exam_name VARCHAR(255) DEFAULT NULL,
    exam_total_marks INT DEFAULT NULL,
    exam_marks_obtained DECIMAL(10,2) DEFAULT NULL,
    exam_percentage DECIMAL(5,2) DEFAULT NULL,
    exam_grade VARCHAR(10) DEFAULT NULL,
    admission_amount DECIMAL(10, 2) DEFAULT 0.00,
    advance_payment DECIMAL(10, 2) DEFAULT 0.00,
    fees_json JSON DEFAULT NULL COMMENT 'Stores fees as JSON array',
    is_active BOOLEAN DEFAULT 1,
    remark TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE
);

-- Student Documents
CREATE TABLE IF NOT EXISTS student_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Employees Table
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    org_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    designation VARCHAR(100) DEFAULT NULL,
    department VARCHAR(100) DEFAULT NULL,
    salary DECIMAL(10, 2) DEFAULT 0.00,
    photo VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE
);

-- Employee Documents Table
CREATE TABLE IF NOT EXISTS employee_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    document_type ENUM('qualification', 'supporting') NOT NULL DEFAULT 'supporting',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Employee Payments Table
CREATE TABLE IF NOT EXISTS employee_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    transaction_type ENUM('salary', 'bonus', 'deduction', 'advance') NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT,
    payment_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Attendance
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    date DATE NOT NULL,
    in_time TIME,
    out_time TIME,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Student Documents
CREATE TABLE IF NOT EXISTS student_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Student Payments
CREATE TABLE IF NOT EXISTS student_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    transaction_type ENUM('credit', 'debit') NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Advance Payments Table
CREATE TABLE IF NOT EXISTS advance_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_date DATE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Advance Payment Adjustments Table
CREATE TABLE IF NOT EXISTS advance_payment_adjustments (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT
);
";

if ($conn->multi_query($sql)) {
    echo "Tables created successfully.\n";
    // Consume all results to clear the buffer
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
} else {
    echo "Error creating tables: " . $conn->error . "\n";
}

// Schema Updates for existing tables
$schema_updates = [
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS class VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS stream VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS batch VARCHAR(50) DEFAULT '2025-2026'",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS roll_number VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS photo VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS admission_amount DECIMAL(10, 2) DEFAULT 0.00",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS advance_payment DECIMAL(10, 2) DEFAULT 0.00",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT 1",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS remark TEXT",
    "ALTER TABLE students ADD COLUMN IF NOT EXISTS fee DECIMAL(10, 2) DEFAULT 0.00"
];

foreach ($schema_updates as $update_sql) {
    // Suppress errors for existing columns if IF NOT EXISTS is not supported by the DB version
    try {
        $conn->query($update_sql);
    } catch (Exception $e) {
        // Ignore error if column exists
    }
}

// Fix student_documents table if it exists with wrong schema
$check_table = $conn->query("SHOW TABLES LIKE 'student_documents'");
if ($check_table->num_rows > 0) {
    // Check if the table has the old schema (file_path instead of filepath)
    $check_columns = $conn->query("SHOW COLUMNS FROM student_documents LIKE 'file_path'");
    if ($check_columns->num_rows > 0) {
        // Old schema detected, migrate it
        echo "Migrating student_documents table to new schema...\n";
        
        // Rename columns
        $conn->query("ALTER TABLE student_documents CHANGE COLUMN file_path filepath VARCHAR(255) NOT NULL");
        
        // Add filename column if it doesn't exist
        $check_filename = $conn->query("SHOW COLUMNS FROM student_documents LIKE 'filename'");
        if ($check_filename->num_rows == 0) {
            $conn->query("ALTER TABLE student_documents ADD COLUMN filename VARCHAR(255) NOT NULL AFTER student_id");
            // Try to extract filename from filepath for existing records
            $conn->query("UPDATE student_documents SET filename = SUBSTRING_INDEX(filepath, '/', -1) WHERE filename = ''");
        }
        
        echo "student_documents table migration completed.\n";
    }
}

// Insert default admin if not exists
$adminUser = 'admin';
$adminPass = password_hash('admin123', PASSWORD_DEFAULT);
$checkAdmin = "SELECT * FROM admins WHERE username = '$adminUser'";
$result = $conn->query($checkAdmin);

if ($result->num_rows == 0) {
    $insertAdmin = "INSERT INTO admins (username, password) VALUES ('$adminUser', '$adminPass')";
    if ($conn->query($insertAdmin) === TRUE) {
        echo "Default admin created (User: admin, Pass: admin123).\n";
    } else {
        echo "Error creating admin: " . $conn->error . "\n";
    }
} else {
    echo "Admin already exists.\n";
}

// Insert seed organization
$checkOrg = "SELECT * FROM organizations WHERE email = 'demo@school.com'";
$orgResult = $conn->query($checkOrg);

if ($orgResult->num_rows == 0) {
    $orgPass = password_hash('demo123', PASSWORD_DEFAULT);
    $insertOrg = "INSERT INTO organizations (name, address, principal_name, owner_name, email, phone, alt_phone, password) 
                  VALUES ('Demo High School', '123 Education Street, Knowledge City, 500001', 'Dr. Rajesh Kumar', 'Mr. Suresh Patel', 'demo@school.com', '9876543210', '9876543211', '$orgPass')";
    
    if ($conn->query($insertOrg) === TRUE) {
        $org_id = $conn->insert_id;
        echo "Seed organization created (Email: demo@school.com, Pass: demo123).\n";
        
        // Insert 5 seed students
        $students = [
            ['Aarav Sharma', '10A', '2025-2026', '101', '123 Main Street, City', '9123456781', 'aarav.sharma@email.com', 5000.00, 1, 'Excellent student, good in mathematics'],
            ['Diya Patel', '10A', '2025-2026', '102', '456 Park Avenue, City', '9123456782', 'diya.patel@email.com', 5000.00, 1, 'Active in sports and cultural activities'],
            ['Arjun Singh', '10B', '2025-2026', '103', '789 Lake Road, City', '9123456783', 'arjun.singh@email.com', 5000.00, 1, NULL],
            ['Ananya Reddy', '10B', '2025-2026', '104', '321 Hill View, City', '9123456784', 'ananya.reddy@email.com', 5000.00, 0, 'On leave for medical reasons'],
            ['Rohan Verma', '10C', '2025-2026', '105', '654 Garden Street, City', '9123456785', 'rohan.verma@email.com', 5000.00, 1, 'Good leadership qualities']
        ];
        
        $studentCount = 0;
        foreach ($students as $student) {
            $stmt = $conn->prepare("INSERT INTO students (org_id, name, class, batch, roll_number, address, phone, email, admission_amount, is_active, remark) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssssdis", $org_id, $student[0], $student[1], $student[2], $student[3], $student[4], $student[5], $student[6], $student[7], $student[8], $student[9]);
            
            if ($stmt->execute()) {
                $studentCount++;
            } else {
                echo "Error creating student {$student[0]}: " . $stmt->error . "\n";
            }
        }
        
        echo "Seed data: Created $studentCount students.\n";
    } else {
        echo "Error creating organization: " . $conn->error . "\n";
    }
} else {
    echo "Seed organization already exists.\n";
}

$conn->close();
?>
