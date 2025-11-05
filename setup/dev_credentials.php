<?php
// Development Credentials Display - FOR DEVELOPMENT PURPOSES ONLY
// This file should be deleted in production environments

// Security check - only allow access from localhost
if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('Access denied. This page is for development purposes only.');
}

include_once dirname(__DIR__) . "/config/db.con.php";

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Development Credentials</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-5'>
        <div class='row'>
            <div class='col-md-12'>
                <div class='alert alert-warning'>
                    <h2>⚠️ DEVELOPMENT PURPOSES ONLY</h2>
                    <p>This page shows user credentials for development/testing. 
                    Do not use this in production environments!</p>
                </div>
                
                <div class='card'>
                    <div class='card-header'>
                        <h3>Available User Accounts</h3>
                    </div>
                    <div class='card-body'>";

try {
    // Fetch all users from database
    $stmt = $conn->prepare("SELECT id, username, role, status FROM users ORDER BY role, username");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    if ($users) {
        echo "<div class='table-responsive'>
                <table class='table table-striped table-hover'>
                    <thead class='table-dark'>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Password</th>
                            <th>Login Link</th>
                        </tr>
                    </thead>
                    <tbody>";
        
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td>" . htmlspecialchars($user['status']) . "</td>";
            echo "<td><span class='badge bg-info'>123</span></td>";
            echo "<td><a href='login.php' class='btn btn-sm btn-primary'>Login</a></td>";
            echo "</tr>";
        }
        
        echo "</tbody>
                </table>
              </div>";
    } else {
        echo "<div class='alert alert-info'>No users found in database.</div>";
    }
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error fetching users: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "          </div>
                </div>
                
                <div class='card mt-4'>
                    <div class='card-header'>
                        <h3>How to Use These Credentials</h3>
                    </div>
                    <div class='card-body'>
                        <ol>
                            <li>Go to the <a href='login.php'>Login Page</a></li>
                            <li>Enter any username from the table above</li>
                            <li>Use <strong>123</strong> as the password for all accounts</li>
                            <li>You will be redirected based on your role:
                                <ul>
                                    <li>Admins: <a href='../admin/dashboard.php'>Admin Dashboard</a></li>
                                    <li>Clients: <a href='../client/dashboard.php'>Client Dashboard</a></li>
                                    <li>Freelancers: <a href='../freelancer/dashboard.php'>Freelancer Dashboard</a></li>
                                </ul>
                            </li>
                        </ol>
                    </div>
                </div>
                
                <div class='mt-4'>
                    <a href='login.php' class='btn btn-success'>Go to Login Page</a>
                    <a href='../index.php' class='btn btn-secondary'>Home Page</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
?>