<?php
session_start();

// Log the logout activity before destroying the session
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    // Include the logging functions
    include_once "../config/db.con.php";
    include_once "../includes/logging_functions.php";
    
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    // Log the logout activity
    if ($role === 'admin') {
        logAdminLogout($userId);
    } else {
        logUserLogout($userId);
    }
}

// Unset all session variables
$_SESSION = array();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to homepage
header("Location: ../index.php");
exit();
?>