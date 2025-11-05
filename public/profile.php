<?php
// Start the session
session_start();

// Include the database connection
include('../config/db.con.php');

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data from the database using prepared statement
try {
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "User not found.";
        exit();
    }
} catch (PDOException $e) {
    error_log("Profile fetch error: " . $e->getMessage());
    $error = "An error occurred while fetching profile data.";
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize inputs
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $bio = sanitizeInput($_POST['bio']);
    
    if ($user['role'] == 'freelancer') {
        $status = sanitizeInput($_POST['status']);
    }

    // Validate input
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = "First name, last name, and email are required.";
    } else {
        try {
            // Handle profile picture upload if it exists
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                // Create uploads directory if it doesn't exist
                $uploadDir = '../uploads/proPic/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $profile_picture = $uploadDir . basename($_FILES['profile_picture']['name']);
                move_uploaded_file($_FILES['profile_picture']['tmp_name'], $profile_picture);
            } else {
                // If no new profile picture, retain old one
                $profile_picture = $user['profile_picture'];
            }

            // Update user data in the database
            if ($user['role'] == 'freelancer') {
                $update_query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, bio = ?, profile_picture = ?, status = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->execute([$first_name, $last_name, $email, $bio, $profile_picture, $status, $user_id]);
            } else {
                $update_query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, bio = ?, profile_picture = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->execute([$first_name, $last_name, $email, $bio, $profile_picture, $user_id]);
            }

            // Update session data
            $_SESSION['username'] = $first_name . ' ' . $last_name;
            
            $success = "Profile updated successfully!";
            
            // Refresh user data
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Profile update error: " . $e->getMessage());
            $error = "An error occurred while updating profile.";
        }
    }
}
?>

<!-- Custom CSS -->
<style>
    /* Profile Card Styles */
    .profile-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .profile-card:hover {
        transform: scale(1.05);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .profile-form-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .profile-form-card:hover {
        transform: scale(1.02);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }

    .profile-button {
        background-color: #D9641E !important;
        color: #fff !important;
        transition: background-color 0.3s ease;
    }

    .profile-button:hover {
        background-color: #007bff;
        color: #fff;
    }

    .card-title {
        font-size: 1.5rem;
        font-weight: bold;
    }

    .card-text {
        font-size: 1rem;
        color: #6c757d;
    }

    .card-header {
        background: linear-gradient(to right,
                rgba(255, 87, 34, 0.9),
                rgba(255, 153, 0, 0.9)),
            url("https://via.placeholder.com/1920x800") no-repeat center center;
        background-size: cover;
    }

    .card-body p {
        font-size: 0.9rem;
        color: #6c757d;
    }

    /* Custom Hover for Input Fields */
    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.25rem rgba(38, 143, 255, 0.5);
    }

    .form-select:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.25rem rgba(38, 143, 255, 0.5);
    }
</style>

<?php
$logoutPage = "../auth/logout.php";
include('../includes/index_header.php');
?>

<!-- Profile Section -->
<section class="profile py-5" data-aos="fade-up" data-aos-duration="1000">
    <div class="container">
        <h2 class="my-5 text-center fw-bold ">Your Profile</h2>

        <!-- Display success or error messages -->
        <?php if (isset($success)) { ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php } elseif (isset($error)) { ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php } ?>

        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow-lg profile-card" data-aos="flip-left" data-aos-duration="1500">
                    <img src="<?php echo $user['profile_picture'] ? htmlspecialchars($user['profile_picture']) : '../assets/images/default-avatar.jpg'; ?>" alt="Profile Picture" class="card-img-top rounded-circle mx-auto mt-4" style="width: 150px; height: 150px; object-fit: cover;">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                        <p class="card-text text-muted"><?php echo htmlspecialchars($user['bio']); ?></p>
                        <p class="text-muted"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p class="text-muted"><i class="fas fa-user"></i> Role: <?php echo ucfirst($user['role']); ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Profile Update Form -->
                <div class="card shadow-lg profile-form-card" data-aos="flip-right" data-aos-duration="1500">
                    <div class="card-header">
                        <h4 class="text-light mt-2"><i class="fas fa-edit text-light"></i><b> Update Your Profile</b></h4>
                    </div>
                    <div class="card-body">
                        <form action="profile.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="profile_picture" class="form-label">Profile Picture</label>
                                <input type="file" class="form-control" id="profile_picture" name="profile_picture">
                            </div>
                            <?php
                            if ($user['role'] == 'freelancer') { ?>
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" <?php echo ($user['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo ($user['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            <?php }
                            ?>
                            <button type="submit" class="btn profile-button"><i class="fas fa-save"></i> Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include('../includes/footer.php'); ?>

<!-- AOS Library Scripts -->
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
    AOS.init();
</script>