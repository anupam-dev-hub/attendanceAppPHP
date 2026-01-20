<?php
// org/expenses.php
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

// Force navbar to show all links
$force_show_nav = true;

// Ensure expenses table exists
$conn->query("CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    org_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    expense_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_org_date (org_id, expense_date),
    INDEX idx_org_category (org_id, category)
)");

$success_message = isset($_GET['success_message']) ? $_GET['success_message'] : '';
$error_message = isset($_GET['error_message']) ? $_GET['error_message'] : '';

// Handle new expense submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $expense_date = $_POST['expense_date'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    
    if ($title === '' || $category === '' || $expense_date === '' || $amount <= 0) {
        $error_message = 'Please provide title, category, date, and a positive amount.';
    } else {
        $stmt = $conn->prepare("INSERT INTO expenses (org_id, title, category, amount, expense_date, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('issdss', $org_id, $title, $category, $amount, $expense_date, $notes);
        if ($stmt->execute()) {
            $stmt->close();
            header('Location: expenses.php?success_message=' . urlencode('Expense added successfully.'));
            exit;
        } else {
            $stmt->close();
            header('Location: expenses.php?error_message=' . urlencode('Failed to add expense. Please try again.'));
            exit;
        }
    }
}

// Filters
$filter_from_date = $_GET['from_date'] ?? '';
$filter_to_date = $_GET['to_date'] ?? '';
$filter_category = $_GET['category'] ?? '';
$filter_search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = max(1, intval($_GET['page'] ?? 1));

// Get expense statistics
$statsStmt = $conn->prepare("SELECT 
    COUNT(*) as total_expenses,
    SUM(amount) as total_amount,
    COUNT(DISTINCT category) as total_categories
    FROM expenses WHERE org_id = ?");
$statsStmt->bind_param("i", $org_id);
$statsStmt->execute();
$statsResult = $statsStmt->get_result();
$stats = $statsResult->fetch_assoc();
$total_expense_count = $stats['total_expenses'];
$total_expense_amount = $stats['total_amount'] ?? 0;
$total_categories = $stats['total_categories'];
$statsStmt->close();

// Get this month's expenses
$thisMonthStmt = $conn->prepare("SELECT SUM(amount) as monthly_total 
    FROM expenses 
    WHERE org_id = ? 
    AND YEAR(expense_date) = YEAR(CURDATE()) 
    AND MONTH(expense_date) = MONTH(CURDATE())");
$thisMonthStmt->bind_param("i", $org_id);
$thisMonthStmt->execute();
$thisMonthResult = $thisMonthStmt->get_result();
$thisMonthData = $thisMonthResult->fetch_assoc();
$this_month_expenses = $thisMonthData['monthly_total'] ?? 0;
$thisMonthStmt->close();

// Get category list
$categories = [];
$catResult = $conn->prepare("SELECT DISTINCT category FROM expenses WHERE org_id = ? ORDER BY category ASC");
$catResult->bind_param('i', $org_id);
$catResult->execute();
$catRows = $catResult->get_result();
while ($row = $catRows->fetch_assoc()) {
    $categories[] = $row['category'];
}
$catResult->close();

// Build expense list query with filters
$conditions = ['org_id = ?'];
$params = [$org_id];
$types = 'i';

if (!empty($filter_from_date)) {
    $conditions[] = 'DATE(expense_date) >= ?';
    $types .= 's';
    $params[] = $filter_from_date;
}

if (!empty($filter_to_date)) {
    $conditions[] = 'DATE(expense_date) <= ?';
    $types .= 's';
    $params[] = $filter_to_date;
}

if (!empty($filter_category)) {
    $conditions[] = 'category = ?';
    $types .= 's';
    $params[] = $filter_category;
}

if (!empty($filter_search)) {
    $conditions[] = '(title LIKE ? OR category LIKE ? OR notes LIKE ?)';
    $types .= 'sss';
    $search_param = "%{$filter_search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$whereClause = implode(' AND ', $conditions);

// Get statistics
$stats_query = "SELECT 
    COALESCE(SUM(amount), 0) as total_amount,
    COUNT(*) as total_records,
    COUNT(DISTINCT category) as total_categories
    FROM expenses WHERE $whereClause";
$statsStmt = $conn->prepare($stats_query);
$statsStmt->bind_param($types, ...$params);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM expenses WHERE $whereClause";
$countStmt = $conn->prepare($count_query);
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$count_result = $countStmt->get_result()->fetch_assoc();
$total_records = $count_result['total'];

// Pagination
$per_page = 50;
$total_pages = ceil($total_records / $per_page);
$offset = ($page - 1) * $per_page;

// Get expense records
$data_query = "SELECT id, title, category, amount, expense_date, notes
              FROM expenses
              WHERE $whereClause
              ORDER BY expense_date DESC
              LIMIT ? OFFSET ?";
$dataStmt = $conn->prepare($data_query);
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';
$dataStmt->bind_param($types, ...$params);
$dataStmt->execute();
$expenses_result = $dataStmt->get_result();

// Build filter URL for pagination
$filter_params = [];
if (!empty($filter_from_date)) $filter_params[] = "from_date=" . urlencode($filter_from_date);
if (!empty($filter_to_date)) $filter_params[] = "to_date=" . urlencode($filter_to_date);
if (!empty($filter_category)) $filter_params[] = "category=" . urlencode($filter_category);
if (!empty($filter_search)) $filter_params[] = "search=" . urlencode($filter_search);
$filter_url = !empty($filter_params) ? "&" . implode("&", $filter_params) : "";

// Current month total (for summary)
$current_month = date('n');
$curr_year = date('Y');
$period_conditions = ['org_id = ?', 'YEAR(expense_date) = ?', 'MONTH(expense_date) = ?'];
$period_params = [$org_id, $curr_year, $current_month];
$period_where = implode(' AND ', $period_conditions);
$currStmt = $conn->prepare("SELECT SUM(amount) as total_amount FROM expenses WHERE $period_where");
$currStmt->bind_param('iii', ...$period_params);
$currStmt->execute();
$curr_res = $currStmt->get_result();
$current_month_amount = ($row = $curr_res->fetch_assoc()) ? floatval($row['total_amount']) : 0;
$currStmt->close();

// Last 6 months trend (amount per month)
$trend = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('n', strtotime("-$i months"));
    $y = date('Y', strtotime("-$i months"));
    $trend_conditions = ['org_id = ?', 'YEAR(expense_date) = ?', 'MONTH(expense_date) = ?'];
    $trend_types = 'iii';
    $trend_params = [$org_id, $y, $m];
    if (!empty($filter_category)) {
        $trend_conditions[] = 'category = ?';
        $trend_types .= 's';
        $trend_params[] = $filter_category;
    }
    $trend_where = implode(' AND ', $trend_conditions);
    $tStmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) as amt FROM expenses WHERE $trend_where");
    $tStmt->bind_param($trend_types, ...$trend_params);
    $tStmt->execute();
    $tRes = $tStmt->get_result()->fetch_assoc();
    $trend[] = [
        'label' => date('M Y', mktime(0,0,0,$m,1,$y)),
        'amount' => floatval($tRes['amt'])
    ];
    $tStmt->close();
}

// Top categories (respects filters; defaults to last 90 days if no date filters)
$top_conditions = ['org_id = ?'];
$top_types = 'i';
$top_params = [$org_id];

if (!empty($filter_from_date)) {
    $top_conditions[] = 'DATE(expense_date) >= ?';
    $top_types .= 's';
    $top_params[] = $filter_from_date;
}
if (!empty($filter_to_date)) {
    $top_conditions[] = 'DATE(expense_date) <= ?';
    $top_types .= 's';
    $top_params[] = $filter_to_date;
}
if (!empty($filter_category)) {
    $top_conditions[] = 'category = ?';
    $top_types .= 's';
    $top_params[] = $filter_category;
}
if (!empty($filter_search)) {
    $top_conditions[] = '(title LIKE ? OR category LIKE ? OR notes LIKE ?)';
    $top_types .= 'sss';
    $search_param = isset($search_param) ? $search_param : "%{$filter_search}%";
    $top_params[] = $search_param;
    $top_params[] = $search_param;
    $top_params[] = $search_param;
}
// If no explicit date filter, default to last 90 days window
if (empty($filter_from_date) && empty($filter_to_date)) {
    $top_conditions[] = 'expense_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)';
}

$top_where = implode(' AND ', $top_conditions);
$topCatStmt = $conn->prepare("SELECT category, SUM(amount) as total FROM expenses WHERE $top_where GROUP BY category ORDER BY total DESC");
$topCatStmt->bind_param($top_types, ...$top_params);
$topCatStmt->execute();
$top_categories = $topCatStmt->get_result();
$topCatStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/ux-improvements.css">
    <style>
        body {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 antialiased">
    <?php include 'navbar.php'; ?>
    <?php include 'modals/expense_history_modal.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Expenses</h2>
                <p class="mt-2 text-sm text-gray-600">Track organization expenses and view summaries.</p>
            </div>
            <button onclick="openAddExpenseModal()" class="bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 text-white font-semibold px-6 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                <i class="fas fa-plus mr-2"></i> Add Expense
            </button>
        </div>

        <?php if ($success_message): ?>
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-4 rounded-xl shadow-lg mb-6 flex items-center">
                <i class="fas fa-check-circle text-2xl mr-3"></i>
                <span class="font-semibold"><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="bg-gradient-to-r from-red-500 to-red-600 text-white px-6 py-4 rounded-xl shadow-lg mb-6 flex items-center">
                <i class="fas fa-exclamation-circle text-2xl mr-3"></i>
                <span class="font-semibold"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Expenses Amount -->
            <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden transform transition hover:scale-105">
                <div class="absolute top-0 right-0 w-20 h-20 bg-white opacity-10 rounded-full -mr-10 -mt-10"></div>
                <div class="relative z-10">
                    <p class="text-red-100 text-sm font-medium uppercase tracking-wide mb-2">Total Expenses</p>
                    <h3 class="text-4xl font-bold">₹<?php echo number_format($total_expense_amount, 0); ?></h3>
                    <div class="mt-4">
                        <i class="fas fa-money-bill-wave text-3xl opacity-50"></i>
                    </div>
                </div>
            </div>

            <!-- Total Records -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden transform transition hover:scale-105">
                <div class="absolute top-0 right-0 w-20 h-20 bg-white opacity-10 rounded-full -mr-10 -mt-10"></div>
                <div class="relative z-10">
                    <p class="text-blue-100 text-sm font-medium uppercase tracking-wide mb-2">Total Records</p>
                    <h3 class="text-4xl font-bold"><?php echo number_format($total_expense_count); ?></h3>
                    <div class="mt-4">
                        <i class="fas fa-receipt text-3xl opacity-50"></i>
                    </div>
                </div>
            </div>

            <!-- Total Categories -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden transform transition hover:scale-105">
                <div class="absolute top-0 right-0 w-20 h-20 bg-white opacity-10 rounded-full -mr-10 -mt-10"></div>
                <div class="relative z-10">
                    <p class="text-purple-100 text-sm font-medium uppercase tracking-wide mb-2">Categories</p>
                    <h3 class="text-4xl font-bold"><?php echo number_format($total_categories); ?></h3>
                    <div class="mt-4">
                        <i class="fas fa-tags text-3xl opacity-50"></i>
                    </div>
                </div>
            </div>

            <!-- This Month -->
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden transform transition hover:scale-105">
                <div class="absolute top-0 right-0 w-20 h-20 bg-white opacity-10 rounded-full -mr-10 -mt-10"></div>
                <div class="relative z-10">
                    <p class="text-orange-100 text-sm font-medium uppercase tracking-wide mb-2">This Month</p>
                    <h3 class="text-4xl font-bold">₹<?php echo number_format($this_month_expenses, 0); ?></h3>
                    <p class="text-orange-100 text-xs mt-1"><?php echo date('F Y'); ?></p>
                    <div class="mt-2">
                        <i class="fas fa-calendar-alt text-3xl opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expense History Section -->
        <div class="mt-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Expense History</h2>
            
            <!-- Category Breakdown Cards -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-chart-pie mr-2"></i>Expense Categories</h3>
                <?php if ($top_categories->num_rows > 0): ?>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                        <?php while ($cat = $top_categories->fetch_assoc()): ?>
                            <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-4 cursor-pointer hover:shadow-lg transition transform hover:-translate-y-1" onclick="openExpenseHistoryModal('<?php echo addslashes(htmlspecialchars($cat['category'])); ?>')">
                                <p class="text-sm text-gray-700 font-semibold mb-2 truncate"><?php echo htmlspecialchars($cat['category']); ?></p>
                                <p class="text-xl font-bold text-orange-600">₹<?php echo number_format($cat['total'], 2); ?></p>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-gray-500 py-8">
                        <i class="fas fa-inbox text-3xl mb-2 block opacity-30"></i>
                        No expense categories found. Add an expense to get started.
                    </p>
                <?php endif; ?>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Filters</h3>
                <form method="GET" class="space-y-4">
                    <!-- Global Search -->
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <div class="relative">
                            <input
                                type="text"
                                name="search"
                                value="<?php echo htmlspecialchars($filter_search); ?>"
                                placeholder="Search by title, category, or notes..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Date From -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                            <input type="date" name="from_date" value="<?php echo htmlspecialchars($filter_from_date); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-teal-500 focus:border-teal-500">
                        </div>

                        <!-- Date To -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                            <input type="date" name="to_date" value="<?php echo htmlspecialchars($filter_to_date); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-teal-500 focus:border-teal-500">
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-teal-500 focus:border-teal-500">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $filter_category === $cat ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 bg-teal-600 text-white px-4 py-2 rounded-md hover:bg-teal-700 transition font-medium">
                            <i class="fas fa-filter mr-2"></i> Filter
                        </button>
                        <a href="expenses.php" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition font-medium text-center">
                            <i class="fas fa-times mr-2"></i> Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Expenses Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Notes</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if ($expenses_result->num_rows > 0): ?>
                                <?php while ($exp = $expenses_result->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo date('M d, Y', strtotime($exp['expense_date'])); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <div class="font-medium"><?php echo htmlspecialchars($exp['title']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-3 py-1 bg-orange-100 text-orange-800 text-xs font-medium rounded-full">
                                                <?php echo htmlspecialchars($exp['category']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            <?php echo $exp['notes'] ? htmlspecialchars($exp['notes']) : '-'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold">
                                            <span class="text-red-600">₹<?php echo number_format($exp['amount'], 2); ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                        <i class="fas fa-inbox text-3xl mb-2 block opacity-50"></i>
                                        No expense records found
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
    </div>

    <!-- Add Expense Modal -->
    <div id="addExpenseModal" class="fixed z-50 inset-0 overflow-y-auto opacity-0 pointer-events-none transition-opacity duration-200" style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeAddExpenseModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-orange-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Add Expense</h3>
                            <form id="addExpenseForm" method="POST" class="space-y-4 mt-4" onsubmit="handleAddExpenseSubmit(event)">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                                        <input type="text" name="title" class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-teal-500" required>
                                    </div>
                                    <div class="relative">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                        <input 
                                            type="text" 
                                            id="categoryInput" 
                                            name="category" 
                                            class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-teal-500 focus:outline-none" 
                                            placeholder="e.g., Rent, Utilities" 
                                            autocomplete="off"
                                            oninput="filterCategories()"
                                            required>
                                        <div id="categorySuggestions" class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-300 rounded shadow-lg z-10 max-h-48 overflow-y-auto hidden">
                                        </div>
                                        <?php 
                                            $categories_result = $conn->prepare("SELECT DISTINCT category FROM expenses WHERE org_id = ? ORDER BY category ASC");
                                            $categories_result->bind_param('i', $org_id);
                                            $categories_result->execute();
                                            $categories_rows = $categories_result->get_result();
                                            $all_categories = [];
                                            while ($row = $categories_rows->fetch_assoc()): 
                                                $all_categories[] = $row['category'];
                                            endwhile; 
                                            $categories_result->close();
                                        ?>
                                        <script>
                                            const allCategories = <?php echo json_encode($all_categories); ?>;
                                        </script>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount (₹)</label>
                                        <input type="number" step="0.01" min="0" name="amount" class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-teal-500" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                                        <input type="date" name="expense_date" class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-teal-500" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                        <textarea name="notes" rows="2" class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-teal-500" placeholder="Optional"></textarea>
                                    </div>
                                </div>
                                <input type="hidden" name="add_expense" value="1">
                            </form>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-3">
                    <button type="button" onclick="document.getElementById('addExpenseForm').submit()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-teal-600 text-base font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 sm:w-auto sm:text-sm">
                        Save Expense
                    </button>
                    <button type="button" onclick="closeAddExpenseModal()" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Autocomplete functionality
        let selectedIndex = -1;

        function filterCategories() {
            const input = document.getElementById('categoryInput');
            const suggestionsDiv = document.getElementById('categorySuggestions');
            const value = input.value.toLowerCase().trim();
            
            selectedIndex = -1;

            if (!value) {
                suggestionsDiv.classList.add('hidden');
                return;
            }

            const filtered = allCategories.filter(cat => 
                cat.toLowerCase().includes(value)
            );

            if (filtered.length === 0) {
                suggestionsDiv.classList.add('hidden');
                return;
            }

            suggestionsDiv.innerHTML = '';
            filtered.forEach((category, index) => {
                const div = document.createElement('div');
                div.className = 'px-4 py-2 cursor-pointer hover:bg-teal-100 transition flex items-center';
                div.innerHTML = `
                    <i class="fas fa-tag text-gray-400 mr-2 text-sm"></i>
                    <span>${escapeHtml(category)}</span>
                `;
                div.dataset.index = index;
                div.onclick = () => selectCategory(category);
                suggestionsDiv.appendChild(div);
            });

            suggestionsDiv.classList.remove('hidden');
        }

        function selectCategory(category) {
            document.getElementById('categoryInput').value = category;
            document.getElementById('categorySuggestions').classList.add('hidden');
            selectedIndex = -1;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Keyboard navigation
        document.getElementById('categoryInput').addEventListener('keydown', function(e) {
            const suggestionsDiv = document.getElementById('categorySuggestions');
            const items = suggestionsDiv.querySelectorAll('div[data-index]');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                updateSelection(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, -1);
                updateSelection(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (selectedIndex >= 0 && items[selectedIndex]) {
                    items[selectedIndex].click();
                } else if (this.value.trim()) {
                    document.getElementById('addExpenseForm').submit();
                }
            } else if (e.key === 'Escape') {
                suggestionsDiv.classList.add('hidden');
            }
        });

        function updateSelection(items) {
            items.forEach((item, index) => {
                if (index === selectedIndex) {
                    item.classList.add('bg-teal-100');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('bg-teal-100');
                }
            });
        }

        // Close suggestions when clicking outside
        document.addEventListener('click', function(e) {
            const input = document.getElementById('categoryInput');
            const suggestionsDiv = document.getElementById('categorySuggestions');
            if (!input.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                suggestionsDiv.classList.add('hidden');
            }
        });

        function openAddExpenseModal() {
            const modal = document.getElementById('addExpenseModal');
            modal.style.display = 'block';
            setTimeout(() => {
                modal.style.opacity = '1';
                modal.style.pointerEvents = 'auto';
                document.getElementById('categoryInput').focus();
            }, 10);
        }

        function closeAddExpenseModal() {
            const modal = document.getElementById('addExpenseModal');
            modal.style.opacity = '0';
            modal.style.pointerEvents = 'none';
            document.getElementById('categorySuggestions').classList.add('hidden');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 200);
        }

        // Close modal when clicking outside
        document.getElementById('addExpenseModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddExpenseModal();
            }
        });

        // Close modal when pressing Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAddExpenseModal();
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
