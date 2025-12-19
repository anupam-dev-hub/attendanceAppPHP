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

// Get filter parameters
$filter_class = isset($_GET['class']) ? $_GET['class'] : '';
$filter_batch = isset($_GET['batch']) ? $_GET['batch'] : '';
$filter_month = isset($_GET['month']) ? intval($_GET['month']) : (int)date('n');
$filter_year = isset($_GET['year']) ? intval($_GET['year']) : (int)date('Y');

// Build WHERE clause for filters
$where_conditions = ["s.org_id = ?"];
$params = [$org_id];
$param_types = "i";

if ($filter_class) {
    $where_conditions[] = "s.class = ?";
    $params[] = $filter_class;
    $param_types .= "s";
}

if ($filter_batch) {
    $where_conditions[] = "s.batch = ?";
    $params[] = $filter_batch;
    $param_types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Get total students and active students
$students_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
    FROM students s WHERE $where_clause";
$stmt = $conn->prepare($students_query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$students_stats = $result->fetch_assoc();
$stmt->close();

// Get total fees collected (debit = payments received)
$total_collected_query = "SELECT COALESCE(SUM(sp.amount), 0) as total
    FROM student_payments sp
    JOIN students s ON sp.student_id = s.id
    WHERE sp.transaction_type = 'debit' AND $where_clause";
$stmt = $conn->prepare($total_collected_query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$total_collected = $result->fetch_assoc()['total'];
$stmt->close();

// Get total fees due (credit = fees owed)
$total_due_query = "SELECT COALESCE(SUM(sp.amount), 0) as total
    FROM student_payments sp
    JOIN students s ON sp.student_id = s.id
    WHERE sp.transaction_type = 'credit' AND $where_clause";
$stmt = $conn->prepare($total_due_query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$total_due = $result->fetch_assoc()['total'];
$stmt->close();

$total_outstanding = $total_due - $total_collected;

// Get monthly collection data for chart (last 6 months)
$monthly_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month = $filter_month - $i;
    $year = $filter_year;
    while ($month <= 0) {
        $month += 12;
        $year--;
    }
    while ($month > 12) {
        $month -= 12;
        $year++;
    }
    
    $month_start = date('Y-m-01', mktime(0, 0, 0, $month, 1, $year));
    $month_end = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));
    
    $query = "SELECT COALESCE(SUM(sp.amount), 0) as total
        FROM student_payments sp
        JOIN students s ON sp.student_id = s.id
        WHERE sp.transaction_type = 'debit' 
        AND DATE(sp.created_at) BETWEEN ? AND ?
        AND $where_clause";
    
    $stmt = $conn->prepare($query);
    $extended_params = array_merge([$month_start, $month_end], $params);
    $extended_types = "ss" . $param_types;
    $stmt->bind_param($extended_types, ...$extended_params);
    $stmt->execute();
    $result = $stmt->get_result();
    $amount = $result->fetch_assoc()['total'];
    $stmt->close();
    
    $monthly_data[] = [
        'month' => date('M Y', mktime(0, 0, 0, $month, 1, $year)),
        'amount' => floatval($amount)
    ];
}

// Get payment category breakdown
$category_query = "SELECT 
    sp.category,
    SUM(CASE WHEN sp.transaction_type = 'debit' THEN sp.amount ELSE 0 END) as collected,
    SUM(CASE WHEN sp.transaction_type = 'credit' THEN sp.amount ELSE 0 END) as due
    FROM student_payments sp
    JOIN students s ON sp.student_id = s.id
    WHERE $where_clause
    GROUP BY sp.category
    ORDER BY collected DESC
    LIMIT 10";
$stmt = $conn->prepare($category_query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$category_result = $stmt->get_result();
$category_data = [];
while ($row = $category_result->fetch_assoc()) {
    $category_data[] = $row;
}
$stmt->close();

// Get top paying students
$top_students_query = "SELECT 
    s.id,
    s.name,
    s.class,
    s.roll_number,
    COALESCE(SUM(CASE WHEN sp.transaction_type = 'debit' THEN sp.amount ELSE 0 END), 0) as total_paid
    FROM students s
    LEFT JOIN student_payments sp ON s.id = sp.student_id
    WHERE $where_clause
    GROUP BY s.id
    ORDER BY total_paid DESC
    LIMIT 5";
$stmt = $conn->prepare($top_students_query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$top_students = $stmt->get_result();
$stmt->close();

// Get students with outstanding fees
$outstanding_query = "SELECT 
    s.id,
    s.name,
    s.class,
    s.roll_number,
    COALESCE(SUM(CASE WHEN sp.transaction_type = 'credit' THEN sp.amount ELSE 0 END), 0) -
    COALESCE(SUM(CASE WHEN sp.transaction_type = 'debit' THEN sp.amount ELSE 0 END), 0) as outstanding
    FROM students s
    LEFT JOIN student_payments sp ON s.id = sp.student_id
    WHERE $where_clause
    GROUP BY s.id
    HAVING outstanding > 0
    ORDER BY outstanding DESC
    LIMIT 10";
$stmt = $conn->prepare($outstanding_query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$outstanding_students = $stmt->get_result();
$stmt->close();

// Get available classes and batches for filters
$classes_result = $conn->query("SELECT DISTINCT class FROM students WHERE org_id = $org_id AND class IS NOT NULL ORDER BY class");
$batches_result = $conn->query("SELECT DISTINCT batch FROM students WHERE org_id = $org_id AND batch IS NOT NULL ORDER BY batch DESC");
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

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Filters</h3>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Class</label>
                    <select name="class" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <option value="">All Classes</option>
                        <?php while ($class = $classes_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($class['class']); ?>" <?php echo $filter_class === $class['class'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Batch</label>
                    <select name="batch" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <option value="">All Batches</option>
                        <?php while ($batch = $batches_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($batch['batch']); ?>" <?php echo $filter_batch === $batch['batch'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($batch['batch']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Month</label>
                    <select name="month" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <?php
                        $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                        for ($m = 1; $m <= 12; $m++) {
                            $selected = $m == $filter_month ? 'selected' : '';
                            echo "<option value='$m' $selected>{$months[$m-1]}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                    <select name="year" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <?php
                        $current_year = (int)date('Y');
                        for ($y = $current_year - 2; $y <= $current_year + 1; $y++) {
                            $selected = $y == $filter_year ? 'selected' : '';
                            echo "<option value='$y' $selected>$y</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="md:col-span-4 flex gap-3">
                    <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                        Apply Filters
                    </button>
                    <a href="finance_overview.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2 px-6 rounded-lg transition">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card teal">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-teal-100 text-sm font-medium">Total Collected</p>
                        <h3 class="text-3xl font-bold mt-2">₹<?php echo number_format($total_collected, 2); ?></h3>
                    </div>
                    <svg class="w-12 h-12 text-white opacity-50" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z"/>
                    </svg>
                </div>
            </div>

            <div class="stat-card blue">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">Total Outstanding</p>
                        <h3 class="text-3xl font-bold mt-2">₹<?php echo number_format($total_outstanding, 2); ?></h3>
                    </div>
                    <svg class="w-12 h-12 text-white opacity-50" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"/>
                    </svg>
                </div>
            </div>

            <div class="stat-card green">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm font-medium">Active Students</p>
                        <h3 class="text-3xl font-bold mt-2"><?php echo $students_stats['active']; ?></h3>
                        <p class="text-purple-100 text-xs mt-1">of <?php echo $students_stats['total']; ?> total</p>
                    </div>
                    <svg class="w-12 h-12 text-white opacity-50" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                </div>
            </div>

            <div class="stat-card orange">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-orange-100 text-sm font-medium">Collection Rate</p>
                        <h3 class="text-3xl font-bold mt-2"><?php echo $total_due > 0 ? number_format(($total_collected / $total_due) * 100, 1) : 0; ?>%</h3>
                    </div>
                    <svg class="w-12 h-12 text-white opacity-50" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z"/>
                    </svg>
                </div>
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
