<?php
include '../config/db.php'; // Include database connection
// include '../includes/session.php'; // Include session management
// Ensure only super admins can access this page
// if ($_SESSION['role'] !== 'super_admin') {
//     header("Location: ../public/index.php");
//     exit;
// }

// Handle form submission for adding/editing admins
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $username = $_POST['username'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $status = $_POST['status'];

    if ($id) {
        // Update existing admin, include password only if provided
        if (!empty($password)) {
            $stmt = $conn->prepare("UPDATE users SET username = ?, first_name = ?, last_name = ?, email = ?, password = ?, status = ? WHERE id = ?");
            $stmt->execute([$username, $first_name, $last_name, $email, $password, $status, $id]);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, first_name = ?, last_name = ?, email = ?, status = ? WHERE id = ?");
            $stmt->execute([$username, $first_name, $last_name, $email, $status, $id]);
        }
    } else {
        // Add new admin
        $stmt = $conn->prepare("INSERT INTO users (username, first_name, last_name, email, password, role, status) VALUES (?, ?, ?, ?, ?, 'admin', ?)");
        $stmt->execute([$username, $first_name, $last_name, $email, $password, $status]);
    }
    header("Location: manage_admins.php");
    exit;
}

// Handle delete admin
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage_admins.php");
    exit;
}

// Fetch all admins
$stmt = $conn->query("SELECT id, username, first_name, last_name, email, status FROM users WHERE role = 'admin'");
$admins = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins</title>
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- Your custom CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome -->
    <style>
        .form-container, .table-container {
            margin: 20px auto;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            max-width: 800px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        form {
            display: grid;
            gap: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #28a745;
            color: white;
        }
        .btn {
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 5px;
        }
        .btn-edit {
            background-color: #ffc107;
            color: white;
        }
        .btn-edit:hover {
            background-color: #e0a800;
        }
        .btn-delete {
            background-color: #dc3545;
            color: white;
        }
        .btn-delete:hover {
            background-color: #c82333;
        }
        .btn-submit {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-submit:hover {
            background-color: #218838;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <h2><?php echo isset($_GET['edit']) ? 'Edit Admin' : 'Add Admin'; ?></h2>
        <form method="POST" action="manage_admins.php">
            <?php
            // Populate form fields for editing
            $editAdmin = null;
            if (isset($_GET['edit'])) {
                $id = $_GET['edit'];
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
                $stmt->execute([$id]);
                $editAdmin = $stmt->fetch();
            }
            ?>
            <input type="hidden" name="id" value="<?php echo $editAdmin['id'] ?? ''; ?>">
            <label>Username:</label>
            <input type="text" name="username" value="<?php echo $editAdmin['username'] ?? ''; ?>" required>
            <label>First Name:</label>
            <input type="text" name="first_name" value="<?php echo $editAdmin['first_name'] ?? ''; ?>" required>
            <label>Last Name:</label>
            <input type="text" name="last_name" value="<?php echo $editAdmin['last_name'] ?? ''; ?>" required>
            <label>Email:</label>
            <input type="email" name="email" value="<?php echo $editAdmin['email'] ?? ''; ?>" required>
            <label>Password: <small>(Leave blank to retain current password)</small></label>
            <input type="password" name="password">
            <label>Status:</label>
            <select name="status" required>
                <option value="active" <?php echo (isset($editAdmin['status']) && $editAdmin['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo (isset($editAdmin['status']) && $editAdmin['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
            </select>
            <button type="submit" class="btn-submit"><?php echo isset($_GET['edit']) ? 'Update Admin' : 'Add Admin'; ?></button>
        </form>
    </div>

    <div class="table-container">
        <h2>Admin List</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($admins): ?>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($admin['id']); ?></td>
                            <td><?php echo htmlspecialchars($admin['username']); ?></td>
                            <td><?php echo htmlspecialchars($admin['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($admin['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                            <td><?php echo htmlspecialchars($admin['status']); ?></td>
                            <td>
                                <a href="manage_admins.php?edit=<?php echo $admin['id']; ?>" class="btn btn-edit">Edit</a>
                                <a href="manage_admins.php?delete=<?php echo $admin['id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this admin?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">No admins found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>

</html>
