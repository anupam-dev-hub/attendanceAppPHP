<?php
session_start();

require '../functions.php';
require '../config.php';

if (!isOrg()) {
    redirect('../index.php');
}

$org_id = $_SESSION['user_id'];

if (!isSubscribed($org_id)) {
    redirect('dashboard.php');
}

$current_page = basename($_SERVER['PHP_SELF']);

// Handle filters
$filter_from_date = $_GET['from_date'] ?? '';
$filter_to_date = $_GET['to_date'] ?? '';
$filter_employee = $_GET['employee'] ?? '';
$filter_transaction_type = $_GET['transaction_type'] ?? '';
$filter_category = $_GET['category'] ?? '';
$filter_search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = max(1, intval($_GET['page'] ?? 1));

// Build WHERE clause
$where_parts = ["e.org_id = ?"];
$params = [$org_id];
$param_types = "i";

if (!empty($filter_from_date)) {
    $where_parts[] = "DATE(ep.created_at) >= ?";
    $params[] = $filter_from_date;
    $param_types .= "s";
}

if (!empty($filter_to_date)) {
    $where_parts[] = "DATE(ep.created_at) <= ?";
    $params[] = $filter_to_date;
    $param_types .= "s";
}

if (!empty($filter_employee)) {
    $where_parts[] = "ep.employee_id = ?";
    $params[] = $filter_employee;
    $param_types .= "i";
}

if (!empty($filter_transaction_type)) {
    if ($filter_transaction_type === 'debit') {
        $where_parts[] = "ep.transaction_type = 'debit'";
    } elseif ($filter_transaction_type === 'credit') {
        $where_parts[] = "ep.transaction_type = 'credit'";
    }
}

if (!empty($filter_category)) {
    $where_parts[] = "ep.category = ?";
    $params[] = $filter_category;
    $param_types .= "s";
}

if (!empty($filter_search)) {
    $where_parts[] = "(e.name LIKE ? OR ep.category LIKE ?)";
    $search_param = "%{$filter_search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ss";
}

$where_clause = implode(" AND ", $where_parts);

// Get statistics
$stats_query = "SELECT 
    COALESCE(SUM(CASE WHEN ep.transaction_type = 'debit' THEN ABS(ep.amount) ELSE 0 END), 0) as total_paid,
    COALESCE(SUM(CASE WHEN ep.transaction_type = 'credit' THEN ABS(ep.amount) ELSE 0 END), 0) as total_due,
    COUNT(*) as total_transactions
    FROM employee_payments ep
    JOIN employees e ON ep.employee_id = e.id
    WHERE $where_clause";

$stmt = $conn->prepare($stats_query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM employee_payments ep
    JOIN employees e ON ep.employee_id = e.id
    WHERE $where_clause";

$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$count_result = $stmt->get_result()->fetch_assoc();
$total_records = $count_result['total'];

// Pagination
$per_page = 50;
$total_pages = ceil($total_records / $per_page);
$offset = ($page - 1) * $per_page;

// Get payment records
$data_query = "SELECT 
    ep.id,
    ep.amount,
    ep.transaction_type,
    ep.category,
    ep.created_at,
    e.id as employee_id,
    e.name as employee_name
    FROM employee_payments ep
    JOIN employees e ON ep.employee_id = e.id
    WHERE $where_clause
    ORDER BY ep.created_at DESC
    LIMIT ? OFFSET ?";

$stmt = $conn->prepare($data_query);
$params[] = $per_page;
$params[] = $offset;
$param_types .= "ii";
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$payments = $stmt->get_result();

// Get employees for filter
$employees_query = "SELECT id, name FROM employees WHERE org_id = ? ORDER BY name";
$stmt = $conn->prepare($employees_query);
$stmt->bind_param("i", $org_id);
$stmt->execute();
$employees = $stmt->get_result();

// Get unique categories for filter
$categories_query = "SELECT DISTINCT category FROM employee_payments ep
    JOIN employees e ON ep.employee_id = e.id
    WHERE e.org_id = ?
    ORDER BY category";
$stmt = $conn->prepare($categories_query);
$stmt->bind_param("i", $org_id);
$stmt->execute();
$categories = $stmt->get_result();

// Build filter URL for pagination
$filter_params = [];
if (!empty($filter_from_date)) $filter_params[] = "from_date=" . urlencode($filter_from_date);
if (!empty($filter_to_date)) $filter_params[] = "to_date=" . urlencode($filter_to_date);
if (!empty($filter_employee)) $filter_params[] = "employee=" . urlencode($filter_employee);
if (!empty($filter_transaction_type)) $filter_params[] = "transaction_type=" . urlencode($filter_transaction_type);
if (!empty($filter_category)) $filter_params[] = "category=" . urlencode($filter_category);
if (!empty($filter_search)) $filter_params[] = "search=" . urlencode($filter_search);
$filter_url = !empty($filter_params) ? "&" . implode("&", $filter_params) : "";

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Payments</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include 'navbar.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Employee Payments</h1>
            <p class="text-gray-600">Track and view all employee payment transactions with detailed filters</p>
        </div>

        <!-- Statistics Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Paid -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Total Paid</p>
                        <p class="text-3xl font-bold text-green-600 mt-2">₹<?php echo number_format($stats['total_paid'], 2); ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-arrow-down text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Due -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Total Due</p>
                        <p class="text-3xl font-bold text-red-600 mt-2">₹<?php echo number_format($stats['total_due'], 2); ?></p>
                    </div>
                    <div class="bg-red-100 p-3 rounded-full">
                        <i class="fas fa-arrow-up text-red-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Transactions -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Total Transactions</p>
                        <p class="text-3xl font-bold text-blue-600 mt-2"><?php echo number_format($stats['total_transactions']); ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-exchange-alt text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Filters</h3>
            <form method="GET" class="space-y-4">
                <!-- Global Search -->
                <div class="relative">
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-search text-purple-500 mr-2 text-xs"></i> Search
                    </label>
                    <div class="relative">
                        <input
                            type="text"
                            name="search"
                            value="<?php echo htmlspecialchars($filter_search); ?>"
                            placeholder="Search by employee name or category..."
                            class="w-full pl-10 pr-4 py-2.5 border-2 border-gray-200 rounded-lg transition-all duration-200 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 hover:border-gray-300">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Date From -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <i class="fas fa-calendar text-green-500 mr-2 text-xs"></i> From Date
                        </label>
                        <input type="date" name="from_date" value="<?php echo htmlspecialchars($filter_from_date); ?>" class="w-full px-3 py-2.5 border-2 border-gray-200 rounded-lg transition-all duration-200 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 hover:border-gray-300">
                    </div>

                <!-- Date To -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-calendar-check text-red-500 mr-2 text-xs"></i> To Date
                    </label>
                    <input type="date" name="to_date" value="<?php echo htmlspecialchars($filter_to_date); ?>" class="w-full px-3 py-2.5 border-2 border-gray-200 rounded-lg transition-all duration-200 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 hover:border-gray-300">
                </div>

                <!-- Employee -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-user-tie text-blue-500 mr-2 text-xs"></i> Employee
                    </label>
                    <select name="employee" class="w-full px-3 py-2.5 border-2 border-gray-200 rounded-lg transition-all duration-200 focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200 hover:border-gray-300">
                        <option value="">All Employees</option>
                        <?php 
                        $employees->data_seek(0);
                        while ($row = $employees->fetch_assoc()): 
                        ?>
                            <option value="<?php echo htmlspecialchars($row['id']); ?>" <?php echo $filter_employee === (string)$row['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($row['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Transaction Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Type</label>
                    <select name="transaction_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-teal-500 focus:border-teal-500">
                        <option value="">All Types</option>
                        <option value="debit" <?php echo $filter_transaction_type === 'debit' ? 'selected' : ''; ?>>Paid</option>
                        <option value="credit" <?php echo $filter_transaction_type === 'credit' ? 'selected' : ''; ?>>Due</option>
                    </select>
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-teal-500 focus:border-teal-500">
                        <option value="">All Categories</option>
                        <?php 
                        $categories->data_seek(0);
                        while ($row = $categories->fetch_assoc()): 
                        ?>
                            <option value="<?php echo htmlspecialchars($row['category']); ?>" <?php echo $filter_category === $row['category'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($row['category']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                </div>
                <!-- Buttons -->
                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 bg-teal-600 text-white px-4 py-2 rounded-md hover:bg-teal-700 transition font-medium">
                        <i class="fas fa-filter mr-2"></i> Filter
                    </button>
                    <a href="employee_payments.php" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition font-medium text-center">
                        <i class="fas fa-times mr-2"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Payments Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Employee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if ($payments->num_rows > 0): ?>
                            <?php while ($row = $payments->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="font-medium"><?php echo htmlspecialchars($row['employee_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($row['category']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($row['transaction_type'] === 'debit'): ?>
                                            <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                                                <i class="fas fa-arrow-down mr-1"></i> Paid
                                            </span>
                                        <?php else: ?>
                                            <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full">
                                                <i class="fas fa-arrow-up mr-1"></i> Due
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold">
                                        <?php if ($row['transaction_type'] === 'debit'): ?>
                                            <span class="text-green-600">-₹<?php echo number_format($row['amount'], 2); ?></span>
                                        <?php else: ?>
                                            <span class="text-red-600">₹<?php echo number_format($row['amount'], 2); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-3xl mb-2 block opacity-50"></i>
                                    No payment records found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="bg-white px-6 py-4 border-t border-gray-200 flex items-center justify-center gap-2 flex-wrap">
                    <!-- First Page -->
                    <?php if ($page > 1): ?>
                        <a href="?page=1<?php echo $filter_url; ?>" class="px-3 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">
                            <i class="fas fa-chevron-left mr-1"></i> First
                        </a>
                    <?php else: ?>
                        <span class="px-3 py-2 bg-gray-100 text-gray-400 rounded cursor-not-allowed">
                            <i class="fas fa-chevron-left mr-1"></i> First
                        </span>
                    <?php endif; ?>

                    <!-- Previous Page -->
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $filter_url; ?>" class="px-3 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php else: ?>
                        <span class="px-3 py-2 bg-gray-100 text-gray-400 rounded cursor-not-allowed">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                    <?php endif; ?>

                    <!-- Page Info -->
                    <span class="px-4 py-2 text-gray-700 font-medium">
                        Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                    </span>

                    <!-- Next Page -->
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $filter_url; ?>" class="px-3 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="px-3 py-2 bg-gray-100 text-gray-400 rounded cursor-not-allowed">
                            <i class="fas fa-chevron-right"></i>
                        </span>
                    <?php endif; ?>

                    <!-- Last Page -->
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $total_pages; ?><?php echo $filter_url; ?>" class="px-3 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">
                            Last <i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    <?php else: ?>
                        <span class="px-3 py-2 bg-gray-100 text-gray-400 rounded cursor-not-allowed">
                            Last <i class="fas fa-chevron-right ml-1"></i>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Records Summary -->
        <div class="mt-4 text-gray-600 text-sm">
            Showing <?php echo $total_records > 0 ? (($page - 1) * $per_page) + 1 : 0; ?> to <?php echo min($page * $per_page, $total_records); ?> of <?php echo $total_records; ?> records
        </div>
    </div>
</body>
</html>
