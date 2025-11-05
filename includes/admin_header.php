<?php
// Ensure only admins can access admin pages
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - FreelanceHub Admin' : 'FreelanceHub Admin'; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Remix Icon -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    
    <!-- Custom styles -->
    <style>
        :root {
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 80px;
            --header-height: 70px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            top: var(--header-height);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        .sidebar.collapsed .nav-text,
        .sidebar.collapsed .sidebar-header h2,
        .sidebar.collapsed .logo-text {
            opacity: 0;
            visibility: hidden;
            width: 0;
            transition: all 0.2s ease;
        }
        
        .sidebar.collapsed .nav-item {
            justify-content: center;
        }
        
        .sidebar.collapsed .nav-icon {
            margin-right: 0;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--header-height);
            min-height: calc(100vh - var(--header-height));
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .main-content.expanded {
            margin-left: var(--sidebar-collapsed-width);
        }
        
        /* Header Gradient */
        .header-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #ef4444;
            color: white;
            font-size: 10px;
            font-weight: 600;
            padding: 2px 5px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
        }
        
        /* Dropdown Menu */
        .dropdown {
            position: relative;
        }
        
        .dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            min-width: 220px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .dropdown-menu.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .dropdown-menu::before {
            content: '';
            position: absolute;
            top: -6px;
            right: 20px;
            width: 12px;
            height: 12px;
            background: white;
            transform: rotate(45deg);
        }
        
        /* Avatar Styles */
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        /* Nav Item Hover Effect */
        .nav-item {
            position: relative;
            overflow: hidden;
        }
        
        .nav-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        
        .nav-item.active::before,
        .nav-item:hover::before {
            transform: translateX(0);
        }
        
        /* Tooltip for collapsed sidebar */
        .tooltip {
            position: absolute;
            left: calc(100% + 15px);
            top: 50%;
            transform: translateY(-50%);
            background: #1f2937;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 14px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            pointer-events: none;
            z-index: 1000;
        }
        
        .tooltip::before {
            content: '';
            position: absolute;
            right: 100%;
            top: 50%;
            transform: translateY(-50%);
            border: 6px solid transparent;
            border-right-color: #1f2937;
        }
        
        .sidebar.collapsed .nav-item:hover .tooltip {
            opacity: 1;
            visibility: visible;
        }
        
        /* Search Bar */
        .search-bar {
            position: relative;
            max-width: 400px;
        }
        
        .search-bar input {
            width: 100%;
            padding: 10px 16px 10px 44px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .search-bar input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .search-bar input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.4);
        }
        
        .search-bar i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
        }
        
        /* Mobile Overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 35;
        }
        
        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        /* Scrollbar Styles */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 40;
            }
            
            .sidebar.mobile-active {
                transform: translateX(0);
            }
            
            .sidebar.collapsed {
                width: var(--sidebar-width);
            }
            
            .main-content,
            .main-content.expanded {
                margin-left: 0;
            }
            
            .search-bar {
                display: none;
            }
        }
        
        @media (max-width: 640px) {
            :root {
                --header-height: 60px;
            }
            
            .sidebar {
                width: 280px;
            }
            
            .header-title {
                font-size: 16px;
            }
            
            .avatar {
                width: 36px;
                height: 36px;
                font-size: 14px;
            }
        }
        
        /* Animations */
        @keyframes slideInRight {
            from {
                transform: translateX(20px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .animate-slide-in {
            animation: slideInRight 0.3s ease forwards;
        }
        
        /* Notification Dropdown */
        .notification-dropdown {
            width: 360px;
            max-height: 480px;
            overflow-y: auto;
        }
        
        .notification-item {
            transition: background 0.2s ease;
        }
        
        .notification-item:hover {
            background: #f8fafc;
        }
        
        .notification-item.unread {
            background: #f0f9ff;
        }
        
        @media (max-width: 640px) {
            .notification-dropdown {
                width: calc(100vw - 32px);
                max-width: 360px;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="fixed top-0 left-0 right-0 header-gradient shadow-lg z-50 flex items-center justify-between px-4 lg:px-6" style="height: var(--header-height);">
        <div class="flex items-center space-x-4 flex-1">
            <!-- Mobile Menu Toggle -->
            <button id="mobileMenuToggle" class="text-white hover:bg-white/10 p-2 rounded-lg transition-all lg:hidden">
                <i class="ri-menu-line text-2xl"></i>
            </button>
            
            <!-- Desktop Sidebar Toggle -->
            <button id="desktopSidebarToggle" class="hidden lg:block text-white hover:bg-white/10 p-2 rounded-lg transition-all">
                <i class="ri-menu-fold-line text-2xl"></i>
            </button>
            
            <!-- Page Title -->
            <h1 class="header-title text-lg lg:text-xl font-bold text-white">
                <?php echo isset($page_title) ? $page_title : 'Admin Dashboard'; ?>
            </h1>
            
            <!-- Search Bar (Desktop) -->
            <div class="search-bar hidden lg:block ml-auto">
                <i class="ri-search-line"></i>
                <input type="text" placeholder="Search anything...">
            </div>
        </div>
        
        <div class="flex items-center space-x-2 lg:space-x-4">
            <!-- Search Button (Mobile) -->
            <button class="lg:hidden text-white hover:bg-white/10 p-2 rounded-lg transition-all">
                <i class="ri-search-line text-xl"></i>
            </button>
            
            <!-- Notifications -->
            <div class="dropdown">
                <button id="notificationBtn" class="relative text-white hover:bg-white/10 p-2 rounded-lg transition-all">
                    <i class="ri-notification-3-line text-xl lg:text-2xl"></i>
                    <span class="notification-badge">3</span>
                </button>
                
                <div id="notificationDropdown" class="dropdown-menu notification-dropdown">
                    <div class="p-4 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-gray-800">Notifications</h3>
                            <span class="text-xs bg-indigo-100 text-indigo-600 px-2 py-1 rounded-full">3 New</span>
                        </div>
                    </div>
                    <div class="max-h-96 overflow-y-auto">
                        <div class="notification-item unread p-4 border-b border-gray-100 cursor-pointer">
                            <div class="flex items-start space-x-3">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                    <i class="ri-user-add-line text-blue-600"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800">New user registered</p>
                                    <p class="text-xs text-gray-500 mt-1">John Doe just created an account</p>
                                    <p class="text-xs text-gray-400 mt-1">5 minutes ago</p>
                                </div>
                                <div class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0 mt-2"></div>
                            </div>
                        </div>
                        <div class="notification-item unread p-4 border-b border-gray-100 cursor-pointer">
                            <div class="flex items-start space-x-3">
                                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                    <i class="ri-money-dollar-circle-line text-green-600"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800">New order received</p>
                                    <p class="text-xs text-gray-500 mt-1">Order #1234 - $500.00</p>
                                    <p class="text-xs text-gray-400 mt-1">1 hour ago</p>
                                </div>
                                <div class="w-2 h-2 bg-green-500 rounded-full flex-shrink-0 mt-2"></div>
                            </div>
                        </div>
                        <div class="notification-item unread p-4 border-b border-gray-100 cursor-pointer">
                            <div class="flex items-start space-x-3">
                                <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center flex-shrink-0">
                                    <i class="ri-alert-line text-yellow-600"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800">Pending approval</p>
                                    <p class="text-xs text-gray-500 mt-1">5 gigs waiting for approval</p>
                                    <p class="text-xs text-gray-400 mt-1">2 hours ago</p>
                                </div>
                                <div class="w-2 h-2 bg-yellow-500 rounded-full flex-shrink-0 mt-2"></div>
                            </div>
                        </div>
                        <div class="notification-item p-4 border-b border-gray-100 cursor-pointer">
                            <div class="flex items-start space-x-3">
                                <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0">
                                    <i class="ri-star-line text-purple-600"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800">New review posted</p>
                                    <p class="text-xs text-gray-500 mt-1">Sarah rated 5 stars</p>
                                    <p class="text-xs text-gray-400 mt-1">3 hours ago</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3 border-t border-gray-100">
                        <a href="#" class="text-sm text-indigo-600 hover:text-indigo-700 font-medium flex items-center justify-center">
                            View all notifications
                            <i class="ri-arrow-right-line ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Settings -->
            <button class="hidden lg:block text-white hover:bg-white/10 p-2 rounded-lg transition-all">
                <i class="ri-settings-3-line text-2xl"></i>
            </button>
            
            <!-- User Profile Dropdown -->
            <div class="dropdown">
                <button id="profileBtn" class="flex items-center space-x-2 lg:space-x-3 hover:bg-white/10 p-1 lg:p-2 rounded-lg transition-all">
                    <div class="avatar">
                        <?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?>
                    </div>
                    <span class="hidden md:block text-white font-medium text-sm lg:text-base">
                        <?php echo $_SESSION['username'] ?? 'Admin'; ?>
                    </span>
                    <i class="ri-arrow-down-s-line text-white hidden lg:block"></i>
                </button>
                
                <div id="profileDropdown" class="dropdown-menu">
                    <div class="p-4 border-b border-gray-100">
                        <p class="font-semibold text-gray-800"><?php echo $_SESSION['username'] ?? 'Admin'; ?></p>
                        <p class="text-sm text-gray-500"><?php echo $_SESSION['email'] ?? 'admin@freelancehub.com'; ?></p>
                    </div>
                    <div class="py-2">
                        <a href="#" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-50 transition-colors">
                            <i class="ri-user-line text-gray-600"></i>
                            <span class="text-gray-700">My Profile</span>
                        </a>
                        <a href="#" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-50 transition-colors">
                            <i class="ri-settings-3-line text-gray-600"></i>
                            <span class="text-gray-700">Settings</span>
                        </a>
                        <a href="#" class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-50 transition-colors">
                            <i class="ri-question-line text-gray-600"></i>
                            <span class="text-gray-700">Help & Support</span>
                        </a>
                    </div>
                    <div class="border-t border-gray-100 py-2">
                        <a href="../auth/logout.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-red-50 transition-colors text-red-600">
                            <i class="ri-logout-box-line"></i>
                            <span class="font-medium">Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Sidebar Overlay (Mobile) -->
    <div id="sidebarOverlay" class="sidebar-overlay lg:hidden"></div>
    
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar fixed left-0 bg-white shadow-xl overflow-y-auto">
        <div class="sidebar-header p-6 border-b border-gray-100">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center flex-shrink-0">
                    <i class="ri-dashboard-3-line text-white text-xl"></i>
                </div>
                <div class="logo-text overflow-hidden">
                    <h2 class="text-lg font-bold text-gray-800 whitespace-nowrap">FreelanceHub</h2>
                    <p class="text-xs text-gray-500">Admin Panel</p>
                </div>
            </div>
        </div>
        
        <nav class="p-4">
            <ul class="space-y-1">
                <li>
                    <a href="dashboard.php" class="nav-item flex items-center space-x-3 p-3 rounded-xl transition-all <?php echo (isset($active_page) && $active_page === 'dashboard') ? 'active bg-gradient-to-r from-indigo-50 to-purple-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50'; ?>">
                        <i class="nav-icon ri-dashboard-line text-xl flex-shrink-0"></i>
                        <span class="nav-text font-medium">Dashboard</span>
                        <span class="tooltip">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="manage_users.php" class="nav-item flex items-center space-x-3 p-3 rounded-xl transition-all <?php echo (isset($active_page) && $active_page === 'users') ? 'active bg-gradient-to-r from-indigo-50 to-purple-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50'; ?>">
                        <i class="nav-icon ri-user-line text-xl flex-shrink-0"></i>
                        <span class="nav-text font-medium">Users</span>
                        <span class="tooltip">Users</span>
                    </a>
                </li>
                <li>
                    <a href="manage_freelancers.php" class="nav-item flex items-center space-x-3 p-3 rounded-xl transition-all <?php echo (isset($active_page) && $active_page === 'freelancers') ? 'active bg-gradient-to-r from-indigo-50 to-purple-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50'; ?>">
                        <i class="nav-icon ri-user-star-line text-xl flex-shrink-0"></i>
                        <span class="nav-text font-medium">Freelancers</span>
                        <span class="tooltip">Freelancers</span>
                    </a>
                </li>
                <li>
                    <a href="manage_clients.php" class="nav-item flex items-center space-x-3 p-3 rounded-xl transition-all <?php echo (isset($active_page) && $active_page === 'clients') ? 'active bg-gradient-to-r from-indigo-50 to-purple-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50'; ?>">
                        <i class="nav-icon ri-user-follow-line text-xl flex-shrink-0"></i>
                        <span class="nav-text font-medium">Clients</span>
                        <span class="tooltip">Clients</span>
                    </a>
                </li>
                <li>
                    <a href="manage_gigs.php" class="nav-item flex items-center space-x-3 p-3 rounded-xl transition-all <?php echo (isset($active_page) && $active_page === 'gigs') ? 'active bg-gradient-to-r from-indigo-50 to-purple-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50'; ?>">
                        <i class="nav-icon ri-briefcase-line text-xl flex-shrink-0"></i>
                        <span class="nav-text font-medium">Gigs</span>
                        <span class="tooltip">Gigs</span>
                    </a>
                </li>
                <li>
                    <a href="manage_orders.php" class="nav-item flex items-center space-x-3 p-3 rounded-xl transition-all <?php echo (isset($active_page) && $active_page === 'orders') ? 'active bg-gradient-to-r from-indigo-50 to-purple-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50'; ?>">
                        <i class="nav-icon ri-file-list-line text-xl flex-shrink-0"></i>
                        <span class="nav-text font-medium">Orders</span>
                        <span class="tooltip">Orders</span>
                    </a>
                </li>
                <li>
                    <a href="manage_categories.php" class="nav-item flex items-center space-x-3 p-3 rounded-xl transition-all <?php echo (isset($active_page) && $active_page === 'categories') ? 'active bg-gradient-to-r from-indigo-50 to-purple-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50'; ?>">
                        <i class="nav-icon ri-folder-line text-xl flex-shrink-0"></i>
                        <span class="nav-text font-medium">Categories</span>
                        <span class="tooltip">Categories</span>
                    </a>
                </li>
                <li>
                    <a href="manage_transactions.php" class="nav-item flex items-center space-x-3 p-3 rounded-xl transition-all <?php echo (isset($active_page) && $active_page === 'transactions') ? 'active bg-gradient-to-r from-indigo-50 to-purple-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50'; ?>">
                        <i class="nav-icon ri-exchange-line text-xl flex-shrink-0"></i>
                        <span class="nav-text font-medium">Transactions</span>
                        <span class="tooltip">Transactions</span>
                    </a>
                </li>
                <li>
                    <a href="manage_reviews.php" class="nav-item flex items-center space-x-3 p-3 rounded-xl transition-all <?php echo (isset($active_page) && $active_page === 'reviews') ? 'active bg-gradient-to-r from-indigo-50 to-purple-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50'; ?>">
                        <i class="nav-icon ri-star-line text-xl flex-shrink-0"></i>
                        <span class="nav-text font-medium">Reviews</span>
                        <span class="tooltip">Reviews</span>
                    </a>
                </li>
            </ul>
            
            <!-- Sidebar Footer -->
            <div class="mt-8 p-4 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center flex-shrink-0">
                        <i class="ri-lightbulb-line text-white text-xl"></i>
                    </div>
                    <div class="nav-text overflow-hidden">
                        <p class="text-sm font-semibold text-gray-800">Need Help?</p>
                        <p class="text-xs text-gray-600">Check our docs</p>
                    </div>
                </div>
            </div>
        </nav>
    </aside>
    
    <!-- Main Content -->
    <main id="mainContent" class="main-content p-4 lg:p-6">

    <script>
        // Sidebar Toggle Functionality
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const desktopToggle = document.getElementById('desktopSidebarToggle');
        const mobileToggle = document.getElementById('mobileMenuToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        // Desktop Sidebar Toggle
        desktopToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            const icon = desktopToggle.querySelector('i');
            if (sidebar.classList.contains('collapsed')) {
                icon.className = 'ri-menu-unfold-line text-2xl';
            } else {
                icon.className = 'ri-menu-fold-line text-2xl';
            }
            
            // Save state to localStorage
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });
        
        // Mobile Sidebar Toggle
        mobileToggle.addEventListener('click', () => {
            sidebar.classList.toggle('mobile-active');
            sidebarOverlay.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('mobile-active') ? 'hidden' : '';
        });
        
        // Close mobile sidebar when clicking overlay
        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('mobile-active');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        });
        
        // Restore sidebar state from localStorage
        if (window.innerWidth >= 1024) {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
                desktopToggle.querySelector('i').className = 'ri-menu-unfold-line text-2xl';
            }
        }
        
        // Profile Dropdown
        const profileBtn = document.getElementById('profileBtn');
        const profileDropdown = document.getElementById('profileDropdown');
        
        profileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            profileDropdown.classList.toggle('active');
            notificationDropdown.classList.remove('active');
        });
        
        // Notification Dropdown
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationDropdown = document.getElementById('notificationDropdown');
        
        notificationBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            notificationDropdown.classList.toggle('active');
            profileDropdown.classList.remove('active');
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!profileBtn.contains(e.target) && !profileDropdown.contains(e.target)) {
                profileDropdown.classList.remove('active');
            }
            if (!notificationBtn.contains(e.target) && !notificationDropdown.contains(e.target)) {
                notificationDropdown.classList.remove('active');
            }
        });
        
        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                sidebar.classList.remove('mobile-active');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            } else {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
            }
        });
        
        // Close mobile menu when clicking on a nav link
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', () => {
                if (window.innerWidth < 1024) {
                    sidebar.classList.remove('mobile-active');
                    sidebarOverlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
    </script>
</body>
</html>