<?php
session_start();
include_once "./config/db.con.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freelancer Website</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- AOS Animation -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="./assets/css/index_style.css">

</head>

<body>

    <?php
    $logoutPage = "./config/logout.php";
    $loginPage = "./auth/login.php";
    $regPage = "./auth/signup.php";
    $profilePage = "./public/profile.php";
    $homePage = "index.php";
    $logo = "./assets/img/Logo.png";

    if (isset($_SESSION['role']) == 'client') {
        $myGigsPage = "./client/dashboard.php";
    } else {
        $myGigsPage = "./freelancer/dashboard.php";
    }

    include './includes/index_header.php';
    include('includes/theme_toggle.php');
    ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1 data-aos="fade-up">Find the Best Freelancers</h1>
            <p data-aos="fade-up" data-aos-delay="100">Hire professionals for your projects, or showcase your skills to the world. It's that easy!</p>
            <a href="./public/gig.php" class="btn btn-outline-light btn-lg mt-3 ms-2" data-aos="zoom-in" data-aos-delay="250">Explore Features</a>
        </div>
    </section>
    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container bg-transparent">
            <h2 data-aos="fade-right">Why Choose Us?</h2>
            <div class="row mt-4">
                <div class="col-md-4" data-aos="fade-up">
                    <div class="feature-card text-center">
                        <i class="fas fa-briefcase fa-4x mb-3 clo"></i>
                        <h5>Hire Professionals</h5>
                        <p>Find and hire the best talent for your project needs.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card text-center">
                        <i class="fas fa-globe fa-4x mb-3 clo"></i>
                        <h5>Global Talent</h5>
                        <p>Access freelancers from around the globe, no matter where you are.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card text-center">
                        <i class="fas fa-dollar-sign fa-4x mb-3 clo"></i>
                        <h5>Affordable Pricing</h5>
                        <p>Get quality services at competitive rates that fit your budget.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials" id="testimonials">
        <div class="containe bg-transparentr">
            <h2 class="text-center mb-4" data-aos="fade-up">What Our Clients Say</h2>
            <div class="testimonial-row">
                <?php
                $sql = "SELECT r.comment, r.rating, u.username FROM reviews r JOIN users u ON r.client_id = u.id";
                try {
                    $stmt = $conn->prepare($sql);
                    $stmt->execute();

                    if ($stmt->rowCount() > 0) {
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $comment = $row["comment"];
                            $rating = $row["rating"];
                            $userName = $row["username"];
                ?>
                            <div class="testimonial-item" data-aos="fade-up" data-aos-delay="<?php echo rand(100, 500); ?>">
                                <i class="fas fa-quote-left mb-3"></i>
                                <p><?php echo htmlspecialchars($comment); ?></p>
                                <h5><?php echo htmlspecialchars($userName); ?></h5>
                                <div class="rating">
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                    }
                                    ?>
                                </div>
                            </div>
                <?php
                        }
                    } else {
                        echo "<p class='text-center'>No reviews found.</p>";
                    }
                } catch (PDOException $e) {
                    echo "<p class='text-center'>Error fetching reviews: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                ?>
            </div>
        </div>
    </section>


    <!-- Call-to-Action Section -->
    <section class="cta">
        <div class="container">
            <h2 data-aos="fade-up">Start Your Journey Today</h2>
            <p data-aos="fade-up" data-aos-delay="100">Sign up now and join the thousands of users already enjoying our services!</p>
            <a href="./auth/signup.php" class="btn btn-light" data-aos="zoom-in" data-aos-delay="200">Sign Up Now</a>
        </div>
    </section>

    <?php include('includes/footer.php'); ?>

    <!-- Bootstrap, AOS Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

    <!-- Custom JS -->
    <script>
        AOS.init();
    </script>

</body>