<?php
session_start();
include '../config/db.con.php'; // Include database connection

// Ensure only freelancers access this page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'freelancer') {
    header("Location: ../auth/login.php");
    exit;
}

$freelancer_id = $_SESSION['user_id'];

// Handle Accept Job Request
if (isset($_POST['accept_request'])) {
    // Validate input
    $job_request_id = validateInteger($_POST['job_request_id']);
    
    if ($job_request_id) {
        try {
            $stmt = $conn->prepare("UPDATE job_requests SET status = 'accepted' WHERE id = ? AND freelancer_id = ?");
            $stmt->execute([$job_request_id, $freelancer_id]);
            header("Location: manage_job_requests.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error accepting job request: " . $e->getMessage());
            $error = "Error accepting job request.";
        }
    }
}

// Handle Complete Job Request
if (isset($_POST['complete_request'])) {
    // Validate input
    $job_request_id = validateInteger($_POST['job_request_id']);
    
    if ($job_request_id) {
        try {
            $completion_date = date('Y-m-d H:i:s');
            $stmt = $conn->prepare("UPDATE job_requests SET status = 'completed', completion_date = ? WHERE id = ? AND freelancer_id = ?");
            $stmt->execute([$completion_date, $job_request_id, $freelancer_id]);
            header("Location: manage_job_requests.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error completing job request: " . $e->getMessage());
            $error = "Error completing job request.";
        }
    }
}

// Handle Cancel Job Request
if (isset($_POST['cancel_request'])) {
    // Validate input
    $job_request_id = validateInteger($_POST['job_request_id']);
    
    if ($job_request_id) {
        try {
            $stmt = $conn->prepare("UPDATE job_requests SET status = 'cancelled' WHERE id = ? AND freelancer_id = ?");
            $stmt->execute([$job_request_id, $freelancer_id]);
            header("Location: manage_job_requests.php");
            exit;
        } catch (PDOException $e) {
            error_log("Error canceling job request: " . $e->getMessage());
            $error = "Error canceling job request.";
        }
    }
}

// Fetch Job Requests for the Freelancer
try {
    $stmt = $conn->prepare("SELECT jr.id, jr.status, jr.request_date, jr.completion_date, g.title AS gig_title, u.first_name AS client_name, u.email AS client_email
                             FROM job_requests jr
                             JOIN gigs g ON jr.gig_id = g.id
                             JOIN users u ON jr.client_id = u.id
                             WHERE jr.freelancer_id = ?
                             ORDER BY jr.request_date DESC");
    $stmt->execute([$freelancer_id]);
    $job_requests = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching job requests: " . $e->getMessage());
    $error = "Error fetching job requests.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Job Requests</title>
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- Your custom CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table th,
        .table td {
            text-align: center;
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <!-- Display error message if any -->
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <h1 class="text-center">Manage Job Requests</h1>

        <?php if (isset($job_requests) && count($job_requests) > 0): ?>
            <table class="table table-bordered mt-4">
                <thead>
                    <tr>
                        <th>Gig Title</th>
                        <th>Client Name</th>
                        <th>Client Email</th>
                        <th>Status</th>
                        <th>Request Date</th>
                        <th>Completion Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($job_requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['gig_title']); ?></td>
                            <td><?php echo htmlspecialchars($request['client_name']); ?></td>
                            <td><?php echo htmlspecialchars($request['client_email']); ?></td>
                            <td>
                                <span class="badge bg-<?php
                                                        switch ($request['status']) {
                                                            case 'pending':
                                                                echo 'warning';
                                                                break;
                                                            case 'accepted':
                                                                echo 'primary';
                                                                break;
                                                            case 'completed':
                                                                echo 'success';
                                                                break;
                                                            case 'cancelled':
                                                                echo 'danger';
                                                                break;
                                                        }
                                                        ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($request['request_date']); ?></td>
                            <td><?php echo $request['completion_date'] ? htmlspecialchars($request['completion_date']) : 'N/A'; ?></td>
                            <td>
                                <form action="" method="POST" class="d-inline">
                                    <input type="hidden" name="job_request_id" value="<?php echo $request['id']; ?>">
                                    <?php if ($request['status'] === 'pending'): ?>
                                        <button type="submit" name="accept_request" class="btn btn-primary btn-sm">Accept</button>
                                    <?php endif; ?>
                                    <?php if ($request['status'] === 'accepted'): ?>
                                        <button type="submit" name="complete_request" class="btn btn-success btn-sm">Mark as Completed</button>
                                    <?php endif; ?>
                                    <?php if (in_array($request['status'], ['pending', 'accepted'])): ?>
                                        <button type="submit" name="cancel_request" class="btn btn-danger btn-sm">Cancel</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-center mt-5">No job requests found.</p>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>

</html>