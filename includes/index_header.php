<?php
if (!isset($root_path)) {
    $root_path = file_exists('./config/db.con.php') ? './' : '../';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure image helper is available
if (!function_exists('resolveAvatarUrl') && file_exists(__DIR__ . '/image_helper.php')) {
    include_once __DIR__ . '/image_helper.php';
}

$currentPage = basename($_SERVER['PHP_SELF']);
$role = strtolower($_SESSION['role'] ?? '');
$username = $_SESSION['username'] ?? 'User';
$userId = $_SESSION['user_id'] ?? null;
$userEmail = $_SESSION['email'] ?? '';
$firstName = $_SESSION['first_name'] ?? '';
$lastName = $_SESSION['last_name'] ?? '';
$displayName = trim($firstName . ' ' . $lastName) ?: $username;

// Dynamic Dashboard URL based on role
$dashUrl = match($role) {
    'admin' => $root_path . 'admin/dashboard.php',
    'client' => $root_path . 'client/dashboard.php',
    default => $root_path . 'freelancer/dashboard.php',
};

// Resolve user avatar
$navAvatar = null;
if (function_exists('resolveAvatarUrl')) {
    $navAvatar = resolveAvatarUrl($_SESSION['profile_picture'] ?? null, $root_path, $displayName);
} else {
    $navAvatar = $root_path . 'assets/img/placeholder-avatar.svg';
}

// Role badge configuration
$roleBadge = match($role) {
    'admin' => ['label' => 'Admin', 'bg' => 'bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-800'],
    'client' => ['label' => 'Client', 'bg' => 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800'],
    'freelancer' => ['label' => 'Freelancer Pro', 'bg' => 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800'],
    default => ['label' => 'Member', 'bg' => 'bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 border-purple-200 dark:border-purple-800']
};
?>

<!-- Immediate Theme Application (Prevents Flash of White in Dark Mode) -->
<script>
    (function () {
        try {
            var savedTheme = localStorage.getItem('freelancehub_theme');
            var systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (savedTheme === 'dark' || (!savedTheme && systemDark)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        } catch (e) {}
    })();
</script>

<!-- Favicon Links -->
<link rel="shortcut icon" href="<?php echo $root_path; ?>assets/img/favicon.ico" type="image/x-icon">
<link rel="icon" type="image/svg+xml" href="<?php echo $root_path; ?>assets/img/favicon.svg">

<!-- Main Floating Dock Header (100% Pure Tailwind CSS) -->
<header id="main-site-header" class="fixed top-0 left-0 right-0 z-50 pointer-events-none transition-all duration-300">
    <div id="header-container" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 transition-all duration-300 pointer-events-auto">
        <nav id="header-dock" class="relative flex items-center justify-between px-4 sm:px-6 py-2.5 sm:py-3 rounded-2xl sm:rounded-3xl bg-white/85 dark:bg-slate-900/85 backdrop-blur-xl border border-slate-200/80 dark:border-slate-800/80 shadow-sm transition-all duration-300">
            
            <!-- Left: Brand Logo & Animated Pulse Badge -->
            <a href="<?php echo $root_path; ?>index.php" class="flex items-center gap-3 group relative flex-shrink-0" aria-label="FreelanceHub Home">
                <div class="relative w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-gradient-to-tr from-indigo-600 via-purple-600 to-pink-500 flex items-center justify-center shadow-md shadow-purple-500/20 group-hover:scale-105 group-hover:rotate-6 transition-all duration-300">
                    <i class="ri-flashlight-fill text-xl sm:text-2xl text-white drop-shadow-sm"></i>
                    <span class="absolute -top-1 -right-1 w-3 h-3 bg-emerald-400 rounded-full border-2 border-white dark:border-slate-900 shadow-sm animate-pulse"></span>
                </div>
                <div class="flex flex-col">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                            Freelance<span class="text-indigo-600 dark:text-indigo-400">Hub</span>
                        </span>
                        <span class="hidden md:inline-flex px-1.5 py-0.5 text-[10px] font-extrabold uppercase tracking-widest rounded-md bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800/60">
                            PRO
                        </span>
                    </div>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium -mt-0.5 hidden sm:block">
                        Elite Freelance Network
                    </span>
                </div>
            </a>

            <!-- Center: Desktop Navigation Links (Floating Glass Island) -->
            <div class="hidden lg:flex items-center gap-1 xl:gap-2">
                <a href="<?php echo $root_path; ?>index.php" class="px-4 py-2 rounded-full text-sm font-medium transition-all flex items-center gap-1.5 <?= $currentPage === 'index.php' ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-100/70 dark:hover:bg-slate-800/70'; ?>">
                    <i class="ri-home-5-line text-base"></i>
                    <span>Home</span>
                </a>

                <a href="<?php echo $root_path; ?>public/gig.php" class="px-4 py-2 rounded-full text-sm font-medium transition-all flex items-center gap-1.5 <?= in_array($currentPage, ['gig.php', 'gig_detail.php']) ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-100/70 dark:hover:bg-slate-800/70'; ?>">
                    <i class="ri-briefcase-4-line text-base"></i>
                    <span>Browse Gigs</span>
                    <span class="ml-1 px-1.5 py-0.2 text-[10px] font-bold rounded-full bg-gradient-to-r from-rose-500 to-amber-500 text-white shadow-xs">
                        Hot
                    </span>
                </a>

                <!-- Categories Dropdown Trigger -->
                <div class="relative group">
                    <button type="button" class="px-4 py-2 rounded-full text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-100/70 dark:hover:bg-slate-800/70 transition-all flex items-center gap-1 cursor-pointer">
                        <i class="ri-apps-2-line text-base"></i>
                        <span>Explore</span>
                        <i class="ri-arrow-down-s-line text-sm transition-transform duration-200 group-hover:rotate-180"></i>
                    </button>

                    <!-- Categories Mega-Dropdown -->
                    <div class="absolute left-1/2 -translate-x-1/2 top-full mt-3 w-80 p-3 rounded-2xl bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border border-slate-200 dark:border-slate-800 shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 transform origin-top scale-95 group-hover:scale-100 z-50">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 px-3 py-1 mb-1">
                            Top Service Sectors
                        </div>
                        <div class="space-y-1">
                            <a href="<?php echo $root_path; ?>public/gig.php?search=Development" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-indigo-50/80 dark:hover:bg-slate-800/80 transition-all text-slate-700 dark:text-slate-200 group/cat">
                                <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                    <i class="ri-code-s-slash-line text-base"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-semibold group-hover/cat:text-indigo-600 dark:group-hover/cat:text-indigo-400">Development & IT</div>
                                    <div class="text-[10px] text-slate-400">Web, Mobile & Software</div>
                                </div>
                                <i class="ri-arrow-right-s-line text-slate-400 group-hover/cat:translate-x-0.5 transition-transform"></i>
                            </a>

                            <a href="<?php echo $root_path; ?>public/gig.php?search=Design" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-purple-50/80 dark:hover:bg-slate-800/80 transition-all text-slate-700 dark:text-slate-200 group/cat">
                                <div class="w-8 h-8 rounded-lg bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                                    <i class="ri-palette-line text-base"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-semibold group-hover/cat:text-purple-600 dark:group-hover/cat:text-purple-400">Design & Creative</div>
                                    <div class="text-[10px] text-slate-400">UI/UX, Branding & 3D</div>
                                </div>
                                <i class="ri-arrow-right-s-line text-slate-400 group-hover/cat:translate-x-0.5 transition-transform"></i>
                            </a>

                            <a href="<?php echo $root_path; ?>public/gig.php?search=Marketing" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-emerald-50/80 dark:hover:bg-slate-800/80 transition-all text-slate-700 dark:text-slate-200 group/cat">
                                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                    <i class="ri-line-chart-line text-base"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-semibold group-hover/cat:text-emerald-600 dark:group-hover/cat:text-emerald-400">Digital Marketing</div>
                                    <div class="text-[10px] text-slate-400">SEO, Ads & Social Growth</div>
                                </div>
                                <i class="ri-arrow-right-s-line text-slate-400 group-hover/cat:translate-x-0.5 transition-transform"></i>
                            </a>

                            <a href="<?php echo $root_path; ?>public/gig.php?search=Writing" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-amber-50/80 dark:hover:bg-slate-800/80 transition-all text-slate-700 dark:text-slate-200 group/cat">
                                <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                                    <i class="ri-article-line text-base"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-semibold group-hover/cat:text-amber-600 dark:group-hover/cat:text-amber-400">Writing & Content</div>
                                    <div class="text-[10px] text-slate-400">Copywriting, Tech & Blogs</div>
                                </div>
                                <i class="ri-arrow-right-s-line text-slate-400 group-hover/cat:translate-x-0.5 transition-transform"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <a href="<?php echo $root_path; ?>index.php#features" class="px-4 py-2 rounded-full text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-100/70 dark:hover:bg-slate-800/70 transition-all">
                    <span>Features</span>
                </a>

                <a href="<?php echo $root_path; ?>index.php#testimonials" class="px-4 py-2 rounded-full text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-100/70 dark:hover:bg-slate-800/70 transition-all">
                    <span>Reviews</span>
                </a>
            </div>

            <!-- Right: Theme Switcher & User Profile or Auth CTAs -->
            <div class="flex items-center gap-2 sm:gap-3">
                
                <!-- Desktop Theme Toggle Button (Pure Tailwind) -->
                <button type="button" id="theme-toggle-desktop" class="w-10 h-10 rounded-full border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-800/80 text-slate-700 dark:text-amber-400 flex items-center justify-center hover:scale-105 active:scale-95 transition-all shadow-xs cursor-pointer" aria-label="Toggle Theme" title="Toggle Theme (Light / Dark)">
                    <i class="ri-moon-clear-line text-lg dark:hidden text-indigo-600"></i>
                    <i class="ri-sun-line text-lg hidden dark:block text-amber-400"></i>
                </button>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- User Profile Dropdown -->
                    <div class="relative group" id="profile-dropdown-wrapper">
                        <button type="button" class="flex items-center gap-2.5 p-1 sm:pr-3 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800/80 transition-all border border-transparent hover:border-slate-200 dark:hover:border-slate-700 cursor-pointer" id="user-menu-btn" aria-expanded="false" aria-haspopup="true">
                            <div class="relative w-9 h-9 sm:w-10 sm:h-10 rounded-full overflow-hidden ring-2 ring-purple-500/30 flex-shrink-0">
                                <img src="<?php echo htmlspecialchars($navAvatar); ?>" alt="<?php echo htmlspecialchars($displayName); ?>" class="w-full h-full object-cover">
                                <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-slate-900"></span>
                            </div>
                            <div class="hidden xl:flex flex-col text-left">
                                <span class="text-xs font-bold text-slate-900 dark:text-slate-100 leading-tight truncate max-w-[110px]">
                                    <?php echo htmlspecialchars($displayName); ?>
                                </span>
                                <span class="text-[10px] text-slate-400 capitalize leading-tight">
                                    <?php echo htmlspecialchars($roleBadge['label']); ?>
                                </span>
                            </div>
                            <i class="ri-arrow-down-s-line text-slate-400 group-hover:rotate-180 transition-transform duration-200 hidden sm:block"></i>
                        </button>

                        <!-- Glassmorphic User Dropdown Menu -->
                        <div class="absolute right-0 top-full mt-3 w-64 p-2 rounded-2xl bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border border-slate-200 dark:border-slate-800 shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 transform origin-top-right scale-95 group-hover:scale-100 z-50">
                            <!-- User Card Header -->
                            <div class="p-3 mb-1 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60">
                                <div class="flex items-center gap-3">
                                    <img src="<?php echo htmlspecialchars($navAvatar); ?>" alt="Avatar" class="w-10 h-10 rounded-full object-cover border border-white dark:border-slate-700 shadow-xs">
                                    <div class="min-w-0 flex-1">
                                        <div class="text-xs font-bold text-slate-900 dark:text-white truncate"><?php echo htmlspecialchars($displayName); ?></div>
                                        <div class="text-[11px] text-slate-400 truncate">@<?php echo htmlspecialchars($username); ?></div>
                                        <span class="inline-block mt-1 px-2 py-0.5 text-[9px] font-extrabold uppercase rounded-full border <?php echo $roleBadge['bg']; ?>">
                                            <?php echo htmlspecialchars($roleBadge['label']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Links -->
                            <div class="space-y-0.5 py-1">
                                <a href="<?php echo $dashUrl; ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-purple-50 dark:hover:bg-slate-800 hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                                    <i class="ri-dashboard-3-line text-base text-purple-500"></i>
                                    <span>Control Dashboard</span>
                                </a>

                                <a href="<?php echo $root_path; ?>public/profile.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-purple-50 dark:hover:bg-slate-800 hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                                    <i class="ri-user-settings-line text-base text-indigo-500"></i>
                                    <span>Profile & Settings</span>
                                </a>

                                <?php if ($role === 'freelancer'): ?>
                                    <a href="<?php echo $root_path; ?>freelancer/manage_job_requests.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-purple-50 dark:hover:bg-slate-800 hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                                        <i class="ri-task-line text-base text-emerald-500"></i>
                                        <span>Job Orders & Escrow</span>
                                    </a>
                                <?php endif; ?>

                                <?php if ($role === 'client'): ?>
                                    <a href="<?php echo $root_path; ?>public/gig.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-purple-50 dark:hover:bg-slate-800 hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
                                        <i class="ri-search-eye-line text-base text-cyan-500"></i>
                                        <span>Hire Freelancers</span>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <div class="pt-1.5 mt-1 border-t border-slate-100 dark:border-slate-800">
                                <a href="<?php echo $root_path; ?>auth/logout.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors">
                                    <i class="ri-logout-box-r-line text-base"></i>
                                    <span>Sign Out</span>
                                </a>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Unauthenticated CTAs -->
                    <div class="hidden sm:flex items-center gap-2">
                        <a href="<?php echo $root_path; ?>auth/login.php" class="px-4 py-2 text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-200 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50/70 dark:hover:bg-slate-800/70 rounded-full transition-all">
                            Sign In
                        </a>

                        <a href="<?php echo $root_path; ?>auth/signup.php" class="inline-flex items-center gap-1.5 px-5 py-2.5 text-xs sm:text-sm font-bold text-white bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 rounded-full shadow-md shadow-purple-500/25 hover:shadow-lg hover:shadow-purple-500/40 hover:scale-[1.03] active:scale-[0.98] transition-all duration-300">
                            <span>Get Started</span>
                            <i class="ri-arrow-right-line text-xs font-normal"></i>
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Mobile Menu Hamburger Trigger -->
                <button type="button" id="mobile-menu-btn" class="w-10 h-10 rounded-xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-800/80 text-slate-700 dark:text-white flex items-center justify-center lg:hidden hover:bg-slate-100 dark:hover:bg-slate-700 active:scale-95 transition-all cursor-pointer" aria-label="Toggle Navigation Menu">
                    <i class="ri-menu-4-line text-xl" id="mobile-menu-icon"></i>
                </button>
            </div>
        </nav>
    </div>
</header>

<!-- Mobile Navigation Drawer Overlay (Pure Tailwind) -->
<div id="mobile-menu-overlay" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 opacity-0 pointer-events-none transition-opacity duration-300" aria-hidden="true"></div>

<!-- Mobile Navigation Sliding Glass Drawer (Pure Tailwind) -->
<aside id="mobile-menu" role="dialog" aria-modal="true" aria-label="Mobile Navigation" class="fixed top-0 right-0 bottom-0 w-80 max-w-[85vw] bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-2xl z-50 translate-x-full transition-transform duration-300 ease-in-out flex flex-col">
    <!-- Drawer Header -->
    <div class="p-5 border-b border-slate-200/80 dark:border-slate-800/80 bg-gradient-to-br from-purple-50/70 via-white to-indigo-50/50 dark:from-slate-900 dark:via-slate-900 dark:to-indigo-950/40">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white shadow-sm">
                    <i class="ri-flashlight-fill text-lg"></i>
                </div>
                <span class="text-lg font-black tracking-tight text-slate-900 dark:text-white">
                    Freelance<span class="text-indigo-600 dark:text-indigo-400">Hub</span>
                </span>
            </div>
            
            <button type="button" id="mobile-menu-close" class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-slate-800 transition-all cursor-pointer" aria-label="Close Mobile Menu">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>

        <!-- User Identity or Welcome Banner -->
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/80 dark:bg-slate-800/80 border border-slate-200/70 dark:border-slate-700/60 shadow-2xs backdrop-blur-md">
                <img src="<?php echo htmlspecialchars($navAvatar); ?>" alt="Avatar" class="w-11 h-11 rounded-full object-cover border border-purple-200 dark:border-slate-700 shadow-xs">
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold text-slate-900 dark:text-white truncate"><?php echo htmlspecialchars($displayName); ?></div>
                    <div class="text-[11px] text-slate-400 truncate">@<?php echo htmlspecialchars($username); ?></div>
                    <span class="inline-block mt-1 px-2 py-0.5 text-[9px] font-extrabold uppercase rounded-full border <?php echo $roleBadge['bg']; ?>">
                        <?php echo htmlspecialchars($roleBadge['label']); ?>
                    </span>
                </div>
            </div>
        <?php else: ?>
            <div class="p-3 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200/60 dark:border-indigo-800/60 text-slate-700 dark:text-slate-300 text-xs">
                <p class="font-bold mb-0.5 text-indigo-700 dark:text-indigo-400">Join the Pro Marketplace</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Access thousands of top freelancers and vetted gigs worldwide.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Drawer Body -->
    <div class="flex-1 p-5 space-y-6 overflow-y-auto">
        
        <!-- Segmented Theme Switcher for Mobile -->
        <div>
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">
                Color Theme
            </div>
            <div class="flex p-1 bg-slate-100 dark:bg-slate-800 rounded-full border border-slate-200 dark:border-slate-700">
                <button type="button" class="flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-bold rounded-full transition-all cursor-pointer text-slate-700 dark:text-slate-400" data-theme-choice="light" id="theme-btn-light">
                    <i class="ri-sun-line text-sm text-amber-500"></i>
                    <span>Light</span>
                </button>
                <button type="button" class="flex-1 flex items-center justify-center gap-1.5 py-2 text-xs font-bold rounded-full transition-all cursor-pointer text-slate-400 dark:text-slate-200" data-theme-choice="dark" id="theme-btn-dark">
                    <i class="ri-moon-clear-line text-sm text-indigo-400"></i>
                    <span>Dark</span>
                </button>
            </div>
        </div>

        <!-- Navigation Menu -->
        <div class="space-y-1">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">
                Main Menu
            </div>

            <a href="<?php echo $root_path; ?>index.php" class="flex items-center gap-3.5 p-3 rounded-2xl text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-purple-50 dark:hover:bg-slate-800/80 hover:text-purple-600 transition-colors <?= $currentPage === 'index.php' ? 'bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400' : ''; ?>">
                <div class="w-8 h-8 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                    <i class="ri-home-5-line text-base"></i>
                </div>
                <span>Home</span>
            </a>

            <a href="<?php echo $root_path; ?>public/gig.php" class="flex items-center gap-3.5 p-3 rounded-2xl text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-purple-50 dark:hover:bg-slate-800/80 hover:text-purple-600 transition-colors <?= in_array($currentPage, ['gig.php', 'gig_detail.php']) ? 'bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400' : ''; ?>">
                <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                    <i class="ri-briefcase-4-line text-base"></i>
                </div>
                <span>Browse Gigs & Services</span>
                <span class="ml-auto px-2 py-0.5 text-[10px] font-bold rounded-full bg-rose-500 text-white">Hot</span>
            </a>

            <a href="<?php echo $root_path; ?>index.php#features" class="flex items-center gap-3.5 p-3 rounded-2xl text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-purple-50 dark:hover:bg-slate-800/80 hover:text-purple-600 transition-colors">
                <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                    <i class="ri-sparkling-line text-base"></i>
                </div>
                <span>Why FreelanceHub</span>
            </a>

            <a href="<?php echo $root_path; ?>index.php#testimonials" class="flex items-center gap-3.5 p-3 rounded-2xl text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-purple-50 dark:hover:bg-slate-800/80 hover:text-purple-600 transition-colors">
                <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <i class="ri-feedback-line text-base"></i>
                </div>
                <span>Client Reviews</span>
            </a>
        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- User Workspace Section -->
            <div class="space-y-1 pt-4 border-t border-slate-200/80 dark:border-slate-800/80">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">
                    My Account
                </div>

                <a href="<?php echo $dashUrl; ?>" class="flex items-center gap-3.5 p-3 rounded-2xl text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-purple-50 dark:hover:bg-slate-800/80 hover:text-purple-600 transition-colors">
                    <div class="w-8 h-8 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                        <i class="ri-dashboard-line text-base"></i>
                    </div>
                    <span>Control Dashboard</span>
                </a>

                <a href="<?php echo $root_path; ?>public/profile.php" class="flex items-center gap-3.5 p-3 rounded-2xl text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-purple-50 dark:hover:bg-slate-800/80 hover:text-purple-600 transition-colors">
                    <div class="w-8 h-8 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <i class="ri-user-settings-line text-base"></i>
                    </div>
                    <span>Profile & Account</span>
                </a>

                <?php if ($role === 'freelancer'): ?>
                    <a href="<?php echo $root_path; ?>freelancer/manage_job_requests.php" class="flex items-center gap-3.5 p-3 rounded-2xl text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-purple-50 dark:hover:bg-slate-800/80 hover:text-purple-600 transition-colors">
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <i class="ri-task-line text-base"></i>
                        </div>
                        <span>Manage Job Orders</span>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Drawer Footer Actions -->
    <div class="p-5 border-t border-slate-200/80 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/50">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="<?php echo $root_path; ?>auth/logout.php" class="flex items-center justify-center gap-2 w-full py-3 px-4 rounded-xl text-xs font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 transition-colors">
                <i class="ri-logout-box-r-line text-base"></i>
                <span>Sign Out</span>
            </a>
        <?php else: ?>
            <div class="grid grid-cols-2 gap-3">
                <a href="<?php echo $root_path; ?>auth/login.php" class="flex items-center justify-center py-2.5 px-4 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    Sign In
                </a>
                <a href="<?php echo $root_path; ?>auth/signup.php" class="flex items-center justify-center py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-indigo-600 to-purple-600 shadow-md shadow-purple-500/20 hover:opacity-95 transition-opacity">
                    Get Started
                </a>
            </div>
        <?php endif; ?>
    </div>
</aside>

<!-- Pure Tailwind Header Interactions Script -->
<script>
    (function () {
        'use strict';

        function initHeaderEngine() {
            var header = document.getElementById('main-site-header');
            var headerDock = document.getElementById('header-dock');
            var mobileBtn = document.getElementById('mobile-menu-btn');
            var mobileClose = document.getElementById('mobile-menu-close');
            var mobileOverlay = document.getElementById('mobile-menu-overlay');
            var mobileMenu = document.getElementById('mobile-menu');
            var themeBtnDesktop = document.getElementById('theme-toggle-desktop');
            var themeBtnLight = document.getElementById('theme-btn-light');
            var themeBtnDark = document.getElementById('theme-btn-dark');

            // 1. Dynamic Scroll Dock Effect using Tailwind utility classes
            function handleScroll() {
                if (!headerDock) return;
                if (window.scrollY > 20) {
                    headerDock.classList.add('shadow-lg', 'border-purple-500/30', 'dark:border-indigo-500/30', 'bg-white/95', 'dark:bg-slate-900/95');
                    headerDock.classList.remove('shadow-sm', 'bg-white/85', 'dark:bg-slate-900/85');
                } else {
                    headerDock.classList.remove('shadow-lg', 'border-purple-500/30', 'dark:border-indigo-500/30', 'bg-white/95', 'dark:bg-slate-900/95');
                    headerDock.classList.add('shadow-sm', 'bg-white/85', 'dark:bg-slate-900/85');
                }
            }
            window.addEventListener('scroll', handleScroll, { passive: true });
            handleScroll();

            // 2. High-Performance Tailwind Dark Theme Controller
            function syncThemeUI(isDark) {
                if (themeBtnLight && themeBtnDark) {
                    if (isDark) {
                        themeBtnLight.classList.remove('bg-white', 'dark:bg-slate-700', 'shadow-xs', 'text-slate-900', 'dark:text-white');
                        themeBtnLight.classList.add('text-slate-400');
                        themeBtnDark.classList.add('bg-white', 'dark:bg-slate-700', 'shadow-xs', 'text-slate-900', 'dark:text-white');
                        themeBtnDark.classList.remove('text-slate-400');
                    } else {
                        themeBtnLight.classList.add('bg-white', 'dark:bg-slate-700', 'shadow-xs', 'text-slate-900', 'dark:text-white');
                        themeBtnLight.classList.remove('text-slate-400');
                        themeBtnDark.classList.remove('bg-white', 'dark:bg-slate-700', 'shadow-xs', 'text-slate-900', 'dark:text-white');
                        themeBtnDark.classList.add('text-slate-400');
                    }
                }
            }

            function setTheme(mode) {
                var isDark = mode === 'dark';
                if (isDark) {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('freelancehub_theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('freelancehub_theme', 'light');
                }
                syncThemeUI(isDark);
            }

            function toggleTheme() {
                var isCurrentlyDark = document.documentElement.classList.contains('dark');
                setTheme(isCurrentlyDark ? 'light' : 'dark');
            }

            if (themeBtnDesktop && !themeBtnDesktop.hasAttribute('data-bound')) {
                themeBtnDesktop.setAttribute('data-bound', 'true');
                themeBtnDesktop.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleTheme();
                });
            }

            if (themeBtnLight && !themeBtnLight.hasAttribute('data-bound')) {
                themeBtnLight.setAttribute('data-bound', 'true');
                themeBtnLight.addEventListener('click', function(e) {
                    e.preventDefault();
                    setTheme('light');
                });
            }

            if (themeBtnDark && !themeBtnDark.hasAttribute('data-bound')) {
                themeBtnDark.setAttribute('data-bound', 'true');
                themeBtnDark.addEventListener('click', function(e) {
                    e.preventDefault();
                    setTheme('dark');
                });
            }

            syncThemeUI(document.documentElement.classList.contains('dark'));

            // 3. Fluid Mobile Drawer using pure Tailwind CSS utility classes
            function openDrawer() {
                if (!mobileMenu || !mobileOverlay) return;
                mobileOverlay.classList.remove('opacity-0', 'pointer-events-none');
                mobileOverlay.classList.add('opacity-100', 'pointer-events-auto');
                mobileMenu.classList.remove('translate-x-full');
                mobileMenu.classList.add('translate-x-0');
                document.body.classList.add('overflow-hidden');
            }

            function closeDrawer() {
                if (!mobileMenu || !mobileOverlay) return;
                mobileOverlay.classList.add('opacity-0', 'pointer-events-none');
                mobileOverlay.classList.remove('opacity-100', 'pointer-events-auto');
                mobileMenu.classList.add('translate-x-full');
                mobileMenu.classList.remove('translate-x-0');
                document.body.classList.remove('overflow-hidden');
            }

            if (mobileBtn && !mobileBtn.hasAttribute('data-bound')) {
                mobileBtn.setAttribute('data-bound', 'true');
                mobileBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    openDrawer();
                });
            }

            if (mobileClose && !mobileClose.hasAttribute('data-bound')) {
                mobileClose.setAttribute('data-bound', 'true');
                mobileClose.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeDrawer();
                });
            }

            if (mobileOverlay && !mobileOverlay.hasAttribute('data-bound')) {
                mobileOverlay.setAttribute('data-bound', 'true');
                mobileOverlay.addEventListener('click', function(e) {
                    e.preventDefault();
                    closeDrawer();
                });
            }

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeDrawer();
                }
            });

            if (mobileMenu) {
                mobileMenu.querySelectorAll('a').forEach(function(link) {
                    link.addEventListener('click', closeDrawer);
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initHeaderEngine);
        } else {
            initHeaderEngine();
        }
    })();
</script>