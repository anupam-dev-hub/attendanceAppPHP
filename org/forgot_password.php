<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Attendance App</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-500 to-blue-700 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-xl p-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900">Attendance App</h2>
                <p class="text-gray-600 text-sm mt-2">Reset Your Password</p>
            </div>

            <?php
            session_start();
            require '../config.php';
            require '../functions.php';
            require_once '../email_config.php';

            $message = '';
            $error = '';
            $step = 'email'; // 'email' or 'otp'

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (isset($_POST['step']) && $_POST['step'] === 'email') {
                    // Step 1: Send OTP
                    $email = trim($_POST['email']);

                    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $error = 'Please enter a valid email address.';
                    } else {
                        // Check if org exists
                        $stmt = $conn->prepare("SELECT id FROM organizations WHERE email = ?");
                        $stmt->bind_param("s", $email);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            $org = $result->fetch_assoc();
                            $org_id = $org['id'];

                            // Generate OTP (6 digits)
                            $otp_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                            $otp_token = bin2hex(random_bytes(32));

                            // Expire in 15 minutes
                            $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                            // Store OTP
                            $insertStmt = $conn->prepare("INSERT INTO org_password_resets (org_id, email, otp_token, otp_code, expires_at) VALUES (?, ?, ?, ?, ?)");
                            $insertStmt->bind_param("issss", $org_id, $email, $otp_token, $otp_code, $expires_at);

                            if ($insertStmt->execute()) {
                                // Send OTP email
                                $subject = "Password Reset OTP - Attendance App";
                                $templatePath = EMAIL_TEMPLATES_DIR . 'password_reset_otp.html';
                                $body = '';

                                if (file_exists($templatePath)) {
                                    $body = file_get_contents($templatePath);
                                    $placeholders = [
                                        '{OTP_CODE}' => $otp_code,
                                        '{EXPIRY_MINUTES}' => '15',
                                        '{SUPPORT_EMAIL}' => SENDER_EMAIL,
                                    ];
                                    $body = strtr($body, $placeholders);
                                } else {
                                    $body = "Your password reset OTP is: $otp_code\n\nThis OTP is valid for 15 minutes.\n\nIf you didn't request this, ignore this email.";
                                }

                                sendEmail($email, $subject, $body, ['is_html' => file_exists($templatePath)]);

                                // Store token in session for verification
                                $_SESSION['reset_token'] = $otp_token;
                                $_SESSION['reset_email'] = $email;

                                $message = 'OTP has been sent to your email. Check your inbox for the 6-digit code.';
                                $step = 'otp';
                            } else {
                                $error = 'Failed to generate OTP. Please try again.';
                            }
                            $insertStmt->close();
                        } else {
                            $error = 'No account found with this email address.';
                        }
                        $stmt->close();
                    }
                } elseif (isset($_POST['step']) && $_POST['step'] === 'otp') {
                    // Step 2: Verify OTP and reset password
                    $otp_code = trim($_POST['otp_code']);
                    $new_password = trim($_POST['new_password']);
                    $confirm_password = trim($_POST['confirm_password']);

                    if (empty($otp_code)) {
                        $error = 'Please enter the OTP code.';
                        $step = 'otp';
                    } elseif (empty($new_password) || empty($confirm_password)) {
                        $error = 'Please enter and confirm your new password.';
                        $step = 'otp';
                    } elseif ($new_password !== $confirm_password) {
                        $error = 'Passwords do not match.';
                        $step = 'otp';
                    } elseif (strlen($new_password) < 6) {
                        $error = 'Password must be at least 6 characters long.';
                        $step = 'otp';
                    } else {
                        // Verify OTP
                        $reset_email = $_SESSION['reset_email'] ?? '';
                        $stmt = $conn->prepare("SELECT id, org_id FROM org_password_resets WHERE otp_code = ? AND email = ? AND is_used = 0 AND expires_at > NOW()");
                        $stmt->bind_param("ss", $otp_code, $reset_email);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            $reset = $result->fetch_assoc();
                            $org_id = $reset['org_id'];
                            $reset_id = $reset['id'];

                            // Hash new password
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                            // Update org password
                            $updateStmt = $conn->prepare("UPDATE organizations SET password = ? WHERE id = ?");
                            $updateStmt->bind_param("si", $hashed_password, $org_id);

                            if ($updateStmt->execute()) {
                                // Mark OTP as used
                                $markStmt = $conn->prepare("UPDATE org_password_resets SET is_used = 1 WHERE id = ?");
                                $markStmt->bind_param("i", $reset_id);
                                $markStmt->execute();
                                $markStmt->close();

                                // Clear session
                                unset($_SESSION['reset_token']);
                                unset($_SESSION['reset_email']);

                                $message = 'Password reset successfully! You can now login with your new password.';
                                $step = 'success';
                            } else {
                                $error = 'Failed to update password. Please try again.';
                                $step = 'otp';
                            }
                            $updateStmt->close();
                        } else {
                            $error = 'Invalid OTP or OTP has expired. Please try again.';
                            $step = 'otp';
                        }
                        $stmt->close();
                    }
                }
            }
            ?>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($message && $step !== 'success'): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($step === 'success'): ?>
                <div class="text-center">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
                        <div class="text-green-600 text-5xl mb-4">✓</div>
                        <h3 class="text-xl font-bold text-green-800 mb-2">Password Reset Successful!</h3>
                        <p class="text-green-700 mb-6">Your password has been reset. You can now login with your new credentials.</p>
                        <a href="index.php" class="inline-block bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded transition">
                            Go to Login
                        </a>
                    </div>
                </div>
            <?php elseif ($step === 'email'): ?>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="step" value="email">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Email Address</label>
                        <input type="email" name="email" required placeholder="your@organization.com" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition">
                        Send OTP
                    </button>
                    <div class="text-center">
                        <a href="index.php" class="text-blue-600 hover:text-blue-800 text-sm">Back to Login</a>
                    </div>
                </form>
            <?php else: ?>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="step" value="otp">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">OTP Code</label>
                        <input type="text" name="otp_code" required placeholder="000000" maxlength="6" pattern="\d{6}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-center text-2xl tracking-widest">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">New Password</label>
                        <div class="relative">
                            <input type="password" name="new_password" id="new_password" required placeholder="Enter new password" class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <button type="button" onclick="togglePassword('new_password')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-600 hover:text-gray-800">
                                <svg class="h-5 w-5 toggle-eye" id="eye-new" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <svg class="h-5 w-5 toggle-eye-slash hidden" id="eye-slash-new" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Confirm Password</label>
                        <div class="relative">
                            <input type="password" name="confirm_password" id="confirm_password" required placeholder="Confirm new password" class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <button type="button" onclick="togglePassword('confirm_password')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-600 hover:text-gray-800">
                                <svg class="h-5 w-5 toggle-eye" id="eye-confirm" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <svg class="h-5 w-5 toggle-eye-slash hidden" id="eye-slash-confirm" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition">
                        Reset Password
                    </button>
                    <div class="text-center">
                        <a href="index.php" class="text-blue-600 hover:text-blue-800 text-sm">Back to Login</a>
                    </div>
                </form>
            <?php endif; ?>

            <div class="mt-8 pt-8 border-t border-gray-200">
                <p class="text-center text-gray-600 text-xs">
                    &copy; 2025 Attendance App. All rights reserved.
                </p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const eyeIcon = document.getElementById(`eye-${fieldId}`);
            const eyeSlashIcon = document.getElementById(`eye-slash-${fieldId}`);

            if (field.type === 'password') {
                field.type = 'text';
                eyeIcon.classList.add('hidden');
                eyeSlashIcon.classList.remove('hidden');
            } else {
                field.type = 'password';
                eyeIcon.classList.remove('hidden');
                eyeSlashIcon.classList.add('hidden');
            }
        }
    </script>
</body>
</html>
