
<?php
// Include necessary files for connection and session
include('../config/db_connection.php');
include('../includes/header.php');

// Assuming the user is logged in and the user ID is stored in session
session_start();
$userId = $_SESSION['user_id'];  // Adjust as per your session variable

// Get freelancer's ID (from query string or form submission)
$freelancerId = $_GET['freelancer_id'] ?? null;  // Adjust based on your URL structure
$projectId = $_GET['project_id'] ?? null;  // Assuming a project ID is passed in the URL

if (!$freelancerId || !$projectId) {
    // If no freelancer ID or project ID is passed, redirect to the project page or show an error
    header('Location: projects.php');
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

// Check if the client has already left a review for this freelancer
$checkReviewSql = "SELECT * FROM reviews WHERE client_id = :client_id AND freelancer_id = :freelancer_id AND project_id = :project_id";
$checkStmt = $conn->prepare($checkReviewSql);
$checkStmt->execute([
    'client_id' => $userId,
    'freelancer_id' => $freelancerId,
    'project_id' => $projectId,
]);

$review = $checkStmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave a Review</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet"> <!-- AOS Library -->
</head>
<body>
    <section class="leave-review">
        <div class="container">
            <h1 class="text-center mb-4" data-aos="fade-up">Leave a Review for <?php echo htmlspecialchars($freelancer['username']); ?></h1>
            <div class="row">
                <!-- Freelancer Details Section -->
                <div class="col-md-6" data-aos="fade-up">
                    <div class="freelancer-card">
                        <img src="../assets/images/<?php echo htmlspecialchars($freelancer['profile_picture']); ?>" alt="Freelancer Image" class="img-fluid rounded-circle mb-3" width="120">
                        <h3><?php echo htmlspecialchars($freelancer['username']); ?></h3>
                        <p><strong>Skills:</strong> <?php echo htmlspecialchars($freelancer['skills']); ?></p>
                    </div>
                </div>

                <!-- Review Form Section -->
                <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <?php if ($review): ?>
                        <h4>Your Review</h4>
                        <p><strong>Rating:</strong> 
                            <?php
                            $rating = $review['rating'] ?? 0;
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                            }
                            ?>
                        </p>
                        <p><strong>Review:</strong> <?php echo htmlspecialchars($review['comment']); ?></p>
                        <p><strong>Submitted on:</strong> <?php echo $review['created_at']; ?></p>
                    <?php else: ?>
                        <h4>Write Your Review</h4>
                        <form action="leave_review_action.php" method="POST">
                            <div class="form-group">
                                <label for="rating">Rating</label>
                                <div class="stars">
                                    <i class="far fa-star" data-value="1"></i>
                                    <i class="far fa-star" data-value="2"></i>
                                    <i class="far fa-star" data-value="3"></i>
                                    <i class="far fa-star" data-value="4"></i>
                                    <i class="far fa-star" data-value="5"></i>
                                </div>
                                <input type="hidden" id="rating" name="rating" value="0">
                            </div>
                            <div class="form-group">
                                <label for="comment">Your Review</label>
                                <textarea class="form-control" id="comment" name="comment" rows="4" required></textarea>
                            </div>
                            <input type="hidden" name="clientId" value="<?php echo $userId; ?>">
                            <input type="hidden" name="freelancerId" value="<?php echo $freelancerId; ?>">
                            <input type="hidden" name="projectId" value="<?php echo $projectId; ?>">
                            <button type="submit" class="btn btn-primary">Submit Review</button>
                        </form>
                    <?php endif; ?>
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

        // Handle Star Rating
        document.querySelectorAll('.stars i').forEach(function(star) {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-value');
                document.getElementById('rating').value = rating;
                document.querySelectorAll('.stars i').forEach(function(s) {
                    s.classList.remove('fas');
                    s.classList.add('far');
                });
                for (let i = 0; i < rating; i++) {
                    document.querySelectorAll('.stars i')[i].classList.remove('far');
                    document.querySelectorAll('.stars i')[i].classList.add('fas');
                }
            });
        });
    </script>
</body>
</html>
