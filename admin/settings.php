<?php
// admin/settings.php
session_start();
require '../config.php';
require '../functions.php';

if (!isAdmin()) {
    redirect('index.php');
}

$success = '';
$error = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $upi_id = $_POST['upi_id'];
    
    // Update UPI ID
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('upi_id', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param("ss", $upi_id, $upi_id);
    if (!$stmt->execute()) {
        $error = "Error updating UPI ID: " . $stmt->error;
    }
    $stmt->close();

    // Handle QR Code Upload
    if (isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] == 0) {
        $qrPath = uploadFile($_FILES['qr_code'], '../uploads/');
        if ($qrPath) {
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('qr_code', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("ss", $qrPath, $qrPath);
            if (!$stmt->execute()) {
                $error = "Error updating QR Code: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Failed to upload QR Code.";
        }
    }

    if (!$error) {
        $success = "Settings updated successfully.";
    }
}

// Fetch Current Settings
$settings = [];
$result = $conn->query("SELECT * FROM settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                    <a href="settings.php" class="text-white hover:text-blue-100 font-medium transition">Settings</a>
                    <a href="../logout.php" class="text-white hover:text-red-200 font-medium transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Payment Settings</h2>
            <p class="mt-2 text-sm text-gray-600">Configure payment details for organizations.</p>
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

        <div class="bg-white shadow overflow-hidden sm:rounded-lg p-6">
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="upi_id">
                        UPI ID
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500" id="upi_id" type="text" name="upi_id" value="<?php echo htmlspecialchars($settings['upi_id'] ?? ''); ?>" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Current QR Code
                    </label>
                    <?php if (isset($settings['qr_code'])): ?>
                        <div class="mt-2">
                            <img src="<?php echo htmlspecialchars($settings['qr_code']); ?>" alt="QR Code" class="max-w-xs border border-gray-200 rounded shadow-sm">
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-sm">No QR Code uploaded.</p>
                    <?php endif; ?>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="qrInput">
                        Upload New QR Code
                    </label>
                    <input class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none p-2" id="qrInput" type="file" name="qr_code" accept="image/*">
                    <div id="previewContainer" class="mt-4 hidden">
                        <p class="text-sm text-gray-600 mb-2">New QR Preview:</p>
                        <img id="qrPreview" src="" class="max-w-xs border border-gray-200 rounded shadow-sm">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition" type="submit">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('qrInput').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('qrPreview').src = e.target.result;
                    document.getElementById('previewContainer').classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            } else {
                document.getElementById('previewContainer').classList.add('hidden');
            }
        });
    </script>
</body>
</html>
