<?php
// org/finance_overview.php
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

// Get filter parameters (date range)
$filter_from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01');
$filter_to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-t');

// Build WHERE clause for org
$where_clause = "s.org_id = ?";
$org_params = [$org_id];
$org_param_types = "i";

// Get total students and active students
$students_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
    FROM students s WHERE $where_clause";
$stmt = $conn->prepare($students_query);
$stmt->bind_param($org_param_types, ...$org_params);
$stmt->execute();
$result = $stmt->get_result();
$students_stats = $result->fetch_assoc();
$stmt->close();

// Get total fees collected (debit = payments received) within date range
$total_collected_query = "SELECT COALESCE(SUM(ABS(sp.amount)), 0) as total
    FROM student_payments sp
    JOIN students s ON sp.student_id = s.id
    WHERE sp.transaction_type = 'debit' AND $where_clause AND DATE(sp.created_at) BETWEEN ? AND ?";
$stmt = $conn->prepare($total_collected_query);
$extended_params = array_merge($org_params, [$filter_from, $filter_to]);
$stmt->bind_param('iss', ...$extended_params);
$stmt->execute();
$result = $stmt->get_result();
$total_collected = $result->fetch_assoc()['total'];
$stmt->close();

// Get total fees due (credit = fees owed) within date range
$total_due_query = "SELECT COALESCE(SUM(ABS(sp.amount)), 0) as total
    FROM student_payments sp
    JOIN students s ON sp.student_id = s.id
    WHERE sp.transaction_type = 'credit' AND $where_clause AND DATE(sp.created_at) BETWEEN ? AND ?";
$stmt = $conn->prepare($total_due_query);
$extended_params = array_merge($org_params, [$filter_from, $filter_to]);
$stmt->bind_param('iss', ...$extended_params);
$stmt->execute();
$result = $stmt->get_result();
$total_due = $result->fetch_assoc()['total'];
$stmt->close();

$total_outstanding = $total_due - $total_collected;

// Employee payments: payouts (debit) and salary liabilities (credit)
$emp_query = "SELECT 
    COALESCE(SUM(CASE WHEN ep.transaction_type = 'debit' THEN ABS(ep.amount) ELSE 0 END), 0) AS total_paid,
    COALESCE(SUM(CASE WHEN ep.transaction_type = 'credit' THEN ABS(ep.amount) ELSE 0 END), 0) AS total_salary
    FROM employee_payments ep
    JOIN employees e ON ep.employee_id = e.id
    WHERE e.org_id = ? AND DATE(ep.created_at) BETWEEN ? AND ?";
$stmt = $conn->prepare($emp_query);
$stmt->bind_param('iss', $org_id, $filter_from, $filter_to);
$stmt->execute();
$emp_res = $stmt->get_result()->fetch_assoc();
$employee_paid = floatval($emp_res['total_paid']);
$employee_outstanding = floatval($emp_res['total_salary'] - $emp_res['total_paid']);
$stmt->close();

// Employee payout trend (last 6 months from filter_to)
$employee_trend = [];
$to_date = new DateTime($filter_to);
for ($i = 5; $i >= 0; $i--) {
    $trend_date = clone $to_date;
    $trend_date->modify("-$i months");
    $trend_month = (int)$trend_date->format('n');
    $trend_year = (int)$trend_date->format('Y');
    
    $trStmt = $conn->prepare("SELECT COALESCE(SUM(ep.amount),0) as amt FROM employee_payments ep JOIN employees e ON ep.employee_id = e.id WHERE e.org_id = ? AND ep.transaction_type = 'debit' AND YEAR(ep.created_at) = ? AND MONTH(ep.created_at) = ?");
    $trStmt->bind_param('iii', $org_id, $trend_year, $trend_month);
    $trStmt->execute();
    $trRes = $trStmt->get_result()->fetch_assoc();
    $employee_trend[] = [
        'label' => $trend_date->format('M Y'),
        'amount' => floatval($trRes['amt'])
    ];
    $trStmt->close();
}

// Expenses total (filtered by date range)
$exp_stmt = $conn->prepare("SELECT COALESCE(SUM(ABS(amount)), 0) AS total_expenses FROM expenses WHERE org_id = ? AND DATE(expense_date) BETWEEN ? AND ?");
$exp_stmt->bind_param('iss', $org_id, $filter_from, $filter_to);
$exp_stmt->execute();
$exp_total_row = $exp_stmt->get_result()->fetch_assoc();
$expenses_total = floatval($exp_total_row['total_expenses']);
$exp_stmt->close();

// Subscription spend (filtered by date range, non-rejected)
$sub_stmt = $conn->prepare("SELECT COALESCE(SUM(ABS(amount)), 0) AS total_sub FROM subscriptions WHERE org_id = ? AND status <> 'rejected' AND DATE(created_at) BETWEEN ? AND ?");
$sub_stmt->bind_param('iss', $org_id, $filter_from, $filter_to);
$sub_stmt->execute();
$sub_row = $sub_stmt->get_result()->fetch_assoc();
$subscription_total = floatval($sub_row['total_sub']);
$sub_stmt->close();

// Net cash position (simplified): student collections minus payouts and expenses
$net_balance = $total_collected - ($employee_paid + $expenses_total + $subscription_total);

// Get monthly collection data for chart (last 6 months from filter_to)
$monthly_data = [];
$to_date = new DateTime($filter_to);
for ($i = 5; $i >= 0; $i--) {
    $chart_date = clone $to_date;
    $chart_date->modify("-$i months");
    $month_start = $chart_date->format('Y-m-01');
    $month_end = $chart_date->format('Y-m-t');
    
    $query = "SELECT COALESCE(SUM(sp.amount), 0) as total
        FROM student_payments sp
        JOIN students s ON sp.student_id = s.id
        WHERE sp.transaction_type = 'debit' 
        AND DATE(sp.created_at) BETWEEN ? AND ?
        AND $where_clause";
    
    $stmt = $conn->prepare($query);
    $extended_params = array_merge([$month_start, $month_end], $org_params);
    $stmt->bind_param('ssi', ...$extended_params);
    $stmt->execute();
    $result = $stmt->get_result();
    $amount = $result->fetch_assoc()['total'];
    $stmt->close();
    
    $monthly_data[] = [
        'month' => $chart_date->format('M Y'),
        'amount' => floatval($amount)
    ];
}

// Get payment category breakdown (within date range)
$category_query = "SELECT 
    sp.category,
    SUM(CASE WHEN sp.transaction_type = 'debit' THEN sp.amount ELSE 0 END) as collected,
    SUM(CASE WHEN sp.transaction_type = 'credit' THEN sp.amount ELSE 0 END) as due
    FROM student_payments sp
    JOIN students s ON sp.student_id = s.id
    WHERE $where_clause AND DATE(sp.created_at) BETWEEN ? AND ?
    GROUP BY sp.category
    ORDER BY collected DESC
    LIMIT 10";
$stmt = $conn->prepare($category_query);
$extended_params = array_merge($org_params, [$filter_from, $filter_to]);
$stmt->bind_param('iss', ...$extended_params);
$stmt->execute();
$category_result = $stmt->get_result();
$category_data = [];
while ($row = $category_result->fetch_assoc()) {
    $category_data[] = $row;
}
$stmt->close();

// Get top paying students (within date range)
$top_students_query = "SELECT 
    s.id,
    s.name,
    s.class,
    s.roll_number,
    COALESCE(SUM(CASE WHEN sp.transaction_type = 'debit' THEN sp.amount ELSE 0 END), 0) as total_paid
    FROM students s
    LEFT JOIN student_payments sp ON s.id = sp.student_id AND DATE(sp.created_at) BETWEEN ? AND ?
    WHERE $where_clause
    GROUP BY s.id
    ORDER BY total_paid DESC
    LIMIT 5";
$stmt = $conn->prepare($top_students_query);
$extended_params = array_merge([$filter_from, $filter_to], $org_params);
$stmt->bind_param('ssi', ...$extended_params);
$stmt->execute();
$top_students = $stmt->get_result();
$stmt->close();

// Get students with outstanding fees (within date range)
$outstanding_query = "SELECT 
    s.id,
    s.name,
    s.class,
    s.roll_number,
    COALESCE(SUM(CASE WHEN sp.transaction_type = 'credit' THEN ABS(sp.amount) ELSE 0 END), 0) as total_due,
    COALESCE(SUM(CASE WHEN sp.transaction_type = 'debit' THEN ABS(sp.amount) ELSE 0 END), 0) as total_paid,
    (COALESCE(SUM(CASE WHEN sp.transaction_type = 'credit' THEN ABS(sp.amount) ELSE 0 END), 0) -
     COALESCE(SUM(CASE WHEN sp.transaction_type = 'debit' THEN ABS(sp.amount) ELSE 0 END), 0)) as outstanding
    FROM students s
    LEFT JOIN student_payments sp ON s.id = sp.student_id AND DATE(sp.created_at) BETWEEN ? AND ?
    WHERE $where_clause
    GROUP BY s.id, s.name, s.class, s.roll_number
    HAVING outstanding > 0
    ORDER BY outstanding DESC
    LIMIT 10";
$stmt = $conn->prepare($outstanding_query);
$extended_params = array_merge([$filter_from, $filter_to], $org_params);
$stmt->bind_param('ssi', ...$extended_params);
$stmt->execute();
$outstanding_students = $stmt->get_result();
$stmt->close();

// Recent expenses (filtered by date range, last 5)
$exp_recent_stmt = $conn->prepare("SELECT title, category, amount, expense_date FROM expenses WHERE org_id = ? AND DATE(expense_date) BETWEEN ? AND ? ORDER BY expense_date DESC, created_at DESC LIMIT 5");
$exp_recent_stmt->bind_param('iss', $org_id, $filter_from, $filter_to);
$exp_recent_stmt->execute();
$recent_expenses = $exp_recent_stmt->get_result();
$exp_recent_stmt->close();

// Top expense categories (filtered by date range)
$exp_cat_stmt = $conn->prepare("SELECT category, SUM(amount) as total FROM expenses WHERE org_id = ? AND DATE(expense_date) BETWEEN ? AND ? GROUP BY category ORDER BY total DESC LIMIT 5");
$exp_cat_stmt->bind_param('iss', $org_id, $filter_from, $filter_to);
$exp_cat_stmt->execute();
$expense_categories = $exp_cat_stmt->get_result();
$exp_cat_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Overview</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../assets/css/ux-improvements.css">
    <style>
        body {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 1rem;
            padding: 1.5rem;
            color: white;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
        }
        
        .stat-card.green {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-card.blue {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        }
        
        .stat-card.orange {
            background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
        }
        
        .stat-card.teal {
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50 antialiased">
    <?php include 'navbar.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Finance Overview</h2>
            <p class="mt-2 text-sm text-gray-600">Monitor your organization's financial health and payment trends.</p>
        </div>

        <!-- Date Filter -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-calendar-alt text-teal-600 mr-2"></i> Date Range Filter
            </h3>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-calendar-day text-green-500 mr-2 text-xs"></i> From Date
                    </label>
                    <input type="date" name="from" value="<?php echo htmlspecialchars($filter_from); ?>" class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg transition-all duration-200 focus:outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-200 hover:border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-calendar-check text-red-500 mr-2 text-xs"></i> To Date
                    </label>
                    <input type="date" name="to" value="<?php echo htmlspecialchars($filter_to); ?>" class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg transition-all duration-200 focus:outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-200 hover:border-gray-300">
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 text-white font-semibold py-2.5 px-6 rounded-lg shadow-md transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                        <i class="fas fa-filter mr-2"></i> Apply Filter
                    </button>
                    <a href="finance_overview.php" class="bg-gradient-to-r from-gray-200 to-gray-300 hover:from-gray-300 hover:to-gray-400 text-gray-700 font-semibold py-2.5 px-6 rounded-lg shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Student Collections -->
            <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden transform transition hover:scale-105">
                <div class="absolute top-0 right-0 w-20 h-20 bg-white opacity-10 rounded-full -mr-10 -mt-10"></div>
                <div class="relative z-10">
                    <p class="text-green-100 text-sm font-medium uppercase tracking-wide mb-2">Student Collections</p>
                    <h3 class="text-4xl font-bold">₹<?php echo number_format($total_collected, 0); ?></h3>
                    <div class="mt-4">
                        <i class="fas fa-hand-holding-usd text-3xl opacity-50"></i>
                    </div>
                </div>
            </div>

            <!-- Student Outstanding -->
            <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden transform transition hover:scale-105">
                <div class="absolute top-0 right-0 w-20 h-20 bg-white opacity-10 rounded-full -mr-10 -mt-10"></div>
                <div class="relative z-10">
                    <p class="text-orange-100 text-sm font-medium uppercase tracking-wide mb-2">Student Outstanding</p>
                    <h3 class="text-4xl font-bold">₹<?php echo number_format($total_outstanding, 0); ?></h3>
                    <div class="mt-4">
                        <i class="fas fa-exclamation-triangle text-3xl opacity-50"></i>
                    </div>
                </div>
            </div>

            <!-- Employee Payouts -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden transform transition hover:scale-105">
                <div class="absolute top-0 right-0 w-20 h-20 bg-white opacity-10 rounded-full -mr-10 -mt-10"></div>
                <div class="relative z-10">
                    <p class="text-purple-100 text-sm font-medium uppercase tracking-wide mb-2">Employee Payouts</p>
                    <h3 class="text-4xl font-bold">₹<?php echo number_format($employee_paid, 0); ?></h3>
                    <div class="mt-4">
                        <i class="fas fa-money-check-alt text-3xl opacity-50"></i>
                    </div>
                </div>
            </div>

            <!-- Employee Outstanding -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden transform transition hover:scale-105">
                <div class="absolute top-0 right-0 w-20 h-20 bg-white opacity-10 rounded-full -mr-10 -mt-10"></div>
                <div class="relative z-10">
                    <p class="text-blue-100 text-sm font-medium uppercase tracking-wide mb-2">Employee Outstanding</p>
                    <h3 class="text-4xl font-bold">₹<?php echo number_format(max($employee_outstanding, 0), 0); ?></h3>
                    <div class="mt-4">
                        <i class="fas fa-clock text-3xl opacity-50"></i>
                    </div>
                </div>
            </div>

            <!-- Expenses -->
            <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden transform transition hover:scale-105">
                <div class="absolute top-0 right-0 w-20 h-20 bg-white opacity-10 rounded-full -mr-10 -mt-10"></div>
                <div class="relative z-10">
                    <p class="text-red-100 text-sm font-medium uppercase tracking-wide mb-2">Expenses</p>
                    <h3 class="text-4xl font-bold">₹<?php echo number_format($expenses_total, 0); ?></h3>
                    <div class="mt-4">
                        <i class="fas fa-file-invoice-dollar text-3xl opacity-50"></i>
                    </div>
                </div>
            </div>

            <!-- Net Balance -->
            <div class="bg-gradient-to-br from-teal-500 to-teal-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden transform transition hover:scale-105">
                <div class="absolute top-0 right-0 w-20 h-20 bg-white opacity-10 rounded-full -mr-10 -mt-10"></div>
                <div class="relative z-10">
                    <p class="text-teal-100 text-sm font-medium uppercase tracking-wide mb-2">Net Balance</p>
                    <h3 class="text-4xl font-bold">₹<?php echo number_format($net_balance, 0); ?></h3>
                    <p class="text-teal-100 text-xs mt-1">Collections - Payouts - Expenses</p>
                    <div class="mt-2">
                        <i class="fas fa-chart-line text-3xl opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employee Payout Trend -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Employee Payouts (Last 6 Months)</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($employee_trend as $et): ?>
                    <div class="p-4 rounded-lg bg-gray-50 border">
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($et['label']); ?></p>
                        <p class="text-xl font-semibold text-gray-900">₹<?php echo number_format($et['amount'], 2); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Monthly Collection Chart -->
            <div class="chart-container">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Collections (Last 6 Months)</h3>
                <canvas id="monthlyChart"></canvas>
            </div>

            <!-- Category Breakdown Chart -->
            <div class="chart-container">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Collection by Category</h3>
                <canvas id="categoryChart"></canvas>
            </div>
        </div>

        <!-- Tables Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Top Paying Students -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Paying Students</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Paid</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if ($top_students->num_rows > 0): ?>
                                <?php while ($student = $top_students->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($student['name']); ?>
                                            <span class="text-xs text-gray-500">(Roll: <?php echo htmlspecialchars($student['roll_number'] ?: 'N/A'); ?>)</span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($student['class']); ?></td>
                                        <td class="px-4 py-3 text-sm font-semibold text-green-600 text-right">₹<?php echo number_format($student['total_paid'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-gray-500">No payment data available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Outstanding Fees -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Students with Outstanding Fees</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Due</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if ($outstanding_students->num_rows > 0): ?>
                                <?php while ($student = $outstanding_students->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($student['name']); ?>
                                            <span class="text-xs text-gray-500">(Roll: <?php echo htmlspecialchars($student['roll_number'] ?: 'N/A'); ?>)</span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($student['class']); ?></td>
                                        <td class="px-4 py-3 text-sm font-semibold text-red-600 text-right">₹<?php echo number_format($student['outstanding'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-gray-500">All students have paid their fees!</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Expenses Snapshot -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Expenses</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if ($recent_expenses->num_rows > 0): ?>
                                <?php while ($exp = $recent_expenses->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-600"><?php echo date('M d, Y', strtotime($exp['expense_date'])); ?></td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($exp['title']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($exp['category']); ?></td>
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-900 text-right">₹<?php echo number_format($exp['amount'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No expenses recorded.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Expense Categories</h3>
                <?php if ($expense_categories->num_rows > 0): ?>
                    <ul class="space-y-3">
                        <?php while ($cat = $expense_categories->fetch_assoc()): ?>
                            <li class="flex justify-between text-sm text-gray-800">
                                <span><?php echo htmlspecialchars($cat['category']); ?></span>
                                <span class="font-semibold">₹<?php echo number_format($cat['total'], 2); ?></span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-sm text-gray-500">No expense data yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Monthly Collection Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = <?php echo json_encode($monthly_data); ?>;
        
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyData.map(d => d.month),
                datasets: [{
                    label: 'Collections (₹)',
                    data: monthlyData.map(d => d.amount),
                    borderColor: '#0d9488',
                    backgroundColor: 'rgba(13, 148, 136, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#0d9488',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Amount: ₹' + context.parsed.y.toLocaleString('en-IN', {minimumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value.toLocaleString('en-IN');
                            }
                        }
                    }
                }
            }
        });

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryData = <?php echo json_encode($category_data); ?>;
        
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: categoryData.map(d => d.category),
                datasets: [{
                    label: 'Collections',
                    data: categoryData.map(d => parseFloat(d.collected)),
                    backgroundColor: [
                        '#0d9488',
                        '#6366f1',
                        '#f59e0b',
                        '#ef4444',
                        '#8b5cf6',
                        '#14b8a6',
                        '#f97316',
                        '#06b6d4',
                        '#ec4899',
                        '#84cc16'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return label + ': ₹' + value.toLocaleString('en-IN', {minimumFractionDigits: 2}) + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
