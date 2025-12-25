<?php
// org/navbar.php
// Reusable navigation bar for organization pages

// Determine current page for active highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$is_subscribed = isSubscribed($org_id);
?>
<!DOCTYPE html>
<style>
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
</style>

<nav class="bg-teal-600 shadow-lg">
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
