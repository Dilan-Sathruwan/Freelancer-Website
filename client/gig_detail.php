<?php
// Include database connection and session management
include('../config/db.con.php');
session_start();

// Check if 'id' is provided in the query string
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $gigId = (int)$_GET['id'];

    try {
        // Prepare and execute query to fetch gig details along with category name
        $sql = "SELECT g.*, u.username, u.profile_picture, c.name AS category_name
                FROM gigs g
                JOIN users u ON g.freelancer_id = u.id
                JOIN categories c ON g.category_id = c.id
                WHERE g.id = :gigId AND g.status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':gigId', $gigId, PDO::PARAM_INT);
        $stmt->execute();

        // Check if the gig exists
        if ($stmt->rowCount() > 0) {
            $gig = $stmt->fetch(PDO::FETCH_ASSOC);

            // Extract gig details
            $title = htmlspecialchars($gig['title']);
            $description = htmlspecialchars($gig['description']);
            $category = htmlspecialchars($gig['category_name']);
            $price = number_format($gig['price'], 2);
            $deliveryTime = (int)$gig['delivery_time'];
            $image = !empty($gig['image']) ? htmlspecialchars($gig['image']) : 'assets/images/default_gig.jpg'; // Default image fallback
            $username = htmlspecialchars($gig['username']);
            $profilePic = htmlspecialchars($gig['profile_picture']);
            $createdAt = date('F j, Y', strtotime($gig['created_at']));
        } else {
            die("<p class='text-center'>Gig not found or inactive.</p>");
        }
    } catch (PDOException $e) {
        die("<p class='text-center'>Error fetching gig details: " . htmlspecialchars($e->getMessage()) . "</p>");
    }
} else {
    die("<p class='text-center'>Invalid gig ID.</p>");
}

// Check if the user is logged in and is a client
$isClient = isset($_SESSION['role']) && $_SESSION['role'] === 'client';


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gig Details</title>
    <link rel="stylesheet" href="assets/css/style.css"> <!-- Include your CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"> <!-- Font Awesome -->
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }

        .gig-card {
            max-width: 900px;
            margin: 50px auto;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .gig-header {
            display: flex;
            align-items: center;
            padding: 20px;
            color: #fff;
            background: linear-gradient(to right,
                    rgba(255, 87, 34, 0.9),
                    rgba(255, 153, 0, 0.9)),
                url("https://via.placeholder.com/1920x800") no-repeat center center;
            background-size: cover;
        }


        .gig-header img {
            border-radius: 50%;
            width: 70px;
            height: 70px;
            margin-right: 15px;
            border: 3px solid #ffffff;
        }

        .gig-header h3 {
            margin: 0;
            font-size: 1.5rem;
        }

        .gig-header p {
            margin: 5px 0 0;
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .gig-image img {
            width: 100%;
            height: auto;
        }

        .gig-details {
            padding: 20px;
        }

        .gig-details h4 {
            margin-bottom: 10px;
            font-size: 1.2rem;
            color: #333;
        }

        .gig-details p {
            margin-bottom: 15px;
            font-size: 1rem;
            line-height: 1.6;
        }

        .gig-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #28a745;
        }

        .action-buttons {
            text-align: center;
            padding: 20px;
            background: #f1f1f1;
        }

        .action-buttons a,
        .action-buttons button {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            font-size: 1rem;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            transition: 0.3s ease;
        }

        .action-buttons a {
            background: #ff5722;
        }

        .action-buttons a:hover {
            background: #da3300;
        }

    </style>
</head>

<body>
    <div class="gig-card">
        <div class="gig-header">
            <img src="<?php echo $profilePic; ?>" alt="Profile Picture">
            <div>
                <h3><?php echo $title; ?></h3>
                <p>Posted by <strong><?php echo $username; ?></strong> on <?php echo $createdAt; ?></p>
            </div>
        </div>
        <div class="gig-image">
            <img src="<?php echo $image; ?>" alt="<?php echo $image ?>">
        </div>
        <div class="gig-details">
            <h4>Category:</h4>
            <p><?php echo $category; ?></p>
            <h4>Description:</h4>
            <p><?php echo nl2br($description); ?></p>
            <h4>Delivery Time:</h4>
            <p><?php echo $deliveryTime; ?> days</p>
            <h4>Price:</h4>
            <p class="gig-price">$<?php echo $price; ?></p>
        </div>
        <div class="action-buttons">
            <a href="./dashboard.php"><i class="fas fa-arrow-left"></i> Back to Gigs</a>
        </div>
    </div>
</body>

</html>