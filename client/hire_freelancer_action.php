<?php
// hire_freelancer_action.php

include('../config/db_connection.php');
session_start();

$clientId = $_POST['clientId'];
$freelancerId = $_POST['freelancerId'];
$projectTitle = $_POST['projectTitle'];
$projectDescription = $_POST['projectDescription'];
$budget = $_POST['budget'];
$deadline = $_POST['deadline'];

$sql = "INSERT INTO projects (client_id, freelancer_id, title, description, budget, deadline, status)
        VALUES (:client_id, :freelancer_id, :title, :description, :budget, :deadline, 'pending')";
$stmt = $conn->prepare($sql);

try {
    $stmt->execute([
        'client_id' => $clientId,
        'freelancer_id' => $freelancerId,
        'title' => $projectTitle,
        'description' => $projectDescription,
        'budget' => $budget,
        'deadline' => $deadline,
    ]);
    
    echo "Project has been successfully posted. You can view it in your dashboard.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
