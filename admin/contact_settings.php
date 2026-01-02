<?php
// admin/contact_settings.php
session_start();
require '../config.php';
require '../functions.php';

if (!isAdmin()) {
    redirect('index.php');
}

$admin_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contact_email = trim($_POST['contact_email'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $contact_address = trim($_POST['contact_address'] ?? '');

    $stmt = $conn->prepare("UPDATE admins SET contact_email = ?, contact_phone = ?, contact_address = ? WHERE id = ?");
    $stmt->bind_param('sssi', $contact_email, $contact_phone, $contact_address, $admin_id);
    if ($stmt->execute()) {
        $success = 'Contact details updated successfully.';
    } else {
        $error = 'Failed to update contact details: ' . $stmt->error;
    }
    $stmt->close();
}

// Fetch current values
$contact_email = '';
$contact_phone = '';
$contact_address = '';

$stmt = $conn->prepare("SELECT contact_email, contact_phone, contact_address FROM admins WHERE id = ?");
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$stmt->bind_result($contact_email, $contact_phone, $contact_address);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Contact Details</title>
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
        .dropdown-content a.active { background-color: #3b82f6; color: #fff; }
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
                            <a href="contact_settings.php" class="active">Contact Details</a>
                        </div>
                    </div>
                    <a href="../logout.php" class="text-white hover:text-red-200 font-medium transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Contact Details</h2>
            <p class="mt-2 text-sm text-gray-600">Set contact information displayed for admin queries.</p>
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

        <div class="bg-white shadow sm:rounded-lg p-6">
            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Contact Email</label>
                    <input type="email" name="contact_email" value="<?php echo htmlspecialchars($contact_email); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-teal-500" placeholder="support@example.com">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Contact Phone</label>
                    <input type="text" name="contact_phone" value="<?php echo htmlspecialchars($contact_phone); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-teal-500" placeholder="+91-98765-43210">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Contact Address</label>
                    <textarea name="contact_address" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-teal-500" placeholder="Office address, city, state, pincode"><?php echo htmlspecialchars($contact_address); ?></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded shadow focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Save Contact Details
                    </button>
                </div>
            </form>
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
