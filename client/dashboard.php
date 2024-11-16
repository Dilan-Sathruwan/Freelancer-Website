<?php
// Include necessary files for connection and session
include('../config/db_connection.php');
include('../includes/header.php');

// Assuming the user is logged in and the user ID is stored in session
session_start();
$userId = $_SESSION['user_id'];  // Adjust as per your session variable
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet"> <!-- AOS Library -->
</head>
<body>
    <!-- Dashboard Header -->
    <section class="dashboard-header">
        <div class="container">
            <h1 class="text-center mb-4" data-aos="fade-up">Welcome to Your Dashboard</h1>
            <div class="row">
                <!-- Profile Section -->
                <div class="col-md-4" data-aos="fade-up">
                    <div class="profile-card text-center">
                        <img src="../assets/images/default-avatar.png" alt="Profile Picture" class="img-fluid rounded-circle mb-3" width="120">
                        <h3><?php echo htmlspecialchars($_SESSION['username']); ?></h3>
                        <p>Client</p>
                        <a href="edit_profile.php" class="btn btn-primary">Edit Profile</a>
                    </div>
                </div>

                <!-- My Gigs Section -->
                <div class="col-md-8" data-aos="fade-up" data-aos-delay="100">
                    <h3>Your Gigs</h3>
                    <div class="row">
                        <?php
                        $sql = "SELECT * FROM gigs WHERE client_id = :user_id ORDER BY created_at DESC";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute(['user_id' => $userId]);

                        if ($stmt->rowCount() > 0) {
                            while ($gig = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $gigId = $gig['id'];
                                $title = htmlspecialchars($gig['title']);
                                $price = number_format($gig['price'], 2);
                                $description = htmlspecialchars(substr($gig['description'], 0, 150)) . '...'; // Limit description length
                        ?>
                            <div class="col-md-4 mb-4">
                                <div class="gig-card">
                                    <h5><a href="gig_detail.php?id=<?php echo $gigId; ?>"><?php echo $title; ?></a></h5>
                                    <p><?php echo $description; ?></p>
                                    <p><strong>Price: $<?php echo $price; ?></strong></p>
                                    <a href="gig_detail.php?id=<?php echo $gigId; ?>" class="btn btn-info">View Details</a>
                                </div>
                            </div>
                        <?php
                            }
                        } else {
                            echo "<p>No gigs found.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Active Projects Section -->
    <section class="active-projects" id="active-projects" style="padding: 50px 0;">
        <div class="container">
            <h2 class="text-center" data-aos="fade-up">Active Projects</h2>
            <div class="row">
                <?php
                $sql = "SELECT p.id, p.title, p.status, f.username, p.created_at
                        FROM projects p
                        JOIN freelancers f ON p.freelancer_id = f.id
                        WHERE p.client_id = :user_id AND p.status != 'completed'
                        ORDER BY p.created_at DESC";
                $stmt = $conn->prepare($sql);
                $stmt->execute(['user_id' => $userId]);

                if ($stmt->rowCount() > 0) {
                    while ($project = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $projectId = $project['id'];
                        $title = htmlspecialchars($project['title']);
                        $status = htmlspecialchars($project['status']);
                        $freelancer = htmlspecialchars($project['username']);
                        $createdAt = date('F j, Y', strtotime($project['created_at']));
                ?>
                    <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="100">
                        <div class="project-card">
                            <h5><a href="project_detail.php?id=<?php echo $projectId; ?>"><?php echo $title; ?></a></h5>
                            <p>Status: <strong><?php echo $status; ?></strong></p>
                            <p>Freelancer: <em><?php echo $freelancer; ?></em></p>
                            <p><small>Started on: <?php echo $createdAt; ?></small></p>
                            <a href="project_detail.php?id=<?php echo $projectId; ?>" class="btn btn-info">View Project</a>
                        </div>
                    </div>
                <?php
                    }
                } else {
                    echo "<p>No active projects found.</p>";
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include('../includes/footer.php'); ?>

    <!-- AOS Script for Animations -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
</body>
</html>
