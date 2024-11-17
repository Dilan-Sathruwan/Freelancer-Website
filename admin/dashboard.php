<?php
include '../config/db.php';

if (isset($_GET["search"])) {
    $sql = "SELECT id, username, first_name, last_name, email, role, status FROM users WHERE username LIKE :search OR first_name LIKE :search OR last_name LIKE :search OR email LIKE :search";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':search', '%' . $_GET["search"] . '%', PDO::PARAM_STR);
    $result = $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
} else {
    $sql = "SELECT id, username, first_name, last_name, email, role, status FROM users";
    $stmt = $conn->query($sql);
}

// include '../includes/header.php';
// include '../includes/session.php';

// Ensure only admins can access this page
// if ($_SESSION['role'] !== 'admin') {
//     header("Location: ../public/index.php");
//     exit;
// }

$freelancerCount = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'freelancer'")->fetchColumn();
$clientCount = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
$adminCount = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

$stmt = $conn->query("SELECT id, username, first_name, last_name, email, role, status FROM users");
$users = $stmt->fetchAll();


if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

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
            background-color: black;
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


        .btn-danger {
            background-color: #000000;
        }
    </style>
</head>

<body>
    <link rel="stylesheet" href="../assets/css/external.css">
    <div style="position: fixed; right: 10px; top: 10px;">
        <a href="../config/logout.php" class="btn btn-danger">Log out</a>
    </div>
    
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

        <!-- Recent activity -->
        <input type="text" id="searchInput" placeholder="Type to Search by username" onkeyup="Search(event)" class="w-100 mt-5 p-2 border-0">
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
                        <th>Role</th>
                        <th>action</th>
                    </tr>
                </thead>

                <tbody id="tbody">
                </tbody>
            </table>
        </div>
    </div>

    <?php
    // Include footer
    include '../includes/admin_footer.php';
    ?>

    <script>
        function Search(event) {
            const tbody = document.getElementById('tbody');
            tbody.innerHTML = '';

            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'dashboard.php?search=' + encodeURIComponent(event.target.value));
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const users = JSON.parse(xhr.responseText);
                    users.forEach(user => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                    <td>${user.id}</td>
                    <td>${user.username}</td>
                    <td>${user.first_name}</td>
                    <td>${user.last_name}</td>
                    <td>${user.email}</td>
                    <td>${user.status}</td>
                    <td>${user.role}</td>
                    <td>
                        <a href="dashboard.php?delete=${user.id}" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </td>
                `;
                        tbody.appendChild(row);
                    });
                }
            };
            xhr.send();
        }

        Search({
            target: {
                value: ''
            }
        });
    </script>

</body>

</html>