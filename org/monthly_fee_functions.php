<?php
// org/monthly_fee_functions.php
// Functions for managing monthly fee initialization for students

/**
 * Check if monthly fee already exists for a student in a specific month/year
 * 
 * @param mysqli $conn Database connection
 * @param int $student_id Student ID
 * @param int $month Month (1-12)
 * @param int $year Year
 * @return bool True if fee entry exists, false otherwise
 */
function hasMonthlyFee($conn, $student_id, $month, $year) {
    $category = "Monthly Fee - " . date('F Y', mktime(0, 0, 0, $month, 1, $year));
    
    $stmt = $conn->prepare("
        SELECT id 
        FROM student_payments 
        WHERE student_id = ? 
        AND category = ? 
        AND transaction_type = 'credit'
        LIMIT 1
    ");
    $stmt->bind_param("is", $student_id, $category);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    
    return $exists;
}

/**
 * Initialize monthly fee for a student for a specific month/year
 * 
 * @param mysqli $conn Database connection
 * @param int $student_id Student ID
 * @param float $fee_amount Monthly fee amount
 * @param int $month Month (1-12)
 * @param int $year Year
 * @return array Result with 'success' (bool) and 'message' (string)
 */
function initializeMonthlyFee($conn, $student_id, $fee_amount, $month, $year) {
    // Check if fee amount is valid
    if ($fee_amount <= 0) {
        return [
            'success' => false,
            'message' => 'Invalid fee amount. Fee must be greater than 0.'
        ];
    }

    // Always store fee as a negative credit (amount owed)
    $fee_amount = -abs($fee_amount);
    
    // Check for duplicate entry
    if (hasMonthlyFee($conn, $student_id, $month, $year)) {
        return [
            'success' => false,
            'message' => 'Monthly fee for ' . date('F Y', mktime(0, 0, 0, $month, 1, $year)) . ' already exists for this student.'
        ];
    }
    
    // Create fee entry
    $category = "Monthly Fee - " . date('F Y', mktime(0, 0, 0, $month, 1, $year));
    $description = "Monthly tuition fee for " . date('F Y', mktime(0, 0, 0, $month, 1, $year));
    
    $stmt = $conn->prepare("
        INSERT INTO student_payments 
        (student_id, amount, transaction_type, category, description) 
        VALUES (?, ?, 'credit', ?, ?)
    ");
    $stmt->bind_param("idss", $student_id, $fee_amount, $category, $description);
    
    if ($stmt->execute()) {
        $stmt->close();
        return [
            'success' => true,
            'message' => 'Monthly fee for ' . date('F Y', mktime(0, 0, 0, $month, 1, $year)) . ' initialized successfully.'
        ];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return [
            'success' => false,
            'message' => 'Database error: ' . $error
        ];
    }
}

/**
 * Initialize monthly fee for current month when student is activated
 * 
 * @param mysqli $conn Database connection
 * @param int $student_id Student ID
 * @param float $fee_amount Monthly fee amount
 * @return array Result with 'success' (bool) and 'message' (string)
 */
function initializeCurrentMonthFee($conn, $student_id, $fee_amount) {
    $current_month = (int)date('n');
    $current_year = (int)date('Y');
    
    return initializeMonthlyFee($conn, $student_id, $fee_amount, $current_month, $current_year);
}

/**
 * Initialize monthly fees for multiple months for a student
 * 
 * @param mysqli $conn Database connection
 * @param int $student_id Student ID
 * @param float $fee_amount Monthly fee amount
 * @param int $start_month Start month (1-12)
 * @param int $start_year Start year
 * @param int $months_count Number of months to initialize
 * @return array Results with count and details
 */
function initializeMultipleMonthlyFees($conn, $student_id, $fee_amount, $start_month, $start_year, $months_count) {
    $results = [
        'total' => $months_count,
        'success' => 0,
        'failed' => 0,
        'skipped' => 0,
        'details' => []
    ];
    
    for ($i = 0; $i < $months_count; $i++) {
        $month = $start_month + $i;
        $year = $start_year;
        
        // Handle year overflow
        while ($month > 12) {
            $month -= 12;
            $year++;
        }
        
        $result = initializeMonthlyFee($conn, $student_id, $fee_amount, $month, $year);
        
        if ($result['success']) {
            $results['success']++;
        } else {
            if (strpos($result['message'], 'already exists') !== false) {
                $results['skipped']++;
            } else {
                $results['failed']++;
            }
        }
        
        $results['details'][] = [
            'month' => $month,
            'year' => $year,
            'result' => $result
        ];
    }
    
    return $results;
}

/**
 * Initialize monthly fees for all active students in an organization
 * Reads fees from fees_json column and creates payment entries for each fee
 * 
 * @param mysqli $conn Database connection
 * @param int $org_id Organization ID
 * @param int $month Month (1-12)
 * @param int $year Year
 * @return array Results with count and details
 */
function initializeMonthlyFeesForAllActiveStudents($conn, $org_id, $month, $year) {
    // Fetch all active students with their fee amounts from fees_json
    $query = "
        SELECT id, name, fees_json 
        FROM students 
        WHERE org_id = ? 
        AND is_active = 1 
        AND fees_json IS NOT NULL
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $org_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $results = [
        'total' => 0,
        'success' => 0,
        'failed' => 0,
        'skipped' => 0,
        'details' => []
    ];
    
    while ($row = $result->fetch_assoc()) {
        // Parse fees_json to get individual fees
        $fees = json_decode($row['fees_json'], true);
        
        if (!is_array($fees) || empty($fees)) {
            continue;
        }
        
        // For each fee in the student's fees_json
        foreach ($fees as $fee_name => $fee_amount) {
            $results['total']++;
            
            // Initialize this specific fee for the month
            $fee_result = initializeMonthlyFeeForFeeType($conn, $row['id'], $fee_name, $fee_amount, $month, $year);
            
            if ($fee_result['success']) {
                $results['success']++;
            } else {
                if (strpos($fee_result['message'], 'already exists') !== false) {
                    $results['skipped']++;
                } else {
                    $results['failed']++;
                }
            }
            
            $results['details'][] = [
                'student_id' => $row['id'],
                'student_name' => $row['name'],
                'fee_name' => $fee_name,
                'fee_amount' => $fee_amount,
                'result' => $fee_result
            ];
        }
    }
    
    $stmt->close();
    return $results;
}

/**
 * Initialize monthly fee for a specific fee type for a student
 * 
 * @param mysqli $conn Database connection
 * @param int $student_id Student ID
 * @param string $fee_name Name of the fee (e.g., "Monthly Fee", "Library Fee")
 * @param float $fee_amount Fee amount
 * @param int $month Month (1-12)
 * @param int $year Year
 * @return array Result with 'success' (bool) and 'message' (string)
 */
function initializeMonthlyFeeForFeeType($conn, $student_id, $fee_name, $fee_amount, $month, $year) {
    // Check if fee amount is valid
    if ($fee_amount <= 0) {
        return [
            'success' => false,
            'message' => "Invalid fee amount for $fee_name. Fee must be greater than 0."
        ];
    }

    // Store as negative credit (amount owed)
    $fee_amount = -abs($fee_amount);
    
    // Check for duplicate entry
    $category = "$fee_name - " . date('F Y', mktime(0, 0, 0, $month, 1, $year));
    
    $stmt = $conn->prepare("
        SELECT id 
        FROM student_payments 
        WHERE student_id = ? 
        AND category = ? 
        AND transaction_type = 'credit'
        LIMIT 1
    ");
    $stmt->bind_param("is", $student_id, $category);
    $stmt->execute();
    $check_result = $stmt->get_result();
    $stmt->close();
    
    if ($check_result->num_rows > 0) {
        return [
            'success' => false,
            'message' => "$fee_name for " . date('F Y', mktime(0, 0, 0, $month, 1, $year)) . " already exists."
        ];
    }
    
    // Create fee entry
    $description = "$fee_name for " . date('F Y', mktime(0, 0, 0, $month, 1, $year));
    
    $stmt = $conn->prepare("
        INSERT INTO student_payments 
        (student_id, amount, transaction_type, category, description) 
        VALUES (?, ?, 'credit', ?, ?)
    ");
    $stmt->bind_param("idss", $student_id, $fee_amount, $category, $description);
    
    if ($stmt->execute()) {
        $stmt->close();
        return [
            'success' => true,
            'message' => "$fee_name for " . date('F Y', mktime(0, 0, 0, $month, 1, $year)) . " initialized."
        ];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return [
            'success' => false,
            'message' => 'Database error: ' . $error
        ];
    }
}

/**
 * Get monthly fee status for a student
 * 
 * @param mysqli $conn Database connection
 * @param int $student_id Student ID
 * @param int $months_back How many months back to check (default: 6)
 * @return array Monthly fee status
 */
function getMonthlyFeeStatus($conn, $student_id, $months_back = 6) {
    $status = [];
    $current_month = (int)date('n');
    $current_year = (int)date('Y');
    
    for ($i = 0; $i < $months_back; $i++) {
        $month = $current_month - $i;
        $year = $current_year;
        
        // Handle year underflow
        while ($month <= 0) {
            $month += 12;
            $year--;
        }
        
        $has_fee = hasMonthlyFee($conn, $student_id, $month, $year);
        
        // Check if there's a payment for this month
        $category = "Monthly Fee - " . date('F Y', mktime(0, 0, 0, $month, 1, $year));
        
        $stmt = $conn->prepare("
            SELECT 
                SUM(CASE WHEN transaction_type = 'credit' THEN ABS(amount) ELSE 0 END) as total_due,
                SUM(CASE WHEN transaction_type = 'debit' THEN ABS(amount) ELSE 0 END) as total_paid
            FROM student_payments 
            WHERE student_id = ? 
            AND category = ?
        ");
        $stmt->bind_param("is", $student_id, $category);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        $total_due = (float)($row['total_due'] ?? 0);
        $total_paid = (float)($row['total_paid'] ?? 0);
        $balance = $total_due - $total_paid;
        
        $status[] = [
            'month' => $month,
            'year' => $year,
            'month_name' => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
            'has_fee' => $has_fee,
            'total_due' => $total_due,
            'total_paid' => $total_paid,
            'balance' => $balance,
            'status' => $balance <= 0 ? 'paid' : ($has_fee ? 'pending' : 'not_initialized')
        ];
    }
    
    return $status;
}
?>
