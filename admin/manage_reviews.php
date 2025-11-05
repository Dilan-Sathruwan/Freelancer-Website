<?php
session_start();
include '../config/db.con.php';

// Set page variables
$page_title = 'Manage Reviews';
$active_page = 'reviews';

// Ensure only admins can access this page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Handle review deletion
if (isset($_GET['delete'])) {
    $id = validateInteger($_GET['delete']);
    
    if ($id) {
        try {
            $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Review deleted successfully.";
        } catch (PDOException $e) {
            error_log("Delete review error: " . $e->getMessage());
            $error = "Error deleting review.";
        }
    }
}

// Handle review status update
if (isset($_GET['toggle_status'])) {
    $id = validateInteger($_GET['toggle_status']);
    
    if ($id) {
        try {
            // Get current status
            $stmt = $conn->prepare("SELECT status FROM reviews WHERE id = ?");
            $stmt->execute([$id]);
            $review = $stmt->fetch();
            
            if ($review) {
                $newStatus = ($review['status'] === 'active') ? 'hidden' : 'active';
                $stmt = $conn->prepare("UPDATE reviews SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $id]);
                $success = "Review status updated successfully.";
            }
        } catch (PDOException $e) {
            error_log("Update review status error: " . $e->getMessage());
            $error = "Error updating review status.";
        }
    }
}

try {
    // Get all reviews with user and gig info
    $stmt = $conn->query("
        SELECT r.id, r.rating, r.comment, r.status, r.created_at,
               c.username as client_username, f.username as freelancer_username, g.title as gig_title
        FROM reviews r
        JOIN users c ON r.client_id = c.id
        JOIN users f ON r.freelancer_id = f.id
        JOIN gigs g ON r.gig_id = g.id
        ORDER BY r.created_at DESC
    ");
    $reviews = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch reviews error: " . $e->getMessage());
    $error = "Error fetching reviews.";
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
        
        <!-- Reviews Table -->
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">All Reviews</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Freelancer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gig</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($reviews as $review): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($review['id']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($review['client_username']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($review['freelancer_username']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($review['gig_title']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="flex items-center">
                                    <div class="flex text-yellow-400">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="ri-star<?php echo $i <= $review['rating'] ? '-fill' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="ml-2"><?php echo htmlspecialchars($review['rating']); ?>/5</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate"><?php echo htmlspecialchars($review['comment']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $review['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo htmlspecialchars($review['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="?toggle_status=<?php echo $review['id']; ?>" 
                                       class="text-indigo-600 hover:text-indigo-900"
                                       onclick="return confirm('Are you sure you want to <?php echo $review['status'] === 'active' ? 'hide' : 'activate'; ?> this review?');">
                                        <?php echo $review['status'] === 'active' ? 'Hide' : 'Show'; ?>
                                    </a>
                                    <a href="?delete=<?php echo $review['id']; ?>" 
                                       class="text-red-600 hover:text-red-900"
                                       onclick="return confirm('Are you sure you want to delete this review? This action cannot be undone.');">
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