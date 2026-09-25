<?php
// leave_review_action.php
include('../config/db.con.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validate client session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    $_SESSION['error'] = "You must be logged in as a client to leave a review.";
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$clientId = (int)$_SESSION['user_id'];
$freelancerId = isset($_POST['freelancerId']) ? (int)$_POST['freelancerId'] : 0;
$projectId = !empty($_POST['projectId']) && is_numeric($_POST['projectId']) ? (int)$_POST['projectId'] : null;
$gigId = !empty($_POST['gigId']) && is_numeric($_POST['gigId']) ? (int)$_POST['gigId'] : null;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 5;
$comment = trim($_POST['comment'] ?? '');

// Normalize rating between 1 and 5
if ($rating < 1) $rating = 1;
if ($rating > 5) $rating = 5;

if ($freelancerId <= 0 || empty($comment)) {
    $_SESSION['error'] = "Please provide both a rating and comments for your review.";
    $redirect = "leave_review.php?freelancer_id=" . $freelancerId;
    if ($projectId) $redirect .= "&project_id=" . $projectId;
    if ($gigId) $redirect .= "&gig_id=" . $gigId;
    header("Location: " . $redirect);
    exit;
}

try {
    $conn->beginTransaction();

    // Check if review already exists for this client + freelancer + project/gig
    $existing = null;
    if ($projectId) {
        $stmtCheck = $conn->prepare("SELECT id FROM reviews WHERE client_id = ? AND freelancer_id = ? AND project_id = ? LIMIT 1");
        $stmtCheck->execute([$clientId, $freelancerId, $projectId]);
        $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    } elseif ($gigId) {
        $stmtCheck = $conn->prepare("SELECT id FROM reviews WHERE client_id = ? AND freelancer_id = ? AND gig_id = ? LIMIT 1");
        $stmtCheck->execute([$clientId, $freelancerId, $gigId]);
        $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    }

    if ($existing) {
        // Update review
        $stmtUpdate = $conn->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE id = ?");
        $stmtUpdate->execute([$rating, $comment, $existing['id']]);
    } else {
        // Insert new review
        $stmtInsert = $conn->prepare("
            INSERT INTO reviews (client_id, freelancer_id, project_id, gig_id, rating, comment)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmtInsert->execute([$clientId, $freelancerId, $projectId, $gigId, $rating, $comment]);
    }

    // Recalculate average rating for freelancer
    $stmtAvg = $conn->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE freelancer_id = ?");
    $stmtAvg->execute([$freelancerId]);
    $avgRow = $stmtAvg->fetch(PDO::FETCH_ASSOC);
    $newAvg = $avgRow && $avgRow['avg_rating'] !== null ? round((float)$avgRow['avg_rating'], 2) : (float)$rating;

    // Update freelancer_profiles
    $stmtProfile = $conn->prepare("UPDATE freelancer_profiles SET rating = ? WHERE user_id = ?");
    $stmtProfile->execute([$newAvg, $freelancerId]);

    // Update freelancers table (for backward compatibility)
    $stmtFreelancers = $conn->prepare("UPDATE freelancers SET rating = ? WHERE user_id = ? OR id = ?");
    $stmtFreelancers->execute([$newAvg, $freelancerId, $freelancerId]);

    // If review is associated with a gig, update gigs avg_rating and reviews_count
    if ($gigId) {
        $stmtGigAvg = $conn->prepare("SELECT AVG(rating) as gig_avg, COUNT(*) as gig_cnt FROM reviews WHERE gig_id = ?");
        $stmtGigAvg->execute([$gigId]);
        $gigStats = $stmtGigAvg->fetch(PDO::FETCH_ASSOC);
        if ($gigStats) {
            $stmtGigUpdate = $conn->prepare("UPDATE gigs SET avg_rating = ?, reviews_count = ? WHERE id = ?");
            $stmtGigUpdate->execute([round((float)$gigStats['gig_avg'], 2), (int)$gigStats['gig_cnt'], $gigId]);
        }
    }

    $conn->commit();
    $_SESSION['success'] = "Thank you! Your review has been successfully submitted.";
    header("Location: dashboard.php");
    exit;
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Error in leave_review_action: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while saving your review. Please try again.";
    header("Location: dashboard.php");
    exit;
}
?>
