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
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = validateEmailInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $accountType = sanitizeInput($_POST['account_type'] ?? 'client');

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($firstName) || empty($lastName) || empty($accountType)) {
        $error = "Please fill in all required fields.";
    } elseif ($accountType === 'admin') {
        $error = "Administrator accounts cannot be created through public registration.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match. Please re-enter both passwords.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters in length.";
    } elseif (!in_array($accountType, ['client', 'freelancer'])) {
        $error = "Please select whether you want to join as a Client or a Freelancer.";
    } else {
        try {
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);

            if ($stmt->fetch()) {
                $error = "Username or email is already registered. Please sign in or use different details.";
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Insert user into database
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hashedPassword, $firstName, $lastName, $accountType]);

                $userId = $conn->lastInsertId();

                // Create profile based on account type
                if ($accountType === 'freelancer') {
                    $stmtFp = $conn->prepare("INSERT INTO freelancer_profiles (user_id) VALUES (?)");
                    $stmtFp->execute([$userId]);

                    // Also create entry in legacy freelancers table for full system compatibility
                    $stmtFree = $conn->prepare("INSERT INTO freelancers (user_id, username, skills, hourly_rate, location, rating, bio) VALUES (?, ?, 'Freelancer', 25.00, 'Remote', 5.00, '')");
                    $stmtFree->execute([$userId, $username]);
                } elseif ($accountType === 'client') {
                    $stmtCp = $conn->prepare("INSERT INTO client_profiles (user_id) VALUES (?)");
                    $stmtCp->execute([$userId]);
                }

                // Create wallet for user
                $stmtW = $conn->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0.00)");
                $stmtW->execute([$userId]);

                $success = "Welcome to FreelanceHub! Your account was created successfully. You can now sign in.";
            }
        } catch (PDOException $e) {
            error_log("Signup error: " . $e->getMessage());
            $error = "An unexpected error occurred during registration. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create an Account - FreelanceHub</title>
    <meta name="description" content="Join FreelanceHub as a client or freelancer. Hire top talent or offer your services on the leading freelance network.">
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

    <!-- Main Registration Container -->
    <main class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="max-w-xl w-full">
            <!-- Glassmorphic Card -->
            <div class="bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl rounded-3xl shadow-2xl shadow-purple-950/5 dark:shadow-black/50 border border-slate-200/90 dark:border-slate-800 p-8 sm:p-10" data-aos="fade-up">
                <!-- Branding Header -->
                <div class="text-center mb-8">
                    <div class="w-14 h-14 bg-gradient-to-tr from-indigo-600 via-purple-600 to-pink-500 rounded-2xl flex items-center justify-center text-white text-2xl mx-auto mb-4 shadow-lg shadow-purple-500/25">
                        <i class="ri-user-add-line"></i>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">Join FreelanceHub</h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Connect with global opportunities in minutes</p>
                </div>

                <!-- Flash Alerts -->
                <?php if ($error): ?>
                    <div class="mb-6 p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 flex items-center justify-between text-xs sm:text-sm shadow-2xs">
                        <div class="flex items-center gap-2.5">
                            <i class="ri-error-warning-fill text-rose-500 text-lg flex-shrink-0"></i>
                            <span><?= htmlspecialchars($error); ?></span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-rose-400 hover:text-rose-600 dark:hover:text-rose-300 p-1 cursor-pointer"><i class="ri-close-line"></i></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="mb-6 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 flex items-center justify-between text-xs sm:text-sm shadow-2xs">
                        <div class="flex items-center gap-2.5">
                            <i class="ri-checkbox-circle-fill text-emerald-600 dark:text-emerald-400 text-lg flex-shrink-0"></i>
                            <span><?= htmlspecialchars($success); ?></span>
                        </div>
                        <a href="login.php" class="px-3.5 py-1.5 bg-emerald-600 text-white rounded-xl text-xs font-bold hover:bg-emerald-700 transition-colors ml-2 flex-shrink-0">Sign In</a>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-6">
                    <!-- Interactive Account Type Selection Cards -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2.5">I want to join as a:</label>
                        <input type="hidden" name="account_type" id="account_type" value="<?= htmlspecialchars($_POST['account_type'] ?? 'client'); ?>">

                        <div class="grid grid-cols-2 gap-3 sm:gap-4">
                            <!-- Option A: Client Card -->
                            <div id="card-client" onclick="selectRole('client')" class="role-card cursor-pointer rounded-2xl p-4 border-2 transition-all duration-200 flex flex-col justify-between text-left <?= (!isset($_POST['account_type']) || $_POST['account_type'] === 'client') ? 'border-purple-600 bg-purple-50/50 dark:bg-purple-950/40 shadow-sm' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800/60 hover:border-slate-300 dark:hover:border-slate-700'; ?>">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xl">
                                        <i class="ri-briefcase-4-line"></i>
                                    </div>
                                    <div id="check-client" class="w-5 h-5 rounded-full border-2 flex items-center justify-center <?= (!isset($_POST['account_type']) || $_POST['account_type'] === 'client') ? 'border-purple-600 bg-purple-600 text-white' : 'border-slate-300 dark:border-slate-600'; ?>">
                                        <i class="ri-check-line text-xs <?= (!isset($_POST['account_type']) || $_POST['account_type'] === 'client') ? '' : 'hidden'; ?>"></i>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900 dark:text-white text-sm">Hiring Client</h4>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">Hire talent and order services</p>
                                </div>
                            </div>

                            <!-- Option B: Freelancer Card -->
                            <div id="card-freelancer" onclick="selectRole('freelancer')" class="role-card cursor-pointer rounded-2xl p-4 border-2 transition-all duration-200 flex flex-col justify-between text-left <?= (isset($_POST['account_type']) && $_POST['account_type'] === 'freelancer') ? 'border-purple-600 bg-purple-50/50 dark:bg-purple-950/40 shadow-sm' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800/60 hover:border-slate-300 dark:hover:border-slate-700'; ?>">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-950 text-purple-600 dark:text-purple-400 flex items-center justify-center text-xl">
                                        <i class="ri-code-box-line"></i>
                                    </div>
                                    <div id="check-freelancer" class="w-5 h-5 rounded-full border-2 flex items-center justify-center <?= (isset($_POST['account_type']) && $_POST['account_type'] === 'freelancer') ? 'border-purple-600 bg-purple-600 text-white' : 'border-slate-300 dark:border-slate-600'; ?>">
                                        <i class="ri-check-line text-xs <?= (isset($_POST['account_type']) && $_POST['account_type'] === 'freelancer') ? '' : 'hidden'; ?>"></i>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900 dark:text-white text-sm">Freelancer</h4>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">Publish gigs and earn money</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Name Fields (Grid) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">First Name <span class="text-rose-500">*</span></label>
                            <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? ''); ?>" required placeholder="e.g. Alex" class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all">
                        </div>
                        <div>
                            <label for="last_name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Last Name <span class="text-rose-500">*</span></label>
                            <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? ''); ?>" required placeholder="e.g. Rivera" class="w-full px-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all">
                        </div>
                    </div>

                    <!-- Username & Email Fields -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="username" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Username <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                    <i class="ri-user-smile-line"></i>
                                </span>
                                <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>" required placeholder="username" class="w-full pl-10 pr-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all">
                            </div>
                        </div>

                        <div>
                            <label for="email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Email Address <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                    <i class="ri-mail-line"></i>
                                </span>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="alex@example.com" class="w-full pl-10 pr-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all">
                            </div>
                        </div>
                    </div>

                    <!-- Password and Confirmation -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Password <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                    <i class="ri-lock-2-line"></i>
                                </span>
                                <input type="password" id="password" name="password" required placeholder="Min. 6 characters" oninput="checkStrength(this.value)" class="w-full pl-10 pr-10 py-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all">
                                <button type="button" onclick="togglePasswordVisibility('password', 'eye-pwd')" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 focus:outline-none">
                                    <i id="eye-pwd" class="ri-eye-line"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Confirm Password <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                    <i class="ri-lock-check-line"></i>
                                </span>
                                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Re-enter password" class="w-full pl-10 pr-10 py-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all">
                                <button type="button" onclick="togglePasswordVisibility('confirm_password', 'eye-cpwd')" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 focus:outline-none">
                                    <i id="eye-cpwd" class="ri-eye-line"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Password Strength Meter -->
                    <div id="strength-container" class="hidden">
                        <div class="flex items-center justify-between text-[11px] font-semibold text-slate-500 dark:text-slate-400 mb-1">
                            <span>Password Strength:</span>
                            <span id="strength-label" class="text-rose-500">Weak</span>
                        </div>
                        <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div id="strength-bar" class="h-full bg-rose-500 w-1/4 transition-all duration-300"></div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button type="submit" class="w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-sm shadow-xl shadow-purple-500/20 hover:shadow-purple-500/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2">
                            <span>Create Free Account</span>
                            <i class="ri-arrow-right-line"></i>
                        </button>
                    </div>

                    <!-- Terms Note -->
                    <p class="text-[11px] text-center text-slate-400 dark:text-slate-500">
                        By continuing, you agree to FreelanceHub's <a href="#" class="text-purple-600 dark:text-purple-400 hover:underline">Terms of Service</a> and <a href="#" class="text-purple-600 dark:text-purple-400 hover:underline">Privacy Policy</a>.
                    </p>
                </form>

                <!-- Footer Switcher Link -->
                <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800 text-center">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Already have an account?
                        <a href="login.php" class="font-bold text-purple-600 dark:text-purple-400 hover:text-purple-500 transition-colors ml-1">
                            Sign in here &rarr;
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

        // Role Card Selection Handler with dark mode support
        function selectRole(role) {
            document.getElementById('account_type').value = role;

            const cardClient = document.getElementById('card-client');
            const cardFreelancer = document.getElementById('card-freelancer');
            const checkClient = document.getElementById('check-client');
            const checkFreelancer = document.getElementById('check-freelancer');

            const activeClass = 'role-card cursor-pointer rounded-2xl p-4 border-2 transition-all duration-200 flex flex-col justify-between text-left border-purple-600 bg-purple-50/50 dark:bg-purple-950/40 shadow-sm';
            const inactiveClass = 'role-card cursor-pointer rounded-2xl p-4 border-2 transition-all duration-200 flex flex-col justify-between text-left border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800/60 hover:border-slate-300 dark:hover:border-slate-700';

            const activeCheck = 'w-5 h-5 rounded-full border-2 flex items-center justify-center border-purple-600 bg-purple-600 text-white';
            const inactiveCheck = 'w-5 h-5 rounded-full border-2 flex items-center justify-center border-slate-300 dark:border-slate-600';

            if (role === 'client') {
                cardClient.className = activeClass;
                cardFreelancer.className = inactiveClass;
                checkClient.className = activeCheck;
                checkClient.querySelector('i').classList.remove('hidden');
                checkFreelancer.className = inactiveCheck;
                checkFreelancer.querySelector('i').classList.add('hidden');
            } else {
                cardFreelancer.className = activeClass;
                cardClient.className = inactiveClass;
                checkFreelancer.className = activeCheck;
                checkFreelancer.querySelector('i').classList.remove('hidden');
                checkClient.className = inactiveCheck;
                checkClient.querySelector('i').classList.add('hidden');
            }
        }

        // Toggle Password Show/Hide
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

        // Password Strength Indicator
        function checkStrength(val) {
            const container = document.getElementById('strength-container');
            const bar = document.getElementById('strength-bar');
            const label = document.getElementById('strength-label');

            if (!val || val.length === 0) {
                container.classList.add('hidden');
                return;
            }
            container.classList.remove('hidden');

            let score = 0;
            if (val.length >= 6) score++;
            if (val.length >= 10) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            if (score <= 1) {
                bar.className = 'h-full bg-rose-500 w-1/4 transition-all duration-300';
                label.className = 'text-rose-500 font-bold';
                label.textContent = 'Weak';
            } else if (score <= 3) {
                bar.className = 'h-full bg-amber-500 w-2/3 transition-all duration-300';
                label.className = 'text-amber-600 font-bold';
                label.textContent = 'Good';
            } else {
                bar.className = 'h-full bg-emerald-500 w-full transition-all duration-300';
                label.className = 'text-emerald-600 font-bold';
                label.textContent = 'Strong';
            }
        }
    </script>
</body>

</html>