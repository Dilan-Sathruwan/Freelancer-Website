<?php
session_start();

include '../config/db.con.php'; // Include database connection

// Check if user is logged in and is a freelancer
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'freelancer') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch freelancer's profile info
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'freelancer'");
    $stmt->execute([$user_id]);
    $freelancer = $stmt->fetch();
    
    if (!$freelancer) {
        $_SESSION['error'] = "Freelancer not found.";
        header("Location: ../index.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Error fetching freelancer: " . $e->getMessage());
    $_SESSION['error'] = "Error fetching freelancer data.";
    header("Location: ../index.php");
    exit;
}

if (isset($_POST['profileSubmit'])) {
    // Sanitize input
    $username = sanitizeInput($_POST['username']);
    
    try {
        $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ? AND role = 'freelancer'");
        $stmt->execute([$username, $user_id]);
        header("Location: dashboard.php");
        exit;
    } catch (PDOException $e) {
        error_log("Error updating profile: " . $e->getMessage());
        $error = "Error updating profile.";
    }
}

if (isset($_GET['deleteId'])) {
    // Validate input
    $gigId = validateInteger($_GET['deleteId']);
    
    if ($gigId) {
        try {
            $stmt = $conn->prepare("DELETE FROM gigs WHERE id = ? AND freelancer_id = ?");
            $stmt->execute([$gigId, $user_id]);
            header("Location: dashboard.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error deleting gig: " . $e->getMessage());
            $error = "Error deleting gig.";
        }
    }
}

if (isset($_POST['updateGig'])) {
    // Validate and sanitize inputs
    $gigId = validateInteger($_POST['gitID']);
    $title = sanitizeInput($_POST['title']);
    $delivery_time = validateInteger($_POST['delivery_time']);
    $description = sanitizeInput($_POST['description']);
    $price = floatval($_POST['price']);
    
    if ($gigId && $title && $delivery_time && $description && $price > 0) {
        try {
            $stmt = $conn->prepare("UPDATE gigs SET title = ?, description = ?, delivery_time = ?, price = ? WHERE id = ? AND freelancer_id = ?");
            $stmt->execute([$title, $description, $delivery_time, $price, $gigId, $user_id]);
            header("Location: dashboard.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error updating gig: " . $e->getMessage());
            $error = "Error updating gig.";
        }
    }
}

if (isset($_POST['addGig'])) {
    // Validate and sanitize inputs
    $title = sanitizeInput($_POST['new_title']);
    $description = sanitizeInput($_POST['new_description']);
    $category = validateInteger($_POST['new_category']);
    $price = floatval($_POST['new_price']);
    $delivery_time = validateInteger($_POST['new_delivery_time']);
    $image = null;
    
    if ($title && $description && $category && $price > 0 && $delivery_time) {
        // Handle image upload
        if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] == 0) {
            $target_dir = "../uploads/gigs/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $image = $target_dir . basename($_FILES['new_image']['name']);
            move_uploaded_file($_FILES['new_image']['tmp_name'], $image);
        }

        // Insert gig into the database
        try {
            $stmt = $conn->prepare("INSERT INTO gigs (freelancer_id, title, description, category_id, price, delivery_time, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $description, $category, $price, $delivery_time, $image]);
            header("Location: dashboard.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error adding gig: " . $e->getMessage());
            $error = "Error adding gig.";
        }
    }
}
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

        .button-section {
            margin: 30px 0;
            text-align: center;
        }

        .button-section a {
            margin: 10px;
            padding: 15px 30px;
            text-decoration: none;
            background-color: #D9641E;
            color: #fff;
            border-radius: 5px;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .button-section a:hover {
            background-color: #D9641E;
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

        .cc {
            background-color: #D9641E !important;
            color: #fff !important;
        }
    </style>
</head>

<body>

    <?php
    $logoutPage = "../auth/logout.php";
    $myGigsPage = "../freelancer/dashboard.php";

    include('../includes/index_header.php'); ?>
    <div class="container">
        <div class="row mt-5">
            <div class="col-md-12 text-center">
                <h1>Welcome, <?php echo htmlspecialchars($freelancer['first_name']); ?>!</h1>
                <p>Your current role: <strong>Freelancer</strong></p>
            </div>
        </div>

        <!-- Display error message if any -->
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Freelancer Profile Section -->
        <div class="card">
            <div class="card-header">
                <h3>Your Profile</h3>
            </div>
            <div class="card-body">
                <form action="" method="post">
                    <p><strong>Username:</strong>
                        <input class="border-0" name="username" type="text" value="<?php echo htmlspecialchars($freelancer['username']); ?>">
                    </p>
                    <p><strong>Email:</strong>
                        <input type="text" disabled name="email" value="<?php echo htmlspecialchars($freelancer['email']); ?>" class="border-0">
                    </p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($freelancer['status']); ?></p>
                    <button type="submit" name="profileSubmit" class="btn cc">Update</button>
                </form>
            </div>
        </div>

        <!-- Button section -->
        <div class="button-section">
            <a href="manage_job_requests.php" title="Manage Users"><i class="fas fa-user-plus"></i> Manage Jobs</a>
            <!-- <a href="manage_freelancers.php"><i class="fas fa-chart-line"></i> Manage Freelancers</a> -->
        </div>

        <!-- Manage Gigs Section -->
        <div class="card">
            <div class="card-header">
                <h3>Your Gigs</h3>
            </div>
            <div class="card-body">
                <!-- Add Gig Form -->

                <form action="" method="POST" enctype="multipart/form-data" class="mb-4">
                    <h5 class="mb-3">Add a New Gig</h5>
                    <div class="mb-3">
                        <input type="text" name="new_title" class="form-control" placeholder="Gig Title" required>
                    </div>
                    <div class="mb-3">
                        <textarea name="new_description" class="form-control" rows="3" placeholder="Gig Description" required></textarea>
                    </div>
                    <div class="mb-3">
                        <input type="number" name="new_price" class="form-control" placeholder="Price ($)" required>
                    </div>
                    <div class="mb-3">
                        <input type="number" name="new_delivery_time" class="form-control" placeholder="Delivery Time (Days)" required>
                    </div>
                    <div class="mb-3">
                        <select name="new_category" class="form-control" required>
                            <option value="" disabled selected>Choose a Category</option>
                            <?php
                            try {
                                $stmt = $conn->prepare("SELECT * FROM categories WHERE status = 'active'");
                                $stmt->execute();
                                $categories = $stmt->fetchAll();
                                foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['id']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach;
                            } catch (PDOException $e) {
                                error_log("Error fetching categories: " . $e->getMessage());
                            } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <input type="file" name="new_image" class="form-control">
                    </div>
                    <button type="submit" name="addGig" class="btn cc"><i class="fas fa-plus-circle"></i> Add Gig</button>
                </form>

                <!-- List Existing Gigs -->
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Gig Title</th>
                            <th>Description</th>
                            <th>Delivery Days</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt = $conn->prepare("SELECT * FROM gigs WHERE freelancer_id = ?");
                            $stmt->execute([$user_id]);
                            $gigs = $stmt->fetchAll();
                            if ($gigs):
                                foreach ($gigs as $gig): ?>
                                    <tr>
                                        <form action="" method="POST">
                                            <td>
                                                <input type="text" name="title" class="border-0" value="<?php echo htmlspecialchars($gig['title']); ?>">
                                            </td>
                                            <td class="d-none">
                                                <input type="text" name="gitID" class="border-0" value="<?php echo htmlspecialchars($gig['id']); ?>">
                                            </td>
                                            <td>
                                                <textarea name="description" class="border-0" cols="30" rows="3"><?php echo htmlspecialchars($gig['description']); ?></textarea>
                                            </td>
                                            <td>
                                                <input name="delivery_time" class="border-0" value="<?php echo htmlspecialchars($gig['delivery_time']); ?>">
                                            </td>
                                            <td>$<input type="text" name="price" class="border-0" value="<?php echo htmlspecialchars($gig['price']); ?>"></td>
                                            <td><?php echo htmlspecialchars($gig['status']); ?></td>
                                            <td style="width: 150px  align-items-center">
                                                <button type="submit" name="updateGig" class="btn btn-dark btn-sm" style="margin-right: 10px;">Update</button>
                                                <a href="dashboard.php?deleteId=<?php echo $gig['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this gig?');" style="margin-right: 10px;">Delete</a>
                                            </td>
                                        </form>
                                    </tr>
                                <?php endforeach;
                            else: ?>
                                <tr>
                                    <td colspan="6">No gigs found. Add one above!</td>
                                </tr>
                            <?php endif;
                        } catch (PDOException $e) {
                            error_log("Error fetching gigs: " . $e->getMessage());
                            echo "<tr><td colspan='6'>Error fetching gigs.</td></tr>";
                        } ?>
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