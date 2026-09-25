<?php
session_start();
include_once dirname(__DIR__) . "/config/db.con.php";

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($newPassword) || empty($confirmPassword)) {
        $error = "Please fill in all required fields.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match. Please re-enter both fields.";
    } elseif (strlen($newPassword) < 6) {
        $error = "New password must be at least 6 characters in length.";
    } else {
        try {
            // Check if user exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            $stmt->closeCursor();

            if ($user) {
                // Hash the new password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                // Update the password
                $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateStmt->execute([$hashedPassword, $user['id']]);
                $updateStmt->closeCursor();

                $message = "Your password has been successfully updated! You can now sign in with your new credentials.";
            } else {
                $error = "No user account was found matching that username or email address.";
            }
        } catch (PDOException $e) {
            error_log("Password reset error: " . $e->getMessage());
            $error = "An error occurred while resetting your password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - FreelanceHub</title>
    <meta name="description" content="Reset your FreelanceHub account password securely.">
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
            <div class="bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl rounded-3xl shadow-2xl shadow-purple-950/5 dark:shadow-black/50 border border-slate-200/90 dark:border-slate-800 p-8 sm:p-10" data-aos="fade-up">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="w-14 h-14 bg-gradient-to-tr from-purple-600 via-indigo-600 to-pink-500 rounded-2xl flex items-center justify-center text-white text-2xl mx-auto mb-4 shadow-lg shadow-purple-500/25">
                        <i class="ri-key-2-line"></i>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">Reset Password</h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Enter your account credentials to set a new password</p>
                </div>

                <!-- Flash Alerts -->
                <?php if ($error): ?>
                    <div class="mb-6 p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 flex items-center justify-between text-xs sm:text-sm">
                        <div class="flex items-center gap-2.5">
                            <i class="ri-error-warning-fill text-rose-500 text-lg flex-shrink-0"></i>
                            <span><?= htmlspecialchars($error); ?></span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-rose-400 hover:text-rose-600 dark:hover:text-rose-300 p-1 cursor-pointer"><i class="ri-close-line"></i></button>
                    </div>
                <?php endif; ?>

                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 flex items-center justify-between text-xs sm:text-sm">
                        <div class="flex items-center gap-2.5">
                            <i class="ri-checkbox-circle-fill text-emerald-600 dark:text-emerald-400 text-lg flex-shrink-0"></i>
                            <span><?= htmlspecialchars($message); ?></span>
                        </div>
                        <a href="login.php" class="px-3.5 py-1.5 bg-emerald-600 text-white rounded-xl text-xs font-bold hover:bg-emerald-700 transition-colors ml-2 flex-shrink-0">Sign In</a>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-5">
                    <div>
                        <label for="username" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Username or Registered Email</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                <i class="ri-user-smile-line"></i>
                            </span>
                            <input type="text" id="username" name="username" required placeholder="Enter username or email" value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>" class="w-full pl-10 pr-4 py-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all">
                        </div>
                    </div>

                    <div>
                        <label for="new_password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">New Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                <i class="ri-lock-2-line"></i>
                            </span>
                            <input type="password" id="new_password" name="new_password" required placeholder="Min. 6 characters" class="w-full pl-10 pr-10 py-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all">
                            <button type="button" onclick="togglePasswordVisibility('new_password', 'eye-npwd')" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 focus:outline-none cursor-pointer">
                                <i id="eye-npwd" class="ri-eye-line"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Confirm New Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                <i class="ri-lock-check-line"></i>
                            </span>
                            <input type="password" id="confirm_password" name="confirm_password" required placeholder="Re-type new password" class="w-full pl-10 pr-10 py-3 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700 text-slate-900 dark:text-white placeholder-slate-400 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all">
                            <button type="button" onclick="togglePasswordVisibility('confirm_password', 'eye-cpwd')" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 focus:outline-none cursor-pointer">
                                <i id="eye-cpwd" class="ri-eye-line"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-sm shadow-xl shadow-purple-500/20 hover:shadow-purple-500/30 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer">
                            <span>Update Password</span>
                            <i class="ri-arrow-right-line"></i>
                        </button>
                    </div>
                </form>

                <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800 text-center">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Remembered your password?
                        <a href="login.php" class="font-bold text-purple-600 dark:text-purple-400 hover:text-purple-500 transition-colors ml-1">
                            Back to sign in &rarr;
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
    </script>
</body>

</html>