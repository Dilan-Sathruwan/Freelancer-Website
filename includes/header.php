<?php
// Unified Header Component for Client, Freelancer, and Public Pages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Calculate dynamic root path
$root_path = file_exists('./config/db.con.php') ? './' : '../';

// Include index_header with proper root path
include_once __DIR__ . '/index_header.php';
?>
