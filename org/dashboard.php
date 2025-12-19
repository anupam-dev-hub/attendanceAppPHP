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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <?php include 'navbar.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Dashboard</h2>
            <p class="mt-2 text-sm text-gray-600">Welcome to your organization management panel.</p>
        </div>
        
        <?php if (!$is_active): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-8" role="alert">
                <span class="block sm:inline">Your subscription is not active. <a href="subscribe.php" class="font-bold underline hover:text-red-900">Subscribe Now</a> to access all features.</span>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                 <div class="bg-white shadow-lg rounded-lg p-6 text-center hover:shadow-xl transition duration-300">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Subscription</h3>
                    <a href="subscribe.php" class="inline-block bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded transition">Manage</a>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-8" role="alert">
                <span class="block sm:inline">Subscription Active until <strong><?php echo date('Y-m-d', strtotime($subscription['to_date'])); ?></strong></span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white shadow-lg rounded-lg p-6 text-center hover:shadow-xl transition duration-300">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Students</h3>
                    <p class="text-gray-500 mb-4 text-sm">Manage student records and attendance.</p>
                    <a href="students.php" class="inline-block bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded transition">Manage</a>
                </div>
                <div class="bg-white shadow-lg rounded-lg p-6 text-center hover:shadow-xl transition duration-300">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Employees</h3>
                    <p class="text-gray-500 mb-4 text-sm">Manage employee details and payroll.</p>
                    <a href="employees.php" class="inline-block bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded transition">Manage</a>
                </div>

                <div class="bg-white shadow-lg rounded-lg p-6 text-center hover:shadow-xl transition duration-300">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Finance</h3>
                    <p class="text-gray-500 mb-4 text-sm">View reports, manage fees and payments.</p>
                    <a href="finance_overview.php" class="inline-block bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded transition">View</a>
                </div>

                 <div class="bg-white shadow-lg rounded-lg p-6 text-center hover:shadow-xl transition duration-300">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Subscription</h3>
                    <p class="text-gray-500 mb-4 text-sm">View plan details and renewal.</p>
                    <a href="subscribe.php" class="inline-block bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded transition">Manage</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
