<?php
// email_config.php
// Hostinger Email Configuration

define('EMAIL_DRIVER', 'smtp'); // Options: 'smtp', 'mail', 'sendmail'

// SMTP Configuration (for Hostinger)
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465); // 465 for SSL, 587 for TLS
define('SMTP_SECURE', 'ssl'); // 'ssl' or 'tls'
define('SMTP_AUTH_EMAIL', getenv('HOSTINGER_EMAIL') ?: 'moonbr@attendanceit.in'); // Set in environment or update here
define('SMTP_AUTH_PASSWORD', getenv('HOSTINGER_PASSWORD') ?: '+1OjN2vL='); // Set in environment or update here

// Sender Configuration
define('SENDER_EMAIL', SMTP_AUTH_EMAIL);
define('SENDER_NAME', 'Attendance App'); // Your app name

// Enable/Disable Email Debugging
define('EMAIL_DEBUG', true);

// Email Templates
define('EMAIL_TEMPLATES_DIR', __DIR__ . '/email_templates/');

// Retry Configuration
define('EMAIL_RETRY_COUNT', 3);
define('EMAIL_RETRY_DELAY', 2); // seconds

?>
