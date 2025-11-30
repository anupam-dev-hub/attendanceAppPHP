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

// Fetch Students
$result = $conn->query("SELECT * FROM students WHERE org_id = $org_id ORDER BY class ASC, roll_number ASC");
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
    <style>
        body,
        .font-sans {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
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
    <nav class="bg-teal-600 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-white text-xl font-bold tracking-wide"><?php echo htmlspecialchars($_SESSION['org_name']); ?></a>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="dashboard.php" class="text-white hover:text-teal-100 font-medium transition">Dashboard</a>
                    <a href="subscribe.php" class="text-white hover:text-teal-100 font-medium transition">Subscription</a>
                    <a href="students.php" class="text-white hover:text-teal-100 font-medium transition">Students</a>
                    <a href="employees.php" class="text-white hover:text-teal-100 font-medium transition">Employees</a>
                    <a href="../logout.php" class="text-white hover:text-red-200 font-medium transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Manage Students</h2>
                <p class="mt-2 text-sm text-gray-600">Add, edit, and view student records.</p>
            </div>
            <button onclick="openAddModal()" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded shadow transition">
                Add New Student
            </button>
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
                <div class="mb-4 flex gap-4 items-end">
                    <div class="flex-1">
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
                    <div class="flex-1">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Filter by Batch</label>
                        <select id="batchFilter" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-teal-500">
                            <option value="">All Batches</option>
                            <?php
                            $startYear = 2025;
                            for ($i = 0; $i <= 5; $i++) {
                                $y1 = $startYear + $i;
                                $y2 = $y1 + 1;
                                $batchOption = "$y1-$y2";
                                echo "<option value='$batchOption'>$batchOption</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Filter by Status</label>
                        <select id="statusFilter" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-teal-500">
                            <option value="">All Students</option>
                            <option value="Active">Active Only</option>
                            <option value="Inactive">Inactive Only</option>
                        </select>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table id="studentsTable" class="min-w-full divide-y divide-gray-200 display">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photo</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Roll No</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">QR Code</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 transition"
                                    data-status="<?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>"
                                    style="<?php echo !$row['is_active'] ? 'background-color: #fef2f2 !important;' : ''; ?>">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php if ($row['photo']): ?>
                                            <img src="<?php echo htmlspecialchars($row['photo']); ?>"
                                                alt="Photo"
                                                class="w-10 h-10 rounded-full object-cover cursor-pointer border-2 border-gray-300 hover:border-teal-500 transition"
                                                onclick="viewPhoto('<?php echo htmlspecialchars($row['photo']); ?>', '<?php echo htmlspecialchars($row['name']); ?>')">
                                        <?php else: ?>
                                            <span class="text-gray-400 text-xs">No photo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['class']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['batch']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['roll_number'] ?: 'N/A'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₹<?php echo htmlspecialchars($row['fee'] ?: '0.00'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['phone']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button
                                            onclick="toggleStudentStatus(<?php echo $row['id']; ?>, <?php echo $row['is_active']; ?>, this)"
                                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 <?php echo $row['is_active'] ? 'bg-teal-600' : 'bg-gray-300'; ?>"
                                            title="<?php echo $row['is_active'] ? 'Active - Click to deactivate' : 'Inactive - Click to activate'; ?>">
                                            <span class="sr-only">Toggle status</span>
                                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform <?php echo $row['is_active'] ? 'translate-x-6' : 'translate-x-1'; ?>"></span>
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick='generateQR("STU-<?php echo $row['id']; ?>", "<?php echo htmlspecialchars($row['name']); ?>", "<?php echo htmlspecialchars($row['batch']); ?>", "<?php echo htmlspecialchars($row['roll_number'] ?: 'NA'); ?>", "<?php echo htmlspecialchars($row['class']); ?>")' class="text-blue-600 hover:text-blue-900 font-bold">Generate QR</button>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick='openPaymentModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)' class="text-green-600 hover:text-green-900 font-bold mr-3">Pay</button>
                                        <button onclick='viewStudent(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)' class="text-indigo-600 hover:text-indigo-900 font-bold mr-3">More Info</button>
                                        <button onclick='openEditModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)' class="text-teal-600 hover:text-teal-900 font-bold">Edit</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
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
    <?php include 'modals/payment_modal.php'; ?>
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