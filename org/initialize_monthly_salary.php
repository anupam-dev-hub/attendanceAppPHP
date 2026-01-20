<?php
// org/initialize_monthly_salary.php
session_start();
require '../config.php';
require '../functions.php';

if (!isOrg()) {
    redirect('../index.php');
}

$org_id = $_SESSION['user_id'];

if (!isSubscribed($org_id)) {
    redirect('dashboard.php');
}

$success_message = '';
$error_message = '';

// Handle salary initialization request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['initialize_salaries'])) {
    $month = isset($_POST['month']) ? intval($_POST['month']) : (int)date('n');
    $year = isset($_POST['year']) ? intval($_POST['year']) : (int)date('Y');
    
    if ($month < 1 || $month > 12) {
        $error_message = "Invalid month selected.";
    } elseif ($year < 2020 || $year > 2050) {
        $error_message = "Invalid year selected.";
    } else {
        $month_name = date('F Y', mktime(0, 0, 0, $month, 1, $year));
        $category = "Salary - " . $month_name;
        
        // Get selected employees from POST
        $selected_employees = isset($_POST['select']) ? $_POST['select'] : [];
        
        $success_count = 0;
        $duplicate_count = 0;
        $error_count = 0;
        $duplicates_list = [];
        
        foreach ($selected_employees as $employee_id => $checked) {
            $employee_id = intval($employee_id);
            
            // Get employee's base salary
            $salaryStmt = $conn->prepare("SELECT salary FROM employees WHERE id = ? AND org_id = ?");
            $salaryStmt->bind_param("ii", $employee_id, $org_id);
            $salaryStmt->execute();
            $salaryResult = $salaryStmt->get_result();
            
            if ($salaryResult->num_rows === 0) {
                $error_count++;
                $salaryStmt->close();
                continue;
            }
            
            $salaryRow = $salaryResult->fetch_assoc();
            $amount = floatval($salaryRow['salary']);
            $salaryStmt->close();
            
            if ($amount <= 0) {
                $error_count++; // Skip if salary is 0 or negative
                continue;
            }
            
            // Check if salary already initialized for this employee for this month (duplicate check)
            $checkStmt = $conn->prepare("
                SELECT id FROM employee_payments 
                WHERE employee_id = ? AND category = ? AND transaction_type = 'credit'
            ");
            $checkStmt->bind_param("is", $employee_id, $category);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                $duplicate_count++;
                // Get employee name for duplicate list
                $nameStmt = $conn->prepare("SELECT name FROM employees WHERE id = ?");
                $nameStmt->bind_param("i", $employee_id);
                $nameStmt->execute();
                $nameResult = $nameStmt->get_result();
                if ($nameRow = $nameResult->fetch_assoc()) {
                    $duplicates_list[] = $nameRow['name'];
                }
                $nameStmt->close();
                $checkStmt->close();
                continue;
            }
            $checkStmt->close();
            
            // Insert salary payment (credit = expense/liability with positive amount)
            $description = "Monthly salary for " . $month_name;
            $positive_amount = abs($amount); // Always positive
            $insertStmt = $conn->prepare("
                INSERT INTO employee_payments (employee_id, amount, transaction_type, category, description, payment_date)
                VALUES (?, ?, 'credit', ?, ?, NOW())
            ");
            $insertStmt->bind_param("idss", $employee_id, $positive_amount, $category, $description);
            
            if ($insertStmt->execute()) {
                $success_count++;
            } else {
                $error_count++;
            }
            $insertStmt->close();
        }
        
        if ($success_count > 0) {
            $success_message = "Successfully initialized salaries for {$success_count} employees for $month_name.";
            if ($duplicate_count > 0) {
                $success_message .= " ({$duplicate_count} already had salaries initialized: " . implode(', ', $duplicates_list) . ")";
            }
        } elseif ($duplicate_count > 0) {
            $error_message = "No new salaries initialized. All {$duplicate_count} selected employees already have salaries for $month_name: " . implode(', ', $duplicates_list);
        } else {
            $error_message = "No salaries were initialized. Please select at least one employee.";
        }
    }
}

// Get list of active employees with balance
// Balance: credit=owed (negative), debit=paid (positive)
$employees_query = "
    SELECT e.id, e.name, e.salary,
           COALESCE(SUM(CASE 
               WHEN ep.transaction_type = 'debit' THEN ep.amount
               WHEN ep.transaction_type = 'credit' THEN -ep.amount
               ELSE 0 
           END), 0) AS net_balance
    FROM employees e
    LEFT JOIN employee_payments ep ON e.id = ep.employee_id
    WHERE e.org_id = ? 
    AND e.is_active = 1 
    GROUP BY e.id
    ORDER BY e.name ASC
";
$stmt = $conn->prepare($employees_query);
$stmt->bind_param("i", $org_id);
$stmt->execute();
$employees_result = $stmt->get_result();

// Fetch salary initialization history with employee details
$history_details_query = "
    SELECT ep.category,
           e.id as employee_id,
           e.name as employee_name,
           ep.amount,
           ep.created_at
    FROM employee_payments ep
    JOIN employees e ON ep.employee_id = e.id
    WHERE e.org_id = ? 
    AND ep.transaction_type = 'credit'
    AND ep.category LIKE 'Salary - %'
    ORDER BY ep.category DESC, e.name ASC
";
$details_stmt = $conn->prepare($history_details_query);
$details_stmt->bind_param("i", $org_id);
$details_stmt->execute();
$details_result = $details_stmt->get_result();

// Group details by category
$grouped_history = [];
while ($detail = $details_result->fetch_assoc()) {
    $category = $detail['category'];
    if (!isset($grouped_history[$category])) {
        $grouped_history[$category] = [
            'employee_count' => 0,
            'total_amount' => 0,
            'last_init_date' => $detail['created_at'],
            'employees' => []
        ];
    }
    $grouped_history[$category]['employee_count']++;
    $grouped_history[$category]['total_amount'] += $detail['amount'];
    $grouped_history[$category]['employees'][] = [
        'name' => $detail['employee_name'],
        'amount' => $detail['amount']
    ];
}
?>
<?php $force_show_nav = true; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initialize Monthly Salaries</title>
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
            <h2 class="text-3xl font-bold text-gray-900">Initialize Monthly Salaries</h2>
            <p class="mt-2 text-sm text-gray-600">Initialize monthly salaries for all active employees.</p>
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
                title: 'Initialize Monthly Salaries?',
                html: `This will initialize monthly salaries for all active employees for <strong>${month} ${year}</strong>.<br><br>Employees who already have salaries initialized will be skipped automatically.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d9488',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Initialize',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }
        
        function toggleAll(source) {
            const checkboxes = document.querySelectorAll('.employee-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = source.checked;
            });
        }
        </script>

        <!-- Initialize Salaries Form -->
        <div class="bg-white shadow sm:rounded-lg mb-8">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Initialize Salaries for Month</h3>
                <form method="POST" id="salaryForm" class="space-y-4" onsubmit="event.preventDefault(); confirmInitialize(this);">
                    <input type="hidden" name="initialize_salaries" value="1">
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
                            <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded transition">
                                Initialize Salaries
                            </button>
                        </div>
                    </div>

                    <!-- Employee List -->
                    <div class="mt-6">
                        <h4 class="text-md font-semibold text-gray-800 mb-4">Active Employees</h4>
                        <?php if ($employees_result->num_rows > 0): ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <input type="checkbox" onclick="toggleAll(this)" class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Base Salary</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php while ($employee = $employees_result->fetch_assoc()): 
                                            $balance = abs($employee['net_balance']);
                                        ?>
                                            <tr>
                                                <td class="px-3 py-4 whitespace-nowrap">
                                                    <input type="checkbox" name="select[<?php echo $employee['id']; ?>]" value="1" class="employee-checkbox rounded border-gray-300 text-teal-600 focus:ring-teal-500" checked>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($employee['name']); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                    ₹<?php echo number_format($employee['salary'], 2); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?php 
                                                    $net_balance = (float)$employee['net_balance'];
                                                    if ($net_balance > 0) {
                                                        echo 'text-green-600';
                                                    } elseif ($net_balance < 0) {
                                                        echo 'text-red-600';
                                                    } else {
                                                        echo 'text-gray-500';
                                                    }
                                                ?>">
                                                    ₹<?php echo number_format(abs($net_balance), 2); ?>
                                                    <?php if ($net_balance < 0): ?>
                                                        <span class="text-xs">(Due)</span>
                                                    <?php elseif ($net_balance > 0): ?>
                                                        <span class="text-xs">(Paid)</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-500">
                                <p>No active employees found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Salary Initialization History -->
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Salary Initialization History by Month</h3>
                <?php if (count($grouped_history) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employees Initialized</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Initialized</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php $row_index = 0; foreach ($grouped_history as $category => $history): $row_index++; ?>
                                    <tr class="hover:bg-gray-50 transition cursor-pointer" onclick="toggleEmployeeDetails(<?php echo $row_index; ?>)">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <span id="expand-icon-<?php echo $row_index; ?>" class="inline-block mr-2">▶</span>
                                            <?php 
                                                // Extract month from category like "Salary - January 2026"
                                                $month = str_replace('Salary - ', '', $category);
                                                echo htmlspecialchars($month);
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                            <?php echo $history['employee_count']; ?> employee(s)
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                            ₹<?php echo number_format($history['total_amount'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($history['last_init_date'])); ?>
                                        </td>
                                    </tr>
                                    <tr id="details-row-<?php echo $row_index; ?>" class="details-row hidden">
                                        <td colspan="4" class="px-6 py-4 bg-gray-50">
                                            <div class="ml-6">
                                                <h4 class="text-sm font-medium text-gray-900 mb-3">Employees Initialized:</h4>
                                                <div class="space-y-2">
                                                    <?php foreach ($history['employees'] as $emp): ?>
                                                        <div class="flex justify-between text-sm text-gray-700">
                                                            <span><?php echo htmlspecialchars($emp['name']); ?></span>
                                                            <span class="font-medium">₹<?php echo number_format($emp['amount'], 2); ?></span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <p>No salary initialization history found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style>
        .hidden {
            display: none;
        }
    </style>

    <script>
        function toggleEmployeeDetails(rowIndex) {
            const detailsRow = document.getElementById('details-row-' + rowIndex);
            const expandIcon = document.getElementById('expand-icon-' + rowIndex);
            
            if (detailsRow.classList.contains('hidden')) {
                detailsRow.classList.remove('hidden');
                expandIcon.textContent = '▼';
            } else {
                detailsRow.classList.add('hidden');
                expandIcon.textContent = '▶';
            }
        }
    </script>
</body>
</html>
