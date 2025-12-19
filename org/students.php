<?php
// org/students.php
session_start();
require '../config.php';
require '../functions.php';

if (!isOrg()) {
    redirect('index.php');
}

$org_id = $_SESSION['user_id'];

if (!isSubscribed($org_id)) {
    redirect('dashboard.php');
}

// Include Logic Modules
require 'modules/students_logic.php';

// Handle Session Flash Messages
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Fetch Students with Net Balance
$result = $conn->query("
    SELECT s.*, 
           COALESCE(SUM(CASE 
               WHEN sp.transaction_type = 'debit' THEN sp.amount 
               WHEN sp.transaction_type = 'credit' THEN -sp.amount 
               ELSE 0 
           END), 0) AS net_balance
    FROM students s
    LEFT JOIN student_payments sp ON s.id = sp.student_id
    WHERE s.org_id = $org_id
    GROUP BY s.id
    ORDER BY s.class ASC, s.roll_number ASC
");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>
    <!-- Modern Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        body,
        .font-sans {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;
        }
        
        /* DataTables Customization for Tailwind */
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

        #qrcode {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
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
        
        /* Mobile optimizations */
        @media (max-width: 640px) {
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter {
                float: none;
                text-align: left;
                margin-bottom: 1rem;
            }
            
            .dataTables_wrapper .dataTables_info,
            .dataTables_wrapper .dataTables_paginate {
                float: none;
                text-align: center;
                margin-top: 1rem;
            }
            
            .dataTables_wrapper .dataTables_paginate .paginate_button {
                padding: 0.25rem 0.5rem;
                margin: 0 2px;
                font-size: 0.875rem;
            }
            
            div.dataTables_wrapper div.dataTables_filter input {
                width: 100%;
                margin-left: 0;
                margin-top: 0.5rem;
            }
        }
    </style>
    
    <!-- jQuery and DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    
    <!-- Other Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        // Configure PDF.js worker
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    </script>
    <style>
        /* DataTables Customization for Tailwind */
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

        #qrcode {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
    </style>
    <link rel="stylesheet" href="../assets/css/payment_tab.css?v=<?php echo time(); ?>">
</head>

<body class="bg-gray-50 font-sans antialiased">
    <?php include 'navbar.php'; ?>

    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-10">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 md:mb-8">
            <div>
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900">Manage Students</h2>
                <p class="mt-2 text-sm text-gray-600">Add, edit, and view student records.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="initialize_monthly_fees.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow transition whitespace-nowrap inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Monthly Fees
                </a>
                <button onclick="openAddModal()" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded shadow transition whitespace-nowrap">
                    Add New Student
                </button>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <!-- Student List -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg p-4">
            <?php if ($result->num_rows > 0): ?>
                <!-- Custom Filters -->
                <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Filter by Class</label>
                        <select id="classFilter" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-teal-500">
                            <option value="">All Classes</option>
                            <?php
                            // Fetch unique classes
                            $classesResult = $conn->query("SELECT DISTINCT class FROM students WHERE org_id = $org_id AND class IS NOT NULL ORDER BY class ASC");
                            while ($classRow = $classesResult->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($classRow['class']) . "'>" . htmlspecialchars($classRow['class']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Filter by Batch</label>
                        <select id="batchFilter" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-teal-500">
                            <option value="">All Batches</option>
                             <?php
                            // Fetch unique classes
                            $classesResult = $conn->query("SELECT DISTINCT batch FROM students WHERE org_id = $org_id AND batch IS NOT NULL ORDER BY batch ASC");
                            while ($classRow = $classesResult->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($classRow['batch']) . "'>" . htmlspecialchars($classRow['batch']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Filter by Status</label>
                        <select id="statusFilter" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-teal-500">
                            <option value="">All Students</option>
                            <option value="Active">Active Only</option>
                            <option value="Inactive">Inactive Only</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button id="deactivateClassBatchBtn"
                                onclick="deactivateClassBatch()"
                                class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded shadow transition">
                            Deactivate Class
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto -mx-4 sm:mx-0">
                    <div class="inline-block min-w-full align-middle">
                        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                            <table id="studentsTable" class="divide-y divide-gray-200 display" style="width:100%">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th scope="col" class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photo</th>
                                        <th scope="col" class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                                        <th scope="col" class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                                        <th scope="col" class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Roll No</th>
                                        <th scope="col" class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                        <th scope="col" class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                        <th scope="col" class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">QR Code</th>
                                        <th scope="col" class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr class="hover:bg-gray-50 transition"
                                            data-status="<?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>"
                                            style="<?php echo !$row['is_active'] ? 'background-color: #fef2f2 !important;' : ''; ?>">
                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php if ($row['photo']): ?>
                                                    <img src="<?php echo htmlspecialchars($row['photo']); ?>"
                                                        alt="Photo"
                                                        class="w-10 h-10 rounded-full object-cover cursor-pointer border-2 border-gray-300 hover:border-teal-500 transition"
                                                        onclick="viewPhoto('<?php echo htmlspecialchars($row['photo']); ?>', '<?php echo htmlspecialchars($row['name']); ?>')">
                                                <?php else: ?>
                                                    <span class="text-gray-400 text-xs">No photo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['class']); ?></td>
                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['batch']); ?></td>
                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['roll_number'] ?: 'N/A'); ?></td>
                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium <?php 
                                                $balance = (float)$row['net_balance'];
                                                if ($balance > 0) {
                                                    echo 'text-green-600';
                                                } elseif ($balance < 0) {
                                                    echo 'text-red-600';
                                                } else {
                                                    echo 'text-gray-500';
                                                }
                                            ?>">
                                                ₹<?php echo number_format(abs($balance), 2); ?>
                                                <?php if ($balance < 0): ?>
                                                    <span class="text-xs">(Due)</span>
                                                <?php elseif ($balance > 0): ?>
                                                    <span class="text-xs">(Paid)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['phone']); ?></td>
                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button
                                                    onclick="toggleStudentStatus(<?php echo $row['id']; ?>, <?php echo $row['is_active']; ?>, this)"
                                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 <?php echo $row['is_active'] ? 'bg-teal-600' : 'bg-gray-300'; ?>"
                                                    title="<?php echo $row['is_active'] ? 'Active - Click to deactivate' : 'Inactive - Click to activate'; ?>">
                                                    <span class="sr-only">Toggle status</span>
                                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform <?php echo $row['is_active'] ? 'translate-x-6' : 'translate-x-1'; ?>"></span>
                                                </button>
                                            </td>
                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button onclick='generateQR("STU-<?php echo $row['id']; ?>", "<?php echo htmlspecialchars($row['name']); ?>", "<?php echo htmlspecialchars($row['batch']); ?>", "<?php echo htmlspecialchars($row['roll_number'] ?: 'NA'); ?>", "<?php echo htmlspecialchars($row['class']); ?>")' class="text-blue-600 hover:text-blue-900 font-bold text-xs sm:text-sm">QR</button>
                                            </td>
                                            <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex flex-wrap gap-2">
                                                    <button type="button" class="payment-btn text-green-600 hover:text-green-900 font-bold text-xs sm:text-sm" data-student-id="<?php echo $row['id']; ?>" data-student-name="<?php echo htmlspecialchars($row['name']); ?>" data-student-fee="<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>">Pay</button>
                                                    <button onclick='viewStudent(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)' class="text-indigo-600 hover:text-indigo-900 font-bold text-xs sm:text-sm">More</button>
                                                    <button onclick='openEditModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)' class="text-teal-600 hover:text-teal-900 font-bold text-xs sm:text-sm">Edit</button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="p-6 text-center text-gray-500">
                    No students found.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Include Modals -->
    <?php include 'modals/student_form_modal.php'; ?>
    <?php include 'modals/conflict_modal.php'; ?>
    <?php include 'modals/photo_modal.php'; ?>
    <?php include 'modals/qr_modal.php'; ?>
    <?php include 'modals/document_modal.php'; ?>
    <?php include 'modals/student_view_modal.php'; ?>

    <!-- Include JavaScript -->
    <script src="js/students.js?v=<?php echo time(); ?>"></script>
    <script>
        // Re-open modal if there was an error (and it wasn't a conflict)
        <?php if ($error && !$conflict_student): ?>
            // We would ideally repopulate the form here with POST data, 
            // but for simplicity we'll just open it. 
            // A better approach would be to echo the POST data into JS variables.
            openAddModal();
            // If it was an edit, we might lose the ID unless we persist it.
            // For now, user re-enters.
        <?php endif; ?>
    </script>
</body>

</html>