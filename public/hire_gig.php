<?php
// Include database connection and session management
include('../config/db.php');
session_start();

// Check if the user is logged in and is a client
if (isset($_SESSION['role']) && $_SESSION['role'] === 'client') {
    // Check if gig_id and freelancer_id are provided
    if (isset($_POST['gig_id'], $_POST['freelancer_id']) && is_numeric($_POST['gig_id']) && is_numeric($_POST['freelancer_id'])) {
        $gigId = (int)$_POST['gig_id'];
        $freelancerId = (int)$_POST['freelancer_id'];
        $clientId = $_SESSION['id']; // Assuming the client's user_id is stored in session

        try {
            // Prepare and execute query to insert the job request into the database
            $sql = "INSERT INTO job_requests (client_id, freelancer_id, gig_id, status, request_date)
                    VALUES (:clientId, :freelancerId, :gigId, 'pending', NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':clientId', $clientId, PDO::PARAM_INT);
            $stmt->bindParam(':freelancerId', $freelancerId, PDO::PARAM_INT);
            $stmt->bindParam(':gigId', $gigId, PDO::PARAM_INT);
            $stmt->execute();

            // Redirect the user with a success message
            $_SESSION['message'] = "Job request successfully created. Await freelancer's response!";
            header("Location: gig_detail.php?id=" . $gigId); // Redirect back to gig details
            exit;
        } catch (PDOException $e) {
            // If an error occurs, display the error message
            $_SESSION['error'] = "Error creating job request: " . htmlspecialchars($e->getMessage());
            header("Location: gig_detail.php?id=" . $gigId); // Redirect back to gig details
            exit;
        }
    } else {
        // If gig_id or freelancer_id is missing or invalid, show an error
        $_SESSION['error'] = "Invalid gig or freelancer information.";
        header("Location: index.php"); // Redirect to the homepage or gigs list
        exit;
    }
} else {
    // If the user is not a client, redirect to the homepage
    $_SESSION['error'] = "You must be logged in as a client to request a job.";
    header("Location: index.php"); // Redirect to the homepage
    exit;
}
