<?php
// admin/email_tester.php
// Email Configuration & Testing Tool for Hostinger

session_start();
require '../config.php';
require '../functions.php';
require '../email_service.php';

if (!isAdmin()) {
    redirect('index.php');
}

$success = '';
$error = '';
$configStatus = '';

// Check email configuration
if (defined('SMTP_AUTH_EMAIL') && SMTP_AUTH_EMAIL !== 'your-email@yourdomain.com') {
    $configStatus = 'configured';
} else {
    $configStatus = 'not_configured';
}

// Test email if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'send_test') {
        $testEmail = trim($_POST['test_email']);
        
        if (empty($testEmail)) {
            $error = "Please enter a test email address.";
        } elseif (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email address format.";
        } else {
            try {
                $emailService = new EmailService();
                $result = $emailService->testEmail($testEmail);
                
                if ($result['success']) {
                    $success = "Test email sent successfully to $testEmail! Please check your inbox.";
                } else {
                    $error = "Failed to send test email: " . $result['error'];
                }
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
    elseif ($_POST['action'] === 'send_custom') {
        $to = trim($_POST['custom_to']);
        $subject = trim($_POST['custom_subject']);
        $body = $_POST['custom_body'];
        
        if (empty($to) || empty($subject) || empty($body)) {
            $error = "All fields are required.";
        } elseif (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid recipient email format.";
        } else {
            try {
                $emailService = new EmailService();
                $options = [
                    'is_html' => isset($_POST['custom_html']) ? true : false
                ];
                
                $result = $emailService->send($to, $subject, $body, $options);
                
                if ($result['success']) {
                    $success = "Email sent successfully to $to!";
                } else {
                    $error = "Failed to send email: " . $result['error'];
                }
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Tester - Admin Panel</title>
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
                    <a href="email_tester.php" class="text-white hover:text-blue-100 font-medium transition">Email Tester</a>
                    <a href="../logout.php" class="text-white hover:text-red-200 font-medium transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Email Configuration Tester</h2>
            <p class="mt-2 text-sm text-gray-600">Test and verify your Hostinger email configuration.</p>
        </div>

        <!-- Status Card -->
        <div class="mb-6">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Configuration Status</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="p-4 rounded-lg <?php echo $configStatus === 'configured' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
                        <p class="text-sm font-medium <?php echo $configStatus === 'configured' ? 'text-green-800' : 'text-red-800'; ?>">
                            <strong>Status:</strong> <?php echo $configStatus === 'configured' ? '✅ Configured' : '❌ Not Configured'; ?>
                        </p>
                        <p class="text-sm <?php echo $configStatus === 'configured' ? 'text-green-700' : 'text-red-700'; ?> mt-2">
                            <?php echo $configStatus === 'configured' 
                                ? 'Your email service is ready to use.' 
                                : 'Please configure your Hostinger email settings in email_config.php'; 
                            ?>
                        </p>
                    </div>
                    <div class="p-4 rounded-lg bg-blue-50 border border-blue-200">
                        <p class="text-sm font-medium text-blue-800"><strong>Configuration File:</strong></p>
                        <p class="text-xs text-blue-700 mt-2 font-mono">email_config.php</p>
                        <p class="text-xs text-blue-600 mt-1">Update SMTP credentials there</p>
                    </div>
                </div>
            </div>
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

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Test Email Section -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-6">Send Test Email</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="send_test">
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Test Email Address</label>
                        <input type="email" name="test_email" required 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500"
                               placeholder="Enter email to receive test">
                        <p class="text-xs text-gray-500 mt-2">We'll send a test email to verify configuration</p>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded focus:outline-none focus:shadow-outline transition">
                        Send Test Email
                    </button>
                    
                    <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <p class="text-sm text-blue-800"><strong>What this does:</strong></p>
                        <ul class="text-sm text-blue-700 list-disc list-inside mt-2 space-y-1">
                            <li>Connects to Hostinger SMTP server</li>
                            <li>Authenticates with provided credentials</li>
                            <li>Sends a simple test email</li>
                            <li>Confirms delivery capability</li>
                        </ul>
                    </div>
                </form>
            </div>

            <!-- Custom Email Section -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-6">Send Custom Email</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="send_custom">
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Recipient Email</label>
                        <input type="email" name="custom_to" required 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Subject</label>
                        <input type="text" name="custom_subject" required 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Message Body</label>
                        <textarea name="custom_body" required rows="6"
                                  class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="custom_html" id="custom_html" class="h-4 w-4 text-blue-600">
                        <label for="custom_html" class="ml-2 block text-sm text-gray-700">Send as HTML</label>
                    </div>

                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded focus:outline-none focus:shadow-outline transition">
                        Send Custom Email
                    </button>
                </form>
            </div>
        </div>

        <!-- Configuration Guide -->
        <div class="mt-8 bg-white shadow overflow-hidden sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Hostinger SMTP Configuration Guide</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <div class="p-4 bg-gray-50 rounded-lg">
                    <h4 class="font-semibold text-gray-900 mb-3">SMTP Settings</h4>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li><strong>SMTP Host:</strong> <code class="bg-white px-2 py-1 rounded">smtp.hostinger.com</code></li>
                        <li><strong>SMTP Port:</strong> <code class="bg-white px-2 py-1 rounded">465</code> (SSL)</li>
                        <li><strong>Or Port:</strong> <code class="bg-white px-2 py-1 rounded">587</code> (TLS)</li>
                        <li><strong>Security:</strong> <code class="bg-white px-2 py-1 rounded">SSL/TLS</code></li>
                    </ul>
                </div>

                <div class="p-4 bg-gray-50 rounded-lg">
                    <h4 class="font-semibold text-gray-900 mb-3">Update email_config.php</h4>
                    <p class="text-sm text-gray-700 mb-3">Edit the following in <code class="bg-white px-2 py-1 rounded">email_config.php</code>:</p>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li><strong>SMTP_AUTH_EMAIL:</strong> Your Hostinger email</li>
                        <li><strong>SMTP_AUTH_PASSWORD:</strong> Your email password</li>
                        <li><strong>SENDER_NAME:</strong> Your company name</li>
                    </ul>
                </div>
            </div>

            <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <h4 class="font-semibold text-yellow-900 mb-2">⚠️ Important Notes</h4>
                <ul class="text-sm text-yellow-800 space-y-2 list-disc list-inside">
                    <li>Get SMTP credentials from Hostinger Email settings</li>
                    <li>Use app passwords if 2FA is enabled</li>
                    <li>Port 465 = SSL (recommended)</li>
                    <li>Port 587 = TLS (alternative)</li>
                    <li>Keep credentials secure - never commit to version control</li>
                    <li>Consider using environment variables for production</li>
                </ul>
            </div>

            <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h4 class="font-semibold text-blue-900 mb-2">📖 Hostinger Email Setup Steps</h4>
                <ol class="text-sm text-blue-800 space-y-2 list-decimal list-inside">
                    <li>Go to Hostinger Control Panel</li>
                    <li>Navigate to Email &gt; Email Accounts</li>
                    <li>Create an email account (e.g., noreply@yourdomain.com)</li>
                    <li>Get the SMTP settings from Email Accounts &gt; More Details</li>
                    <li>Update email_config.php with your credentials</li>
                    <li>Test using this tool</li>
                </ol>
            </div>
        </div>

        <!-- API Documentation -->
        <div class="mt-8 bg-white shadow overflow-hidden sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">API Documentation</h3>
            
            <div class="space-y-6">
                <div class="p-4 bg-gray-50 rounded-lg">
                    <h4 class="font-semibold text-gray-900 mb-2">Test Email API</h4>
                    <p class="text-sm text-gray-700 mb-2"><strong>Endpoint:</strong> <code>POST /api/test_email.php?action=test</code></p>
                    <p class="text-sm text-gray-700 mb-2"><strong>Parameters:</strong></p>
                    <ul class="text-sm text-gray-700 list-disc list-inside">
                        <li>test_email (required): Email to test</li>
                    </ul>
                </div>

                <div class="p-4 bg-gray-50 rounded-lg">
                    <h4 class="font-semibold text-gray-900 mb-2">Send Custom Email API</h4>
                    <p class="text-sm text-gray-700 mb-2"><strong>Endpoint:</strong> <code>POST /api/test_email.php?action=send</code></p>
                    <p class="text-sm text-gray-700 mb-2"><strong>Parameters:</strong></p>
                    <ul class="text-sm text-gray-700 list-disc list-inside">
                        <li>to (required): Recipient email</li>
                        <li>subject (required): Email subject</li>
                        <li>body (required): Email body</li>
                        <li>is_html (optional): 1 or 0</li>
                        <li>cc (optional): Comma-separated CC emails</li>
                        <li>bcc (optional): Comma-separated BCC emails</li>
                    </ul>
                </div>

                <div class="p-4 bg-gray-50 rounded-lg">
                    <h4 class="font-semibold text-gray-900 mb-2">Get Configuration API</h4>
                    <p class="text-sm text-gray-700"><strong>Endpoint:</strong> <code>GET /api/test_email.php?action=config</code></p>
                    <p class="text-sm text-gray-700 mt-2">Returns current email configuration (masked for security)</p>
                </div>
            </div>
        </div>

        <!-- Integration Example -->
        <div class="mt-8 bg-white shadow overflow-hidden sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Integration Example</h3>
            <p class="text-sm text-gray-700 mb-4">To use the email service in your code:</p>
            
            <div class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-auto">
                <pre class="text-xs">// Include the email service
require 'email_service.php';

// Create service instance
$emailService = new EmailService();

// Send email
$result = $emailService->send(
    'recipient@example.com',
    'Hello!',
    'This is my email body',
    ['is_html' => true]
);

if ($result['success']) {
    echo "Email sent!";
} else {
    echo "Error: " . $result['error'];
}</pre>
            </div>
        </div>
    </div>
</body>
</html>
