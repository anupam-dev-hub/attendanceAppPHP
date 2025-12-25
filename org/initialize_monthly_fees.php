<?php
// org/initialize_monthly_fees.php
session_start();
require '../config.php';
require '../functions.php';
require 'monthly_fee_functions.php';

if (!isOrg()) {
    redirect('../index.php');
}

$org_id = $_SESSION['user_id'];

if (!isSubscribed($org_id)) {
    redirect('dashboard.php');
}

$success_message = '';
$error_message = '';
$results = null;

// Handle fee initialization request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['initialize_fees'])) {
    $month = isset($_POST['month']) ? intval($_POST['month']) : (int)date('n');
    $year = isset($_POST['year']) ? intval($_POST['year']) : (int)date('Y');
    
    if ($month < 1 || $month > 12) {
        $error_message = "Invalid month selected.";
    } elseif ($year < 2020 || $year > 2050) {
        $error_message = "Invalid year selected.";
    } else {
        $results = initializeMonthlyFeesForAllActiveStudents($conn, $org_id, $month, $year);
        
        $month_name = date('F Y', mktime(0, 0, 0, $month, 1, $year));
        
        if ($results['success'] > 0) {
            $success_message = "Successfully initialized monthly fees for {$results['success']} students for $month_name.";
            if ($results['skipped'] > 0) {
                $success_message .= " ({$results['skipped']} already had fees initialized)";
            }
        } elseif ($results['skipped'] > 0) {
            $error_message = "All {$results['skipped']} active students already have fees initialized for $month_name.";
        } else {
            $error_message = "No fees were initialized. Please check if you have active students with fee amounts set.";
        }
    }
}

// Get list of active students with fee status
$students_query = "
    SELECT id, name, class, batch, fees_json, is_active 
    FROM students 
    WHERE org_id = ? 
    AND is_active = 1 
    ORDER BY class ASC, roll_number ASC
";
$stmt = $conn->prepare($students_query);
$stmt->bind_param("i", $org_id);
$stmt->execute();
$students_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initialize Monthly Fees</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 antialiased">
    <?php include 'navbar.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Initialize Monthly Fees</h2>
            <p class="mt-2 text-sm text-gray-600">Initialize monthly tuition fees for all active students.</p>
        </div>

        <script>
        <?php if ($success_message): ?>
            Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: '<?php echo addslashes($success_message); ?>',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
            });
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            Swal.fire({
                icon: 'info',
                title: 'Notice',
                text: '<?php echo addslashes($error_message); ?>',
                confirmButtonColor: '#0d9488'
            });
        <?php endif; ?>
        
        function confirmInitialize(form) {
            const month = form.querySelector('[name="month"]').selectedOptions[0].text;
            const year = form.querySelector('[name="year"]').value;
            
            Swal.fire({
                title: 'Initialize Monthly Fees?',
                html: `This will initialize monthly fees for all active students for <strong>${month} ${year}</strong>.<br><br>Students who already have fees will be skipped automatically.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d9488',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Initialize',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Add the submit button to form and submit
                    const submitBtn = document.createElement('input');
                    submitBtn.type = 'hidden';
                    submitBtn.name = 'initialize_fees';
                    submitBtn.value = '1';
                    form.appendChild(submitBtn);
                    form.submit();
                }
            });
        }
        </script>

        <!-- Initialize Fees Form -->
        <div class="bg-white shadow sm:rounded-lg mb-8">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Initialize Fees for Month</h3>
                <form method="POST" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Month</label>
                            <select name="month" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-teal-500" required>
                                <?php
                                $months = [
                                    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                ];
                                $current_month = (int)date('n');
                                foreach ($months as $num => $name) {
                                    $selected = ($num == $current_month) ? 'selected' : '';
                                    echo "<option value='$num' $selected>$name</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                            <select name="year" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-teal-500" required>
                                <?php
                                $current_year = (int)date('Y');
                                for ($y = $current_year - 1; $y <= $current_year + 2; $y++) {
                                    $selected = ($y == $current_year) ? 'selected' : '';
                                    echo "<option value='$y' $selected>$y</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="button" name="initialize_fees" 
                                    class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded shadow transition"
                                    onclick="confirmInitialize(this.form);">
                                Initialize Fees
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results Display -->
        <?php if ($results): ?>
            <div class="bg-white shadow sm:rounded-lg mb-8">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Initialization Results</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <p class="text-sm text-blue-600 font-medium">Total Students</p>
                            <p class="text-2xl font-bold text-blue-900"><?php echo $results['total']; ?></p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <p class="text-sm text-green-600 font-medium">Successfully Initialized</p>
                            <p class="text-2xl font-bold text-green-900"><?php echo $results['success']; ?></p>
                        </div>
                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <p class="text-sm text-yellow-600 font-medium">Already Initialized</p>
                            <p class="text-2xl font-bold text-yellow-900"><?php echo $results['skipped']; ?></p>
                        </div>
                        <div class="bg-red-50 p-4 rounded-lg">
                            <p class="text-sm text-red-600 font-medium">Failed</p>
                            <p class="text-2xl font-bold text-red-900"><?php echo $results['failed']; ?></p>
                        </div>
                    </div>

                    <!-- Detailed Results -->
                    <?php if (!empty($results['details'])): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($results['details'] as $detail): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($detail['student_name']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                ₹<?php echo number_format($detail['fee_amount'], 2); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <?php if ($detail['result']['success']): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        Success
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                        Skipped
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                <?php echo htmlspecialchars($detail['result']['message']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Active Students List -->
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Active Students</h3>
                <?php if ($students_result->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monthly Fee</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last 3 Months Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($student = $students_result->fetch_assoc()): 
                                    $fee_status = getMonthlyFeeStatus($conn, $student['id'], 3);
                                ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($student['name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($student['class']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($student['batch']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php 
                                            $fees = json_decode($student['fees_json'], true);
                                            if (is_array($fees) && count($fees) > 0) {
                                                echo count($fees) . " fee(s) - ₹" . number_format(array_sum($fees), 2);
                                            } else {
                                                echo "No fees";
                                            }
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <div class="flex gap-2">
                                                <?php foreach ($fee_status as $month_status): ?>
                                                    <div class="text-xs" title="<?php echo htmlspecialchars($month_status['month_name']); ?>: ₹<?php echo number_format($month_status['balance'], 2); ?> due">
                                                        <?php if ($month_status['status'] === 'paid'): ?>
                                                            <span class="px-2 py-1 rounded bg-green-100 text-green-800">
                                                                <?php echo date('M', mktime(0, 0, 0, $month_status['month'], 1)); ?>
                                                            </span>
                                                        <?php elseif ($month_status['status'] === 'pending'): ?>
                                                            <span class="px-2 py-1 rounded bg-yellow-100 text-yellow-800">
                                                                <?php echo date('M', mktime(0, 0, 0, $month_status['month'], 1)); ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="px-2 py-1 rounded bg-gray-100 text-gray-800">
                                                                <?php echo date('M', mktime(0, 0, 0, $month_status['month'], 1)); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">No active students found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Information Box -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h4 class="text-lg font-semibold text-blue-900 mb-2">How It Works</h4>
            <ul class="list-disc list-inside text-sm text-blue-800 space-y-1">
                <li>Select the month and year for which you want to initialize fees</li>
                <li>Click "Initialize Fees" to create fee entries for all active students</li>
                <li>The system will automatically skip students who already have fees for that month (no duplicates)</li>
                <li>Only students with a fee amount greater than ₹0 will be processed</li>
                <li>When you activate a student, their current month's fee is automatically initialized</li>
                <li>Students can make payments against these initialized fees through the payment system</li>
            </ul>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
