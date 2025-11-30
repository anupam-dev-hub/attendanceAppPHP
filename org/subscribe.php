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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan = $_POST['plan']; // 1, 3, 6, 12
    $amount = 0;
    
    switch($plan) {
        case 1: $amount = 1000; break;
        case 3: $amount = 2800; break;
        case 6: $amount = 5500; break;
        case 12: $amount = 10000; break;
    }

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
    <script>
        function updateAmount() {
            const plan = document.getElementById('plan').value;
            let amount = 0;
            if (plan == 1) amount = 1000;
            else if (plan == 3) amount = 2800;
            else if (plan == 6) amount = 5500;
            else if (plan == 12) amount = 10000;
            document.getElementById('amount-display').innerText = "Amount to Pay: ₹" + amount;
        }
    </script>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <nav class="bg-teal-600 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-white text-xl font-bold tracking-wide"><?php echo htmlspecialchars($_SESSION['org_name']); ?></a>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="dashboard.php" class="text-white hover:text-teal-100 font-medium transition">Dashboard</a>
                    <?php if (isSubscribed($org_id)): ?>
                        <a href="students.php" class="text-white hover:text-teal-100 font-medium transition">Students</a>
                        <a href="employees.php" class="text-white hover:text-teal-100 font-medium transition">Employees</a>
                    <?php endif; ?>
                    <a href="../logout.php" class="text-white hover:text-red-200 font-medium transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Subscribe to a Plan</h2>
            <p class="mt-2 text-sm text-gray-600">Choose a subscription plan to continue using the services.</p>
        </div>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
            <!-- Left Column: Payment Details & History -->
            <div>
                <div class="bg-white shadow overflow-hidden sm:rounded-lg p-6 mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Payment Details</h3>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center bg-gray-50">
                        <p class="text-gray-700 mb-4"><strong>UPI ID:</strong> <?php echo htmlspecialchars($upi_id); ?></p>
                        <div class="w-48 h-48 bg-gray-200 mx-auto flex items-center justify-center mb-4 rounded-md overflow-hidden">
                            <?php if ($qr_code): ?>
                                <img src="<?php echo htmlspecialchars($qr_code); ?>" alt="Scan to Pay" class="w-full h-full object-cover">
                            <?php else: ?>
                                <span class="text-gray-500 text-sm">QR Code Not Available</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-sm text-gray-500">Scan this QR code to make the payment.</p>
                    </div>
                </div>
                
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Subscription History</h3>
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <?php
                    $hist = $conn->query("SELECT * FROM subscriptions WHERE org_id = $org_id ORDER BY created_at DESC");
                    if ($hist->num_rows > 0):
                    ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valid Until</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while($row = $hist->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $row['plan_months']; ?> Months</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php 
                                                if($row['status']=='active') echo 'bg-green-100 text-green-800';
                                                elseif($row['status']=='pending') echo 'bg-yellow-100 text-yellow-800';
                                                else echo 'bg-red-100 text-red-800';
                                                ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $row['to_date'] ? date('Y-m-d', strtotime($row['to_date'])) : '-'; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                        <div class="p-6 text-center text-gray-500">
                            No history found.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Subscription Form -->
            <div>
                <div class="bg-white shadow overflow-hidden sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Submit Payment Details</h3>
                    <form method="POST" enctype="multipart/form-data" id="subscribeForm" class="space-y-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Select Plan</label>
                            <select name="plan" id="plan" onchange="updateAmount()" required class="block w-full bg-white border border-gray-300 hover:border-gray-400 px-4 py-2 pr-8 rounded shadow leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-teal-500">
                                <option value="1">1 Month - ₹1000</option>
                                <option value="3">3 Months - ₹2800</option>
                                <option value="6">6 Months - ₹5500</option>
                                <option value="12">12 Months - ₹10000</option>
                            </select>
                        </div>
                        
                        <h3 id="amount-display" class="text-2xl font-bold text-teal-600">Amount to Pay: ₹1000</h3>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Upload Transaction Screenshot</label>
                            <input type="file" name="screenshot" accept="image/*" required id="screenshotInput" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none p-2">
                            <div id="screenshotPreviewContainer" class="mt-4 hidden">
                                <p class="text-sm text-gray-600 mb-2">Screenshot Preview:</p>
                                <img id="screenshotPreview" src="" class="max-w-[200px] border border-gray-200 rounded p-1">
                            </div>
                        </div>

                        <button type="submit" id="submitBtn" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 px-4 rounded focus:outline-none focus:shadow-outline transition duration-300">
                            Submit Payment
                        </button>
                    </form>
                </div>
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
    </script>
</body>
</html>
