<?php
// admin/subscriptions.php
session_start();
require '../config.php';
require '../functions.php';

if (!isAdmin()) {
    redirect('index.php');
}

$success = '';
$error = '';

// Handle Approval/Rejection
if (isset($_POST['action']) && isset($_POST['sub_id'])) {
    $sub_id = $_POST['sub_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        // Fetch the subscription details
        $subQuery = $conn->query("SELECT * FROM subscriptions WHERE id = $sub_id");
        $sub = $subQuery->fetch_assoc();
        $org_id = $sub['org_id'];
        $plan_months = $sub['plan_months'];

        // Check for existing active subscription (or the most recent one)
        $lastSubQuery = $conn->query("SELECT to_date FROM subscriptions WHERE org_id = $org_id AND status = 'active' ORDER BY to_date DESC LIMIT 1");
        
        $from_date = date('Y-m-d H:i:s');
        
        if ($lastSubQuery->num_rows > 0) {
            $lastSub = $lastSubQuery->fetch_assoc();
            $last_to_date = $lastSub['to_date'];
            
            // If the last subscription is still active (to_date > now), start after it ends
            if (strtotime($last_to_date) > time()) {
                $from_date = $last_to_date;
            }
        }

        // Calculate to_date
        $to_date = date('Y-m-d H:i:s', strtotime($from_date . " + $plan_months months"));

        $stmt = $conn->prepare("UPDATE subscriptions SET status = 'active', from_date = ?, to_date = ? WHERE id = ?");
        $stmt->bind_param("ssi", $from_date, $to_date, $sub_id);
        
        if ($stmt->execute()) {
            $success = "Subscription approved. Valid from $from_date to $to_date.";
        } else {
            $error = "Error updating subscription: " . $stmt->error;
        }
        $stmt->close();

    } else {
        // Reject
        $stmt = $conn->prepare("UPDATE subscriptions SET status = 'expired' WHERE id = ?");
        $stmt->bind_param("i", $sub_id);
        if ($stmt->execute()) {
            $success = "Subscription rejected.";
        } else {
            $error = "Error updating subscription: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch Pending Subscriptions
$pending = $conn->query("SELECT s.*, o.name as org_name, o.email 
                         FROM subscriptions s 
                         JOIN organizations o ON s.org_id = o.id 
                         WHERE s.status = 'pending' 
                         ORDER BY s.created_at ASC");

// Fetch History
$history = $conn->query("SELECT s.*, o.name as org_name 
                         FROM subscriptions s 
                         JOIN organizations o ON s.org_id = o.id 
                         WHERE s.status != 'pending' 
                         ORDER BY s.created_at DESC LIMIT 50");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subscriptions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .dropdown { position: relative; display: inline-block; }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #fff;
            min-width: 200px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1000;
            border-radius: 0.375rem;
            margin-top: 0.5rem;
            top: 100%;
            right: 0;
        }
        .dropdown-content a {
            color: #1f2937;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: background-color 0.2s;
            font-size: 0.875rem;
        }
        .dropdown-content a:hover { background-color: #f3f4f6; }
        .dropdown:hover .dropdown-content { display: block; }
        .dropdown.open .dropdown-content { display: block; }
        .dropdown-btn { cursor: pointer; }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <nav class="bg-blue-600 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-white text-xl font-bold tracking-wide">Admin Panel</a>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="dashboard.php" class="text-white hover:text-blue-100 font-medium transition">Dashboard</a>
                    <a href="add_org.php" class="text-white hover:text-blue-100 font-medium transition">Add Organization</a>
                    <a href="subscriptions.php" class="text-white hover:text-blue-100 font-medium transition relative">
                        Subscriptions
                        <?php 
                        $pendingCount = getPendingSubscriptionCount();
                        if ($pendingCount > 0): 
                        ?>
                            <span class="absolute -top-2 -right-4 inline-flex items-center justify-center px-1.5 py-0.5 border border-yellow-400 rounded-full text-[10px] font-bold bg-gray-900 text-yellow-400 shadow-sm">
                                <?php echo $pendingCount; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown">
                        <span class="dropdown-btn text-white hover:text-blue-100 font-medium transition">
                            Settings ▾
                        </span>
                        <div class="dropdown-content">
                            <a href="settings.php">Payment Settings</a>
                            <a href="contact_settings.php">Contact Details</a>
                            <a href="subscription_plans.php">Subscription Plans</a>
                        </div>
                    </div>
                    <a href="../logout.php" class="text-white hover:text-red-200 font-medium transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Subscription Requests</h2>
            <p class="mt-2 text-sm text-gray-600">Manage pending and past subscription requests.</p>
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

        <h3 class="text-xl font-semibold text-gray-800 mb-4">Pending Requests</h3>
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-10">
            <?php if ($pending->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Org Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proof</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while($row = $pending->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['org_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $row['plan_months']; ?> Months</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₹<?php echo $row['amount']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition" onclick="openModal('<?php echo htmlspecialchars($row['payment_proof']); ?>', '<?php echo $row['id']; ?>')">
                                            View & Action
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $row['created_at']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-600 font-semibold">Pending</td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-6 text-center text-gray-500">
                    No pending requests.
                </div>
            <?php endif; ?>
        </div>

        <!-- Modal for Payment Proof -->
        <div id="proofModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Payment Proof</h3>
                        <span class="cursor-pointer text-gray-400 hover:text-gray-600 text-2xl font-bold" onclick="closeModal()">&times;</span>
                    </div>
                    <div class="mt-2 px-7 py-3">
                        <img id="modalImg" src="" class="max-w-full max-h-96 mx-auto border border-gray-200 rounded">
                    </div>
                    <div class="items-center px-4 py-3">
                        <form method="POST" id="actionForm" class="flex justify-center gap-4">
                            <input type="hidden" name="sub_id" id="modalSubId">
                            <button type="submit" name="action" value="approve" class="px-4 py-2 bg-green-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-300">
                                Approve
                            </button>
                            <button type="submit" name="action" value="reject" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300">
                                Reject
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for History Proof View -->
        <div id="proofViewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50 flex items-center justify-center">
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

        <script>
            function openModal(imgSrc, subId) {
                document.getElementById('modalImg').src = imgSrc;
                document.getElementById('modalSubId').value = subId;
                document.getElementById('proofModal').classList.remove('hidden');
            }

            function closeModal() {
                document.getElementById('proofModal').classList.add('hidden');
            }

            function openProofModal(imgSrc) {
                document.getElementById('proofViewImg').src = imgSrc;
                document.getElementById('proofViewModal').classList.remove('hidden');
            }

            function closeProofModal() {
                document.getElementById('proofViewModal').classList.add('hidden');
            }

            // Close modal if clicked outside
            window.onclick = function(event) {
                const modal = document.getElementById('proofModal');
                const viewModal = document.getElementById('proofViewModal');
                if (event.target == modal) {
                    closeModal();
                }
                if (event.target == viewModal) {
                    closeProofModal();
                }
            }
        </script>

        <h3 class="text-xl font-semibold text-gray-800 mb-4">Subscription History</h3>
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <?php if ($history->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Org Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days Left</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while($row = $history->fetch_assoc()): 
                                // Calculate days left
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
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['org_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $row['plan_months']; ?> Months</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-semibold">₹<?php echo number_format($row['amount'], 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $daysLeft; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo ($row['status']=='active') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo strtoupper($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php if (!empty($row['payment_proof'])): ?>
                                            <button class="text-blue-600 hover:text-blue-800 font-semibold" onclick="openProofModal('<?php echo htmlspecialchars($row['payment_proof']); ?>')">
                                                More Info
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
                <div class="p-6 text-center text-gray-500">
                    No history found.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropdowns = document.querySelectorAll('.dropdown');
        const closeAll = () => dropdowns.forEach(d => d.classList.remove('open'));
        const openDropdown = (dd) => {
            closeAll();
            dd.classList.add('open');
        };

        dropdowns.forEach(dd => {
            const btn = dd.querySelector('.dropdown-btn');
            const content = dd.querySelector('.dropdown-content');
            if (!btn || !content) return;

            btn.addEventListener('mouseenter', function() {
                openDropdown(dd);
            });

            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const isOpen = dd.classList.contains('open');
                if (isOpen) {
                    closeAll();
                } else {
                    openDropdown(dd);
                }
            });

            content.addEventListener('click', function(e) {
                e.stopPropagation();
                closeAll();
            });
        });

        document.addEventListener('click', function() {
            closeAll();
        });
    });
</script>
</html>
