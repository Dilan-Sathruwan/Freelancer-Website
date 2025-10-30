<?php
session_start();
require_once "../config/db.con.php";

// Debug: Show session status
error_log("Login page loaded. Session ID: " . session_id());

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    error_log("User already logged in. Role: " . ($_SESSION['role'] ?? 'unknown'));
    switch ($_SESSION['role']) {
        case 'client':
            header("Location: ../client/dashboard.php");
            exit();
        case 'freelancer':
            header("Location: ../freelancer/dashboard.php");
            exit();
        default: // admin and any other roles
            header("Location: ../admin/dashboard.php");
            exit();
    }
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Login form submitted");
    
    // Get raw input - avoid any sanitization that might alter the data
    $input_username = trim($_POST['username'] ?? '');
    $input_password = $_POST['password'] ?? '';
    
    error_log("Input username: " . $input_username);
    error_log("Input password length: " . strlen($input_password));
    
    // Validate required fields
    if (empty($input_username) || empty($input_password)) {
        $error_message = "Please fill in all fields.";
        error_log("Validation failed: empty fields");
    } else {
        try {
            // Query user by username or email using prepared statement
            $stmt = $conn->prepare("SELECT id, username, password, role, status FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$input_username, $input_username]);
            
            error_log("Database query executed. Rows found: " . $stmt->rowCount());
            
            // Check if user exists
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                error_log("User found: " . $user['username'] . " (ID: " . $user['id'] . ")");
                
                // Check account status
                if ($user['status'] !== 'active') {
                    $error_message = "Account is not active. Please contact support.";
                    error_log("Account not active: " . $user['status']);
                } 
                // Verify password hash securely
                elseif (password_verify($input_password, $user['password'])) {
                    error_log("Password verification successful");
                    
                    // Regenerate session ID to prevent session fixation attacks
                    session_regenerate_id(true);
                    error_log("Session regenerated. New ID: " . session_id());
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    error_log("Session variables set - user_id: " . $_SESSION['user_id'] . ", username: " . $_SESSION['username'] . ", role: " . $_SESSION['role']);
                    
                    // Update last login timestamp
                    $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $update_stmt->execute([$user['id']]);
                    
                    error_log("Last login timestamp updated");
                    
                    // Redirect based on role
                    switch ($user['role']) {
                        case 'client':
                            error_log("Redirecting client to dashboard");
                            header("Location: ../client/dashboard.php");
                            exit();
                        case 'freelancer':
                            error_log("Redirecting freelancer to dashboard");
                            header("Location: ../freelancer/dashboard.php");
                            exit();
                        default: // admin and any other roles
                            error_log("Redirecting " . $user['role'] . " to admin dashboard");
                            header("Location: ../admin/dashboard.php");
                            exit();
                    }
                } else {
                    $error_message = "Invalid username or password.";
                    error_log("Password verification failed");
                }
            } else {
                $error_message = "Invalid username or password.";
                error_log("User not found in database");
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error_message = "An error occurred during login. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Freelancer Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-6 text-center">
                <h1 class="text-3xl font-bold text-white">Welcome Back</h1>
                <p class="text-blue-100 mt-2">Sign in to your account</p>
            </div>
            
            <form method="POST" class="p-6">
                <?php if ($error_message): ?>
                    <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
                
                <div class="mb-4">
                    <label for="username" class="block text-gray-700 font-medium mb-2">Username or Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            required 
                            class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                            placeholder="Enter your username or email"
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                        >
                    </div>
                </div>
                
                <div class="mb-6">
                    <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required 
                            class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                            placeholder="Enter your password"
                        >
                    </div>
                </div>
                
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <input 
                            id="remember" 
                            type="checkbox" 
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <label for="remember" class="ml-2 block text-sm text-gray-700">
                            Remember me
                        </label>
                    </div>
                    <a href="#" class="text-sm text-blue-600 hover:text-blue-800 transition duration-200">
                        Forgot password?
                    </a>
                </div>
                
                <button 
                    type="submit" 
                    class="w-full bg-gradient-to-r from-blue-600 to-indigo-700 text-white py-3 rounded-lg font-semibold hover:from-blue-700 hover:to-indigo-800 transition duration-300 transform hover:-translate-y-0.5 shadow-lg hover:shadow-xl"
                >
                    Sign In
                </button>
            </form>
            
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                <p class="text-center text-gray-600">
                    Don't have an account? 
                    <a href="signup.php" class="text-blue-600 font-semibold hover:text-blue-800 transition duration-200">
                        Sign up
                    </a>
                </p>
            </div>
        </div>
        
        <div class="mt-6 text-center">
            <a href="../index.php" class="text-gray-600 hover:text-gray-800 transition duration-200 flex items-center justify-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Home
            </a>
        </div>
        
        <div class="mt-6 p-4 bg-yellow-50 rounded-lg">
            <h3 class="font-bold text-yellow-800">Test Credentials:</h3>
            <p class="text-yellow-700">Admin: admin / admin123</p>
            <p class="text-yellow-700">Freelancer: user1 / freelancer1</p>
            <p class="text-yellow-700">Client: client1 / client123</p>
        </div>
    </div>
    
    <script>
        // Add subtle animations
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            if (form) {
                form.style.opacity = '0';
                form.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    form.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    form.style.opacity = '1';
                    form.style.transform = 'translateY(0)';
                }, 100);
            }
        });
    </script>
</body>
</html>