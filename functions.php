<?php
// functions.php

function sendEmail($to, $subject, $message, $options = []) {
    // Try to use the new email service for Hostinger
    if (file_exists(__DIR__ . '/email_service.php')) {
        try {
            require_once __DIR__ . '/email_service.php';
            $emailService = new EmailService();
            $result = $emailService->send($to, $subject, $message, array_merge(['is_html' => false], $options));
            
            // Log the email attempt
            $logFile = __DIR__ . '/email.log';
            $timestamp = date('Y-m-d H:i:s');
            $status = $result['success'] ? 'SUCCESS' : 'FAILED';
            $details = $result['success'] ? '' : ' - Error: ' . $result['error'];
            $logEntry = "[$timestamp] [$status] To: $to | Subject: $subject{$details}" . PHP_EOL;
            file_put_contents($logFile, $logEntry, FILE_APPEND);
            
            return $result['success'];
        } catch (Exception $e) {
            // Fallback to logging if service fails
            $logFile = __DIR__ . '/email.log';
            $timestamp = date('Y-m-d H:i:s');
            $logEntry = "[$timestamp] [ERROR] Exception: " . $e->getMessage() . " | To: $to | Subject: $subject" . PHP_EOL;
            file_put_contents($logFile, $logEntry, FILE_APPEND);
            return false;
        }
    }
    
    // Fallback: Log only if service not available
    $logFile = __DIR__ . '/email.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [LOG] To: $to | Subject: $subject | Message: $message" . PHP_EOL . str_repeat('-', 50) . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    return true;
}

function uploadFile($file, $targetDir) {
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = basename($file["name"]);
    $targetFilePath = $targetDir . uniqid() . '_' . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    
    // Allow certain file formats
    $allowTypes = array('jpg','png','jpeg','gif','pdf','doc','docx');
    if(in_array(strtolower($fileType), $allowTypes)){
        if(move_uploaded_file($file["tmp_name"], $targetFilePath)){
            return $targetFilePath;
        }
    }
    return false;
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isOrg() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'org';
}

function isSubscribed($org_id) {
    global $conn;
    
    // First, check and expire any outdated subscriptions
    checkAndExpireSubscriptions($org_id);

    $stmt = $conn->prepare("SELECT id FROM subscriptions WHERE org_id = ? AND status = 'active' AND to_date > NOW()");
    $stmt->bind_param("i", $org_id);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}

function checkAndExpireSubscriptions($org_id) {
    global $conn;
    $stmt = $conn->prepare("UPDATE subscriptions SET status = 'expired' WHERE org_id = ? AND status = 'active' AND to_date < NOW()");
    $stmt->bind_param("i", $org_id);
    $stmt->execute();
}

function getPendingSubscriptionCount() {
    global $conn;
    $result = $conn->query("SELECT COUNT(*) as count FROM subscriptions WHERE status = 'pending'");
    $row = $result->fetch_assoc();
    return $row['count'];
}
?>
