<?php
// leave_review_action.php

include('../config/db_connection.php');
session_start();

$clientId = $_POST['clientId'];
$freelancerId = $_POST['freelancerId'];
$projectId = $_POST['projectId'];
$rating = $_POST['rating'];
$comment = $_POST['comment'];

// Check if the client has already reviewed this freelancer for the specific project
$sql = "SELECT * FROM reviews WHERE client_id = :client_id AND freelancer_id = :freelancer_id AND project_id = :project_id";
$stmt = $conn->prepare($sql);
$stmt->execute([
    'client_id' => $clientId,
    'freelancer_id' => $freelancerId,
    'project_id' => $projectId,
]);

if ($stmt->rowCount() > 0) {
    // If review already exists, update it
    $sql = "UPDATE reviews SET rating = :rating, comment = :comment WHERE client_id = :client_id AND freelancer_id = :freelancer_id AND project_id = :project_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'rating' => $rating,
        'comment' => $comment,
        'client_id' => $clientId,
        'freelancer_id' => $freelancerId,
        'project_id' => $projectId,
    ]);
    echo "Your review has been updated!";
} else {
    // Insert new review
    $sql = "INSERT INTO reviews (client_id, freelancer_id, project_id, rating, comment) VALUES (:client_id, :freelancer_id, :project_id, :rating, :comment)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'client_id' => $clientId,
        'freelancer_id' => $freelancerId,
        'project_id' => $projectId,
        'rating' => $rating,
        'comment' => $comment,
    ]);
    echo "Your review has been submitted!";
}
?>
