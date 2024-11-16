<?php
session_start();

include '../config/db.php'; // Include database connection

// Fetch freelancer's profile info
$user_id = $_SESSION['id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'freelancer'");
$stmt->execute([$user_id]);
$freelancer = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freelancer Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- Your custom CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card {
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: #007bff;
            color: white;
        }

        .btn-primary {
            background-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .table th,
        .table td {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row mt-5">
            <div class="col-md-12 text-center">
                <h1>Welcome, <?php echo htmlspecialchars($freelancer['first_name']); ?>!</h1>
                <p>Your current role: <strong>Freelancer</strong></p>
            </div>
        </div>

        <!-- Freelancer Profile Section -->
        <div class="card">
            <div class="card-header">
                <h3>Your Profile</h3>
            </div>
            <div class="card-body">
                <p><strong>Username:</strong> <?php echo htmlspecialchars($freelancer['username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($freelancer['email']); ?></p>
                <p><strong>Status:</strong> <?php echo htmlspecialchars($freelancer['status']); ?></p>
                <a href="edit_profile.php" class="btn btn-primary">Edit Profile</a>
            </div>
        </div>

        <!-- Manage Gigs Section -->
        <div class="card">
            <div class="card-header">
                <h3>Your Gigs</h3>
            </div>
            <div class="card-body">
                <a href="add_gig.php" class="btn btn-primary mb-3">Add New Gig</a>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Gig Title</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch freelancer's gigs
                        $stmt = $conn->prepare("SELECT * FROM gigs WHERE freelancer_id = ?");
                        $stmt->execute([$user_id]);
                        $gigs = $stmt->fetchAll();
                        if ($gigs):
                            foreach ($gigs as $gig): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($gig['title']); ?></td>
                                    <td><?php echo htmlspecialchars($gig['description']); ?></td>
                                    <td>$<?php echo htmlspecialchars($gig['price']); ?></td>
                                    <td><?php echo htmlspecialchars($gig['status']); ?></td>
                                    <td>
                                        <a href="edit_gig.php?id=<?php echo $gig['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <a href="delete_gig.php?id=<?php echo $gig['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this gig?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach;
                        else: ?>
                            <tr>
                                <td colspan="5">No gigs found. <a href="add_gig.php">Add a new gig</a></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>

</html>