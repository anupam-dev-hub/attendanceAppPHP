<?php
// org/employees.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Fetch employees
$query = "SELECT * FROM employees WHERE org_id = ? ORDER BY name ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $org_id);
$stmt->execute();
$result = $stmt->get_result();
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
    
    <style>
        body,
        .font-sans {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;
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
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                    <select id="departmentFilter" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">All Departments</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Designation</label>
                    <select id="designationFilter" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">All Designations</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="statusFilter" class="w-full border border-gray-300 rounded-lg px-3 py-2">
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
                                <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
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
                                    <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['phone']); ?></td>
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

    <!-- JavaScript -->
    <script src="js/employees.js?v=<?php echo time(); ?>"></script>
</body>

</html>
<?php $stmt->close(); $conn->close(); ?>
