<?php

// include '../includes/header.php';
// include '../includes/session.php';
include '../config/db.php';

// Ensure only admins can access this page
// if ($_SESSION['role'] !== 'admin') {
//     header("Location: ../public/index.php");
//     exit;
// }

$freelancerCount = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'freelancer'")->fetchColumn();
$clientCount = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
$adminCount = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

$stmt = $conn->query("SELECT id, username, first_name, last_name, email, status FROM users");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- Your custom CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome -->
    <style>
        /* Dashboard custom styles */
        .dashboard-container {
            padding: 20px;
            background-color: #f8f9fa;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .stats-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: space-around;
        }

        .card {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            flex: 1;
            min-width: 250px;
            text-align: center;
        }

        .card h3 {
            font-size: 1.5rem;
            color: #333;
        }

        .card p {
            font-size: 1rem;
            color: #666;
        }

        .card .icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #007bff;
        }

        .button-section {
            margin: 30px 0;
            text-align: center;
        }

        .button-section a {
            margin: 10px;
            padding: 15px 30px;
            text-decoration: none;
            background-color: #007bff;
            color: #fff;
            border-radius: 5px;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .button-section a:hover {
            background-color: #0056b3;
        }

        /* Recent activity table styles */
        .recent-activity {
            margin-top: 40px;
        }

        .recent-activity h3 {
            margin-bottom: 20px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .btn {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
        }

        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Welcome, Admin!</h1>
            <p>Here is your overview of the system</p>
        </div>

        <!-- Stats cards -->
        <div class="stats-cards">
            <div class="card">
                <div class="icon"><i class="fas fa-user-graduate"></i></div>
                <h3><?php echo $freelancerCount; ?></h3>
                <p>Total Freelancer</p>
            </div>
            <div class="card">
                <div class="icon"><i class="fas fa-chalkboard-teacher"></i></div>
                <h3><?php echo $clientCount; ?></h3>
                <p>Total Client</p>
            </div>
            <div class="card">
                <div class="icon"><i class="fas fa-building"></i></div>
                <h3><?php echo $adminCount; ?></h3>
                <p>Total Admin</p>
            </div>
        </div>
        <!-- Button section -->
        <div class="button-section">
            <a href="manage_client.php" title="Manage Users"><i class="fas fa-user-plus"></i> Manage Client</a>
            <a href="manage_admins.php"><i class="fas fa-cogs"></i> Manage Admins</a>
            <a href="manage_freelancers.php"><i class="fas fa-chart-line"></i> Manage Freelancers</a>
        </div>

        <!-- Button section -->
        <div class="button-section">
            <a href="manage_client.php" title="Manage Users"><i class="fas fa-user-plus"></i> Manage Client</a>
            <a href="manage_admins.php"><i class="fas fa-cogs"></i> Manage Admins</a>
            <a href="manage_freelancers.php"><i class="fas fa-chart-line"></i> Manage Freelancers</a>
        </div>

        <!-- Recent activity -->
        <div class="recent-activity">
            <h3>Recent Activity</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['status']); ?></td>
                                <td>
                                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-warning">Edit</a>
                                    <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <a href="reports.php" class="btn">View All Reports</a>
        </div>
    </div>
</body>

</html>

<?php
// Include footer
// include '../includes/footer.php';
?>