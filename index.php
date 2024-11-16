<?php
session_start();
include_once "./config/db.php";

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
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .hero {
            background: linear-gradient(to right, rgba(106, 17, 203, 0.8), rgba(37, 117, 252, 0.8)), url('https://via.placeholder.com/1920x800') no-repeat center center;
            background-size: cover;
            color: #fff;
            text-align: center;
            padding: 120px 20px;
            position: relative;
        }

        .hero h1 {
            font-size: 4rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 1.2rem;
            font-weight: 300;
        }

        .hero .btn {
            font-size: 1.1rem;
            margin-top: 20px;
            padding: 12px 30px;
        }

        .features {
            padding: 80px 20px;
        }

        .features h2 {
            text-align: center;
            margin-bottom: 40px;
            font-size: 2.5rem;
            font-weight: 600;
        }

        .features .feature-card {
            background-color: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .features .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 25px rgba(0, 0, 0, 0.2);
        }

        .footer {
            background-color: #343a40;
            color: #fff;
            text-align: center;
            padding: 40px 20px;
        }

        .footer a {
            color: #f8f9fa;
            text-decoration: none;
            margin: 0 15px;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        .testimonials {
            background-color: #f8f9fa;
            padding: 80px 20px;
        }

        .testimonial-item {
            text-align: center;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .testimonial-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 25px rgba(0, 0, 0, 0.1);
        }

        .testimonial-item i {
            font-size: 3rem;
            color: #2575fc;
            margin-bottom: 20px;
        }

        .testimonial-item h5 {
            font-size: 1.25rem;
            margin-top: 15px;
            font-weight: 500;
        }


        /* Add responsiveness */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .features .feature-card {
                margin-bottom: 30px;
            }
        }
    </style>
</head>

<body>
    <?php
    $loginPage = "./public/login.php";
    $regPage = "./public/sign_up.php";
    include './includes/index_header.php';
    ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1 data-aos="fade-up">Find the Best Freelancers</h1>
            <p data-aos="fade-up" data-aos-delay="100">Hire professionals for your projects, or showcase your skills to the world. It's that easy!</p>
            <a href="register.php" class="btn btn-primary btn-lg mt-3" data-aos="zoom-in" data-aos-delay="200">Get Started</a>
            <a href="#features" class="btn btn-outline-light btn-lg mt-3 ms-2" data-aos="zoom-in" data-aos-delay="250">Explore Features</a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <h2 data-aos="fade-right">Why Choose Us?</h2>
            <div class="row mt-4">
                <div class="col-md-4" data-aos="fade-up">
                    <div class="feature-card text-center">
                        <i class="fas fa-briefcase fa-3x text-primary mb-3"></i>
                        <h5>Hire Professionals</h5>
                        <p>Find and hire the best talent for your project needs.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card text-center">
                        <i class="fas fa-globe fa-3x text-primary mb-3"></i>
                        <h5>Global Talent</h5>
                        <p>Access freelancers from around the globe, no matter where you are.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card text-center">
                        <i class="fas fa-dollar-sign fa-3x text-primary mb-3"></i>
                        <h5>Affordable Pricing</h5>
                        <p>Get quality services at competitive rates that fit your budget.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials" id="testimonials">
        <div class="container">
            <h2 class="text-center mb-4">What Our Clients Say</h2>
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
                        <div class="row">
                            <div class="col-md-4 testimonial-item" data-aos="fade-up">
                                <i class="fas fa-quote-left mb-3"></i>
                                <p><?php echo htmlspecialchars($comment); ?></p>
                                <h5><?php echo htmlspecialchars($userName); ?></h5>
                                <p><?php echo htmlspecialchars($rating); ?></p>
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
    </section>


    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2024 FreelanceHub. All rights reserved.</p>
        <div>
            <a href="#"><i class="fab fa-facebook-f"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-linkedin-in"></i></a>
        </div>
    </footer>

    <!-- Bootstrap JS, Popper.js, and AOS Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

    <!-- Custom JS -->
    <script>
        // Initialize AOS Animations
        AOS.init();
    </script>

</body>

</html>