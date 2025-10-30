<?php
// Include database connection and session management
include('../config/db.con.php');
session_start();

// Check if the user is logged in and is a client
if (isset($_SESSION['role']) && $_SESSION['role'] === 'client' && isset($_SESSION['user_id'])) {
    // Check if gig_id and freelancer_id are provided
    if (isset($_POST['gig_id'], $_POST['freelancer_id']) && is_numeric($_POST['gig_id']) && is_numeric($_POST['freelancer_id'])) {
        $gigId = (int)$_POST['gig_id'];
        $freelancerId = (int)$_POST['freelancer_id'];
        $clientId = $_SESSION['user_id']; // Using the new session variable

        try {
            // Prepare and execute query to insert the job request into the database
            $sql = "INSERT INTO job_requests (client_id, freelancer_id, gig_id, status, request_date)
                    VALUES (?, ?, ?, 'pending', NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$clientId, $freelancerId, $gigId]);

            // Redirect the user with a success message
            $_SESSION['message'] = "Job request successfully created. Await freelancer's response!";
            header("Location: gig_detail.php?id=" . $gigId); // Redirect back to gig details
            exit;
        } catch (PDOException $e) {
            // If an error occurs, display the error message
            error_log("Error creating job request: " . $e->getMessage());
            $_SESSION['error'] = "Error creating job request.";
            header("Location: gig_detail.php?id=" . $gigId); // Redirect back to gig details
            exit;
        }
    } else {
        // If gig_id or freelancer_id is missing or invalid, show an error
        $_SESSION['error'] = "Invalid gig or freelancer information.";
        header("Location: ../index.php"); // Redirect to the homepage or gigs list
        exit;
    }
} else {
    // If the user is not a client, redirect to the homepage
    $_SESSION['error'] = "You must be logged in as a client to request a job.";
    header("Location: ../index.php"); // Redirect to the homepage
    exit;
}