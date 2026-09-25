<?php
// hire_freelancer_action.php
include('../config/db.con.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validate client session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    $_SESSION['error'] = "You must be logged in as a client to post a project.";
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$clientId = (int)$_SESSION['user_id'];
$freelancerId = isset($_POST['freelancerId']) ? (int)$_POST['freelancerId'] : 0;
$projectTitle = trim($_POST['projectTitle'] ?? '');
$projectDescription = trim($_POST['projectDescription'] ?? '');
$budget = (float)($_POST['budget'] ?? 0.0);
$deadline = trim($_POST['deadline'] ?? '');

if ($freelancerId <= 0 || empty($projectTitle) || empty($projectDescription) || $budget <= 0 || empty($deadline)) {
    $_SESSION['error'] = "Please fill in all required fields properly.";
    header("Location: hire_freelancer.php?freelancer_id=" . $freelancerId);
    exit;
}

try {
    // Insert into projects table
    $sqlProject = "INSERT INTO projects (client_id, freelancer_id, title, description, budget, deadline, status)
                   VALUES (:client_id, :freelancer_id, :title, :description, :budget, :deadline, 'pending')";
    $stmtProject = $conn->prepare($sqlProject);
    $stmtProject->execute([
        'client_id' => $clientId,
        'freelancer_id' => $freelancerId,
        'title' => $projectTitle,
        'description' => $projectDescription,
        'budget' => $budget,
        'deadline' => $deadline,
    ]);

    $_SESSION['success'] = "Project proposal for '" . htmlspecialchars($projectTitle) . "' has been successfully submitted!";
    header("Location: dashboard.php");
    exit;
} catch (PDOException $e) {
    error_log("Error in hire_freelancer_action: " . $e->getMessage());
    $_SESSION['error'] = "Failed to submit project proposal. Please try again.";
    header("Location: hire_freelancer.php?freelancer_id=" . $freelancerId);
    exit;
}
?>
