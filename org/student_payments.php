<?php
// org/student_payments.php
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
$filter_from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01');
$filter_to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-t');
$filter_student = isset($_GET['student_id']) ? intval($_GET['student_id']) : '';
$filter_class = isset($_GET['class']) ? $_GET['class'] : '';
$filter_type = isset($_GET['type']) ? $_GET['type'] : ''; // 'credit' or 'debit' or ''
$filter_category = isset($_GET['category']) ? $_GET['category'] : '';
$filter_search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build WHERE clause
$where_parts = ["s.org_id = ?"];
$params = [$org_id];
$param_types = "i";

if (!empty($filter_student)) {
    $where_parts[] = "sp.student_id = ?";
    $params[] = $filter_student;
    $param_types .= "i";
}

if (!empty($filter_class)) {
    $where_parts[] = "s.class = ?";
    $params[] = $filter_class;
    $param_types .= "s";
}

if (!empty($filter_type)) {
    $where_parts[] = "sp.transaction_type = ?";
    $params[] = $filter_type;
    $param_types .= "s";
}

if (!empty($filter_category)) {
    $where_parts[] = "sp.category = ?";
    $params[] = $filter_category;
    $param_types .= "s";
}

if (!empty($filter_search)) {
    $where_parts[] = "(s.name LIKE ? OR s.roll_number LIKE ? OR sp.category LIKE ?)";
    $search_param = "%{$filter_search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "sss";
}

$where_parts[] = "DATE(sp.created_at) BETWEEN ? AND ?";
$params[] = $filter_from;
$params[] = $filter_to;
$param_types .= "ss";

$where_clause = implode(" AND ", $where_parts);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM student_payments sp
                JOIN students s ON sp.student_id = s.id
                WHERE $where_clause";
$stmt = $conn->prepare($count_query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$count_result = $stmt->get_result()->fetch_assoc();
$total_payments = $count_result['total'];
$stmt->close();

// Pagination
$per_page = 50;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;
$total_pages = ceil($total_payments / $per_page);

// Get payments with pagination
$payments_query = "SELECT sp.id, sp.student_id, s.name, s.class, s.roll_number,
                   sp.amount, sp.transaction_type, sp.category, sp.created_at
                   FROM student_payments sp
                   JOIN students s ON sp.student_id = s.id
                   WHERE $where_clause
                   ORDER BY sp.created_at DESC
                   LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;
$param_types .= "ii";

$stmt = $conn->prepare($payments_query);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$payments_result = $stmt->get_result();
$stmt->close();

// Get distinct classes for filter
$class_query = "SELECT DISTINCT class FROM students WHERE org_id = ? ORDER BY class";
$class_stmt = $conn->prepare($class_query);
$class_stmt->bind_param("i", $org_id);
$class_stmt->execute();
$classes = $class_stmt->get_result();
$class_stmt->close();

// Get distinct categories for filter
$category_query = "SELECT DISTINCT category FROM student_payments sp
                   JOIN students s ON sp.student_id = s.id
                   WHERE s.org_id = ? ORDER BY category";
$category_stmt = $conn->prepare($category_query);
$category_stmt->bind_param("i", $org_id);
$category_stmt->execute();
$categories = $category_stmt->get_result();
$category_stmt->close();

// Get summary statistics
$stats_query = "SELECT 
                COALESCE(SUM(CASE WHEN sp.transaction_type = 'debit' THEN ABS(sp.amount) ELSE 0 END), 0) as total_collected,
                COALESCE(SUM(CASE WHEN sp.transaction_type = 'credit' THEN ABS(sp.amount) ELSE 0 END), 0) as total_due,
                COUNT(*) as total_transactions
                FROM student_payments sp
                JOIN students s ON sp.student_id = s.id
                WHERE $where_clause";

$stats_params = array_slice($params, 0, -2);
$stats_types = substr($param_types, 0, -2);

$stats_stmt = $conn->prepare($stats_query);
if (!empty($stats_params)) {
    $stats_stmt->bind_param($stats_types, ...$stats_params);
}
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Payments</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;
            padding-top: 140px !important;
        }
    </style>
</head>
<body class="bg-gray-50 antialiased">
    <?php include 'navbar.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Student Payments</h2>
            <p class="mt-2 text-sm text-gray-600">View and filter all student payment transactions.</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <p class="text-gray-600 text-sm font-medium">Total Collected</p>
                <h3 class="text-2xl font-bold text-green-600 mt-2">₹<?php echo number_format($stats['total_collected'], 2); ?></h3>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <p class="text-gray-600 text-sm font-medium">Total Due</p>
                <h3 class="text-2xl font-bold text-blue-600 mt-2">₹<?php echo number_format($stats['total_due'], 2); ?></h3>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <p class="text-gray-600 text-sm font-medium">Total Transactions</p>
                <h3 class="text-2xl font-bold text-purple-600 mt-2"><?php echo number_format($stats['total_transactions']); ?></h3>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Filters</h3>
            <form method="GET" class="space-y-4">
                <!-- Search Bar -->
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <div class="relative">
                        <input 
                            type="text" 
                            name="search" 
                            value="<?php echo htmlspecialchars($filter_search); ?>" 
                            placeholder="Search by student name, roll number, or category..." 
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
                
                <!-- Filter Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                    <input type="date" name="from" value="<?php echo htmlspecialchars($filter_from); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                    <input type="date" name="to" value="<?php echo htmlspecialchars($filter_to); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Class</label>
                    <select name="class" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <option value="">All Classes</option>
                        <?php while ($row = $classes->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row['class']); ?>" <?php echo $filter_class === $row['class'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($row['class']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Transaction Type</label>
                    <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <option value="">All Types</option>
                        <option value="debit" <?php echo $filter_type === 'debit' ? 'selected' : ''; ?>>Debit (Payment)</option>
                        <option value="credit" <?php echo $filter_type === 'credit' ? 'selected' : ''; ?>>Credit (Fee Due)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <option value="">All Categories</option>
                        <?php while ($row = $categories->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row['category']); ?>" <?php echo $filter_category === $row['category'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($row['category']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                </div>
                <!-- Hidden filter fields for pagination -->
                <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($filter_student); ?>">
                <div class="flex gap-3 items-end mt-4">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 text-white font-semibold py-2.5 px-4 rounded-lg shadow-md transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                        <i class="fas fa-filter mr-2"></i> Apply Filters
                    </button>
                    <a href="student_payments.php" class="flex-1 bg-gradient-to-r from-gray-200 to-gray-300 hover:from-gray-300 hover:to-gray-400 text-gray-800 font-semibold py-2.5 px-4 rounded-lg shadow-sm transition-all duration-200 text-center focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
                        <i class="fas fa-redo text-sm mr-2"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Payments Table -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gradient-to-r from-gray-100 to-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Class</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Type</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if ($payments_result->num_rows > 0): ?>
                            <?php while ($payment = $payments_result->fetch_assoc()): ?>
                                <tr class="hover:bg-blue-50 transition-colors duration-150">
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo date('M d, Y', strtotime($payment['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($payment['name']); ?>
                                        <span class="text-xs text-gray-500">(<?php echo htmlspecialchars($payment['roll_number'] ?: 'N/A'); ?>)</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php echo htmlspecialchars($payment['class']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php echo htmlspecialchars($payment['category']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <?php if ($payment['transaction_type'] === 'debit'): ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Payment</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Fee Due</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-semibold text-right">
                                        <?php if ($payment['transaction_type'] === 'debit'): ?>
                                            <span class="text-green-600">+₹<?php echo number_format(abs($payment['amount']), 2); ?></span>
                                        <?php else: ?>
                                            <span class="text-blue-600">₹<?php echo number_format(abs($payment['amount']), 2); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    No payment records found for the selected filters.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        Showing page <?php echo $page; ?> of <?php echo $total_pages; ?> (<?php echo number_format($total_payments); ?> total)
                    </div>
                    <div class="flex gap-2">
                        <?php $filter_params = "from=" . urlencode($filter_from) . "&to=" . urlencode($filter_to) . "&class=" . urlencode($filter_class) . "&type=" . urlencode($filter_type) . "&category=" . urlencode($filter_category) . "&student_id=" . urlencode($filter_student) . "&search=" . urlencode($filter_search); ?>
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo $filter_params; ?>&page=1" class="px-4 py-2 bg-white border-2 border-gray-200 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-teal-500 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-teal-500">First</a>
                            <a href="?<?php echo $filter_params; ?>&page=<?php echo $page - 1; ?>" class="px-4 py-2 bg-white border-2 border-gray-200 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:border-teal-500 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-teal-500">Previous</a>
                        <?php endif; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo $filter_params; ?>&page=<?php echo $page + 1; ?>" class="px-3 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Next</a>
                            <a href="?<?php echo $filter_params; ?>&page=<?php echo $total_pages; ?>" class="px-3 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Last</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
