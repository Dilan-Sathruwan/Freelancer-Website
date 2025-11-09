<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#667eea">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - FreelanceHub Admin' : 'FreelanceHub Admin'; ?></title>
    
    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Remix Icon -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 80px;
            --header-height: 70px;
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --transition-speed: 0.3s;
            --transition-curve: cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* Header with Glassmorphism */
        .header-gradient {
            background: var(--primary-gradient);
            height: var(--header-height);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }
        
        .header-gradient::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(-20px, 20px) scale(1.1); }
        }
        
        /* Clock & Date Styles */
        .datetime-container {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .datetime-container:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }
        
        .datetime-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .datetime-icon {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .datetime-text {
            color: white;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        
        .datetime-divider {
            width: 1px;
            height: 20px;
            background: rgba(255, 255, 255, 0.3);
        }
        
        /* Sidebar with Enhanced Shadow */
        .sidebar {
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            top: var(--header-height);
            transition: width var(--transition-speed) var(--transition-curve), 
                        transform var(--transition-speed) var(--transition-curve);
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.08);
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        .sidebar.collapsed .nav-text,
        .sidebar.collapsed .logo-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
            transition: opacity 0.2s ease, width 0.3s var(--transition-curve);
        }
        
        .sidebar.collapsed .nav-icon {
            margin-right: 0;
        }
        
        .sidebar.collapsed .sidebar-header {
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }
        
        /* Main Content with Smooth Transition */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--header-height);
            min-height: calc(100vh - var(--header-height));
            transition: margin-left var(--transition-speed) var(--transition-curve);
            will-change: margin-left;
        }
        
        .main-content.expanded {
            margin-left: var(--sidebar-collapsed-width);
        }
        

        
        /* Enhanced Navigation Items */
        .nav-item {
            position: relative;
            display: flex;
            align-items: center;
            padding: 14px 16px;
            border-radius: 14px;
            transition: all 0.3s var(--transition-curve);
            text-decoration: none;
            overflow: hidden;
            margin-bottom: 4px;
        }
        
        .nav-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 5px;
            background: var(--primary-gradient);
            border-radius: 0 5px 5px 0;
            transform: scaleY(0);
            transform-origin: center;
            transition: transform 0.3s var(--transition-curve);
        }
        
        .nav-item::after {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--primary-gradient);
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: 14px;
        }
        
        .nav-item.active::before,
        .nav-item:hover::before {
            transform: scaleY(1);
        }
        
        .nav-item.active::after {
            opacity: 0.08;
        }
        
        .nav-item:hover::after {
            opacity: 0.05;
        }
        
        .nav-item:active {
            transform: scale(0.98);
        }
        
        .nav-item > * {
            position: relative;
            z-index: 1;
        }
        
        /* Modern Tooltip */
        .tooltip {
            position: absolute;
            left: calc(100% + 18px);
            top: 50%;
            transform: translateY(-50%) translateX(-10px);
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            color: white;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s var(--transition-curve);
            pointer-events: none;
            z-index: 1000;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .tooltip::before {
            content: '';
            position: absolute;
            right: 100%;
            top: 50%;
            transform: translateY(-50%);
            border: 7px solid transparent;
            border-right-color: #1f2937;
        }
        
        .sidebar.collapsed .nav-item:hover .tooltip {
            opacity: 1;
            visibility: visible;
            transform: translateY(-50%) translateX(0);
        }
        
        /* Animated Overlay */
        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s var(--transition-curve);
            z-index: 35;
        }
        
        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        /* User Profile Avatar */
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            position: relative;
            transition: all 0.3s ease;
        }
        
        .avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        /* User Dropdown Menu */
        .user-dropdown {
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 0.5rem;
            width: 12rem;
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            z-index: 50;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .user-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .user-dropdown a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            text-decoration: none;
            transition: background-color 0.2s ease;
        }
        
        .user-dropdown a:hover {
            background-color: #f8fafc;
        }
        
        /* Custom Scrollbar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: #f8fafc;
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #cbd5e1 0%, #94a3b8 100%);
            border-radius: 10px;
            transition: background 0.3s ease;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #94a3b8 0%, #64748b 100%);
        }
        
        /* Enhanced Button Styles */
        .btn-glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .btn-glass:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .btn-glass:active {
            transform: translateY(0);
        }
        
        /* Logo Animation */
        .logo-icon {
            transition: transform 0.3s ease;
        }
        
        .logo-icon:hover {
            transform: rotate(180deg) scale(1.1);
        }
        /* Tablet & Mobile Responsive */
        @media (max-width: 1024px) {
            .datetime-container {
                padding: 6px 12px;
                gap: 8px;
            }
            
            .datetime-text {
                font-size: 12px;
            }
            
            .datetime-icon {
                font-size: 16px;
            }
            
            .sidebar {
                position: fixed;
                transform: translateX(-100%);
                width: var(--sidebar-width);
                z-index: 40;
                box-shadow: none;
            }
            
            .sidebar.mobile-active {
                transform: translateX(0);
                box-shadow: 20px 0 60px rgba(0, 0, 0, 0.3);
            }
            
            .sidebar.collapsed {
                width: var(--sidebar-width);
            }
            
            .sidebar.collapsed .nav-text,
            .sidebar.collapsed .logo-text {
                opacity: 1;
                width: auto;
            }
            
            .main-content,
            .main-content.expanded {
                margin-left: 0;
            }
        }
        
        @media (max-width: 768px) {
            .datetime-divider {
                display: none;
            }
            
            .datetime-item:first-child {
                display: none;
            }
        }
        
        @media (max-width: 640px) {
            :root {
                --header-height: 64px;
                --sidebar-width: 280px;
            }
            
            .datetime-container {
                padding: 6px 10px;
            }
            
            .datetime-text {
                font-size: 11px;
            }
        }
        
        @media (max-width: 480px) {
            :root {
                --sidebar-width: 100vw;
                --header-height: 60px;
            }
            
            .datetime-container {
                display: none;
            }
        }
        
        @media (max-width: 360px) {
            .nav-item {
                padding: 10px 12px;
                font-size: 14px;
            }
            
            .avatar {
                width: 34px;
                height: 34px;
                font-size: 14px;
            }
        }
        
        /* Loading Animation */
        @keyframes shimmer {
            0% { background-position: -468px 0; }
            100% { background-position: 468px 0; }
        }
        
        .loading-shimmer {
            animation: shimmer 1.2s ease-in-out infinite;
            background: linear-gradient(to right, #f6f7f8 0%, #edeef1 20%, #f6f7f8 40%, #f6f7f8 100%);
            background-size: 800px 104px;
        }
        
        /* Focus Visible for Accessibility */
        *:focus-visible {
            outline: 3px solid rgba(102, 126, 234, 0.5);
            outline-offset: 2px;
            border-radius: 8px;
        }
        
        /* Reduced Motion Support */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        
        /* Print Styles */
        @media print {
            .sidebar,
            .header-gradient,
            .sidebar-overlay {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                margin-top: 0 !important;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="fixed top-0 left-0 right-0 header-gradient shadow-lg z-50 flex items-center justify-between px-3 sm:px-4 lg:px-6" style="z-index: 9998;">
        <div class="flex items-center gap-2 sm:gap-4 flex-1 min-w-0">
            <!-- Mobile Menu Toggle -->
            <button 
                id="mobileMenuToggle" 
                class="lg:hidden text-white btn-glass p-2.5 rounded-xl transition-all touch-manipulation"
                aria-label="Toggle menu"
                aria-controls="sidebar"
                aria-expanded="false">
                <i class="ri-menu-line text-xl sm:text-2xl"></i>
            </button>
            
            <!-- Desktop Sidebar Toggle -->
            <button 
                id="desktopSidebarToggle" 
                class="hidden lg:flex text-white btn-glass p-2.5 rounded-xl transition-all items-center justify-center"
                aria-label="Toggle sidebar"
                aria-controls="sidebar">
                <i class="ri-menu-fold-line text-2xl"></i>
            </button>
            
            <!-- Page Title with Icon -->
            <div class="flex items-center gap-2 min-w-0">
                <div class="hidden sm:flex w-8 h-8 rounded-lg bg-white/10 items-center justify-center flex-shrink-0">
                    <i class="ri-dashboard-3-fill text-white text-lg"></i>
                </div>
                <h1 class="text-sm sm:text-base lg:text-xl font-bold text-white truncate">
                    <?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard'; ?>
                </h1>
            </div>
        </div>
        
        <!-- Right Section -->
        <div class="flex items-center gap-2 sm:gap-3">
            <!-- Date & Time Display -->
            <div class="datetime-container hidden md:flex">
                <div class="datetime-item">
                    <i class="ri-calendar-line datetime-icon"></i>
                    <span class="datetime-text" id="currentDate">Loading...</span>
                </div>
                <div class="datetime-divider"></div>
                <div class="datetime-item">
                    <i class="ri-time-line datetime-icon"></i>
                    <span class="datetime-text" id="currentTime">Loading...</span>
                </div>
            </div>

            <!-- User Profile Avatar -->
            <div class="relative flex items-center gap-2">
                <button 
                    id="userMenuToggle" 
                    class="text-white btn-glass p-2 rounded-xl transition-all items-center justify-center"
                    aria-label="User menu"
                    aria-haspopup="true"
                    aria-expanded="false">
                    <?php
                    $firstName = isset($_SESSION['username']) ? $_SESSION['username'] : 'A';
                    $initials = strtoupper(substr($firstName, 0, 1));
                    ?>
                    <div class="avatar">
                        <?php echo htmlspecialchars($initials); ?>
                    </div>
                </button>
                <div class="hidden sm:block text-white font-semibold truncate max-w-[120px]">
                    <?php echo htmlspecialchars(isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin'); ?>
                </div>
                
                <!-- User Dropdown Menu -->
                <div id="userDropdown" class="absolute right-0 top-full mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-200 py-2 hidden z-50" role="menu">
                    <div class="px-4 py-2 border-b border-gray-100">
                        <p class="text-sm font-semibold text-gray-900 truncate">
                            <?php echo htmlspecialchars(isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin'); ?>
                        </p>
                        <p class="text-xs text-gray-500">Administrator</p>
                    </div>
                    <a href="../auth/logout.php" 
                       class="flex items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
                       role="menuitem">
                        <i class="ri-logout-box-line text-lg"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Sidebar Overlay -->
    <div id="sidebarOverlay" class="sidebar-overlay" aria-hidden="true" style="z-index: 9996;"></div>
    
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar fixed left-0 bg-white shadow-xl overflow-y-auto" role="navigation" aria-label="Main navigation" style="z-index: 9997;">
        <div class="sidebar-header p-4 sm:p-6 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center flex-shrink-0 logo-icon">
                    <i class="ri-dashboard-3-fill text-white text-2xl"></i>
                </div>
                <div class="logo-text overflow-hidden">
                    <h2 class="text-lg font-black text-gray-800 whitespace-nowrap tracking-tight">
                        FreelanceHub
                    </h2>
                    <p class="text-xs font-medium text-gray-500">Admin Panel</p>
                </div>
            </div>
        </div>
        
        <nav class="p-3 sm:p-4" aria-label="Main menu">
            <ul class="space-y-1" role="list">
                <?php
                $menuItems = [
                    ['dashboard', 'ri-dashboard-line', 'Dashboard', 'dashboard.php'],
                    ['users', 'ri-user-line', 'Users', 'manage_users.php'],
                    ['freelancers', 'ri-user-star-line', 'Freelancers', 'manage_freelancers.php'],
                    ['clients', 'ri-user-follow-line', 'Clients', 'manage_clients.php'],
                    ['gigs', 'ri-briefcase-line', 'Gigs', 'manage_gigs.php'],
                    ['orders', 'ri-file-list-line', 'Orders', 'manage_orders.php'],
                    ['categories', 'ri-folder-line', 'Categories', 'manage_categories.php'],
                    ['transactions', 'ri-exchange-line', 'Transactions', 'manage_transactions.php'],
                    ['reviews', 'ri-star-line', 'Reviews', 'manage_reviews.php'],
                    ['admins', 'ri-shield-user-line', 'Admins', 'manage_admins.php'],
                    ['admin_activity', 'ri-file-list-line', 'Admin Activity', 'admin_activity.php']
                ];
                
                foreach ($menuItems as $item):
                    $isActive = isset($active_page) && $active_page === $item[0];
                    $activeClass = $isActive ? 'active bg-gradient-to-r from-indigo-50 to-purple-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50';
                ?>
                <li role="none">
                    <a href="<?php echo htmlspecialchars($item[3]); ?>" 
                       class="nav-item <?php echo $activeClass; ?>"
                       role="menuitem"
                       <?php echo $isActive ? 'aria-current="page"' : ''; ?>>
                        <i class="nav-icon <?php echo htmlspecialchars($item[1]); ?> text-xl flex-shrink-0 mr-3"></i>
                        <span class="nav-text font-semibold"><?php echo htmlspecialchars($item[2]); ?></span>
                        <span class="tooltip"><?php echo htmlspecialchars($item[2]); ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
                
                <!-- Logout Button -->
                <li role="none" class="mt-6 pt-6 border-t border-gray-200">
                    <a href="../auth/logout.php" 
                       class="nav-item text-red-600 hover:bg-red-50"
                       role="menuitem">
                        <i class="nav-icon ri-logout-box-line text-xl flex-shrink-0 mr-3"></i>
                        <span class="nav-text font-semibold">Logout</span>
                        <span class="tooltip">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>        
    </aside>
    
    <!-- Main Content -->
    <main id="mainContent" class="main-content p-3 sm:p-4 lg:p-6" role="main" style="z-index: 1; position: relative;">

    <script>
        (function() {
            'use strict';
            
            // Cache DOM elements
            const DOM = {
                sidebar: document.getElementById('sidebar'),
                mainContent: document.getElementById('mainContent'),
                desktopToggle: document.getElementById('desktopSidebarToggle'),
                mobileToggle: document.getElementById('mobileMenuToggle'),
                overlay: document.getElementById('sidebarOverlay'),
                currentDate: document.getElementById('currentDate'),
                currentTime: document.getElementById('currentTime'),
                body: document.body
            };
            
            // Date & Time Functions
            const updateDateTime = () => {
                const now = new Date();
                
                // Format date
                const dateOptions = { 
                    weekday: 'short', 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric' 
                };
                const formattedDate = now.toLocaleDateString('en-US', dateOptions);
                
                // Format time
                const timeOptions = { 
                    hour: '2-digit', 
                    minute: '2-digit', 
                    second: '2-digit',
                    hour12: true 
                };
                const formattedTime = now.toLocaleTimeString('en-US', timeOptions);
                
                // Update DOM
                if (DOM.currentDate) DOM.currentDate.textContent = formattedDate;
                if (DOM.currentTime) DOM.currentTime.textContent = formattedTime;
            };
            
            // Initialize date/time and update every second
            updateDateTime();
            setInterval(updateDateTime, 1000);
            
            // Utility functions
            const utils = {
                isMobile: () => window.innerWidth < 1024,
                
                setAria: (element, expanded) => {
                    if (element) element.setAttribute('aria-expanded', expanded);
                },
                
                toggleClass: (element, className, force) => {
                    if (element) {
                        if (force !== undefined) {
                            return element.classList.toggle(className, force);
                        } else {
                            return element.classList.toggle(className);
                        }
                    }
                    return false;
                },
                
                saveState: (key, value) => {
                    try {
                        localStorage.setItem(key, value);
                    } catch (e) {
                        console.warn('LocalStorage not available');
                    }
                },
                
                getState: (key) => {
                    try {
                        return localStorage.getItem(key);
                    } catch (e) {
                        return null;
                    }
                }
            };
            
            // Desktop sidebar toggle
            const toggleDesktopSidebar = () => {
                const collapsed = utils.toggleClass(DOM.sidebar, 'collapsed');
                utils.toggleClass(DOM.mainContent, 'expanded');
                
                const icon = DOM.desktopToggle?.querySelector('i');
                if (icon) {
                    icon.className = collapsed ? 'ri-menu-unfold-line text-2xl' : 'ri-menu-fold-line text-2xl';
                }
                
                utils.saveState('sidebarCollapsed', collapsed);
                utils.setAria(DOM.desktopToggle, !collapsed);
            };
            
            // Mobile sidebar functions
            const closeMobileSidebar = () => {
                utils.toggleClass(DOM.sidebar, 'mobile-active', false);
                utils.toggleClass(DOM.overlay, 'active', false);
                DOM.body.style.overflow = '';
                utils.setAria(DOM.mobileToggle, false);
            };
            
            const toggleMobileSidebar = () => {
                const isActive = utils.toggleClass(DOM.sidebar, 'mobile-active');
                utils.toggleClass(DOM.overlay, 'active', isActive);
                DOM.body.style.overflow = isActive ? 'hidden' : '';
                utils.setAria(DOM.mobileToggle, isActive);
            };
            

            
            // Event listeners
            if (DOM.desktopToggle) {
                DOM.desktopToggle.addEventListener('click', toggleDesktopSidebar);
            }
            if (DOM.mobileToggle) {
                DOM.mobileToggle.addEventListener('click', toggleMobileSidebar);
            }
            if (DOM.overlay) {
                DOM.overlay.addEventListener('click', closeMobileSidebar);
            }
            
            // User dropdown functionality
            const userMenuToggle = document.getElementById('userMenuToggle');
            const userDropdown = document.getElementById('userDropdown');
            
            if (userMenuToggle && userDropdown) {
                userMenuToggle.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isVisible = !userDropdown.classList.contains('hidden');
                    userDropdown.classList.toggle('hidden', isVisible);
                    userMenuToggle.setAttribute('aria-expanded', !isVisible);
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', (e) => {
                    if (!userMenuToggle.contains(e.target) && !userDropdown.contains(e.target)) {
                        userDropdown.classList.add('hidden');
                        userMenuToggle.setAttribute('aria-expanded', false);
                    }
                });
                
                // Close dropdown on escape key
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        userDropdown.classList.add('hidden');
                        userMenuToggle.setAttribute('aria-expanded', false);
                    }
                });
            }
            
            // Close mobile sidebar when clicking nav links
            document.querySelectorAll('.nav-item').forEach(link => {
                link.addEventListener('click', () => {
                    if (utils.isMobile()) {
                        setTimeout(closeMobileSidebar, 300);
                    }
                });
            });
            
            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    if (utils.isMobile()) closeMobileSidebar();
                }
            });
            
            // Restore sidebar state
            const restoreSidebarState = () => {
                if (!utils.isMobile()) {
                    const isCollapsed = utils.getState('sidebarCollapsed') === 'true';
                    if (isCollapsed) {
                        DOM.sidebar?.classList.add('collapsed');
                        DOM.mainContent?.classList.add('expanded');
                        const icon = DOM.desktopToggle?.querySelector('i');
                        if (icon) icon.className = 'ri-menu-unfold-line text-2xl';
                    }
                }
            };
            
            // Debounced resize handler
            let resizeTimer;
            const handleResize = () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    if (utils.isMobile()) {
                        closeMobileSidebar();
                    } else {
                        utils.toggleClass(DOM.sidebar, 'mobile-active', false);
                        utils.toggleClass(DOM.overlay, 'active', false);
                        DOM.body.style.overflow = '';
                    }
                }, 150);
            };
            
            window.addEventListener('resize', handleResize);
            
            // Initialize
            restoreSidebarState();
            
            // Performance optimization: Passive event listeners
            const passiveSupport = (() => {
                let passive = false;
                try {
                    const options = Object.defineProperty({}, 'passive', {
                        get: () => { passive = true; }
                    });
                    window.addEventListener('test', null, options);
                } catch (e) {}
                return passive;
            })();
            
            // Add touch event optimization for mobile
            if ('ontouchstart' in window) {
                document.addEventListener('touchstart', () => {}, passiveSupport ? { passive: true } : false);
            }
            
        })();
    </script>
</body>
</html>