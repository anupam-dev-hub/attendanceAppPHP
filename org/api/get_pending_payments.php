<?php
// org/api/get_pending_payments.php
// Fetch pending payments for a student, grouped by category
session_start();
require '../../config.php';
require '../../functions.php';

header('Content-Type: application/json');

if (!isOrg()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
if ($student_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
    exit;
}

// Ensure student belongs to this org
$check = $conn->query("SELECT id FROM students WHERE id = $student_id AND org_id = {$_SESSION['user_id']}");
if ($check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

// Fetch all transactions for the student to compute net by category (credits = negative due, debits = positive payments)
$sql = "SELECT id, amount, category, transaction_type, description, created_at
        FROM student_payments
        WHERE student_id = $student_id
        ORDER BY category, created_at DESC";

$result = $conn->query($sql);

$pending_by_category = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $category = $row['category'] ?? 'General';
        $type = strtolower($row['transaction_type'] ?? '');
        $raw_amount = (float)$row['amount'];

        // Normalize signed amount: credits negative, debits positive
        $amount = ($type === 'credit') ? -abs($raw_amount) : abs($raw_amount);
        
        // Extract fee type (before " - " if present)
        $fee_type = $category;
        if (strpos($category, ' - ') !== false) {
            $fee_type = substr($category, 0, strpos($category, ' - '));
        }
        
        if (!isset($pending_by_category[$fee_type])) {
            $pending_by_category[$fee_type] = [
                'fee_type' => $fee_type,
                'net_amount' => 0.0,
                'items' => []
            ];
        }
        
        $pending_by_category[$fee_type]['net_amount'] += $amount;
        $pending_by_category[$fee_type]['items'][] = [
            'id' => (int)$row['id'],
            'amount' => $amount,
            'category' => $category,
            'type' => $type,
            'description' => $row['description'] ?? '',
            'date' => date('M Y', strtotime($row['created_at'])),
            'full_date' => $row['created_at']
        ];
    }
}

// Group pending items by month for better display
foreach ($pending_by_category as $fee_type => &$category_data) {
    $months_pending = [];
    foreach ($category_data['items'] as $item) {
        if ($item['type'] === 'credit') {
            // Extract month from category if present (e.g., "Monthly Fee - December 2025")
            if (preg_match('/ - ([A-Za-z]+ \d{4})$/', $item['category'], $matches)) {
                $month_year = $matches[1];
                if (!isset($months_pending[$month_year])) {
                    $months_pending[$month_year] = [
                        'month' => $month_year,
                        'amount' => 0,
                        'paid' => 0,
                        'balance' => 0
                    ];
                }
                $months_pending[$month_year]['amount'] += abs($item['amount']);
            }
        } else if ($item['type'] === 'debit') {
            // Track payments against months
            if (preg_match('/ - ([A-Za-z]+ \d{4})$/', $item['category'], $matches)) {
                $month_year = $matches[1];
                if (!isset($months_pending[$month_year])) {
                    $months_pending[$month_year] = [
                        'month' => $month_year,
                        'amount' => 0,
                        'paid' => 0,
                        'balance' => 0
                    ];
                }
                $months_pending[$month_year]['paid'] += abs($item['amount']);
            }
        }
    }
    
    // Calculate balance for each month and filter only unpaid ones
    $unpaid_months = [];
    foreach ($months_pending as $month => &$data) {
        $data['balance'] = $data['amount'] - $data['paid'];
        if ($data['balance'] > 0) {
            $unpaid_months[] = $data;
        }
    }
    
    // Sort months by date (newest first)
    usort($unpaid_months, function($a, $b) {
        return strtotime($b['month']) - strtotime($a['month']);
    });
    
    $category_data['unpaid_months'] = $unpaid_months;
}

// Prepare only categories that still owe money (net negative)
$pending_list = [];
$total_pending = 0.0;

foreach ($pending_by_category as $cat) {
    if ($cat['net_amount'] < 0) {
        $pending_list[] = $cat;
        $total_pending += $cat['net_amount'];
    }
}

// Sort by amount owed (most negative first)
usort($pending_list, function($a, $b) {
    return $a['net_amount'] <=> $b['net_amount'];
});

echo json_encode([
    'success' => true,
    'pending_payments' => $pending_list,
    // total_pending is negative; helpful to show outstanding absolute value in UI
    'total_pending' => round($total_pending, 2),
    'count' => count($pending_list)
]);
exit;
?>
