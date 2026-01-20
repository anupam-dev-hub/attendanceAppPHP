<?php
// admin/subscription_plans.php
session_start();
require '../config.php';
require '../functions.php';

if (!isAdmin()) {
    redirect('index.php');
}

$success = '';
$error = '';

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add') {
        $duration = intval($_POST['duration_months']);
        $amount = floatval($_POST['amount']);
        
        // Check if plan already exists
        $checkStmt = $conn->prepare("SELECT id FROM subscription_plans WHERE duration_months = ?");
        $checkStmt->bind_param("i", $duration);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            $error = "A plan with this duration already exists. Please edit the existing plan.";
        } else {
            $stmt = $conn->prepare("INSERT INTO subscription_plans (duration_months, amount, is_active) VALUES (?, ?, 1)");
            $stmt->bind_param("id", $duration, $amount);
            if ($stmt->execute()) {
                $success = "Plan added successfully!";
            } else {
                $error = "Error adding plan: " . $stmt->error;
            }
            $stmt->close();
        }
        $checkStmt->close();
        
    } elseif ($action === 'edit') {
        $plan_id = intval($_POST['plan_id']);
        $duration = intval($_POST['duration_months']);
        $amount = floatval($_POST['amount']);
        
        // Check if another plan with same duration exists
        $checkStmt = $conn->prepare("SELECT id FROM subscription_plans WHERE duration_months = ? AND id != ?");
        $checkStmt->bind_param("ii", $duration, $plan_id);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            $error = "Another plan with this duration already exists.";
        } else {
            $stmt = $conn->prepare("UPDATE subscription_plans SET duration_months = ?, amount = ? WHERE id = ?");
            $stmt->bind_param("idi", $duration, $amount, $plan_id);
            if ($stmt->execute()) {
                $success = "Plan updated successfully!";
            } else {
                $error = "Error updating plan: " . $stmt->error;
            }
            $stmt->close();
        }
        $checkStmt->close();
        
    } elseif ($action === 'toggle') {
        $plan_id = intval($_POST['plan_id']);
        $stmt = $conn->prepare("UPDATE subscription_plans SET is_active = NOT is_active WHERE id = ?");
        $stmt->bind_param("i", $plan_id);
        if ($stmt->execute()) {
            $success = "Plan status updated!";
        } else {
            $error = "Error updating status: " . $stmt->error;
        }
        $stmt->close();
        
    } elseif ($action === 'delete') {
        $plan_id = intval($_POST['plan_id']);
        
        // Check if plan is used in any subscriptions
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM subscriptions WHERE plan_months = (SELECT duration_months FROM subscription_plans WHERE id = ?)");
        $checkStmt->bind_param("i", $plan_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            $error = "Cannot delete this plan. It is being used by " . $row['count'] . " subscription(s). Consider deactivating it instead.";
        } else {
            $stmt = $conn->prepare("DELETE FROM subscription_plans WHERE id = ?");
            $stmt->bind_param("i", $plan_id);
            if ($stmt->execute()) {
                $success = "Plan deleted successfully!";
            } else {
                $error = "Error deleting plan: " . $stmt->error;
            }
            $stmt->close();
        }
        $checkStmt->close();
    }
}

// Fetch all plans
$plans = $conn->query("SELECT * FROM subscription_plans ORDER BY duration_months ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subscription Plans</title>
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
            <h2 class="text-3xl font-bold text-gray-900">Manage Subscription Plans</h2>
            <p class="mt-2 text-sm text-gray-600">Add, edit, or manage subscription plans for organizations.</p>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Add New Plan Form -->
            <div class="lg:col-span-1">
                <div class="bg-white shadow overflow-hidden sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Add New Plan</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add">
                        
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Duration (Months)</label>
                            <input type="number" name="duration_months" min="1" required 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Amount (₹)</label>
                            <input type="number" name="amount" step="0.01" min="0" required 
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                        </div>

                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition">
                            Add Plan
                        </button>
                    </form>
                </div>
            </div>

            <!-- Plans List -->
            <div class="lg:col-span-2">
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Current Plans</h3>
                        <p class="mt-1 text-sm text-gray-500">Manage existing subscription plans</p>
                    </div>
                    <?php if ($plans->num_rows > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php while($plan = $plans->fetch_assoc()): ?>
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo $plan['duration_months']; ?> Month<?php echo $plan['duration_months'] > 1 ? 's' : ''; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                ₹<?php echo number_format($plan['amount'], 2); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $plan['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                    <?php echo $plan['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                <button onclick="editPlan(<?php echo $plan['id']; ?>, <?php echo $plan['duration_months']; ?>, <?php echo $plan['amount']; ?>)" 
                                                        class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                                
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle">
                                                    <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                                                    <button type="submit" class="text-yellow-600 hover:text-yellow-900">
                                                        <?php echo $plan['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                    </button>
                                                </form>
                                                
                                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this plan?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-6 text-center text-gray-500">
                            No plans found. Add your first plan using the form on the left.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Edit Plan</h3>
                    <span class="cursor-pointer text-gray-400 hover:text-gray-600 text-2xl font-bold" onclick="closeEditModal()">&times;</span>
                </div>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="plan_id" id="edit_plan_id">
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Duration (Months)</label>
                        <input type="number" name="duration_months" id="edit_duration" min="1" required 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Amount (₹)</label>
                        <input type="number" name="amount" id="edit_amount" step="0.01" min="0" required 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="flex gap-4">
                        <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition">
                            Update Plan
                        </button>
                        <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editPlan(id, duration, amount) {
            document.getElementById('edit_plan_id').value = id;
            document.getElementById('edit_duration').value = duration;
            document.getElementById('edit_amount').value = amount;
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // Close modal if clicked outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }

        // Dropdown menu functionality
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
</body>
</html>
