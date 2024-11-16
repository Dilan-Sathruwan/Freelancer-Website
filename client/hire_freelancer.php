<?php
// Include necessary files for connection and session
include('../config/db_connection.php');
include('../includes/header.php');

// Assuming the user is logged in and the user ID is stored in session
session_start();
$userId = $_SESSION['user_id'];  // Adjust as per your session variable

// Get freelancer's ID (from query string or form submission)
$freelancerId = $_GET['freelancer_id'] ?? null;  // Adjust based on your URL structure

if (!$freelancerId) {
    // If no freelancer ID is passed, redirect to the gigs page or show an error
    header('Location: gig.php');
    exit;
}

// Fetch freelancer details from the database
$sql = "SELECT * FROM freelancers WHERE id = :freelancer_id";
$stmt = $conn->prepare($sql);
$stmt->execute(['freelancer_id' => $freelancerId]);

if ($stmt->rowCount() > 0) {
    $freelancer = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    echo "<p>Freelancer not found!</p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hire Freelancer</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet"> <!-- AOS Library -->
</head>
<body>
    <section class="hire-freelancer">
        <div class="container">
            <h1 class="text-center mb-4" data-aos="fade-up">Hire Freelancer</h1>
            <div class="row">
                <!-- Freelancer Details Section -->
                <div class="col-md-6" data-aos="fade-up">
                    <div class="freelancer-card">
                        <img src="../assets/images/<?php echo htmlspecialchars($freelancer['profile_picture']); ?>" alt="Freelancer Image" class="img-fluid rounded-circle mb-3" width="120">
                        <h3><?php echo htmlspecialchars($freelancer['username']); ?></h3>
                        <p><strong>Skills:</strong> <?php echo htmlspecialchars($freelancer['skills']); ?></p>
                        <p><strong>Hourly Rate:</strong> $<?php echo number_format($freelancer['hourly_rate'], 2); ?> /hr</p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($freelancer['location']); ?></p>
                        <p><strong>Rating:</strong> 
                            <?php
                                $rating = $freelancer['rating'] ?? 0;
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                }
                            ?>
                        </p>
                        <p><?php echo htmlspecialchars($freelancer['bio']); ?></p>
                    </div>
                </div>

                <!-- Hire Freelancer Form -->
                <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <h4>Project Details</h4>
                    <form action="hire_freelancer_action.php" method="POST">
                        <div class="form-group">
                            <label for="projectTitle">Project Title</label>
                            <input type="text" class="form-control" id="projectTitle" name="projectTitle" required>
                        </div>
                        <div class="form-group">
                            <label for="projectDescription">Project Description</label>
                            <textarea class="form-control" id="projectDescription" name="projectDescription" rows="4" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="budget">Budget</label>
                            <input type="number" class="form-control" id="budget" name="budget" placeholder="Enter project budget" required>
                        </div>
                        <div class="form-group">
                            <label for="deadline">Project Deadline</label>
                            <input type="date" class="form-control" id="deadline" name="deadline" required>
                        </div>
                        <input type="hidden" name="clientId" value="<?php echo $userId; ?>">
                        <input type="hidden" name="freelancerId" value="<?php echo $freelancerId; ?>">
                        <button type="submit" class="btn btn-primary">Hire Freelancer</button>
                    </form>
                </div>
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
