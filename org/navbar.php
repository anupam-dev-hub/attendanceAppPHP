<?php
// org/navbar.php
// Reusable navigation bar for organization pages

// Determine current page for active highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$is_subscribed = isSubscribed($org_id);
$nav_links_class = (isset($force_show_nav) && $force_show_nav)
    ? 'flex flex-wrap items-center space-x-6'
    : 'hidden md:flex items-center space-x-6';

// Fetch admin contact details (first admin)
$contact_email = '';
$contact_phone = '';
$contact_address = '';
$adminStmt = $conn->prepare("SELECT contact_email, contact_phone, contact_address FROM admins ORDER BY id ASC LIMIT 1");
if ($adminStmt) {
    $adminStmt->execute();
    $adminStmt->bind_result($contact_email, $contact_phone, $contact_address);
    $adminStmt->fetch();
    $adminStmt->close();
}

// Fetch organization logo
$org_logo = '';
$orgLogoStmt = $conn->prepare("SELECT logo FROM organizations WHERE id = ?");
if ($orgLogoStmt) {
    $orgLogoStmt->bind_param("i", $org_id);
    $orgLogoStmt->execute();
    $orgLogoStmt->bind_result($org_logo);
    $orgLogoStmt->fetch();
    $orgLogoStmt->close();
}

// Check if current month fees are initialized
$current_month_initialized = true; // Default to true (no alert)
$current_month_salary_initialized = true; // Default to true (no alert)
$current_month = date('n');
$current_year = date('Y');
$current_month_year = date('F Y');

// First, check if org has any fees configured
$orgFeesStmt = $conn->prepare("
    SELECT fee_name FROM org_fees WHERE org_id = ?
");
if ($orgFeesStmt) {
    $orgFeesStmt->bind_param("i", $org_id);
    $orgFeesStmt->execute();
    $orgFeesResult = $orgFeesStmt->get_result();
    $configured_fees = [];
    while ($fee_row = $orgFeesResult->fetch_assoc()) {
        $configured_fees[] = $fee_row['fee_name'];
    }
    $orgFeesStmt->close();
    
    // Only check initialization if org has fees configured
    if (!empty($configured_fees)) {
        // Get count of active students
        $activeStudentStmt = $conn->prepare("
            SELECT COUNT(*) as active_count 
            FROM students 
            WHERE org_id = ? AND is_active = 1 AND fees_json IS NOT NULL
        ");
        $activeStudentStmt->bind_param("i", $org_id);
        $activeStudentStmt->execute();
        $activeStudentStmt->bind_result($active_count);
        $activeStudentStmt->fetch();
        $activeStudentStmt->close();
        
        if ($active_count > 0) {
            // Check if fees are initialized for current month
            // For each configured fee type, check if at least 80% of active students have it
            $fees_initialized = true;
            foreach ($configured_fees as $fee_name) {
                $category_pattern = $fee_name . " - " . $current_month_year;
                
                $feeCheckStmt = $conn->prepare("
                    SELECT COUNT(DISTINCT sp.student_id) as initialized_count
                    FROM student_payments sp
                    INNER JOIN students s ON sp.student_id = s.id
                    WHERE s.org_id = ?
                    AND s.is_active = 1
                    AND sp.category = ?
                    AND sp.transaction_type = 'credit'
                ");
                $feeCheckStmt->bind_param("is", $org_id, $category_pattern);
                $feeCheckStmt->execute();
                $feeCheckStmt->bind_result($initialized_count);
                $feeCheckStmt->fetch();
                $feeCheckStmt->close();
                
                // If less than 80% have this fee initialized, show alert
                if ($initialized_count < ($active_count * 0.8)) {
                    $fees_initialized = false;
                    break;
                }
            }
            
            $current_month_initialized = $fees_initialized;
        }
    }
}

// Check if current month salaries are initialized for employees
$activeEmpStmt = $conn->prepare("
    SELECT COUNT(*) as active_count 
    FROM employees 
    WHERE org_id = ? AND is_active = 1
");
if ($activeEmpStmt) {
    $activeEmpStmt->bind_param("i", $org_id);
    $activeEmpStmt->execute();
    $activeEmpStmt->bind_result($active_emp_count);
    $activeEmpStmt->fetch();
    $activeEmpStmt->close();
    
    if ($active_emp_count > 0) {
        $salary_category = "Salary - " . $current_month_year;
        $salaryCheckStmt = $conn->prepare("
            SELECT COUNT(DISTINCT ep.employee_id) as initialized_count
            FROM employee_payments ep
            INNER JOIN employees e ON ep.employee_id = e.id
            WHERE e.org_id = ?
            AND e.is_active = 1
            AND ep.category = ?
            AND ep.transaction_type = 'credit'
        ");
        if ($salaryCheckStmt) {
            $salaryCheckStmt->bind_param("is", $org_id, $salary_category);
            $salaryCheckStmt->execute();
            $salaryCheckStmt->bind_result($salary_initialized_count);
            $salaryCheckStmt->fetch();
            $salaryCheckStmt->close();
            
            if ($salary_initialized_count < ($active_emp_count * 0.8)) {
                $current_month_salary_initialized = false;
            }
        }
    }
}
?>
<style>
    /* Fixed navbar */
    nav.fixed-navbar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
    }
    
    /* Dropdown styles */
    .dropdown { position: relative; display: inline-block; }
    .dropdown-content {
        display: none;
        position: absolute;
        background-color: #fff;
        min-width: 200px;
        box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
        z-index: 1000;
        border-radius: 0.375rem;
        margin-top: 0;
        padding-top: 0.5rem;
        top: 100%;
        left: 0;
    }
    .dropdown-content a {
        color: #1f2937;
        padding: 12px 16px;
        text-decoration: none;
        display: block;
        transition: background-color 0.2s;
        font-size: 0.875rem;
    }
    .dropdown-content a:hover { background-color: #f3f4f6; }
    .dropdown-content a:first-child { border-radius: 0.375rem 0.375rem 0 0; }
    .dropdown-content a:last-child { border-radius: 0 0 0.375rem 0.375rem; }
    .dropdown-content a.active { background-color: #0d9488; color: #fff; font-weight: 600; }
    .dropdown:hover .dropdown-content { display: block; }
    .dropdown-btn { cursor: pointer; padding: 0.5rem 0; font-size: 1rem; line-height: 1.5rem; display: inline-flex; align-items: center; gap: 0.35rem; color: white; }
    .dropdown-btn:hover { color: #ccfbf1; }
    .dropdown-caret { font-size: 0.85rem; line-height: 1; }
    
    /* Dropdown section headers */
    .dropdown-section-header {
        padding: 12px 16px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #6b7280;
        background-color: #f9fafb;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #e5e7eb;
        margin-top: 0.25rem;
    }
    
    .dropdown-section-header:first-child {
        margin-top: 0;
    }
    
    /* Indented items under sections */
    .dropdown-item-indent {
        padding-left: 1.5rem !important;
    }
    
    /* Mobile section header */
    @media (max-width: 768px) {
        .dropdown-section-header {
            color: rgba(255, 255, 255, 0.6);
            background-color: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 10px 16px;
        }
    }
    
    /* Mobile menu styles */
    #mobileMenu { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-in-out; }
    #mobileMenu.open { max-height: 500px; }
    
    /* Mobile dropdown */
    @media (max-width: 768px) {
        .dropdown-content {
            position: static;
            box-shadow: none;
            padding-left: 1rem;
            margin-top: 0.5rem;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 0;
        }
        .dropdown-content a {
            color: #fff;
            padding: 8px 12px;
        }
        .dropdown-content a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .dropdown-content a.active {
            background-color: rgba(255, 255, 255, 0.2);
        }
    }

    /* Blinking notification animation */
    @keyframes blink-attention {
        0%, 100% { background-color: rgba(239, 68, 68, 0.2); }
        50% { background-color: rgba(239, 68, 68, 0.5); }
    }
    
    @keyframes pulse-glow {
        0%, 100% { box-shadow: 0 0 5px rgba(239, 68, 68, 0.5); }
        50% { box-shadow: 0 0 20px rgba(239, 68, 68, 0.8); }
    }
    
    .fee-alert {
        animation: blink-attention 2s ease-in-out infinite, pulse-glow 2s ease-in-out infinite;
        border-radius: 0.375rem;
        padding: 0.25rem 0.5rem;
    }
    
    .fee-alert-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 8px;
        height: 8px;
        background-color: #ef4444;
        border-radius: 50%;
        animation: pulse-glow 1.5s ease-in-out infinite;
        margin-left: 0.25rem;
    }
    
    /* Contact banner with cards */
    @keyframes slideInLeft {
        from { transform: translateX(-100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideInCenter {
        from { transform: scale(0.8); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    
    .contact-banner-wrapper {
        position: fixed;
        top: 64px;
        left: 0;
        right: 0;
        z-index: 999;
        background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);
        padding: 0.5rem 0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    
    body { padding-top: 140px; }
</style>

<nav class="bg-teal-600 shadow-lg fixed-navbar">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center space-x-3">
                <?php if ($org_logo): ?>
                    <img src="<?php echo htmlspecialchars($org_logo); ?>" alt="Organization Logo" class="h-10 w-10 rounded-full object-cover border-2 border-white shadow">
                <?php endif; ?>
                <a href="dashboard.php" class="text-white text-xl font-bold tracking-wide"><?php echo htmlspecialchars($_SESSION['org_name']); ?></a>
            </div>
            
            <!-- Desktop Navigation -->
            <div class="<?php echo $nav_links_class; ?>">
                <a href="dashboard.php" class="text-white hover:text-teal-100 font-medium transition <?php echo $current_page === 'dashboard.php' ? 'text-teal-100' : ''; ?>">Dashboard</a>
                <?php if (!$is_subscribed): ?>
                    <a href="subscribe.php" class="text-white hover:text-teal-100 font-medium transition <?php echo $current_page === 'subscribe.php' ? 'text-teal-100' : ''; ?>">Subscription</a>
                <?php else: ?>
                    <a href="subscribe.php" class="text-white hover:text-teal-100 font-medium transition <?php echo $current_page === 'subscribe.php' ? 'text-teal-100' : ''; ?>">Subscription</a>
                    <a href="students.php" class="text-white hover:text-teal-100 font-medium transition <?php echo $current_page === 'students.php' ? 'text-teal-100' : ''; ?>">Students</a>
                    <a href="employees.php" class="text-white hover:text-teal-100 font-medium transition <?php echo $current_page === 'employees.php' ? 'text-teal-100' : ''; ?>">Employees</a>
                    <a href="attendance.php" class="text-white hover:text-teal-100 font-medium transition <?php echo $current_page === 'attendance.php' ? 'text-teal-100' : ''; ?>">Attendance</a>
                    <div class="dropdown">
                        <span class="dropdown-btn text-white hover:text-teal-100 font-medium transition <?php echo in_array($current_page, ['finance_overview.php', 'initialize_monthly_fees.php', 'manage_fees.php', 'custom_fees.php', 'initialize_monthly_salary.php', 'expenses.php', 'student_payments.php', 'employee_payments.php']) ? 'text-teal-100' : ''; ?>">
                            <span>Finance</span>
                            <?php if (!$current_month_initialized || !$current_month_salary_initialized): ?>
                                <span class="fee-alert-badge"></span>
                            <?php endif; ?>
                            <span class="dropdown-caret">▾</span>
                        </span>
                        <div class="dropdown-content">
                            <div class="dropdown-section-header">Overview</div>
                            <a href="finance_overview.php" <?php echo $current_page === 'finance_overview.php' ? 'class="active dropdown-item-indent"' : 'class="dropdown-item-indent"'; ?>>Finance Overview</a>
                            
                            <div class="dropdown-section-header">Payments</div>
                            <a href="student_payments.php" <?php echo $current_page === 'student_payments.php' ? 'class="active dropdown-item-indent"' : 'class="dropdown-item-indent"'; ?>>Student Payments</a>
                            <a href="employee_payments.php" <?php echo $current_page === 'employee_payments.php' ? 'class="active dropdown-item-indent"' : 'class="dropdown-item-indent"'; ?>>Employee Payments</a>
                            
                            <div class="dropdown-section-header">Fees Management</div>
                            <a href="manage_fees.php" <?php echo $current_page === 'manage_fees.php' ? 'class="active dropdown-item-indent"' : 'class="dropdown-item-indent"'; ?>>Manage Fees</a>
                            <a href="initialize_monthly_fees.php" class="dropdown-item-indent <?php echo $current_page === 'initialize_monthly_fees.php' ? 'active' : ''; ?><?php echo !$current_month_initialized ? ' fee-alert' : ''; ?>">
                                Monthly Fees
                                <?php if (!$current_month_initialized): ?>
                                    <span class="fee-alert-badge"></span>
                                <?php endif; ?>
                            </a>
                            <a href="custom_fees.php" <?php echo $current_page === 'custom_fees.php' ? 'class="active dropdown-item-indent"' : 'class="dropdown-item-indent"'; ?>>Custom Fees</a>
                            
                            <div class="dropdown-section-header">Salaries</div>
                            <a href="initialize_monthly_salary.php" class="dropdown-item-indent <?php echo $current_page === 'initialize_monthly_salary.php' ? 'active' : ''; ?><?php echo !$current_month_salary_initialized ? ' fee-alert' : ''; ?>">
                                Monthly Salaries
                                <?php if (!$current_month_salary_initialized): ?>
                                    <span class="fee-alert-badge"></span>
                                <?php endif; ?>
                            </a>
                            
                            <div class="dropdown-section-header">Expenses</div>
                            <a href="expenses.php" <?php echo $current_page === 'expenses.php' ? 'class="active dropdown-item-indent"' : 'class="dropdown-item-indent"'; ?>>Expenses</a>
                        </div>
                    </div>
                    <div class="dropdown">
                        <span class="dropdown-btn text-white hover:text-teal-100 font-medium transition <?php echo in_array($current_page, ['qr_token.php', 'url_qr.php', 'camera_settings.php']) ? 'text-teal-100' : ''; ?>">
                            <span>App Settings</span>
                            <span class="dropdown-caret">▾</span>
                        </span>
                        <div class="dropdown-content">
                            <a href="qr_token.php" <?php echo $current_page === 'qr_token.php' ? 'class="active"' : ''; ?>>App Token</a>
                            <a href="url_qr.php" <?php echo $current_page === 'url_qr.php' ? 'class="active"' : ''; ?>>URL QR</a>
                            <a href="camera_settings.php" <?php echo $current_page === 'camera_settings.php' ? 'class="active"' : ''; ?>>Camera Settings</a>
                        </div>
                    </div>
                    <div class="dropdown">
                        <span class="dropdown-btn text-white hover:text-teal-100 font-medium transition <?php echo in_array($current_page, ['settings.php', 'org_details.php']) ? 'text-teal-100' : ''; ?>">
                            <span>Settings</span>
                            <span class="dropdown-caret">▾</span>
                        </span>
                        <div class="dropdown-content">
                            <a href="settings.php" <?php echo $current_page === 'settings.php' ? 'class="active"' : ''; ?>>Form Fields</a>
                            <a href="org_details.php" <?php echo $current_page === 'org_details.php' ? 'class="active"' : ''; ?>>Organization Details</a>
                        </div>
                    </div>
                <?php endif; ?>
                <a href="../logout.php" class="text-white hover:text-red-200 font-medium transition">Logout</a>
            </div>
            
            <!-- Mobile menu button -->
            <div class="md:hidden">
                <button id="mobileMenuBtn" class="text-white hover:text-teal-100 focus:outline-none">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Mobile Navigation -->
        <div id="mobileMenu" class="md:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="dashboard.php" class="block text-white hover:bg-teal-700 px-3 py-2 rounded-md font-medium transition <?php echo $current_page === 'dashboard.php' ? 'bg-teal-700' : ''; ?>">Dashboard</a>
                <?php if (!$is_subscribed): ?>
                    <a href="subscribe.php" class="block text-white hover:bg-teal-700 px-3 py-2 rounded-md font-medium transition <?php echo $current_page === 'subscribe.php' ? 'bg-teal-700' : ''; ?>">Subscription</a>
                <?php else: ?>
                    <a href="subscribe.php" class="block text-white hover:bg-teal-700 px-3 py-2 rounded-md font-medium transition <?php echo $current_page === 'subscribe.php' ? 'bg-teal-700' : ''; ?>">Subscription</a>
                    <a href="students.php" class="block text-white hover:bg-teal-700 px-3 py-2 rounded-md font-medium transition <?php echo $current_page === 'students.php' ? 'bg-teal-700' : ''; ?>">Students</a>
                    <a href="employees.php" class="block text-white hover:bg-teal-700 px-3 py-2 rounded-md font-medium transition <?php echo $current_page === 'employees.php' ? 'bg-teal-700' : ''; ?>">Employees</a>
                    <a href="attendance.php" class="block text-white hover:bg-teal-700 px-3 py-2 rounded-md font-medium transition <?php echo $current_page === 'attendance.php' ? 'bg-teal-700' : ''; ?>">Attendance</a>
                    <div class="dropdown">
                        <span id="mobileFinanceBtn" class="block text-white px-3 py-2 rounded-md font-medium cursor-pointer <?php echo in_array($current_page, ['finance_overview.php', 'initialize_monthly_fees.php', 'manage_fees.php', 'custom_fees.php', 'initialize_monthly_salary.php', 'expenses.php', 'student_payments.php', 'employee_payments.php']) ? 'bg-teal-700' : ''; ?> dropdown-btn">
                            <span>Finance</span>
                            <?php if (!$current_month_initialized || !$current_month_salary_initialized): ?>
                                <span class="fee-alert-badge"></span>
                            <?php endif; ?>
                            <span class="dropdown-caret">▾</span>
                        </span>
                        <div id="mobileFinanceDropdown" class="dropdown-content">
                            <div class="dropdown-section-header">Overview</div>
                            <a href="finance_overview.php" <?php echo $current_page === 'finance_overview.php' ? 'class="active dropdown-item-indent"' : 'class="dropdown-item-indent"'; ?>>Finance Overview</a>
                            
                            <div class="dropdown-section-header">Payments</div>
                            <a href="student_payments.php" <?php echo $current_page === 'student_payments.php' ? 'class="active dropdown-item-indent"' : 'class="dropdown-item-indent"'; ?>>Student Payments</a>
                            <a href="employee_payments.php" <?php echo $current_page === 'employee_payments.php' ? 'class="active dropdown-item-indent"' : 'class="dropdown-item-indent"'; ?>>Employee Payments</a>
                            
                            <div class="dropdown-section-header">Fees Management</div>
                            <a href="manage_fees.php" <?php echo $current_page === 'manage_fees.php' ? 'class="active dropdown-item-indent"' : 'class="dropdown-item-indent"'; ?>>Manage Fees</a>
                            <a href="initialize_monthly_fees.php" class="dropdown-item-indent <?php echo $current_page === 'initialize_monthly_fees.php' ? 'active' : ''; ?><?php echo !$current_month_initialized ? ' fee-alert' : ''; ?>">
                                Monthly Fees
                                <?php if (!$current_month_initialized): ?>
                                    <span class="fee-alert-badge"></span>
                                <?php endif; ?>
                            </a>
                            <a href="custom_fees.php" <?php echo $current_page === 'custom_fees.php' ? 'class="active dropdown-item-indent"' : 'class="dropdown-item-indent"'; ?>>Custom Fees</a>
                            
                            <div class="dropdown-section-header">Salaries</div>
                            <a href="initialize_monthly_salary.php" class="dropdown-item-indent <?php echo $current_page === 'initialize_monthly_salary.php' ? 'active' : ''; ?><?php echo !$current_month_salary_initialized ? ' fee-alert' : ''; ?>">
                                Monthly Salaries
                                <?php if (!$current_month_salary_initialized): ?>
                                    <span class="fee-alert-badge"></span>
                                <?php endif; ?>
                            </a>
                            
                            <div class="dropdown-section-header">Expenses</div>
                            <a href="expenses.php" <?php echo $current_page === 'expenses.php' ? 'class="active dropdown-item-indent"' : 'class="dropdown-item-indent"'; ?>>Expenses</a>
                        </div>
                    </div>
                    <div class="dropdown">
                        <span id="mobileAppSettingsBtn" class="block text-white px-3 py-2 rounded-md font-medium cursor-pointer <?php echo in_array($current_page, ['qr_token.php', 'url_qr.php', 'camera_settings.php']) ? 'bg-teal-700' : ''; ?> dropdown-btn">
                            <span>App Settings</span>
                            <span class="dropdown-caret">▾</span>
                        </span>
                        <div id="mobileAppSettingsDropdown" class="dropdown-content">
                            <a href="qr_token.php" <?php echo $current_page === 'qr_token.php' ? 'class="active"' : ''; ?>>App Token</a>
                            <a href="url_qr.php" <?php echo $current_page === 'url_qr.php' ? 'class="active"' : ''; ?>>URL QR</a>
                            <a href="camera_settings.php" <?php echo $current_page === 'camera_settings.php' ? 'class="active"' : ''; ?>>Camera Settings</a>
                        </div>
                    </div>
                    <div class="dropdown">
                        <span id="mobileSettingsBtn" class="block text-white px-3 py-2 rounded-md font-medium cursor-pointer <?php echo in_array($current_page, ['settings.php', 'org_details.php']) ? 'bg-teal-700' : ''; ?> dropdown-btn">
                            <span>Settings</span>
                            <span class="dropdown-caret">▾</span>
                        </span>
                        <div id="mobileSettingsDropdown" class="dropdown-content">
                            <a href="settings.php" <?php echo $current_page === 'settings.php' ? 'class="active"' : ''; ?>>Form Fields</a>
                            <a href="org_details.php" <?php echo $current_page === 'org_details.php' ? 'class="active"' : ''; ?>>Organization Details</a>
                        </div>
                    </div>
                <?php endif; ?>
                <a href="../logout.php" class="block text-white hover:bg-red-700 px-3 py-2 rounded-md font-medium transition">Logout</a>
            </div>
        </div>
    </div>
</nav>

<!-- Contact Banner - Single Line -->
<div class="contact-banner-wrapper">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-center gap-6 text-white text-sm">
            <?php if ($contact_email): ?><div class="flex items-center gap-2"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path></svg><span><?php echo htmlspecialchars($contact_email); ?></span></div><?php endif; ?>
            <?php if ($contact_phone): ?><div class="flex items-center gap-2"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path></svg><span><?php echo htmlspecialchars($contact_phone); ?></span></div><?php endif; ?>
            <?php if ($contact_address): ?><div class="flex items-center gap-2"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg><span><?php echo htmlspecialchars($contact_address); ?></span></div><?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Mobile menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileFinanceBtn = document.getElementById('mobileFinanceBtn');
        const mobileFinanceDropdown = document.getElementById('mobileFinanceDropdown');
        const mobileAppSettingsBtn = document.getElementById('mobileAppSettingsBtn');
        const mobileAppSettingsDropdown = document.getElementById('mobileAppSettingsDropdown');
        const mobileSettingsBtn = document.getElementById('mobileSettingsBtn');
        const mobileSettingsDropdown = document.getElementById('mobileSettingsDropdown');
        
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', function() {
                mobileMenu.classList.toggle('open');
            });
        }
        
        if (mobileFinanceBtn) {
            mobileFinanceBtn.addEventListener('click', function() {
                const isOpen = mobileFinanceDropdown.style.display === 'block';
                mobileFinanceDropdown.style.display = isOpen ? 'none' : 'block';
            });
        }

        if (mobileAppSettingsBtn) {
            mobileAppSettingsBtn.addEventListener('click', function() {
                const isOpen = mobileAppSettingsDropdown.style.display === 'block';
                mobileAppSettingsDropdown.style.display = isOpen ? 'none' : 'block';
            });
        }

        if (mobileSettingsBtn) {
            mobileSettingsBtn.addEventListener('click', function() {
                const isOpen = mobileSettingsDropdown.style.display === 'block';
                mobileSettingsDropdown.style.display = isOpen ? 'none' : 'block';
            });
        }
    });
</script>
