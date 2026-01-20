<?php
// org/subscribe.php
session_start();
require '../config.php';
require '../functions.php';

if (!isOrg()) {
    redirect('index.php');
}

$org_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Fetch Payment Settings
$settings = [];
$result = $conn->query("SELECT * FROM settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$upi_id = $settings['upi_id'] ?? 'Not Configured';
$qr_code = $settings['qr_code'] ?? '';

// Fetch active subscription plans
$plansQuery = $conn->query("SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY duration_months ASC");
$availablePlans = [];
while ($planRow = $plansQuery->fetch_assoc()) {
    $availablePlans[] = $planRow;
}

// Fetch subscription statistics
$total_subscriptions = 0;
$active_subscriptions = 0;
$pending_subscriptions = 0;
$total_spent = 0;
$current_plan_days_left = 0;
$next_renewal_date = null;

$statsStmt = $conn->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'active' THEN amount ELSE 0 END) as spent
    FROM subscriptions WHERE org_id = ?");
$statsStmt->bind_param('i', $org_id);
$statsStmt->execute();
$statsResult = $statsStmt->get_result()->fetch_assoc();
$total_subscriptions = intval($statsResult['total'] ?? 0);
$active_subscriptions = intval($statsResult['active'] ?? 0);
$pending_subscriptions = intval($statsResult['pending'] ?? 0);
$total_spent = floatval($statsResult['spent'] ?? 0);
$statsStmt->close();

// Get current active subscription details
$activeStmt = $conn->prepare("SELECT to_date FROM subscriptions WHERE org_id = ? AND status = 'active' AND to_date > NOW() ORDER BY to_date DESC LIMIT 1");
$activeStmt->bind_param('i', $org_id);
$activeStmt->execute();
$activeResult = $activeStmt->get_result();
if ($activeRow = $activeResult->fetch_assoc()) {
    $next_renewal_date = $activeRow['to_date'];
    $today = new DateTime();
    $toDate = new DateTime($next_renewal_date);
    $diff = $toDate->diff($today);
    if ($toDate > $today) {
        $current_plan_days_left = $diff->days;
    }
}
$activeStmt->close();

// Filter parameters
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_id = intval($_POST['plan_id']);
    
    // Fetch plan details from database
    $planStmt = $conn->prepare("SELECT * FROM subscription_plans WHERE id = ? AND is_active = 1");
    $planStmt->bind_param("i", $plan_id);
    $planStmt->execute();
    $planResult = $planStmt->get_result();
    
    if ($planResult->num_rows === 0) {
        $error = "Invalid plan selected.";
    } else {
        $selectedPlan = $planResult->fetch_assoc();
        $plan = $selectedPlan['duration_months'];
        $amount = $selectedPlan['amount'];

        // Check for existing pending request
        $checkStmt = $conn->prepare("SELECT id FROM subscriptions WHERE org_id = ? AND status = 'pending'");
        $checkStmt->bind_param("i", $org_id);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            $error = "You already have a pending subscription request.";
        } else {
            if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] == 0) {
                $screenshotPath = uploadFile($_FILES['screenshot'], '../uploads/');
                if ($screenshotPath) {
                    $stmt = $conn->prepare("INSERT INTO subscriptions (org_id, plan_months, amount, payment_proof, status) VALUES (?, ?, ?, ?, 'pending')");
                    $stmt->bind_param("iids", $org_id, $plan, $amount, $screenshotPath);
                    
                    if ($stmt->execute()) {
                        // PRG Pattern: Redirect to self to prevent resubmission
                        header("Location: subscribe.php?success=1");
                        exit;
                    } else {
                        $error = "Database error: " . $stmt->error;
                    }
                } else {
                    $error = "Failed to upload screenshot.";
                }
            } else {
                $error = "Please upload a payment screenshot.";
            }
        }
    }
    $planStmt->close();
}

if (isset($_GET['success'])) {
    $success = "Subscription request submitted! Admin will verify your payment.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscribe Plan</title>
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
        .stat-icon-bg {
            position: absolute;
            top: -10px;
            right: -10px;
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        .plan-card {
            transition: all 0.3s ease;
        }
        .plan-card:hover {
            transform: scale(1.05);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
    </style>
    <script>
        // Dynamic plans data from PHP
        const plans = <?php echo json_encode($availablePlans); ?>;
        
        function updateAmount() {
            const planId = document.getElementById('plan').value;
            const selectedPlan = plans.find(p => p.id == planId);
            
            if (selectedPlan) {
                document.getElementById('amount-display').innerText = "Amount to Pay: ₹" + selectedPlan.amount;
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <?php include 'navbar.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900">
                <i class="fas fa-crown text-yellow-500 mr-3"></i>Subscription Plans
            </h1>
            <p class="mt-2 text-lg text-gray-600">Manage your subscription and view payment history</p>
        </div>

        <!-- Alert Messages -->
        <?php if ($success): ?>
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-4 rounded-xl shadow-lg mb-6 flex items-center">
                <i class="fas fa-check-circle text-2xl mr-3"></i>
                <span class="font-semibold"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-gradient-to-r from-red-500 to-red-600 text-white px-6 py-4 rounded-xl shadow-lg mb-6 flex items-center">
                <i class="fas fa-exclamation-circle text-2xl mr-3"></i>
                <span class="font-semibold"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Subscriptions -->
            <div class="gradient-card from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden">
                <div class="stat-icon-bg"></div>
                <div class="relative z-10">
                    <p class="text-blue-100 text-sm font-medium uppercase tracking-wide mb-2">Total Subscriptions</p>
                    <h3 class="text-4xl font-bold"><?php echo $total_subscriptions; ?></h3>
                    <div class="mt-4">
                        <i class="fas fa-list-alt text-3xl opacity-50"></i>
                    </div>
                </div>
            </div>

            <!-- Active Subscription -->
            <div class="gradient-card from-green-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden">
                <div class="stat-icon-bg"></div>
                <div class="relative z-10">
                    <p class="text-green-100 text-sm font-medium uppercase tracking-wide mb-2">Active</p>
                    <h3 class="text-4xl font-bold"><?php echo $active_subscriptions; ?></h3>
                    <div class="mt-4">
                        <i class="fas fa-check-circle text-3xl opacity-50"></i>
                    </div>
                </div>
            </div>

            <!-- Days Remaining -->
            <div class="gradient-card from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden">
                <div class="stat-icon-bg"></div>
                <div class="relative z-10">
                    <p class="text-orange-100 text-sm font-medium uppercase tracking-wide mb-2">Days Remaining</p>
                    <h3 class="text-4xl font-bold"><?php echo $current_plan_days_left > 0 ? $current_plan_days_left : '—'; ?></h3>
                    <div class="mt-4">
                        <i class="fas fa-calendar-day text-3xl opacity-50"></i>
                    </div>
                </div>
            </div>

            <!-- Total Spent -->
            <div class="gradient-card from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white relative overflow-hidden">
                <div class="stat-icon-bg"></div>
                <div class="relative z-10">
                    <p class="text-purple-100 text-sm font-medium uppercase tracking-wide mb-2">Total Spent</p>
                    <h3 class="text-4xl font-bold">₹<?php echo number_format($total_spent); ?></h3>
                    <div class="mt-4">
                        <i class="fas fa-rupee-sign text-3xl opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Plans Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">
                    <i class="fas fa-tags text-teal-600 mr-2"></i>Available Plans
                </h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-<?php echo min(count($availablePlans), 4); ?> gap-6">
                <?php foreach ($availablePlans as $plan): ?>
                    <div class="plan-card bg-white rounded-xl shadow-lg overflow-hidden border-2 border-gray-100">
                        <div class="bg-gradient-to-r from-teal-500 to-teal-600 text-white p-6 text-center">
                            <h3 class="text-2xl font-bold mb-2"><?php echo $plan['duration_months']; ?> Month<?php echo $plan['duration_months'] > 1 ? 's' : ''; ?></h3>
                            <div class="text-4xl font-bold mb-1">₹<?php echo number_format($plan['amount']); ?></div>
                            <p class="text-teal-100 text-sm">₹<?php echo number_format($plan['amount'] / $plan['duration_months']); ?>/month</p>
                        </div>
                        <div class="p-6">
                            <ul class="space-y-3 mb-6">
                                <li class="flex items-center text-gray-700">
                                    <i class="fas fa-check-circle text-green-500 mr-3"></i>
                                    <span><?php echo $plan['duration_months']; ?> months access</span>
                                </li>
                                <li class="flex items-center text-gray-700">
                                    <i class="fas fa-check-circle text-green-500 mr-3"></i>
                                    <span>Full feature access</span>
                                </li>
                                <li class="flex items-center text-gray-700">
                                    <i class="fas fa-check-circle text-green-500 mr-3"></i>
                                    <span>24/7 support</span>
                                </li>
                            </ul>
                            <button onclick="openSubscriptionModal(); selectPlan(<?php echo $plan['id']; ?>)" class="w-full bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 text-white font-bold py-3 px-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
                                <i class="fas fa-rocket mr-2"></i>Subscribe Now
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Subscription History -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">
                    <i class="fas fa-history text-blue-600 mr-2"></i>Subscription History
                </h2>
                
                <!-- Filter -->
                <div class="flex gap-2">
                    <label class="sr-only">Filter by status</label>
                    <select onchange="window.location.href='subscribe.php?status='+this.value" class="bg-white border-2 border-gray-200 text-gray-700 py-2.5 px-4 pr-10 rounded-lg transition-all duration-200 focus:outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-200 hover:border-gray-300 font-medium cursor-pointer">
                        <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>📊 All Status</option>
                        <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>✅ Active</option>
                        <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>⏳ Pending</option>
                        <option value="expired" <?php echo $filter_status === 'expired' ? 'selected' : ''; ?>>❌ Expired</option>
                    </select>
                </div>
            </div>

            <?php
            // Build query with filter
            $histQuery = "SELECT * FROM subscriptions WHERE org_id = $org_id";
            if ($filter_status !== 'all') {
                $histQuery .= " AND status = '" . $conn->real_escape_string($filter_status) . "'";
            }
            $histQuery .= " ORDER BY created_at DESC";
            $hist = $conn->query($histQuery);
            
            if ($hist->num_rows > 0):
            ?>
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <i class="fas fa-box mr-2"></i>Plan
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <i class="fas fa-rupee-sign mr-2"></i>Amount
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <i class="fas fa-calendar mr-2"></i>Payment Date
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <i class="fas fa-info-circle mr-2"></i>Status
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <i class="fas fa-hourglass-half mr-2"></i>Days Left
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <i class="fas fa-calendar-check mr-2"></i>Valid Until
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <i class="fas fa-image mr-2"></i>Proof
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while($row = $hist->fetch_assoc()): 
                            $daysLeft = '';
                            if ($row['status'] === 'active' && !empty($row['to_date'])) {
                                $today = new DateTime();
                                $toDate = new DateTime($row['to_date']);
                                $diff = $toDate->diff($today);
                                if ($toDate > $today) {
                                    $daysLeft = $diff->days . ' days';
                                } else {
                                    $daysLeft = 'Expired';
                                }
                            } else {
                                $daysLeft = '—';
                            }
                        ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="bg-blue-100 rounded-lg p-2 mr-3">
                                            <i class="fas fa-calendar-alt text-blue-600"></i>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-900"><?php echo $row['plan_months']; ?> Month<?php echo $row['plan_months'] > 1 ? 's' : ''; ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">₹<?php echo number_format($row['amount'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php 
                                        if($row['status']=='active') echo 'bg-green-100 text-green-800';
                                        elseif($row['status']=='pending') echo 'bg-yellow-100 text-yellow-800';
                                        else echo 'bg-red-100 text-red-800';
                                        ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo $daysLeft; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo $row['to_date'] ? date('d M Y', strtotime($row['to_date'])) : '—'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php if (!empty($row['payment_proof'])): ?>
                                        <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-lg text-xs font-semibold transition" onclick="openProofModal('<?php echo htmlspecialchars($row['payment_proof']); ?>')">
                                            <i class="fas fa-eye mr-1"></i>View
                                        </button>
                                    <?php else: ?>
                                        <span class="text-gray-400">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
                    <p class="text-gray-500 text-lg">No subscription history found</p>
                    <button onclick="openSubscriptionModal()" class="mt-4 bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-6 rounded-lg transition">
                        <i class="fas fa-plus mr-2"></i>Subscribe Now
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal for Payment Proof View -->
    <div id="proofViewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-[9999] flex items-center justify-center">
        <div class="relative mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Payment Proof Screenshot</h3>
                    <span class="cursor-pointer text-gray-400 hover:text-gray-600 text-2xl font-bold" onclick="closeProofModal()">&times;</span>
                </div>
                <div class="mt-2 px-7 py-3">
                    <img id="proofViewImg" src="" class="max-w-full max-h-96 mx-auto border border-gray-200 rounded">
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Subscription Payment Form -->
    <div id="subscriptionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-[9999] flex items-center justify-center p-4">
        <div class="relative mx-auto w-full max-w-lg shadow-lg rounded-md bg-white">
            <div class="p-5">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-lg font-semibold text-gray-900">Payment Details</h3>
                    <span class="cursor-pointer text-gray-400 hover:text-gray-600 text-2xl font-bold" onclick="closeSubscriptionModal()">&times;</span>
                </div>
                
                <form method="POST" enctype="multipart/form-data" id="subscribeForm" class="space-y-4">
                    <!-- QR Code and UPI Section -->
                    <div class="border border-teal-300 rounded-lg p-4 bg-teal-50 text-center">
                        <p class="text-gray-700 font-semibold text-sm mb-3">Scan & Pay</p>
                        <div class="w-40 h-40 bg-gray-200 mx-auto flex items-center justify-center mb-3 rounded-md overflow-hidden">
                            <?php if ($qr_code): ?>
                                <img src="<?php echo htmlspecialchars($qr_code); ?>" alt="Scan to Pay" class="w-full h-full object-cover">
                            <?php else: ?>
                                <span class="text-gray-500 text-xs">QR Code Not Available</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-gray-700 font-bold text-xs mb-1">UPI ID</p>
                        <p class="text-sm text-teal-600 font-mono"><?php echo htmlspecialchars($upi_id); ?></p>
                    </div>

                    <!-- Plan Selection -->
                    <div>
                        <label class="block text-gray-700 text-xs font-bold mb-2">Select Plan</label>
                        <select name="plan_id" id="plan" onchange="updateAmount()" required class="block w-full bg-white border border-gray-300 hover:border-gray-400 px-3 py-2 pr-8 rounded text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <?php if (count($availablePlans) > 0): ?>
                                <?php foreach ($availablePlans as $plan): ?>
                                    <option value="<?php echo $plan['id']; ?>">
                                        <?php echo $plan['duration_months']; ?> Month<?php echo $plan['duration_months'] > 1 ? 's' : ''; ?> - ₹<?php echo number_format($plan['amount'], 0); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">No plans available</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <!-- Amount Display -->
                    <div class="p-3 bg-teal-50 border border-teal-200 rounded text-center">
                        <p id="amount-display" class="text-lg font-bold text-teal-600">
                            Amount: ₹<?php echo count($availablePlans) > 0 ? number_format($availablePlans[0]['amount'], 0) : '0'; ?>
                        </p>
                    </div>

                    <!-- Screenshot Upload -->
                    <div>
                        <label class="block text-gray-700 text-xs font-bold mb-2">Upload Screenshot</label>
                        <input type="file" name="screenshot" accept="image/*" required id="screenshotInput" class="block w-full text-xs text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none p-2">
                        <div id="screenshotPreviewContainer" class="mt-2 hidden flex justify-center">
                            <div>
                                <p class="text-xs text-gray-600 mb-1">Preview:</p>
                                <img id="screenshotPreview" src="" class="max-w-[120px] border border-gray-200 rounded p-1">
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-3 pt-3">
                        <button type="button" onclick="closeSubscriptionModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-3 rounded transition text-sm">
                            Cancel
                        </button>
                        <button type="submit" id="submitBtn" class="flex-1 bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-3 rounded transition text-sm">
                            Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('screenshotInput').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('screenshotPreview').src = e.target.result;
                    document.getElementById('screenshotPreviewContainer').classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            } else {
                document.getElementById('screenshotPreviewContainer').classList.add('hidden');
            }
        });

        document.getElementById('subscribeForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerText = 'Submitting...';
            btn.classList.add('opacity-50', 'cursor-not-allowed');
        });

        function selectPlan(planId) {
            document.getElementById('plan').value = planId;
            updateAmount();
        }

        function openProofModal(imgSrc) {
            document.getElementById('proofViewImg').src = imgSrc;
            document.getElementById('proofViewModal').classList.remove('hidden');
        }

        function closeProofModal() {
            document.getElementById('proofViewModal').classList.add('hidden');
        }

        function openSubscriptionModal() {
            document.getElementById('subscriptionModal').classList.remove('hidden');
        }

        function closeSubscriptionModal() {
            document.getElementById('subscriptionModal').classList.add('hidden');
        }

        // Close modals if clicked outside
        window.onclick = function(event) {
            const viewModal = document.getElementById('proofViewModal');
            const subModal = document.getElementById('subscriptionModal');
            if (event.target == viewModal) {
                closeProofModal();
            }
            if (event.target == subModal) {
                closeSubscriptionModal();
            }
        }
    </script>
</body>
</html>
