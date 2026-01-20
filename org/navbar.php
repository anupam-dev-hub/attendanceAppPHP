<?php
// org/navbar.php
// Reusable navigation bar for organization pages

// Determine current page for active highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$is_subscribed = isSubscribed($org_id);

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
?>
<!DOCTYPE html>
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
    .dropdown-btn { cursor: pointer; padding: 0.5rem 0; font-size: 1rem; line-height: 1.5rem; display: inline-block; }
    .dropdown-btn:hover { color: #ccfbf1; }
    
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
        padding: 0.75rem 0 1rem 0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        margin-bottom: 1.5rem;
    }
    
    .contact-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }
    
    .contact-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }
    
    .contact-card:nth-child(1) { animation: slideInLeft 0.5s ease-out; }
    .contact-card:nth-child(2) { animation: slideInCenter 0.5s ease-out 0.1s backwards; }
    .contact-card:nth-child(3) { animation: slideInRight 0.5s ease-out 0.2s backwards; }
    
    .contact-icon-wrapper {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #14b8a6, #0d9488);
        box-shadow: 0 2px 4px rgba(13, 148, 136, 0.3);
    }
    
    body { padding-top: 140px; }
</style>

<nav class="bg-teal-600 shadow-lg fixed-navbar">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <a href="dashboard.php" class="text-white text-xl font-bold tracking-wide"><?php echo htmlspecialchars($_SESSION['org_name']); ?></a>
            </div>
            
            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-6">
                <a href="dashboard.php" class="text-white hover:text-teal-100 font-medium transition <?php echo $current_page === 'dashboard.php' ? 'text-teal-100' : ''; ?>">Dashboard</a>
                <?php if (!$is_subscribed): ?>
                    <a href="subscribe.php" class="text-white hover:text-teal-100 font-medium transition <?php echo $current_page === 'subscribe.php' ? 'text-teal-100' : ''; ?>">Subscription</a>
                <?php else: ?>
                    <a href="subscribe.php" class="text-white hover:text-teal-100 font-medium transition <?php echo $current_page === 'subscribe.php' ? 'text-teal-100' : ''; ?>">Subscription</a>
                    <a href="org_details.php" class="text-white hover:text-teal-100 font-medium transition <?php echo $current_page === 'org_details.php' ? 'text-teal-100' : ''; ?>">Organization</a>
                    <a href="students.php" class="text-white hover:text-teal-100 font-medium transition <?php echo $current_page === 'students.php' ? 'text-teal-100' : ''; ?>">Students</a>
                    <a href="employees.php" class="text-white hover:text-teal-100 font-medium transition <?php echo $current_page === 'employees.php' ? 'text-teal-100' : ''; ?>">Employees</a>
                    <div class="dropdown">
                        <span class="dropdown-btn text-white hover:text-teal-100 font-medium transition <?php echo in_array($current_page, ['finance_overview.php', 'initialize_monthly_fees.php', 'manage_fees.php', 'custom_fees.php']) ? 'text-teal-100' : ''; ?>">
                            Finance ▾
                        </span>
                        <div class="dropdown-content">
                            <a href="finance_overview.php" <?php echo $current_page === 'finance_overview.php' ? 'class="active"' : ''; ?>>Finance Overview</a>
                            <a href="manage_fees.php" <?php echo $current_page === 'manage_fees.php' ? 'class="active"' : ''; ?>>Manage Fees</a>
                            <a href="initialize_monthly_fees.php" <?php echo $current_page === 'initialize_monthly_fees.php' ? 'class="active"' : ''; ?>>Monthly Fees</a>
                            <a href="custom_fees.php" <?php echo $current_page === 'custom_fees.php' ? 'class="active"' : ''; ?>>Custom Fees</a>
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
                    <div class="dropdown">
                        <span id="mobileFinanceBtn" class="block text-white px-3 py-2 rounded-md font-medium cursor-pointer <?php echo in_array($current_page, ['finance_overview.php', 'initialize_monthly_fees.php', 'manage_fees.php', 'custom_fees.php']) ? 'bg-teal-700' : ''; ?>">
                            Finance ▾
                        </span>
                        <div id="mobileFinanceDropdown" class="dropdown-content">
                            <a href="finance_overview.php" <?php echo $current_page === 'finance_overview.php' ? 'class="active"' : ''; ?>>Finance Overview</a>
                            <a href="manage_fees.php" <?php echo $current_page === 'manage_fees.php' ? 'class="active"' : ''; ?>>Manage Fees</a>
                            <a href="initialize_monthly_fees.php" <?php echo $current_page === 'initialize_monthly_fees.php' ? 'class="active"' : ''; ?>>Monthly Fees</a>
                            <a href="custom_fees.php" <?php echo $current_page === 'custom_fees.php' ? 'class="active"' : ''; ?>>Custom Fees</a>
                        </div>
                    </div>
                <?php endif; ?>
                <a href="../logout.php" class="block text-white hover:bg-red-700 px-3 py-2 rounded-md font-medium transition">Logout</a>
            </div>
        </div>
    </div>
</nav>

<!-- Contact Banner with Cards -->
<div class="contact-banner-wrapper">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <?php if ($contact_email): ?>
                <div class="contact-card flex items-center gap-3">
                    <div class="contact-icon-wrapper">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Email</p>
                        <p class="text-sm text-gray-900 font-semibold truncate"><?php echo htmlspecialchars($contact_email); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($contact_phone): ?>
                <div class="contact-card flex items-center gap-3">
                    <div class="contact-icon-wrapper">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Phone</p>
                        <p class="text-sm text-gray-900 font-semibold truncate"><?php echo htmlspecialchars($contact_phone); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($contact_address): ?>
                <div class="contact-card flex items-center gap-3">
                    <div class="contact-icon-wrapper">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Address</p>
                        <p class="text-sm text-gray-900 font-semibold truncate"><?php echo htmlspecialchars($contact_address); ?></p>
                    </div>
                </div>
            <?php endif; ?>
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
    });
</script>
