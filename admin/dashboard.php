<?php
// Include necessary files
include '../includes/header.php'; // Include header/navigation
include '../includes/session.php'; // Session management
include '../config/db.php'; // Database connection

// Ensure only admins can access this page
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../public/index.php");
    exit;
}

// Fetch data for dashboard stats
// Assuming table names: students, lecturers, departments
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM students"))['count'];
$total_lecturers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM lecturers"))['count'];
$total_departments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS count FROM departments"))['count'];

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

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
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
                <h3><?php echo $total_students; ?></h3>
                <p>Total Students</p>
            </div>
            <div class="card">
                <div class="icon"><i class="fas fa-chalkboard-teacher"></i></div>
                <h3><?php echo $total_lecturers; ?></h3>
                <p>Total Lecturers</p>
            </div>
            <div class="card">
                <div class="icon"><i class="fas fa-building"></i></div>
                <h3><?php echo $total_departments; ?></h3>
                <p>Total Departments</p>
            </div>
        </div>

        <!-- Recent activity -->
        <div class="recent-activity">
            <h3>Recent Activity</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Description</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch recent activity logs
                    $logs = mysqli_query($conn, "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 5");
                    while ($row = mysqli_fetch_assoc($logs)) {
                        echo "<tr>
                            <td>{$row['id']}</td>
                            <td>{$row['description']}</td>
                            <td>{$row['created_at']}</td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
            <a href="reports.php" class="btn">View All Reports</a>
        </div>
    </div>
</body>
</html>
<?php
// Include footer
include '../includes/footer.php';
?>
