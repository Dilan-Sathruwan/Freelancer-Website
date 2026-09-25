<?php
// Include database connection and session management
include('../config/db.con.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as client
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client' || !isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "You must be logged in as a client to hire a gig.";
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: gig.php");
    exit;
}

$gigId = isset($_POST['gig_id']) && is_numeric($_POST['gig_id']) ? (int)$_POST['gig_id'] : 0;
$freelancerId = isset($_POST['freelancer_id']) && is_numeric($_POST['freelancer_id']) ? (int)$_POST['freelancer_id'] : 0;
$clientId = (int)$_SESSION['user_id'];

if ($gigId <= 0 || $freelancerId <= 0) {
    $_SESSION['error'] = "Invalid gig or freelancer selected.";
    header("Location: gig.php");
    exit;
}

try {
    // Fetch gig details
    $stmtGig = $conn->prepare("SELECT id, title, price, freelancer_id FROM gigs WHERE id = ? AND status = 'active'");
    $stmtGig->execute([$gigId]);
    $gig = $stmtGig->fetch(PDO::FETCH_ASSOC);

    if (!$gig) {
        $_SESSION['error'] = "The selected gig is not active or available.";
        header("Location: gig.php");
        exit;
    }

    $freelancerId = (int)$gig['freelancer_id'];
    $price = (float)$gig['price'];
    $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

    $conn->beginTransaction();

    // 1. Create order
    $stmtOrder = $conn->prepare("
        INSERT INTO orders (order_number, gig_id, client_id, freelancer_id, amount, status)
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    $stmtOrder->execute([$orderNumber, $gigId, $clientId, $freelancerId, $price]);

    // 2. Create job request
    $stmtJobReq = $conn->prepare("
        INSERT INTO job_requests (client_id, freelancer_id, gig_id, status, request_date)
        VALUES (?, ?, ?, 'pending', NOW())
    ");
    $stmtJobReq->execute([$clientId, $freelancerId, $gigId]);

    $conn->commit();

    $_SESSION['success'] = "Order successfully placed for '" . htmlspecialchars($gig['title']) . "'! Your order number is " . $orderNumber . ".";
    header("Location: gig_detail.php?id=" . $gigId);
    exit;
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Error in hire_gig.php: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while placing your order. Please try again.";
    header("Location: gig_detail.php?id=" . $gigId);
    exit;
}
?>