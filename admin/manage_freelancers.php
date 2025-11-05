<?php
session_start();
include '../config/db.con.php';

// Set page variables
$page_title = 'Manage Freelancers';
$active_page = 'freelancers';

// Ensure only admins can access this page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Handle freelancer deletion
if (isset($_GET['delete'])) {
    $id = validateInteger($_GET['delete']);
    
    if ($id) {
        try {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'freelancer'");
            $stmt->execute([$id]);
            $success = "Freelancer deleted successfully.";
        } catch (PDOException $e) {
            error_log("Delete freelancer error: " . $e->getMessage());
            $error = "Error deleting freelancer.";
        }
    }
}

// Handle freelancer status update
if (isset($_GET['toggle_status'])) {
    $id = validateInteger($_GET['toggle_status']);
    
    if ($id) {
        try {
            // Get current status
            $stmt = $conn->prepare("SELECT status FROM users WHERE id = ? AND role = 'freelancer'");
            $stmt->execute([$id]);
            $freelancer = $stmt->fetch();
            
            if ($freelancer) {
                $newStatus = ($freelancer['status'] === 'active') ? 'inactive' : 'active';
                $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'freelancer'");
                $stmt->execute([$newStatus, $id]);
                $success = "Freelancer status updated successfully.";
            }
        } catch (PDOException $e) {
            error_log("Update freelancer status error: " . $e->getMessage());
            $error = "Error updating freelancer status.";
        }
    }
}

try {
    // Get all freelancers with profile info
    $stmt = $conn->query("
        SELECT u.id, u.username, u.first_name, u.last_name, u.email, u.status, u.created_at,
               fp.title, fp.hourly_rate, fp.completed_jobs, fp.success_rate
        FROM users u
        LEFT JOIN freelancer_profiles fp ON u.id = fp.user_id
        WHERE u.role = 'freelancer'
        ORDER BY u.created_at DESC
    ");
    $freelancers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch freelancers error: " . $e->getMessage());
    $error = "Error fetching freelancers.";
}

include '../includes/admin_header.php';
?>

        <!-- Alerts -->
        <?php if (isset($success)): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
            <div class="flex items-center">
                <i class="ri-checkbox-circle-line text-lg mr-2"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
            <div class="flex items-center">
                <i class="ri-error-warning-line text-lg mr-2"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Freelancers Table -->
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">All Freelancers</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hourly Rate</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jobs Completed</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Success Rate</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($freelancers as $freelancer): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($freelancer['id']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($freelancer['username']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($freelancer['first_name'] . ' ' . $freelancer['last_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($freelancer['email']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $freelancer['title'] ? htmlspecialchars($freelancer['title']) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $freelancer['hourly_rate'] ? '$' . number_format($freelancer['hourly_rate'], 2) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($freelancer['completed_jobs'] ?? 0); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $freelancer['success_rate'] ? number_format($freelancer['success_rate'], 1) . '%' : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $freelancer['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo htmlspecialchars($freelancer['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="?toggle_status=<?php echo $freelancer['id']; ?>" 
                                       class="text-indigo-600 hover:text-indigo-900"
                                       onclick="return confirm('Are you sure you want to <?php echo $freelancer['status'] === 'active' ? 'deactivate' : 'activate'; ?> this freelancer?');">
                                        <?php echo $freelancer['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                    </a>
                                    <a href="?delete=<?php echo $freelancer['id']; ?>" 
                                       class="text-red-600 hover:text-red-900"
                                       onclick="return confirm('Are you sure you want to delete this freelancer? This action cannot be undone.');">
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

<?php
include '../includes/admin_footer.php';
?>