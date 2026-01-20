<?php
// org/employees.php
session_start();
require_once '../config.php';
require_once '../functions.php';

// Check if user is logged in and is an organization
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'org') {
    header("Location: ../index.php");
    exit();
}

$org_id = $_SESSION['user_id'];
$_SESSION['org_name'] = $_SESSION['org_name'] ?? 'Organization';

// Fetch organization details
$org_query = "SELECT * FROM organizations WHERE id = ?";
$org_stmt = $conn->prepare($org_query);
$org_stmt->bind_param("i", $org_id);
$org_stmt->execute();
$org_result = $org_stmt->get_result();
$org = $org_result->fetch_assoc();

if ($org) {
    $_SESSION['org_name'] = $org['name'];
}

$org_stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add' || $action === 'edit') {
        $employee_id = isset($_POST['employee_id']) && $_POST['employee_id'] !== '' ? intval($_POST['employee_id']) : null;
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $salary = floatval($_POST['salary'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Handle photo upload
        $photo_path = '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK && !empty($_FILES['photo']['name'])) {
            $upload_dir = '../uploads/employees/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed_photo_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_ext, $allowed_photo_ext)) {
                $photo_name = 'employee_' . time() . '_' . uniqid() . '.' . $file_ext;
                $photo_path = $upload_dir . $photo_name;
                if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
                    $photo_path = ''; // Reset if upload failed
                }
            }
        }
        
        if ($employee_id) {
            // Update existing employee
            if ($photo_path !== '') {
                $stmt = $conn->prepare("UPDATE employees SET name=?, phone=?, email=?, address=?, designation=?, department=?, salary=?, photo=?, is_active=? WHERE id=? AND org_id=?");
                $stmt->bind_param("ssssssdssii", $name, $phone, $email, $address, $designation, $department, $salary, $photo_path, $is_active, $employee_id, $org_id);
            } else {
                $stmt = $conn->prepare("UPDATE employees SET name=?, phone=?, email=?, address=?, designation=?, department=?, salary=?, is_active=? WHERE id=? AND org_id=?");
                $stmt->bind_param("ssssssdiii", $name, $phone, $email, $address, $designation, $department, $salary, $is_active, $employee_id, $org_id);
            }
        } else {
            // Add new employee
            if ($photo_path !== '') {
                $stmt = $conn->prepare("INSERT INTO employees (org_id, name, phone, email, address, designation, department, salary, photo, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssssdsi", $org_id, $name, $phone, $email, $address, $designation, $department, $salary, $photo_path, $is_active);
            } else {
                $stmt = $conn->prepare("INSERT INTO employees (org_id, name, phone, email, address, designation, department, salary, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssssdi", $org_id, $name, $phone, $email, $address, $designation, $department, $salary, $is_active);
            }
        }
        
        if ($stmt->execute()) {
            $new_employee_id = $employee_id ?? $conn->insert_id;
            
            // Handle documents upload
            if (isset($_FILES['documents']) && !empty($_FILES['documents']['name'][0])) {
                $upload_dir = '../uploads/employees/documents/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_count = count($_FILES['documents']['name']);
                for ($i = 0; $i < $file_count; $i++) {
                    if ($_FILES['documents']['error'][$i] === UPLOAD_ERR_OK) {
                        $file_ext = strtolower(pathinfo($_FILES['documents']['name'][$i], PATHINFO_EXTENSION));
                        $file_name = 'document_' . $new_employee_id . '_' . time() . '_' . $i . '.' . $file_ext;
                        $file_path = $upload_dir . $file_name;
                        
                        if (move_uploaded_file($_FILES['documents']['tmp_name'][$i], $file_path)) {
                            $doc_stmt = $conn->prepare("INSERT INTO employee_documents (employee_id, file_name, file_path, document_type) VALUES (?, ?, ?, 'supporting')");
                            $original_name = $_FILES['documents']['name'][$i];
                            $doc_stmt->bind_param("iss", $new_employee_id, $original_name, $file_path);
                            $doc_stmt->execute();
                            $doc_stmt->close();
                        }
                    }
                }
            }
            
            $_SESSION['success_message'] = $employee_id ? 'Employee updated successfully!' : 'Employee added successfully!';
        } else {
            $_SESSION['error_message'] = 'Error saving employee: ' . $conn->error;
        }
        
        $stmt->close();
        header("Location: employees.php");
        exit();
    }
}

// Fetch employee statistics
$statsStmt = $conn->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
    FROM employees WHERE org_id = ?");
$statsStmt->bind_param("i", $org_id);
$statsStmt->execute();
$statsResult = $statsStmt->get_result();
$stats = $statsResult->fetch_assoc();
$total_employees = $stats['total'];
$active_employees = $stats['active'];
$inactive_employees = $stats['inactive'];
$statsStmt->close();

// Calculate total salary expense
$salaryStmt = $conn->prepare("SELECT SUM(salary) as total_salary FROM employees WHERE org_id = ? AND is_active = 1");
$salaryStmt->bind_param("i", $org_id);
$salaryStmt->execute();
$salaryResult = $salaryStmt->get_result();
$salaryData = $salaryResult->fetch_assoc();
$total_monthly_salary = $salaryData['total_salary'] ?? 0;
$salaryStmt->close();

// Fetch employees with outstanding balance
$query = "SELECT e.*, 
    COALESCE(SUM(CASE WHEN ep.transaction_type = 'credit' THEN ep.amount ELSE 0 END), 0) - 
    COALESCE(SUM(CASE WHEN ep.transaction_type = 'debit' THEN ep.amount ELSE 0 END), 0) AS net_balance
    FROM employees e
    LEFT JOIN employee_payments ep ON e.id = ep.employee_id
    WHERE e.org_id = ?
    GROUP BY e.id
    ORDER BY e.name ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $org_id);
$stmt->execute();
$result = $stmt->get_result();

// Prepare attendance summary queries for the current month
$month_start = date('Y-m-01');
$month_end = date('Y-m-t');
$emp_total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM employee_attendance WHERE employee_id = ? AND date BETWEEN ? AND ?");
$emp_present_stmt = $conn->prepare("SELECT COUNT(*) as present FROM employee_attendance WHERE employee_id = ? AND date BETWEEN ? AND ? AND in_time IS NOT NULL");
// Prepare today's status query
$today = date('Y-m-d');
$emp_today_stmt = $conn->prepare("SELECT in_time, out_time FROM employee_attendance WHERE employee_id = ? AND date = ? ORDER BY id DESC LIMIT 1");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees - <?php echo htmlspecialchars($org['name']); ?></title>
    
    <!-- Modern Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Payment Tab CSS (for modern form styling) -->
    <link rel="stylesheet" href="../assets/css/payment_tab.css">
    
    <!-- UX Improvements -->
    <link rel="stylesheet" href="../assets/css/ux-improvements.css">
    
    <style>
        body,
        .font-sans {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;
        }
        
        /* SweetAlert z-index fix for payment history */
        .swal-payment-history-container {
            z-index: 20000 !important;
        }
        
        /* DataTables Customization */
        .dataTables_wrapper .dataTables_length select {
            padding-right: 2rem;
            background-image: none;
            border-radius: 0.25rem;
            border-color: #d1d5db;
        }

        .dataTables_wrapper .dataTables_filter input {
            border-radius: 0.25rem;
            border-color: #d1d5db;
            padding: 0.5rem;
        }
        
        /* DataTables Responsive Customization */
        table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control:before,
        table.dataTable.dtr-inline.collapsed > tbody > tr > th.dtr-control:before {
            background-color: #0d9488;
            border: 2px solid #0d9488;
            box-shadow: 0 0 3px #0d9488;
        }
        
        table.dataTable.dtr-inline.collapsed > tbody > tr.parent > td.dtr-control:before,
        table.dataTable.dtr-inline.collapsed > tbody > tr.parent > th.dtr-control:before {
            background-color: #ef4444;
            border: 2px solid #ef4444;
        }
        
        table.dataTable > tbody > tr.child ul.dtr-details {
            display: block;
            padding-left: 0;
        }
        
        table.dataTable > tbody > tr.child ul.dtr-details > li {
            border-bottom: 1px solid #f3f4f6;
            padding: 0.75rem 0;
        }
        
        table.dataTable > tbody > tr.child span.dtr-title {
            font-weight: 600;
            color: #374151;
            min-width: 100px;
            display: inline-block;
        }
        
        table.dataTable > tbody > tr.child span.dtr-data {
            color: #6b7280;
        }
    </style>
    
    <!-- jQuery and DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    
    <!-- Other Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        // Configure PDF.js worker
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    </script>
</head>

<body class="bg-gray-100 font-sans">
    <!-- Navigation -->
    <?php include 'navbar.php'; ?>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Employees Management</h1>
            <button onclick="openAddModal()" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition">
                + Add Employee
            </button>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-4 rounded-xl shadow-lg mb-6 flex items-center">
                <i class="fas fa-check-circle text-2xl mr-3"></i>
                <span class="font-semibold"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-gradient-to-r from-red-500 to-red-600 text-white px-6 py-4 rounded-xl shadow-lg mb-6 flex items-center">
                <i class="fas fa-exclamation-circle text-2xl mr-3"></i>
                <span class="font-semibold"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Employees -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden transform transition hover:scale-105">
                <div class="absolute top-0 right-0 w-20 h-20 bg-white opacity-10 rounded-full -mr-10 -mt-10"></div>
                <div class="relative z-10">
                    <p class="text-purple-100 text-sm font-medium uppercase tracking-wide mb-2">Total Employees</p>
                    <h3 class="text-4xl font-bold"><?php echo number_format($total_employees); ?></h3>
                    <div class="mt-4">
                        <i class="fas fa-user-tie text-3xl opacity-50"></i>
                    </div>
                </div>
            </div>

            <!-- Active Employees -->
            <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden transform transition hover:scale-105">
                <div class="absolute top-0 right-0 w-20 h-20 bg-white opacity-10 rounded-full -mr-10 -mt-10"></div>
                <div class="relative z-10">
                    <p class="text-green-100 text-sm font-medium uppercase tracking-wide mb-2">Active Employees</p>
                    <h3 class="text-4xl font-bold"><?php echo number_format($active_employees); ?></h3>
                    <div class="mt-4">
                        <i class="fas fa-user-check text-3xl opacity-50"></i>
                    </div>
                </div>
            </div>

            <!-- Inactive Employees -->
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden transform transition hover:scale-105">
                <div class="absolute top-0 right-0 w-20 h-20 bg-white opacity-10 rounded-full -mr-10 -mt-10"></div>
                <div class="relative z-10">
                    <p class="text-orange-100 text-sm font-medium uppercase tracking-wide mb-2">Inactive Employees</p>
                    <h3 class="text-4xl font-bold"><?php echo number_format($inactive_employees); ?></h3>
                    <div class="mt-4">
                        <i class="fas fa-user-times text-3xl opacity-50"></i>
                    </div>
                </div>
            </div>

            <!-- Monthly Salary -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden transform transition hover:scale-105">
                <div class="absolute top-0 right-0 w-20 h-20 bg-white opacity-10 rounded-full -mr-10 -mt-10"></div>
                <div class="relative z-10">
                    <p class="text-blue-100 text-sm font-medium uppercase tracking-wide mb-2">Monthly Salary</p>
                    <h3 class="text-4xl font-bold">₹<?php echo number_format($total_monthly_salary); ?></h3>
                    <p class="text-blue-100 text-xs mt-1">Active employees only</p>
                    <div class="mt-2">
                        <i class="fas fa-money-bill-wave text-3xl opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-filter text-purple-600 mr-2"></i> Filter Employees
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-building text-purple-500 mr-2 text-xs"></i> Department
                    </label>
                    <select id="departmentFilter" class="w-full border-2 border-gray-200 rounded-lg px-3 py-2.5 transition-all duration-200 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 hover:border-gray-300">
                        <option value="">All Departments</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-user-tag text-purple-500 mr-2 text-xs"></i> Designation
                    </label>
                    <select id="designationFilter" class="w-full border-2 border-gray-200 rounded-lg px-3 py-2.5 transition-all duration-200 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 hover:border-gray-300">
                        <option value="">All Designations</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-toggle-on text-purple-500 mr-2 text-xs"></i> Status
                    </label>
                    <select id="statusFilter" class="w-full border-2 border-gray-200 rounded-lg px-3 py-2.5 transition-all duration-200 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 hover:border-gray-300">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Employees Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <?php if ($result->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table id="employeesTable" class="divide-y divide-gray-200 display" style="width:100%">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photo</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Designation</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salary</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Outstanding</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Today</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attendance</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Percent</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">QR Code</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 transition" data-status="<?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>" style="<?php echo !$row['is_active'] ? 'background-color: #fef2f2 !important;' : ''; ?>">
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php if ($row['photo']): ?>
                                            <img src="<?php echo htmlspecialchars($row['photo']); ?>" alt="Photo" class="w-10 h-10 rounded-full object-cover cursor-pointer border-2 border-gray-300 hover:border-teal-500 transition" onclick="viewPhoto('<?php echo htmlspecialchars($row['photo']); ?>', '<?php echo htmlspecialchars($row['name']); ?>')">
                                        <?php else: ?>
                                            <span class="text-gray-400 text-xs">No photo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['designation'] ?: '-'); ?></td>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['department'] ?: '-'); ?></td>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500">₹<?php echo number_format($row['salary'], 2); ?></td>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-semibold <?php $balance = floatval($row['net_balance']); echo $balance > 0 ? 'text-red-600' : ($balance < 0 ? 'text-green-600' : 'text-gray-500'); ?>">₹<?php echo number_format(abs($balance), 2); ?></td>
                                    <?php
                                        // Today's status
                                        $emp_today_stmt->bind_param('is', $row['id'], $today);
                                        $emp_today_stmt->execute();
                                        $emp_today_res = $emp_today_stmt->get_result();
                                        $today_label = 'No Entry';
                                        $today_class = 'bg-gray-100 text-gray-700';
                                        $today_order = '0';
                                        if ($emp_today_res && $emp_today_res->num_rows > 0) {
                                            $today_rec = $emp_today_res->fetch_assoc();
                                            $in_time_today = $today_rec['in_time'];
                                            $out_time_today = $today_rec['out_time'];
                                            if (empty($in_time_today) && empty($out_time_today)) {
                                                $today_label = 'Absent';
                                                $today_class = 'bg-red-100 text-red-700';
                                                $today_order = '0';
                                            } else {
                                                $today_label = 'Present';
                                                $today_class = 'bg-green-100 text-green-700';
                                                $today_order = '1';
                                            }
                                        }
                                    ?>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm" data-order="<?php echo $today_order; ?>">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold <?php echo $today_class; ?>"><?php echo $today_label; ?></span>
                                    </td>
                                    <?php
                                        // Compute attendance summary for current month
                                        $emp_total_stmt->bind_param('iss', $row['id'], $month_start, $month_end);
                                        $emp_total_stmt->execute();
                                        $emp_total_res = $emp_total_stmt->get_result();
                                        $emp_total_row = $emp_total_res->fetch_assoc();
                                        $total_days = (int)($emp_total_row['total'] ?? 0);

                                        $emp_present_stmt->bind_param('iss', $row['id'], $month_start, $month_end);
                                        $emp_present_stmt->execute();
                                        $emp_present_res = $emp_present_stmt->get_result();
                                        $emp_present_row = $emp_present_res->fetch_assoc();
                                        $present_days = (int)($emp_present_row['present'] ?? 0);

                                        $percent = $total_days > 0 ? round(($present_days / $total_days) * 100) : 0;
                                    ?>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo $present_days; ?>/<?php echo $total_days; ?></td>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo $percent; ?>%</td>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['phone']); ?></td>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm">
                                        <button onclick='generateEmployeeQR("E-<?php echo $row['id']; ?>", "<?php echo htmlspecialchars($row['name']); ?>", "<?php echo htmlspecialchars($row['designation'] ?: 'NA'); ?>")' class="text-blue-600 hover:text-blue-900 font-bold text-xs sm:text-sm">QR</button>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="toggleEmployeeStatus(<?php echo $row['id']; ?>, <?php echo $row['is_active']; ?>, this)" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 <?php echo $row['is_active'] ? 'bg-teal-600' : 'bg-gray-300'; ?>" title="<?php echo $row['is_active'] ? 'Active - Click to deactivate' : 'Inactive - Click to activate'; ?>">
                                            <span class="sr-only">Toggle status</span>
                                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform <?php echo $row['is_active'] ? 'translate-x-6' : 'translate-x-1'; ?>"></span>
                                        </button>
                                    </td>
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex flex-wrap gap-2">
                                            <button onclick='viewEmployee(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)' class="text-indigo-600 hover:text-indigo-900 font-bold text-xs sm:text-sm">View</button>
                                            <button onclick='openEditModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)' class="text-teal-600 hover:text-teal-900 font-bold text-xs sm:text-sm">Edit</button>
                                            <button type="button" class="payment-btn text-green-600 hover:text-green-900 font-bold text-xs sm:text-sm" data-employee-id="<?php echo $row['id']; ?>" data-employee-name="<?php echo htmlspecialchars($row['name']); ?>">Payment</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-6 text-center text-gray-500">
                    No employees found. Click "Add Employee" to get started.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Employee Form Modal -->
    <?php include 'modals/employee_form_modal.php'; ?>
    
    <!-- Employee View Modal -->
    <?php include 'modals/employee_view_modal.php'; ?>
    
    <!-- Photo Modal -->
    <?php include 'modals/photo_modal.php'; ?>
    
    <!-- Document Modal -->
    <?php include 'modals/document_modal.php'; ?>
    <?php include 'modals/qr_modal.php'; ?>

    <!-- QR Code Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

    <!-- JavaScript -->
    <script src="js/employees.js?v=<?php echo time(); ?>"></script>
</body>

</html>
<?php $stmt->close(); $conn->close(); ?>
