<!-- Enhanced Modern Navigation -->
<link rel="stylesheet" href="./assets/css/index_header_style.css">
<nav class="fixed w-full top-0 z-50 bg-white/95 backdrop-blur-xl shadow-lg border-b border-gray-100/50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16 sm:h-20">
            <!-- Logo with Animation -->
            <a href="index.php" class="flex items-center space-x-2 sm:space-x-3 group relative">
                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-purple-600 via-purple-500 to-blue-600 rounded-xl sm:rounded-2xl flex items-center justify-center transform group-hover:rotate-12 group-hover:scale-110 transition-all duration-300 shadow-lg group-hover:shadow-purple-500/50">
                    <i class="ri-lightbulb-flash-line text-xl sm:text-2xl text-white"></i>
                    <div class="absolute inset-0 bg-white/20 rounded-xl sm:rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </div>
                <div class="flex flex-col">
                    <span class="text-lg sm:text-2xl font-bold bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent">FreelanceHub</span>
                    <span class="text-[10px] sm:text-xs text-gray-500 -mt-1 hidden sm:block">Your Success Partner</span>
                </div>
            </a>

            <!-- Desktop Menu with Hover Effects -->
            <div class="hidden lg:flex items-center space-x-1 xl:space-x-2">
                <a href="index.php" class="nav-link group px-4 py-2 rounded-lg text-gray-700 hover:text-purple-600 font-medium transition-all duration-300 relative">
                    <span class="relative z-10">Home</span>
                    <div class="absolute inset-0 bg-purple-50 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </a>
                <a href="./public/gig.php" class="nav-link group px-4 py-2 rounded-lg text-gray-700 hover:text-purple-600 font-medium transition-all duration-300 relative">
                    <span class="relative z-10">Browse Gigs</span>
                    <div class="absolute inset-0 bg-purple-50 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    <span class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full animate-pulse hidden group-hover:block"></span>
                </a>
                <a href="#features" class="nav-link group px-4 py-2 rounded-lg text-gray-700 hover:text-purple-600 font-medium transition-all duration-300 relative">
                    <span class="relative z-10">Features</span>
                    <div class="absolute inset-0 bg-purple-50 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </a>
                <a href="#testimonials" class="nav-link group px-4 py-2 rounded-lg text-gray-700 hover:text-purple-600 font-medium transition-all duration-300 relative">
                    <span class="relative z-10">Testimonials</span>
                    <div class="absolute inset-0 bg-purple-50 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </a>
            </div>

            <!-- Auth Buttons & User Menu -->
            <div class="hidden lg:flex items-center space-x-3 xl:space-x-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Notification Bell -->
                    <button class="relative p-2.5 rounded-xl hover:bg-gray-100 transition-all duration-300 group">
                        <i class="ri-notification-3-line text-xl text-gray-600 group-hover:text-purple-600"></i>
                        <span class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white"></span>
                        <span class="absolute top-0.5 right-0.5 w-2.5 h-2.5 bg-red-500 rounded-full animate-ping"></span>
                    </button>

                    <!-- User Dropdown -->
                    <div class="relative group">
                        <button class="flex items-center space-x-3 px-4 py-2.5 rounded-xl hover:bg-gray-100 transition-all duration-300">
                            <div class="w-9 h-9 bg-gradient-to-br from-purple-600 to-blue-600 rounded-lg flex items-center justify-center">
                                <span class="text-white font-semibold text-sm"><?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?></span>
                            </div>
                            <div class="hidden xl:block text-left">
                                <p class="text-sm font-semibold text-gray-700"><?php echo $_SESSION['username'] ?? 'User'; ?></p>
                                <p class="text-xs text-gray-500 capitalize"><?php echo $_SESSION['role'] ?? 'Member'; ?></p>
                            </div>
                            <i class="ri-arrow-down-s-line text-gray-600 group-hover:rotate-180 transition-transform duration-300"></i>
                        </button>

                        <!-- Dropdown Menu -->
                        <div class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-2xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 transform origin-top-right scale-95 group-hover:scale-100">
                            <div class="p-4 border-b border-gray-100">
                                <p class="text-sm font-semibold text-gray-700"><?php echo $_SESSION['username'] ?? 'User'; ?></p>
                                <p class="text-xs text-gray-500"><?php echo $_SESSION['email'] ?? ''; ?></p>
                            </div>
                            <div class="py-2">
                                <a href="<?php echo isset($_SESSION['role']) && $_SESSION['role'] == 'client' ? './client/dashboard.php' : './freelancer/dashboard.php'; ?>" 
                                   class="flex items-center space-x-3 px-4 py-3 hover:bg-purple-50 transition-colors duration-200 group/item">
                                    <i class="ri-dashboard-line text-gray-600 group-hover/item:text-purple-600"></i>
                                    <span class="text-sm text-gray-700 group-hover/item:text-purple-600 font-medium">Dashboard</span>
                                </a>
                                <a href="./public/profile.php" 
                                   class="flex items-center space-x-3 px-4 py-3 hover:bg-purple-50 transition-colors duration-200 group/item">
                                    <i class="ri-user-line text-gray-600 group-hover/item:text-purple-600"></i>
                                    <span class="text-sm text-gray-700 group-hover/item:text-purple-600 font-medium">Profile</span>
                                </a>
                                <a href="#settings" 
                                   class="flex items-center space-x-3 px-4 py-3 hover:bg-purple-50 transition-colors duration-200 group/item">
                                    <i class="ri-settings-3-line text-gray-600 group-hover/item:text-purple-600"></i>
                                    <span class="text-sm text-gray-700 group-hover/item:text-purple-600 font-medium">Settings</span>
                                </a>
                            </div>
                            <div class="p-2 border-t border-gray-100">
                                <a href="./auth/logout.php" 
                                   class="flex items-center space-x-3 px-4 py-3 hover:bg-red-50 rounded-xl transition-colors duration-200 group/item">
                                    <i class="ri-logout-box-line text-gray-600 group-hover/item:text-red-600"></i>
                                    <span class="text-sm text-gray-700 group-hover/item:text-red-600 font-medium">Logout</span>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="./auth/login.php" 
                       class="px-5 xl:px-6 py-2.5 text-purple-600 font-semibold hover:text-purple-700 hover:bg-purple-50 rounded-xl transition-all duration-300">
                        Sign In
                    </a>
                    <a href="./auth/signup.php" 
                       class="relative px-5 xl:px-6 py-2.5 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-xl font-semibold overflow-hidden group shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300">
                        <span class="relative z-10">Get Started</span>
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-purple-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="absolute inset-0 bg-white/20 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Enhanced Mobile Menu Button -->
            <button id="mobile-menu-btn" class="lg:hidden p-2 rounded-xl hover:bg-gray-100 active:scale-95 transition-all duration-300 relative group">
                <div class="w-6 h-5 flex flex-col justify-between">
                    <span class="hamburger-line w-full h-0.5 bg-gray-700 rounded-full transition-all duration-300 origin-left"></span>
                    <span class="hamburger-line w-full h-0.5 bg-gray-700 rounded-full transition-all duration-300"></span>
                    <span class="hamburger-line w-full h-0.5 bg-gray-700 rounded-full transition-all duration-300 origin-left"></span>
                </div>
            </button>
        </div>
    </div>

    <!-- Enhanced Mobile Sidebar Menu -->
    <div id="mobile-menu-overlay"></div>
    
    <div id="mobile-menu">
        <!-- Mobile Menu Header -->
        <div class="sticky top-0 bg-gradient-to-br from-purple-600 to-blue-600 p-6 border-b border-white/10">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white/20 backdrop-blur-lg rounded-xl flex items-center justify-center">
                        <i class="ri-lightbulb-flash-line text-xl text-white"></i>
                    </div>
                    <span class="text-xl font-bold text-white">FreelanceHub</span>
                </div>
                <button id="mobile-menu-close" class="p-2 rounded-lg hover:bg-white/10 active:scale-95 transition-all duration-300">
                    <i class="ri-close-line text-2xl text-white"></i>
                </button>
            </div>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- User Info in Mobile -->
                <div class="flex items-center space-x-3 p-3 bg-white/10 backdrop-blur-lg rounded-xl">
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                        <span class="text-white font-bold text-lg"><?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-white truncate"><?php echo $_SESSION['username'] ?? 'User'; ?></p>
                        <p class="text-xs text-white/70 capitalize"><?php echo $_SESSION['role'] ?? 'Member'; ?></p>
                    </div>
                    <div class="relative">
                        <i class="ri-notification-3-line text-xl text-white"></i>
                        <span class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Mobile Menu Content -->
        <div class="p-4 sm:p-6">
            <!-- Main Navigation -->
            <div class="space-y-2 mb-6">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Menu</p>
                <a href="index.php" class="flex items-center space-x-3 p-3 sm:p-4 rounded-xl text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-all duration-300 group">
                    <i class="ri-home-5-line text-xl group-hover:scale-110 transition-transform duration-300"></i>
                    <span class="font-medium">Home</span>
                </a>
                <a href="./public/gig.php" class="flex items-center space-x-3 p-3 sm:p-4 rounded-xl text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-all duration-300 group">
                    <i class="ri-briefcase-line text-xl group-hover:scale-110 transition-transform duration-300"></i>
                    <span class="font-medium">Browse Gigs</span>
                    <span class="ml-auto text-xs bg-red-500 text-white px-2 py-1 rounded-full">New</span>
                </a>
                <a href="#features" class="flex items-center space-x-3 p-3 sm:p-4 rounded-xl text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-all duration-300 group">
                    <i class="ri-star-line text-xl group-hover:scale-110 transition-transform duration-300"></i>
                    <span class="font-medium">Features</span>
                </a>
                <a href="#testimonials" class="flex items-center space-x-3 p-3 sm:p-4 rounded-xl text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-all duration-300 group">
                    <i class="ri-chat-quote-line text-xl group-hover:scale-110 transition-transform duration-300"></i>
                    <span class="font-medium">Testimonials</span>
                </a>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- User Actions -->
                <div class="space-y-2 mb-6 pt-6 border-t border-gray-200">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Account</p>
                    <a href="<?php echo isset($_SESSION['role']) && $_SESSION['role'] == 'client' ? './client/dashboard.php' : './freelancer/dashboard.php'; ?>" 
                       class="flex items-center space-x-3 p-3 sm:p-4 rounded-xl text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-all duration-300 group">
                        <i class="ri-dashboard-line text-xl group-hover:scale-110 transition-transform duration-300"></i>
                        <span class="font-medium">Dashboard</span>
                    </a>
                    <a href="./public/profile.php" 
                       class="flex items-center space-x-3 p-3 sm:p-4 rounded-xl text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-all duration-300 group">
                        <i class="ri-user-line text-xl group-hover:scale-110 transition-transform duration-300"></i>
                        <span class="font-medium">Profile</span>
                    </a>
                    <a href="#settings" 
                       class="flex items-center space-x-3 p-3 sm:p-4 rounded-xl text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-all duration-300 group">
                        <i class="ri-settings-3-line text-xl group-hover:scale-110 transition-transform duration-300"></i>
                        <span class="font-medium">Settings</span>
                    </a>
                </div>

                <!-- Logout Button -->
                <a href="./auth/logout.php" 
                   class="flex items-center justify-center space-x-2 w-full p-4 sm:p-5 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl active:scale-95 transition-all duration-300">
                    <i class="ri-logout-box-line text-xl"></i>
                    <span>Logout</span>
                </a>
            <?php else: ?>
                <!-- Auth Buttons for Mobile -->
                <div class="space-y-3 pt-6 border-t border-gray-200">
                    <a href="./auth/login.php" 
                       class="flex items-center justify-center space-x-2 w-full p-4 sm:p-5 border-2 border-purple-600 text-purple-600 rounded-xl font-semibold hover:bg-purple-50 active:scale-95 transition-all duration-300">
                        <i class="ri-login-box-line text-xl"></i>
                        <span>Sign In</span>
                    </a>
                    <a href="./auth/signup.php" 
                       class="flex items-center justify-center space-x-2 w-full p-4 sm:p-5 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl active:scale-95 transition-all duration-300">
                        <i class="ri-user-add-line text-xl"></i>
                        <span>Get Started</span>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Additional Info -->
            <div class="mt-8 p-4 bg-gradient-to-br from-purple-50 to-blue-50 rounded-xl">
                <p class="text-xs text-gray-600 text-center">
                    <i class="ri-shield-check-line text-purple-600"></i>
                    Secure & Trusted Platform
                </p>
            </div>
        </div>
    </div>
</nav>