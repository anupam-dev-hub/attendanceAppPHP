<?php
/**
 * Monthly Fee Auto-Initializer (Cron Job)
 * 
 * This script should be run at the start of each month to automatically
 * initialize monthly fees for all active students across all organizations.
 * 
 * Setup Instructions:
 * 1. Make this file executable: chmod +x cron_initialize_monthly_fees.php
 * 2. Add to crontab to run on 1st of each month at 1:00 AM:
 *    0 1 1 * * /usr/bin/php /path/to/attendanceAppPHP/org/cron_initialize_monthly_fees.php >> /var/log/monthly_fees.log 2>&1
 * 
 * Or run manually:
 *    php cron_initialize_monthly_fees.php
 */

// Change to script directory
chdir(__DIR__);

require '../config.php';
require 'monthly_fee_functions.php';

// Configuration
$current_month = (int)date('n');
$current_year = (int)date('Y');
$dry_run = false; // Set to true to test without making changes

echo "========================================\n";
echo "Monthly Fee Auto-Initializer\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Month: " . date('F Y', mktime(0, 0, 0, $current_month, 1, $current_year)) . "\n";
echo "Mode: " . ($dry_run ? 'DRY RUN (no changes)' : 'LIVE') . "\n";
echo "========================================\n\n";

try {
    // Get all organizations
    $org_query = "SELECT id, name, email FROM organizations";
    $org_result = $conn->query($org_query);
    
    if (!$org_result) {
        throw new Exception("Failed to fetch organizations: " . $conn->error);
    }
    
    $total_orgs = $org_result->num_rows;
    $processed_orgs = 0;
    $total_students = 0;
    $total_success = 0;
    $total_skipped = 0;
    $total_failed = 0;
    
    echo "Found $total_orgs organization(s) to process.\n\n";
    
    while ($org = $org_result->fetch_assoc()) {
        $org_id = $org['id'];
        $org_name = $org['name'];
        
        echo "Processing: $org_name (ID: $org_id)\n";
        echo str_repeat("-", 50) . "\n";
        
        // Check if organization has active subscription
        $sub_check = $conn->prepare("
            SELECT id FROM subscriptions 
            WHERE org_id = ? 
            AND status = 'active' 
            AND to_date > NOW() 
            LIMIT 1
        ");
        $sub_check->bind_param("i", $org_id);
        $sub_check->execute();
        $sub_result = $sub_check->get_result();
        
        if ($sub_result->num_rows === 0) {
            echo "  ⚠️  Skipped: No active subscription\n\n";
            continue;
        }
        
        // Initialize fees for this organization
        if (!$dry_run) {
            $results = initializeMonthlyFeesForAllActiveStudents($conn, $org_id, $current_month, $current_year);
        } else {
            // In dry run, just count students
            $count_query = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM students 
                WHERE org_id = ? 
                AND is_active = 1 
                AND fee > 0
            ");
            $count_query->bind_param("i", $org_id);
            $count_query->execute();
            $count_result = $count_query->get_result();
            $count_row = $count_result->fetch_assoc();
            
            $results = [
                'total' => $count_row['count'],
                'success' => 0,
                'skipped' => 0,
                'failed' => 0
            ];
        }
        
        $total_students += $results['total'];
        $total_success += $results['success'];
        $total_skipped += $results['skipped'];
        $total_failed += $results['failed'];
        
        echo "  Total Students: {$results['total']}\n";
        echo "  ✅ Success: {$results['success']}\n";
        echo "  ⏭️  Skipped: {$results['skipped']}\n";
        echo "  ❌ Failed: {$results['failed']}\n\n";
        
        $processed_orgs++;
    }
    
    // Summary
    echo "========================================\n";
    echo "SUMMARY\n";
    echo "========================================\n";
    echo "Organizations Processed: $processed_orgs / $total_orgs\n";
    echo "Total Students: $total_students\n";
    echo "✅ Successfully Initialized: $total_success\n";
    echo "⏭️  Skipped (Already Exist): $total_skipped\n";
    echo "❌ Failed: $total_failed\n";
    echo "========================================\n";
    
    // Send email notification (optional)
    if (!$dry_run && $total_success > 0) {
        $admin_email = "admin@yourdomain.com"; // Change this
        $subject = "Monthly Fee Initialization Complete - " . date('F Y');
        $message = "Monthly fees have been initialized successfully.\n\n";
        $message .= "Summary:\n";
        $message .= "- Organizations: $processed_orgs\n";
        $message .= "- Students Processed: $total_students\n";
        $message .= "- Success: $total_success\n";
        $message .= "- Skipped: $total_skipped\n";
        $message .= "- Failed: $total_failed\n";
        
        // Uncomment to enable email notifications
        // mail($admin_email, $subject, $message);
    }
    
    $conn->close();
    
    exit(0); // Success
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    if (isset($conn)) {
        $conn->close();
    }
    
    exit(1); // Error
}
?>
