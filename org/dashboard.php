<?php
// org/dashboard.php
session_start();
require '../config.php';
require '../functions.php';

if (!isOrg()) {
    redirect('index.php');
}

$org_id = $_SESSION['user_id'];
$is_active = isSubscribed($org_id);

// Fetch subscription details for display if active
$subscription = null;
if ($is_active) {
    $subStmt = $conn->prepare("SELECT * FROM subscriptions WHERE org_id = ? AND status = 'active' AND to_date > NOW() ORDER BY to_date DESC LIMIT 1");
    $subStmt->bind_param("i", $org_id);
    $subStmt->execute();
    $subResult = $subStmt->get_result();
    $subscription = $subResult->fetch_assoc();
}

// Fetch statistics
$total_students = 0;
$active_students = 0;
$total_employees = 0;
$active_employees = 0;
$total_revenue = 0;
$total_expenses = 0;
$today_attendance = 0;

if ($is_active) {
    // Student stats
    $stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(is_active) as active FROM students WHERE org_id = ?");
    $stmt->bind_param('i', $org_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $total_students = intval($res['total'] ?? 0);
    $active_students = intval($res['active'] ?? 0);
    $stmt->close();

    // Employee stats
    $stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(is_active) as active FROM employees WHERE org_id = ?");
    $stmt->bind_param('i', $org_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $total_employees = intval($res['total'] ?? 0);
    $active_employees = intval($res['active'] ?? 0);
    $stmt->close();

    // Revenue (student payments - debit)
    $stmt = $conn->prepare("SELECT SUM(amount) as total FROM student_payments sp JOIN students s ON sp.student_id = s.id WHERE s.org_id = ? AND sp.transaction_type = 'debit'");
    $stmt->bind_param('i', $org_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $total_revenue = floatval($res['total'] ?? 0);
    $stmt->close();

    // Expenses
    $stmt = $conn->prepare("SELECT SUM(amount) as total FROM expenses WHERE org_id = ?");
    $stmt->bind_param('i', $org_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $total_expenses = floatval($res['total'] ?? 0);
    $stmt->close();

    // Today's attendance
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM attendance a JOIN students s ON a.student_id = s.id WHERE s.org_id = ? AND a.date = ? AND (a.in_time IS NOT NULL OR a.out_time IS NOT NULL)");
    $stmt->bind_param('is', $org_id, $today);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $student_attendance = intval($res['total'] ?? 0);
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM employee_attendance ea JOIN employees e ON ea.employee_id = e.id WHERE e.org_id = ? AND ea.date = ? AND (ea.in_time IS NOT NULL OR ea.out_time IS NOT NULL)");
    $stmt->bind_param('is', $org_id, $today);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $employee_attendance = intval($res['total'] ?? 0);
    $stmt->close();

    $today_attendance = $student_attendance + $employee_attendance;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/ux-improvements.css">
    <style>
        body { padding-top: 140px; }
        .gradient-card {
            background: linear-gradient(135deg, var(--tw-gradient-stops));
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .gradient-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .stat-card {
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }
        .quick-action-card {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .quick-action-card:hover {
            border-color: #0d9488;
            transform: scale(1.02);
        }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <?php include 'navbar.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <!-- Header Section -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900">
                <i class="fas fa-chart-line text-teal-600 mr-3"></i>Dashboard
            </h1>
            <p class="mt-2 text-lg text-gray-600">Welcome back! Here's what's happening with your organization today.</p>
        </div>
        
        <?php if (!$is_active): ?>
            <!-- Subscription Alert -->
            <div class="bg-gradient-to-r from-red-500 to-red-600 text-white px-6 py-5 rounded-xl shadow-lg mb-8 flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-3xl mr-4"></i>
                    <div>
                        <h3 class="text-xl font-bold">Subscription Required</h3>
                        <p class="text-red-100 mt-1">Activate your subscription to unlock all features and manage your organization.</p>
                    </div>
                </div>
                <a href="subscribe.php" class="bg-white text-red-600 hover:bg-red-50 font-bold py-3 px-6 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2">
                    <i class="fas fa-rocket mr-2"></i>Subscribe Now
                </a>
            </div>
            
            <!-- Quick Action -->
            <div class="bg-white rounded-xl shadow-lg p-8 text-center">
                <i class="fas fa-crown text-yellow-500 text-6xl mb-4"></i>
                <h3 class="text-2xl font-bold text-gray-800 mb-3">Get Started Today</h3>
                <p class="text-gray-600 mb-6 max-w-2xl mx-auto">Choose a subscription plan that fits your organization's needs and start managing students, employees, and finances efficiently.</p>
                <a href="subscribe.php" class="inline-block bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 text-white font-bold py-3 px-8 rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                    <i class="fas fa-arrow-right mr-2"></i>View Plans
                </a>
            </div>
        <?php else: ?>
            <!-- Subscription Status Banner -->
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-4 rounded-xl shadow-lg mb-8 flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-2xl mr-3"></i>
                    <span class="font-semibold">Subscription Active until <strong><?php echo date('M d, Y', strtotime($subscription['to_date'])); ?></strong></span>
                </div>
                <a href="subscribe.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 px-5 py-2.5 rounded-lg transition-all duration-200 font-semibold focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2">
                    <i class="fas fa-cog mr-2"></i>Manage
                </a>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Students Card -->
                <div class="gradient-card from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white stat-card transform transition-all duration-200 hover:scale-105 hover:shadow-2xl">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-blue-100 text-sm font-medium uppercase tracking-wide">Students</p>
                            <h3 class="text-4xl font-bold mt-2"><?php echo number_format($active_students); ?></h3>
                            <p class="text-blue-100 text-xs mt-1">of <?php echo number_format($total_students); ?> total</p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-full p-4">
                            <i class="fas fa-user-graduate text-3xl"></i>
                        </div>
                    </div>
                    <a href="students.php" class="text-white text-sm font-semibold hover:text-blue-100 flex items-center group transition-colors">
                        View All <i class="fas fa-arrow-right ml-2 text-xs group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>

                <!-- Employees Card -->
                <div class="gradient-card from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white stat-card transform transition-all duration-200 hover:scale-105 hover:shadow-2xl">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-purple-100 text-sm font-medium uppercase tracking-wide">Employees</p>
                            <h3 class="text-4xl font-bold mt-2"><?php echo number_format($active_employees); ?></h3>
                            <p class="text-purple-100 text-xs mt-1">of <?php echo number_format($total_employees); ?> total</p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-full p-4">
                            <i class="fas fa-user-tie text-3xl"></i>
                        </div>
                    </div>
                    <a href="employees.php" class="text-white text-sm font-semibold hover:text-purple-100 flex items-center group transition-colors">
                        View All <i class="fas fa-arrow-right ml-2 text-xs group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>

                <!-- Revenue Card -->
                <div class="gradient-card from-green-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white stat-card">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-green-100 text-sm font-medium uppercase tracking-wide">Total Revenue</p>
                            <h3 class="text-4xl font-bold mt-2">₹<?php echo number_format($total_revenue); ?></h3>
                            <p class="text-green-100 text-xs mt-1">Student payments</p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-full p-4">
                            <i class="fas fa-rupee-sign text-3xl"></i>
                        </div>
                    </div>
                    <a href="student_payments.php" class="text-white text-sm font-medium hover:underline flex items-center">
                        View Details <i class="fas fa-arrow-right ml-2 text-xs"></i>
                    </a>
                </div>

                <!-- Attendance Card -->
                <div class="gradient-card from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white stat-card">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-orange-100 text-sm font-medium uppercase tracking-wide">Today's Attendance</p>
                            <h3 class="text-4xl font-bold mt-2"><?php echo number_format($today_attendance); ?></h3>
                            <p class="text-orange-100 text-xs mt-1">Present today</p>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-full p-4">
                            <i class="fas fa-calendar-check text-3xl"></i>
                        </div>
                    </div>
                    <a href="attendance.php" class="text-white text-sm font-medium hover:underline flex items-center">
                        View Details <i class="fas fa-arrow-right ml-2 text-xs"></i>
                    </a>
                </div>
            </div>

            <!-- Quick Actions Grid -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-bolt text-yellow-500 mr-2"></i>Quick Actions
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Finance Overview -->
                    <a href="finance_overview.php" class="quick-action-card bg-white rounded-xl shadow-md p-6 hover:shadow-xl">
                        <div class="flex items-start">
                            <div class="bg-teal-100 rounded-lg p-3 mr-4">
                                <i class="fas fa-chart-pie text-teal-600 text-2xl"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-900 mb-1">Finance Overview</h3>
                                <p class="text-gray-600 text-sm">View financial reports and analytics</p>
                                <div class="mt-3 text-teal-600 font-medium text-sm flex items-center">
                                    Go to Finance <i class="fas fa-arrow-right ml-2"></i>
                                </div>
                            </div>
                        </div>
                    </a>

                    <!-- Manage Fees -->
                    <a href="manage_fees.php" class="quick-action-card bg-white rounded-xl shadow-md p-6 hover:shadow-xl">
                        <div class="flex items-start">
                            <div class="bg-blue-100 rounded-lg p-3 mr-4">
                                <i class="fas fa-money-bill-wave text-blue-600 text-2xl"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-900 mb-1">Manage Fees</h3>
                                <p class="text-gray-600 text-sm">Configure fee structure and categories</p>
                                <div class="mt-3 text-blue-600 font-medium text-sm flex items-center">
                                    Manage Fees <i class="fas fa-arrow-right ml-2"></i>
                                </div>
                            </div>
                        </div>
                    </a>

                    <!-- Expenses -->
                    <a href="expenses.php" class="quick-action-card bg-white rounded-xl shadow-md p-6 hover:shadow-xl">
                        <div class="flex items-start">
                            <div class="bg-red-100 rounded-lg p-3 mr-4">
                                <i class="fas fa-receipt text-red-600 text-2xl"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-900 mb-1">Expenses</h3>
                                <p class="text-gray-600 text-sm">Track and manage organization expenses</p>
                                <div class="mt-3 text-red-600 font-medium text-sm flex items-center">
                                    View Expenses <i class="fas fa-arrow-right ml-2"></i>
                                </div>
                            </div>
                        </div>
                    </a>

                    <!-- Attendance -->
                    <a href="attendance.php" class="quick-action-card bg-white rounded-xl shadow-md p-6 hover:shadow-xl">
                        <div class="flex items-start">
                            <div class="bg-purple-100 rounded-lg p-3 mr-4">
                                <i class="fas fa-calendar-alt text-purple-600 text-2xl"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-900 mb-1">Attendance</h3>
                                <p class="text-gray-600 text-sm">View daily attendance records</p>
                                <div class="mt-3 text-purple-600 font-medium text-sm flex items-center">
                                    View Attendance <i class="fas fa-arrow-right ml-2"></i>
                                </div>
                            </div>
                        </div>
                    </a>

                    <!-- App Settings -->
                    <a href="qr_token.php" class="quick-action-card bg-white rounded-xl shadow-md p-6 hover:shadow-xl">
                        <div class="flex items-start">
                            <div class="bg-indigo-100 rounded-lg p-3 mr-4">
                                <i class="fas fa-qrcode text-indigo-600 text-2xl"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-900 mb-1">App Token</h3>
                                <p class="text-gray-600 text-sm">Configure mobile app connection</p>
                                <div class="mt-3 text-indigo-600 font-medium text-sm flex items-center">
                                    Generate Token <i class="fas fa-arrow-right ml-2"></i>
                                </div>
                            </div>
                        </div>
                    </a>

                    <!-- Settings -->
                    <a href="settings.php" class="quick-action-card bg-white rounded-xl shadow-md p-6 hover:shadow-xl">
                        <div class="flex items-start">
                            <div class="bg-gray-100 rounded-lg p-3 mr-4">
                                <i class="fas fa-cog text-gray-600 text-2xl"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-900 mb-1">Settings</h3>
                                <p class="text-gray-600 text-sm">Configure form fields and preferences</p>
                                <div class="mt-3 text-gray-600 font-medium text-sm flex items-center">
                                    Open Settings <i class="fas fa-arrow-right ml-2"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Revenue vs Expenses -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-chart-bar text-teal-600 mr-3"></i>Financial Summary
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg">
                            <div class="flex items-center">
                                <div class="bg-green-500 rounded-full p-2 mr-3">
                                    <i class="fas fa-arrow-up text-white"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 font-medium">Total Revenue</p>
                                    <p class="text-2xl font-bold text-gray-900">₹<?php echo number_format($total_revenue, 2); ?></p>
                                </div>
                            </div>
                            <i class="fas fa-coins text-green-500 text-3xl"></i>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-red-50 rounded-lg">
                            <div class="flex items-center">
                                <div class="bg-red-500 rounded-full p-2 mr-3">
                                    <i class="fas fa-arrow-down text-white"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 font-medium">Total Expenses</p>
                                    <p class="text-2xl font-bold text-gray-900">₹<?php echo number_format($total_expenses, 2); ?></p>
                                </div>
                            </div>
                            <i class="fas fa-wallet text-red-500 text-3xl"></i>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg border-2 border-blue-200">
                            <div class="flex items-center">
                                <div class="bg-blue-500 rounded-full p-2 mr-3">
                                    <i class="fas fa-balance-scale text-white"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 font-medium">Net Balance</p>
                                    <p class="text-2xl font-bold text-gray-900">₹<?php echo number_format($total_revenue - $total_expenses, 2); ?></p>
                                </div>
                            </div>
                            <i class="fas fa-chart-line text-blue-500 text-3xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Placeholder -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-bell text-yellow-500 mr-3"></i>Quick Links
                    </h3>
                    <div class="space-y-3">
                        <a href="student_payments.php" class="flex items-center p-3 hover:bg-gray-50 rounded-lg transition">
                            <div class="bg-blue-100 rounded-lg p-2 mr-3">
                                <i class="fas fa-money-check-alt text-blue-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900">Student Payments</p>
                                <p class="text-xs text-gray-500">View payment history</p>
                            </div>
                            <i class="fas fa-chevron-right text-gray-400"></i>
                        </a>
                        <a href="employee_payments.php" class="flex items-center p-3 hover:bg-gray-50 rounded-lg transition">
                            <div class="bg-purple-100 rounded-lg p-2 mr-3">
                                <i class="fas fa-hand-holding-usd text-purple-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900">Employee Payments</p>
                                <p class="text-xs text-gray-500">Manage salaries</p>
                            </div>
                            <i class="fas fa-chevron-right text-gray-400"></i>
                        </a>
                        <a href="initialize_monthly_fees.php" class="flex items-center p-3 hover:bg-gray-50 rounded-lg transition">
                            <div class="bg-green-100 rounded-lg p-2 mr-3">
                                <i class="fas fa-calendar-plus text-green-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900">Monthly Fees</p>
                                <p class="text-xs text-gray-500">Initialize student fees</p>
                            </div>
                            <i class="fas fa-chevron-right text-gray-400"></i>
                        </a>
                        <a href="initialize_monthly_salary.php" class="flex items-center p-3 hover:bg-gray-50 rounded-lg transition">
                            <div class="bg-orange-100 rounded-lg p-2 mr-3">
                                <i class="fas fa-user-clock text-orange-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900">Monthly Salaries</p>
                                <p class="text-xs text-gray-500">Initialize employee salaries</p>
                            </div>
                            <i class="fas fa-chevron-right text-gray-400"></i>
                        </a>
                        <a href="org_details.php" class="flex items-center p-3 hover:bg-gray-50 rounded-lg transition">
                            <div class="bg-teal-100 rounded-lg p-2 mr-3">
                                <i class="fas fa-building text-teal-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900">Organization Details</p>
                                <p class="text-xs text-gray-500">Update organization info</p>
                            </div>
                            <i class="fas fa-chevron-right text-gray-400"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>