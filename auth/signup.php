<?php
session_start();
require_once "../config/db.con.php";

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
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
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and validate input
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $account_type = $_POST['account_type'] ?? '';
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($account_type)) {
        $error_message = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif ($account_type !== 'client' && $account_type !== 'freelancer') {
        $error_message = "Invalid account type selected.";
    } else {
        try {
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                $error_message = "Username or email already exists.";
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (username, password, role, first_name, last_name, email, status, email_verified, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', 0, NOW())");
                $stmt->execute([$username, $hashed_password, $account_type, $first_name, $last_name, $email]);
                
                // Get the inserted user ID
                $user_id = $conn->lastInsertId();
                
                // If user is a freelancer, create a freelancer profile entry
                if ($account_type === 'freelancer') {
                    $stmt = $conn->prepare("INSERT INTO freelancer_profiles (user_id, created_at) VALUES (?, NOW())");
                    $stmt->execute([$user_id]);
                }
                // If user is a client, create a client profile entry
                elseif ($account_type === 'client') {
                    $stmt = $conn->prepare("INSERT INTO client_profiles (user_id, created_at) VALUES (?, NOW())");
                    $stmt->execute([$user_id]);
                }
                
                $success_message = "Account created successfully! You can now login.";
            }
        } catch (PDOException $e) {
            error_log("Signup error: " . $e->getMessage());
            $error_message = "An error occurred during signup. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | Freelancer Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-6 text-center">
                <h1 class="text-3xl font-bold text-white">Create Account</h1>
                <p class="text-blue-100 mt-2">Join our freelancer community</p>
            </div>
            
            <form method="POST" class="p-6">
                <?php if ($error_message): ?>
                    <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="mb-4 p-3 bg-green-50 text-green-700 rounded-lg flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="first_name" class="block text-gray-700 font-medium mb-2">First Name</label>
                        <input 
                            type="text" 
                            id="first_name" 
                            name="first_name" 
                            required 
                            class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                            placeholder="First name"
                            value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                        >
                    </div>
                    
                    <div>
                        <label for="last_name" class="block text-gray-700 font-medium mb-2">Last Name</label>
                        <input 
                            type="text" 
                            id="last_name" 
                            name="last_name" 
                            required 
                            class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                            placeholder="Last name"
                            value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                        >
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="username" class="block text-gray-700 font-medium mb-2">Username</label>
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
                            placeholder="Choose a username"
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                        >
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required 
                            class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                            placeholder="Enter your email"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                        >
                    </div>
                </div>
                
                <div class="mb-4">
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
                            placeholder="Create a password"
                        >
                    </div>
                    <p class="text-xs text-gray-500 mt-1">At least 6 characters</p>
                </div>
                
                <div class="mb-6">
                    <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Confirm Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            required 
                            class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                            placeholder="Confirm your password"
                        >
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2">Account Type</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-blue-50 transition duration-200 <?php echo (isset($_POST['account_type']) && $_POST['account_type'] === 'client') ? 'border-blue-500 bg-blue-50' : ''; ?>">
                            <input 
                                type="radio" 
                                name="account_type" 
                                value="client" 
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500"
                                <?php echo (isset($_POST['account_type']) && $_POST['account_type'] === 'client') ? 'checked' : ''; ?>
                            >
                            <div class="ml-3">
                                <p class="font-medium text-gray-900">Client</p>
                                <p class="text-sm text-gray-500">Hire freelancers for projects</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-blue-50 transition duration-200 <?php echo (isset($_POST['account_type']) && $_POST['account_type'] === 'freelancer') ? 'border-blue-500 bg-blue-50' : ''; ?>">
                            <input 
                                type="radio" 
                                name="account_type" 
                                value="freelancer" 
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500"
                                <?php echo (isset($_POST['account_type']) && $_POST['account_type'] === 'freelancer') ? 'checked' : ''; ?>
                            >
                            <div class="ml-3">
                                <p class="font-medium text-gray-900">Freelancer</p>
                                <p class="text-sm text-gray-500">Offer services to clients</p>
                            </div>
                        </label>
                    </div>
                </div>
                
                <button 
                    type="submit" 
                    class="w-full bg-gradient-to-r from-blue-600 to-indigo-700 text-white py-3 rounded-lg font-semibold hover:from-blue-700 hover:to-indigo-800 transition duration-300 transform hover:-translate-y-0.5 shadow-lg hover:shadow-xl"
                >
                    Create Account
                </button>
            </form>
            
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                <p class="text-center text-gray-600">
                    Already have an account? 
                    <a href="login.php" class="text-blue-600 font-semibold hover:text-blue-800 transition duration-200">
                        Sign in
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