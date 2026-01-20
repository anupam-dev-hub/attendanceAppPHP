<?php
// org/qr_token.php
session_start();
require '../config.php';
require '../functions.php';

if (!isOrg()) {
    redirect('index.php');
}

$org_id = $_SESSION['user_id'];
$is_active = isSubscribed($org_id);

// Generate API token if not present in session
if (!isset($_SESSION['api_token'])) {
    require __DIR__ . '/../api/auth_utils.php';
    $token_data = create_token_for_org($conn, $org_id, 30*24*60*60);
    if ($token_data) {
        $_SESSION['api_token'] = $token_data['token'];
    }
}

$api_token = $_SESSION['api_token'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App Token - Attendance API</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <?php include 'navbar.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">App Token Management</h2>
            <p class="mt-2 text-sm text-gray-600">Use this app token to authenticate attendance API requests.</p>
        </div>

        <?php if (!$is_active): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-8" role="alert">
                <span class="block sm:inline">Your subscription is not active. <a href="subscribe.php" class="font-bold underline hover:text-red-900">Subscribe Now</a> to access this feature.</span>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-8">
                <!-- QR Code Display -->
                <div class="bg-white shadow-lg rounded-lg p-8">
                    <h3 class="text-xl font-semibold text-gray-800 mb-6">Your App Token</h3>
                    
                    <div id="qr-loading" class="text-center py-12">
                        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-teal-600"></div>
                        <p class="mt-4 text-gray-600">Loading app token...</p>
                    </div>

                    <div id="qr-display" class="hidden">
                        <div class="flex justify-center mb-6">
                            <div id="qrcode" style="display: inline-block; padding: 20px; background: white; border: 4px solid #0d9488; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);"></div>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Token String:</label>
                            <div class="flex items-center gap-2">
                                <input type="text" id="token-string" readonly class="flex-1 bg-white border border-gray-300 rounded px-3 py-2 text-sm font-mono" value="">
                                <button onclick="copyToken()" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded transition">
                                    Copy
                                </button>
                            </div>
                        </div>

                        <div class="text-sm text-gray-600 space-y-1">
                            <p><strong>Created:</strong> <span id="token-created">-</span></p>
                            <p><strong>Status:</strong> <span class="text-green-600 font-semibold">Active</span></p>
                        </div>

                        <div class="mt-6">
                            <button onclick="resetToken()" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded transition">
                                Reset Token
                            </button>
                            <p class="mt-2 text-xs text-gray-500 text-center">⚠️ Resetting will invalidate the current token immediately</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        let currentToken = '';

        // Load app token on page load
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($is_active): ?>
                loadQRToken();
            <?php endif; ?>
        });

        async function loadQRToken() {
            try {
                const apiToken = '<?php echo $api_token; ?>';
                
                if (!apiToken) {
                    throw new Error('API token not found. Please logout and login again.');
                }
                
                const response = await fetch('../api/qr_token.php', {
                    headers: {
                        'Authorization': 'Bearer ' + apiToken
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.status === 'success') {
                    displayQRToken(data);
                } else {
                    throw new Error(data.message || 'Failed to load app token');
                }
            } catch (error) {
                console.error('Error loading app token:', error);
                
                // Hide loading spinner
                document.getElementById('qr-loading').classList.add('hidden');
                
                // Show error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded';
                errorDiv.innerHTML = `
                    <p class="font-bold">Error Loading App Token</p>
                    <p class="text-sm">${error.message}</p>
                    <p class="text-sm mt-2">Please try:
                        <br>1. <a href="../logout.php" class="underline">Logout</a> and login again
                        <br>2. Check browser console for details (F12)
                        <br>3. Contact support if issue persists
                    </p>
                `;
                document.getElementById('qr-display').parentElement.appendChild(errorDiv);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: `<p>${error.message}</p><p class="text-sm mt-2">Try logging out and back in.</p>`
                });
            }
        }

        function displayQRToken(data) {
            currentToken = data.token;

            // Clear any existing QR code
            document.getElementById('qrcode').innerHTML = '';
            
            // Generate QR code using QRCode.js (same as student system)
            new QRCode(document.getElementById('qrcode'), {
                text: data.token,
                width: 300,
                height: 300,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });
            
            document.getElementById('token-string').value = data.token;
            document.getElementById('token-created').textContent = new Date(data.created_at).toLocaleString();

            // Hide loading, show display
            document.getElementById('qr-loading').classList.add('hidden');
            document.getElementById('qr-display').classList.remove('hidden');
        }

        function copyToken() {
            const tokenInput = document.getElementById('token-string');
            tokenInput.select();
            document.execCommand('copy');

            Swal.fire({
                icon: 'success',
                title: 'Copied!',
                text: 'Token copied to clipboard',
                timer: 1500,
                showConfirmButton: false
            });
        }

        async function resetToken() {
            const result = await Swal.fire({
                icon: 'warning',
                title: 'Reset App Token?',
                text: 'This will invalidate your current token immediately. Any systems using the old QR code will need to update.',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, reset it',
                cancelButtonText: 'Cancel'
            });

            if (result.isConfirmed) {
                try {
                    // Show loading
                    document.getElementById('qr-display').classList.add('hidden');
                    document.getElementById('qr-loading').classList.remove('hidden');

                    const response = await fetch('../api/qr_token.php', {
                        method: 'POST',
                        headers: {
                            'Authorization': 'Bearer <?php echo $api_token; ?>'
                        }
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        displayQRToken(data);
                        Swal.fire({
                            icon: 'success',
                            title: 'Token Reset!',
                            text: 'Your new app token has been generated.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        throw new Error(data.message || 'Failed to reset token');
                    }
                } catch (error) {
                    console.error('Error resetting app token:', error);
                    document.getElementById('qr-loading').classList.add('hidden');
                    document.getElementById('qr-display').classList.remove('hidden');
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to reset token. Please try again.'
                    });
                }
            }
        }
    </script>
</body>
</html>
