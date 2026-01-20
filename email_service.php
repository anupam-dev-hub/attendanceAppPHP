<?php
// email_service.php
// Email Service API using SMTP for Hostinger

require_once __DIR__ . '/email_config.php';

/**
 * Email Service Class - Direct SMTP Implementation for Hostinger
 */
class EmailService {
    private $host;
    private $port;
    private $secure;
    private $from_email;
    private $from_name;
    private $auth_email;
    private $auth_password;
    
    public function __construct() {
        $this->host = SMTP_HOST;
        $this->port = SMTP_PORT;
        $this->secure = SMTP_SECURE;
        $this->from_email = SENDER_EMAIL;
        $this->from_name = SENDER_NAME;
        $this->auth_email = SMTP_AUTH_EMAIL;
        $this->auth_password = SMTP_AUTH_PASSWORD;
    }
    
    /**
     * Send email via SMTP
     */
    public function send($to, $subject, $body, $options = []) {
        $isHtml = $options['is_html'] ?? true;
        $cc = $options['cc'] ?? [];
        $bcc = $options['bcc'] ?? [];
        $replyTo = $options['reply_to'] ?? $this->from_email;
        $attachments = $options['attachments'] ?? [];
        
        try {
            // Connect to SMTP server
            $errno = 0;
            $errstr = 0;
            
            if ($this->secure === 'ssl') {
                $connection = @fsockopen('ssl://' . $this->host, $this->port, $errno, $errstr, 10);
            } else {
                $connection = @fsockopen($this->host, $this->port, $errno, $errstr, 10);
            }
            
            if (!$connection) {
                throw new Exception("Cannot connect to SMTP server: $errstr ($errno)");
            }
            
            // Read server response
            $response = fgets($connection, 1024);
            if (strpos($response, '220') === false) {
                throw new Exception("SMTP Connection Error: $response");
            }
            
            // Send HELO
            $this->sendCommand($connection, "HELO " . gethostname(), 250);
            
            // Start TLS if needed
            if ($this->secure === 'tls') {
                $this->sendCommand($connection, "STARTTLS", 220);
                stream_socket_enable_crypto($connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            }
            
            // Authenticate
            $this->sendCommand($connection, "AUTH LOGIN", 334);
            $this->sendCommand($connection, base64_encode($this->auth_email), 334);
            $this->sendCommand($connection, base64_encode($this->auth_password), 235);
            
            // Set mail from
            $this->sendCommand($connection, "MAIL FROM: <" . $this->from_email . ">", 250);
            
            // Set recipients
            $this->sendCommand($connection, "RCPT TO: <$to>", 250);
            
            foreach ($cc as $recipient) {
                $this->sendCommand($connection, "RCPT TO: <$recipient>", 250);
            }
            
            foreach ($bcc as $recipient) {
                $this->sendCommand($connection, "RCPT TO: <$recipient>", 250);
            }
            
            // Send data
            $this->sendCommand($connection, "DATA", 354);
            
            // Build email headers
            $headers = "From: " . $this->from_name . " <" . $this->from_email . ">\r\n";
            $headers .= "Reply-To: $replyTo\r\n";
            $headers .= "To: $to\r\n";
            
            if (!empty($cc)) {
                $headers .= "Cc: " . implode(", ", $cc) . "\r\n";
            }
            
            $headers .= "Subject: $subject\r\n";
            $headers .= "Date: " . date('r') . "\r\n";
            $headers .= "X-Mailer: Attendance-App-EmailService\r\n";
            
            if ($isHtml) {
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            } else {
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            }
            
            $headers .= "Content-Transfer-Encoding: 8bit\r\n";
            $headers .= "\r\n";
            
            // Send headers and body
            $message = $headers . $body . "\r\n.\r\n";
            fwrite($connection, $message);
            
            $response = fgets($connection, 1024);
            if (strpos($response, '250') === false) {
                throw new Exception("SMTP Send Error: $response");
            }
            
            // Close connection
            $this->sendCommand($connection, "QUIT", 221);
            fclose($connection);
            
            return [
                'success' => true,
                'message' => 'Email sent successfully',
                'to' => $to
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'to' => $to
            ];
        }
    }
    
    /**
     * Send SMTP command
     */
    private function sendCommand($connection, $command, $expectedCode) {
        fwrite($connection, $command . "\r\n");
        $response = fgets($connection, 1024);
        
        if (strpos($response, (string)$expectedCode) === false) {
            throw new Exception("SMTP Error (expected $expectedCode): $response");
        }
        
        return $response;
    }
    
    /**
     * Test email sending
     */
    public function testEmail($testEmail) {
        $subject = "Test Email - Attendance App";
        $body = "This is a test email from Attendance App.\n\n";
        $body .= "If you receive this email, your email configuration is working correctly!\n\n";
        $body .= "Test Details:\n";
        $body .= "From: " . $this->from_email . "\n";
        $body .= "Sent: " . date('Y-m-d H:i:s') . "\n";
        
        return $this->send($testEmail, $subject, $body, ['is_html' => false]);
    }
    
    /**
     * Send notification email
     */
    public function sendNotification($to, $subject, $templateData = []) {
        $body = "Notification: $subject\n\n";
        foreach ($templateData as $key => $value) {
            $body .= ucfirst($key) . ": " . $value . "\n";
        }
        
        return $this->send($to, $subject, $body, ['is_html' => false]);
    }
}

?>
        
