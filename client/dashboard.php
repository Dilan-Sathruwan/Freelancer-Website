<?php
include('../config/db.php');
session_start();

$userId = $_SESSION['id']; // Logged-in user ID
$userName = $_SESSION['username']; // Logged-in username

// Fetch gig details for the logged-in user
$sql = "
    SELECT g.id AS gig_id, g.title, g.description, g.price, g.image, u.first_name, u.last_name 
    FROM client_gigs cg
    JOIN gigs g ON cg.gig_id = g.id
    JOIN users u ON g.freelancer_id = u.id
    WHERE cg.user_id = :user_id
    AND g.status = 'active';
";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
$stmt->execute();
$gigs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- AOS Animation CSS -->
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .navbar {
            margin-bottom: 20px;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: #343a40;
            color: #fff;
        }

        .btn-primary,
        .btn-info {
            transition: transform 0.2s ease;
        }

        .btn-primary:hover,
        .btn-info:hover {
            transform: scale(1.05);
        }

        img.rounded-circle {
            border: 3px solid #ddd;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Client Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <!-- Profile Section -->
            <div class="col-md-4">
                <div class="card" data-aos="fade-up">
                    <div class="card-header">
                        <h3>Profile</h3>
                    </div>
                    <div class="card-body text-center">
                        <img src="../assets/images/default-avatar.png" alt="Profile Picture" class="img-fluid rounded-circle mb-3" width="120">
                        <h4><?php echo htmlspecialchars($userName); ?></h4>
                        <p class="text-muted">Client</p>
                        <a href="edit_profile.php" class="btn btn-primary">Edit Profile</a>
                    </div>
                </div>
            </div>

            <!-- Gigs Section -->
            <div class="col-md-8">
                <div class="card" data-aos="fade-up" data-aos-delay="100">
                    <div class="card-header">
                        <h3>Your Gigs</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if (!empty($gigs)): ?>
                                <?php foreach ($gigs as $gig): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <img src="<?= htmlspecialchars($gig['image'] ?? 'default.jpg') ?>" class="card-img-top" alt="Gig Image">
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    <a href="gig_detail.php?id=<?= htmlspecialchars($gig['gig_id']) ?>"
                                                        class="text-decoration-none text-dark">
                                                        <?= htmlspecialchars($gig['title']) ?>
                                                    </a>
                                                </h5>
                                                <p class="card-text"><?= htmlspecialchars($gig['description']) ?></p>
                                                <p class="card-text"><strong>Price: $<?= htmlspecialchars($gig['price']) ?></strong></p>
                                                <p class="card-text">Freelancer: <?= htmlspecialchars($gig['first_name'] . ' ' . $gig['last_name']) ?></p>
                                                <a href="gig_detail.php?id=<?= htmlspecialchars($gig['gig_id']) ?>" class="btn btn-info">View Details</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12">
                                    <p class="text-center">No gigs available for you.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Projects Section -->
        <div class="row mt-4">
            <div class="col-md-12">
                <h2 class="text-center" data-aos="fade-up">Active Projects</h2>
            </div>
        </div>
    </div>


    <!-- Footer -->
    <?php include('../includes/footer.php'); ?> <!-- Footer -->
    <footer>
        <p>&copy; 2024 Client Dashboard. All Rights Reserved.</p>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Script for Animations -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
</body>

</html>