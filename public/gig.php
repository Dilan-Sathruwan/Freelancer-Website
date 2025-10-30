<?php
// Include database connection
include('../config/db.con.php');
session_start();


// Fetch all gigs from the database
$sql = "SELECT g.id, g.title, g.description, g.price, g.created_at, u.username, u.profile_picture 
        FROM gigs g
        JOIN users u ON g.freelancer_id = u.id
        ORDER BY g.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$gigs = $stmt->fetchAll(PDO::FETCH_ASSOC);




?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Gigs</title>
    <link rel="stylesheet" href="assets/css/style.css"> <!-- Include your main CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet"> <!-- AOS Library for animations -->
    <style>
        /* Additional styling for gig cards and hover effects */
        .gig-card {
            background-color: #fff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .gig-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .gig-card img {
            border-radius: 50%;
            object-fit: cover;
            width: 60px;
            height: 60px;
            margin-bottom: 15px;
        }

        .gig-card h4 a {
            color: #333;
            text-decoration: none;
            font-size: 18px;
            font-weight: bold;
        }

        .gig-card p {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }

        .gig-card .price {
            font-weight: bold;
            font-size: 18px;
            color: #28a745;
        }

        .gig-card .view-details-btn {
            text-decoration: none;
            padding: 10px 15px;
            background-color: #007bff;
            color: #fff;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .gig-card .view-details-btn:hover {
            background-color: #0056b3;
        }

        /* Font Awesome icons */
        .gig-card .icon {
            font-size: 18px;
            color: #007bff;
            margin-right: 10px;
        }

        /* Responsive Styling */
        @media (max-width: 768px) {
            .gig-card {
                margin-bottom: 20px;
            }
        }

        .fa-eye{
            color: black !important;
        }
        .btn_c {
            background-color: #ff9900bd !important;
            color: black !important;
        }

        .btn_c:hover {
            background-color: #D9641E !important;
        }
    </style>
</head>

<body>
    <!-- Include the header -->
    <?php
      $regPage = "../auth/signup.php";
    $logoutPage = "../config/logout.php";
    include('../includes/index_header.php');
    ?>

    <!-- Gigs Section -->
    <section class="gigs" id="gigs" style="padding: 50px 0;">
        <div class="container">
            <h2 class="text-center mb-4" data-aos="fade-up">Browse All Gigs</h2>
            <div class="row">
                <?php
                try {
                    $stmt = $conn->prepare($sql);
                    $stmt->execute();

                    if ($stmt->rowCount() > 0) {
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $gigId = $row['id'];
                            $title = htmlspecialchars($row['title']);
                            $description = htmlspecialchars(substr($row['description'], 0, 150)) . '...'; // Limit description length
                            $price = number_format($row['price'], 2);
                            $username = htmlspecialchars($row['username']);
                            $profilePic = htmlspecialchars($row['profile_picture']);
                            $createdAt = date('F j, Y', strtotime($row['created_at']));

                ?>

                            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                                <div class="gig-card">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo $profilePic; ?>" alt="Profile Picture">
                                        <div>
                                            <h5 class="mb-1"><?php echo $username; ?></h5>
                                            <p class="text-muted" style="font-size: 14px;">Posted on <?php echo $createdAt; ?></p>
                                        </div>
                                    </div>
                                    <h4><a href="gig_detail.php?id=<?php echo $gigId; ?>"><?php echo $title; ?></a></h4>
                                    <p><?php echo $description; ?></p>
                                    <p class="price">$<?php echo $price; ?></p>
                                    <a href="gig_detail.php?id=<?php echo $gigId; ?>" class="view-details-btn btn_c">
                                        <i class="fas fa-eye icon"></i> View Details
                                    </a>
                                </div>
                            </div>
                <?php
                        }
                    } else {
                        echo "<p class='text-center'>No gigs available at the moment.</p>";
                    }
                } catch (PDOException $e) {
                    echo "<p class='text-center'>Error fetching gigs: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Include footer -->
    <?php include('../includes/footer.php'); ?>

    <!-- Include AOS script for animations -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
</body>

</html>