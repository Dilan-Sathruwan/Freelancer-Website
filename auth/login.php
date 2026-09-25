<?php
session_start();
include_once "../config/db.con.php";

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? '';
    switch ($role) {
        case 'admin':
            header("Location: ../admin/dashboard.php");
            exit;
        case 'client':
            header("Location: ../client/dashboard.php");
            exit;
        case 'freelancer':
            header("Location: ../freelancer/dashboard.php");
            exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both your username/email and password.";
    } else {
        try {
            // Prepare statement to prevent SQL injection
            $stmt = $conn->prepare("SELECT id, username, password, role, status FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            $passwordValid = false;
            if ($user) {
                $passwordValid = password_verify($password, $user['password']);
            }

            if ($user && $passwordValid) {
                // Check if account is active
                if ($user['status'] !== 'active') {
                    $error = "This account is currently deactivated or suspended. Please contact platform support.";
                } else {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    // Update last login
                    try {
                        $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                        $updateStmt->execute([$user['id']]);
                    } catch (PDOException $e) {
                        error_log("Failed to update last login: " . $e->getMessage());
                    }

                    // Log the login activity
                    include_once "../includes/logging_functions.php";
                    if ($user['role'] === 'admin') {
                        logAdminLogin($user['id']);
                    } else {
                        logUserLogin($user['id']);
                    }

                    // Redirect based on role
                    switch ($user['role']) {
                        case 'admin':
                            header("Location: ../admin/dashboard.php");
                            exit;
                        case 'client':
                            header("Location: ../client/dashboard.php");
                            exit;
                        case 'freelancer':
                            header("Location: ../freelancer/dashboard.php");
                            exit;
                        default:
                            header("Location: ../index.php");
                            exit;
                    }
                }
            } else {
                $error = "Invalid credentials. Please verify your username and password.";
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "An error occurred during authentication. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - FreelanceHub</title>
    <meta name="description" content="Sign in to your FreelanceHub account to manage gigs, proposals, orders, and escrow milestones.">
    <meta name="theme-color" content="#6366f1">

    <!-- Favicon -->
    <link rel="shortcut icon" href="../assets/img/favicon.ico" type="image/x-icon">
    <link rel="icon" type="image/svg+xml" href="../assets/img/favicon.svg">

    <!-- Suppress Tailwind Play CDN notice -->
    <script>
        (function() {
            var origWarn = console.warn;
            console.warn = function() {
                if (arguments[0] && typeof arguments[0] === 'string' && arguments[0].indexOf('cdn.tailwindcss.com') !== -1) return;
                origWarn.apply(console, arguments);
            };
        })();
    </script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Remix Icon -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <!-- AOS Animation -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">

    <!-- Tailwind Custom Configuration -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                        secondary: '#8b5cf6',
                        accent: '#ec4899',
                        dark: '#0f172a',
                        light: '#f8fafc',
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 font-sans min-h-screen flex flex-col pt-20 transition-colors duration-300 relative overflow-x-hidden">
    <!-- Ambient Background Blobs -->
    <div class="pointer-events-none absolute inset-0 overflow-hidden opacity-30">
        <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[34rem] h-[34rem] bg-purple-500/20 dark:bg-purple-600/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-10 right-1/4 w-[26rem] h-[26rem] bg-indigo-500/15 dark:bg-indigo-600/15 rounded-full blur-3xl"></div>
    </div>

    <!-- Unified Header Component -->
    <?php include_once "../includes/header.php"; ?>

    <!-- Main Content Container -->
    <main class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="max-w-md w-full">
            <!-- Login Card -->
            <div class="bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl rounded-3xl shadow-2xl shadow-purple-950/5 dark:shadow-black/50 border border-slate-200/90 dark:border-slate-800 p-8 sm:p-10" data-aos="fade-up">
                <!-- Branding Header -->
                <div class="text-center mb-8">
                    <div class="w-14 h-14 bg-gradient-to-tr from-indigo-600 via-purple-600 to-pink-500 rounded-2xl flex items-center justify-center text-white text-2xl mx-auto mb-4 shadow-lg shadow-purple-500/25">
                        <i class="ri-lock-2-line"></i>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">Welcome Back</h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Sign in to manage your gigs, hires, and projects</p>
                </div>

                <!-- Flash Errors -->
                <?php if ($error): ?>
                    <div class="mb-6 p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 flex items-center justify-between text-xs sm:text-sm shadow-2xs">
                        <div class="flex items-center gap-2.5">
                            <i class="ri-error-warning-fill text-rose-500 text-lg flex-shrink-0"></i>
                            <span><?= htmlspecialchars($error); ?></span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-rose-400 hover:text-rose-600 dark:hover:text-rose-300 p-1 cursor-pointer"><i class="ri-close-line"></i></button>
                    </div>
                <?php endif; ?>

                <!-- Quick Demo Login Switcher -->
                <div class="mb-6 p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2 flex items-center justify-between">
                        <span>Demo Quick Fill:</span>
                        <span class="text-purple-600 dark:text-purple-400 font-semibold">1-Click Login</span>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" onclick="fillDemo('client1', '123')" class="px-2 py-1.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-purple-500 text-slate-700 dark:text-slate-200 text-xs font-semibold hover:text-purple-600 dark:hover:text-purple-400 transition-colors flex items-center justify-center gap-1 shadow-2xs cursor-pointer">
                            <i class="ri-user-line text-[11px]"></i> Client
                        </button>
                        <button type="button" onclick="fillDemo('user1', '123')" class="px-2 py-1.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-purple-500 text-slate-700 dark:text-slate-200 text-xs font-semibold hover:text-purple-600 dark:hover:text-purple-400 transition-colors flex items-center justify-center gap-1 shadow-2xs cursor-pointer">
                            <i class="ri-code-box-line text-[11px]"></i> Freelancer
                        </button>
                        <button type="button" onclick="fillDemo('admin', '123')" class="px-2 py-1.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-purple-500 text-slate-700 dark:text-slate-200 text-xs font-semibold hover:text-purple-600 dark:hover:text-purple-400 transition-colors flex items-center justify-center gap-1 shadow-2xs cursor-pointer">
                            <i class="ri-shield-star-line text-[11px]"></i> Admin
                        </button>
                    </div>
                </div>

                <form method="POST" action="" class="space-y-5">
                    <!-- Username or Email -->
                    <div>
                        <label for="username" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Username or Email</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="ri-user-smile-line text-base"></i>
                            </span>
                            <input type="text" id="username" name="username" required placeholder="Enter username or email" value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>" class="w-full pl-10 pr-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all">
                        </div>
                    </div>

                    <!-- Password with Show/Hide Toggle -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-bold text-slate-700 dark:text-slate-300">Password</label>
                            <a href="reset_password.php" class="text-xs font-bold text-purple-600 dark:text-purple-400 hover:underline transition-colors">Forgot password?</a>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="ri-lock-password-line text-base"></i>
                            </span>
                            <input type="password" id="password" name="password" required placeholder="Enter your password" class="w-full pl-10 pr-10 py-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all">
                            <button type="button" onclick="togglePasswordVisibility('password', 'eye-login')" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 focus:outline-none cursor-pointer">
                                <i id="eye-login" class="ri-eye-line text-base"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me Option -->
                    <div class="flex items-center">
                        <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-slate-300 dark:border-slate-700 rounded cursor-pointer">
                        <label for="remember-me" class="ml-2 block text-xs font-medium text-slate-600 dark:text-slate-400 cursor-pointer">
                            Keep me signed in for 30 days
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button type="submit" class="w-full py-3.5 px-6 rounded-2xl bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-sm shadow-md shadow-purple-500/25 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer">
                            <span>Sign In</span>
                            <i class="ri-arrow-right-line text-base"></i>
                        </button>
                    </div>
                </form>

                <!-- Footer Switcher Link -->
                <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800 text-center">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Don't have an account yet?
                        <a href="signup.php" class="font-bold text-purple-600 dark:text-purple-400 hover:underline transition-colors ml-1">
                            Create account free &rarr;
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include_once "../includes/footer.php"; ?>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({ duration: 600, once: true });

        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'ri-eye-off-line';
            } else {
                input.type = 'password';
                icon.className = 'ri-eye-line';
            }
        }

        function fillDemo(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
        }
    </script>
</body>

</html>