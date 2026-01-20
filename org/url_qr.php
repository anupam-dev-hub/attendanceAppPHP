<?php
// org/url_qr.php
session_start();
require '../config.php';
require '../functions.php';

if (!isOrg()) {
    redirect('index.php');
}

$org_id = $_SESSION['user_id'];
$is_active = isSubscribed($org_id);

// Get the base URL (scheme + host only) - properly detect HTTPS
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
          $_SERVER['SERVER_PORT'] == 443 ||
          (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
$base_url = $scheme . '://' . $_SERVER['HTTP_HOST'];
$full_url = $scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>URL QR - Attendance API</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <?php include 'navbar.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">URL QR Code</h2>
            <p class="mt-2 text-sm text-gray-600">Share this QR code to allow others to access your organization.</p>
        </div>

        <?php if (!$is_active): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-8" role="alert">
                <span class="block sm:inline">Your subscription is not active. <a href="subscribe.php" class="font-bold underline hover:text-red-900">Subscribe Now</a> to access this feature.</span>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-8">
                <!-- QR Code Display -->
                <div class="bg-white shadow-lg rounded-lg p-8">
                    <h3 class="text-xl font-semibold text-gray-800 mb-6">Current Page QR Code</h3>
                    
                    <div class="flex flex-col items-center">
                        <div class="flex justify-center mb-6">
                            <div id="qrcode" style="display: inline-block; padding: 20px; background: white; border: 4px solid #0d9488; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);"></div>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-4 mb-4 w-full">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Base URL (QR Code):</label>
                            <div class="flex items-center gap-2">
                                <input type="text" id="url-string" readonly class="flex-1 bg-white border border-gray-300 rounded px-3 py-2 text-sm font-mono break-all" value="<?php echo htmlspecialchars($base_url); ?>">
                                <button onclick="copyUrl()" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded transition whitespace-nowrap">
                                    Copy
                                </button>
                            </div>
                        </div>

                        <div class="text-sm text-gray-600 space-y-1 w-full">
                            <p><strong>Generated:</strong> <span id="generated-time">-</span></p>
                            <p><strong>Status:</strong> <span class="text-green-600 font-semibold">Active</span></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($is_active): ?>
                generateUrlQR();
            <?php endif; ?>
        });

        function generateUrlQR() {
            const baseUrl = '<?php echo htmlspecialchars(addslashes($base_url)); ?>';
            const qrContent = 'appUrl_' + baseUrl;
            
            // Clear any existing QR code
            document.getElementById('qrcode').innerHTML = '';
            
            // Generate QR code with appUrl_ prefix
            new QRCode(document.getElementById('qrcode'), {
                text: qrContent,
                width: 300,
                height: 300,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
            
            document.getElementById('url-string').value = baseUrl;
            document.getElementById('generated-time').textContent = new Date().toLocaleString();
        }

        function copyUrl() {
            const urlInput = document.getElementById('url-string');
            urlInput.select();
            document.execCommand('copy');

            Swal.fire({
                icon: 'success',
                title: 'Copied!',
                text: 'URL copied to clipboard',
                timer: 1500,
                showConfirmButton: false
            });
        }
    </script>
</body>
</html>
