<?php
// org/camera_settings.php
session_start();
require '../config.php';
require '../functions.php';

if (!isOrg()) {
    redirect('index.php');
}

$org_id = $_SESSION['user_id'];
$is_active = isSubscribed($org_id);

// Handle camera type update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $camera_type = isset($_POST['camera_type']) && $_POST['camera_type'] === 'front' ? 'front' : 'back';
    
    // Check if camera_type column exists, if not create it
    $check_column = $conn->query("SHOW COLUMNS FROM organizations LIKE 'camera_type'");
    if ($check_column->num_rows === 0) {
        $conn->query("ALTER TABLE organizations ADD COLUMN camera_type VARCHAR(10) DEFAULT 'back'");
    }
    
    // Update camera type
    $stmt = $conn->prepare("UPDATE organizations SET camera_type = ? WHERE id = ?");
    $stmt->bind_param('si', $camera_type, $org_id);
    
    if ($stmt->execute()) {
        $_SESSION['camera_success'] = "Camera settings updated successfully!";
    } else {
        $_SESSION['camera_error'] = "Failed to update camera settings.";
    }
    $stmt->close();
    
    // Redirect to prevent form resubmission
    header('Location: camera_settings.php');
    exit;
}

// Get session messages and clear them
$success_message = isset($_SESSION['camera_success']) ? $_SESSION['camera_success'] : null;
$error_message = isset($_SESSION['camera_error']) ? $_SESSION['camera_error'] : null;
unset($_SESSION['camera_success']);
unset($_SESSION['camera_error']);

// Ensure camera_type column exists before querying
$check_column = $conn->query("SHOW COLUMNS FROM organizations LIKE 'camera_type'");
if ($check_column->num_rows === 0) {
    $conn->query("ALTER TABLE organizations ADD COLUMN camera_type VARCHAR(10) DEFAULT 'back'");
}

// Get current camera type
$camera_type = 'back'; // default
$stmt = $conn->prepare("SELECT camera_type FROM organizations WHERE id = ?");
$stmt->bind_param('i', $org_id);
$stmt->execute();
$stmt->bind_result($camera_type);
$stmt->fetch();
$stmt->close();

if (!$camera_type) {
    $camera_type = 'back';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Camera Settings - Attendance API</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <?php include 'navbar.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Camera Settings</h2>
            <p class="mt-2 text-sm text-gray-600">Configure the default camera for the mobile scanner app.</p>
        </div>

        <?php if (!$is_active): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-8" role="alert">
                <span class="block sm:inline">Your subscription is not active. <a href="subscribe.php" class="font-bold underline hover:text-red-900">Subscribe Now</a> to access this feature.</span>
            </div>
        <?php else: ?>
            <?php if (isset($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-8" role="alert">
                    <span class="block sm:inline"><?php echo $success_message; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-8" role="alert">
                    <span class="block sm:inline"><?php echo $error_message; ?></span>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 gap-8">
                <div class="bg-white shadow-lg rounded-lg p-8">
                    <h3 class="text-xl font-semibold text-gray-800 mb-6">Default Camera Type</h3>
                    
                    <form method="POST" id="cameraForm">
                        <div class="space-y-6">
                            <div class="flex items-center justify-between bg-gray-50 rounded-lg p-6">
                                <div>
                                    <h4 class="font-semibold text-gray-700 mb-2">📱 Camera Direction</h4>
                                    <p class="text-sm text-gray-600">Choose which camera the mobile app should use by default when scanning QR codes.</p>
                                    <p class="text-xs text-gray-500 mt-2">
                                        <strong>Back Camera:</strong> Standard rear camera (recommended)<br>
                                        <strong>Front Camera:</strong> Selfie camera
                                    </p>
                                </div>
                                
                                <div class="ml-6">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="submitted" value="1">
                                        <input type="checkbox" id="cameraToggle" class="sr-only peer" 
                                               <?php echo $camera_type === 'front' ? 'checked' : ''; ?>
                                               onchange="toggleCamera()">
                                        <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-teal-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-teal-600"></div>
                                        <span class="ml-3 text-sm font-medium text-gray-900" id="cameraLabel">
                                            <?php echo $camera_type === 'front' ? 'Front' : 'Back'; ?> Camera
                                        </span>
                                        <input type="hidden" name="camera_type" id="cameraTypeInput" value="<?php echo $camera_type; ?>">
                                    </label>
                                </div>
                            </div>

                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-blue-800">How It Works</h3>
                                        <div class="mt-2 text-sm text-blue-700">
                                            <ul class="list-disc list-inside space-y-1">
                                                <li>This setting applies to all mobile scanner apps using your organization token</li>
                                                <li>Changes take effect the next time the app is opened or refreshed</li>
                                                <li>Users can still manually switch cameras within the app if needed</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-sm text-gray-600 space-y-1">
                                <p><strong>Current Setting:</strong> <span class="text-teal-600 font-semibold"><?php echo ucfirst($camera_type); ?> Camera</span></p>
                                <p><strong>Status:</strong> <span class="text-green-600 font-semibold">Active</span></p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleCamera() {
            const checkbox = document.getElementById('cameraToggle');
            const input = document.getElementById('cameraTypeInput');
            const label = document.getElementById('cameraLabel');
            
            if (checkbox.checked) {
                input.value = 'front';
                label.textContent = 'Front Camera';
            } else {
                input.value = 'back';
                label.textContent = 'Back Camera';
            }
            
            document.getElementById('cameraForm').submit();
        }

        <?php if (isset($success_message)): ?>
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: '<?php echo $success_message; ?>',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
        <?php endif; ?>
    </script>
</body>
</html>
