<?php
session_start();
include_once "../config/db.con.php";

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
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
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            // Prepare statement to prevent SQL injection
            $stmt = $conn->prepare("SELECT id, username, password, role, status FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            // Debug: Check what we found
            if ($user) {
                error_log("User found: " . $user['username'] . " with role: " . $user['role']);
                error_log("Provided password: " . $password);
                error_log("Stored hash: " . $user['password']);
                
                $passwordValid = password_verify($password, $user['password']);
                error_log("Password valid: " . ($passwordValid ? 'true' : 'false'));
            } else {
                error_log("No user found for username/email: " . $username);
            }
            
            if ($user && $passwordValid) {
                // Check if account is active
                if ($user['status'] !== 'active') {
                    $error = "Account is not active. Please contact administrator.";
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
                        // Log error but don't stop login process
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
                            $error = "Invalid user role.";
                    }
                }
            } else {
                // More informative error message
                if ($user) {
                    $error = "Invalid password. Please try again.";
                } else {
                    $error = "Invalid username/email. Please try again.";
                }
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "An error occurred during login. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FreelanceHub</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Remix Icon -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <!-- Tailwind Custom Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                        secondary: '#8b5cf6',
                        accent: '#ec4899',
                        dark: '#0f172a',
                        light: '#f8fafc',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <!-- Header -->
    <?php include_once "../includes/auth_header.php"; ?>

    <!-- Main Content -->
    <main class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 mt-12">
        <div class="max-w-md w-full space-y-8">
            <div class="bg-white rounded-2xl shadow-xl p-8 sm:p-10 transition-all duration-300 hover:shadow-2xl">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-purple-600 to-blue-600 shadow-lg">
                        <i class="ri-lock-line text-2xl text-white"></i>
                    </div>
                    <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Welcome Back</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Sign in to your FreelanceHub account
                    </p>
                </div>

                <?php if ($error): ?>
                    <div class="mt-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                        <div class="flex items-center">
                            <i class="ri-error-warning-line text-lg mr-2"></i>
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <form class="mt-8 space-y-6" method="POST" action="">
                    <div class="space-y-4">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                                Username or Email
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="ri-user-line text-gray-400"></i>
                                </div>
                                <input
                                    id="username"
                                    name="username"
                                    type="text"
                                    required
                                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition duration-300"
                                    placeholder="Enter your username or email">
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                Password
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="ri-lock-password-line text-gray-400"></i>
                                </div>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    required
                                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition duration-300"
                                    placeholder="Enter your password">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input
                                id="remember-me"
                                name="remember-me"
                                type="checkbox"
                                class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                            <label for="remember-me" class="ml-2 block text-sm text-gray-700">
                                Remember me
                            </label>
                        </div>

                        <div class="text-sm">
                            <a href="reset_password.php" class="font-medium text-purple-600 hover:text-purple-500 transition duration-300">
                                Forgot your password?
                            </a>
                        </div>
                    </div>

                    <div>
                        <button
                            type="submit"
                            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition duration-300">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i class="ri-login-box-line text-purple-300 group-hover:text-purple-200"></i>
                            </span>
                            Sign in
                        </button>
                    </div>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        Don't have an account?
                        <a href="signup.php" class="font-medium text-purple-600 hover:text-purple-500 transition duration-300">
                            Sign up now
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
    <script src="../assets/js/index.js"></script>
    <script src="../assets/js/slider.js"></script>

    <!-- Scripts -->
    <script>
        // Add any necessary JavaScript here
    </script>
</body>
</html>