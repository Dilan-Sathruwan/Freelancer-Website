<?php
session_start();
include '../config/db.con.php';
include '../includes/logging_functions.php';

// Set page variables
$page_title = 'Manage Reviews';
$active_page = 'reviews';

// Ensure only admins can access this page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Pagination variables
$reviewsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $reviewsPerPage;

// Search and filter variables
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$ratingFilter = isset($_GET['rating']) ? validateInteger($_GET['rating']) : '';

// Handle review deletion
if (isset($_GET['delete'])) {
    $id = validateInteger($_GET['delete']);
    
    if ($id) {
        try {
            // Get review data for logging
            $oldStmt = $conn->prepare("SELECT * FROM reviews WHERE id = ?");
            $oldStmt->execute([$id]);
            $oldValues = $oldStmt->fetch();
            
            $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->execute([$id]);
            
            // Log admin delete activity
            logAdminDelete($_SESSION['user_id'], 'review', $id, $oldValues, 'Admin deleted review');
            
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
                
                // Log admin edit activity
                $oldValues = ['status' => $review['status']];
                $newValues = ['status' => $newStatus];
                logAdminEdit($_SESSION['user_id'], 'review', $id, $oldValues, $newValues, 'Admin changed review status');
                
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

// Build query with filters
$reviews = [];
$totalReviews = 0;
$totalPages = 1;

try {
    // Base query
    $query = "SELECT r.id, r.rating, r.comment, r.status, r.created_at,
               c.username as client_username, c.first_name as client_first_name, c.last_name as client_last_name,
               f.username as freelancer_username, f.first_name as freelancer_first_name, f.last_name as freelancer_last_name,
               g.title as gig_title
        FROM reviews r
        JOIN users c ON r.client_id = c.id
        JOIN users f ON r.freelancer_id = f.id
        JOIN gigs g ON r.gig_id = g.id";
    
    $countQuery = "SELECT COUNT(*) FROM reviews r
        JOIN users c ON r.client_id = c.id
        JOIN users f ON r.freelancer_id = f.id
        JOIN gigs g ON r.gig_id = g.id";
    
    // Add filters
    $params = [];
    $whereClause = "";
    
    if (!empty($search)) {
        $whereClause .= (!empty($whereClause) ? " AND " : "") . "(c.username LIKE ? OR f.username LIKE ? OR g.title LIKE ? OR r.comment LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    }
    
    if (!empty($statusFilter)) {
        $whereClause .= (!empty($whereClause) ? " AND " : "") . "r.status = ?";
        $params[] = $statusFilter;
    }
    
    if (!empty($ratingFilter)) {
        $whereClause .= (!empty($whereClause) ? " AND " : "") . "r.rating = ?";
        $params[] = $ratingFilter;
    }
    
    if (!empty($whereClause)) {
        $query .= " WHERE " . $whereClause;
        $countQuery .= " WHERE " . $whereClause;
    }
    
    // Add ordering
    $query .= " ORDER BY r.created_at DESC LIMIT ? OFFSET ?";
    $params[] = (int)$reviewsPerPage;
    $params[] = (int)$offset;
    
    // Get total count for pagination
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute(array_slice($params, 0, count($params) - 2)); // Remove LIMIT/OFFSET params
    $totalReviews = $countStmt->fetchColumn();
    $totalPages = ceil($totalReviews / $reviewsPerPage);
    
    // Get reviews
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $reviews = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch reviews error: " . $e->getMessage());
    $error = "Error fetching reviews: " . $e->getMessage();
}

include '../includes/admin_header.php';
?>

<style>
/* Custom Animations */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes scaleIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
    20%, 40%, 60%, 80% { transform: translateX(5px); }
}

.animate-slide-down {
    animation: slideDown 0.3s ease-out;
}

.animate-slide-up {
    animation: slideUp 0.4s ease-out;
}

.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}

.animate-scale-in {
    animation: scaleIn 0.3s ease-out;
}

.animate-spin {
    animation: spin 1s linear infinite;
}

.animate-shake {
    animation: shake 0.5s ease-in-out;
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
    transition: background 0.3s ease;
}

::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Badge Dot Animation */
.badge-dot {
    position: relative;
    padding-left: 1.25rem;
}

.badge-dot::before {
    content: '';
    position: absolute;
    left: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    width: 6px;
    height: 6px;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.badge-active::before {
    background-color: #10b981;
}

.badge-hidden::before {
    background-color: #ef4444;
}

/* Card Hover Effect */
.card-hover {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.card-hover:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Button Ripple Effect */
.btn-ripple {
    position: relative;
    overflow: hidden;
}

.btn-ripple::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.btn-ripple:active::after {
    width: 300px;
    height: 300px;
}

/* Modal Backdrop Blur */
.modal-backdrop {
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

/* Table Row Hover */
.table-row-hover {
    transition: all 0.2s ease;
}

.table-row-hover:hover {
    background-color: #f8fafc;
    transform: scale(1.01);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
</style>

<!-- Main Container with Gradient Background -->
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="mx-auto">
        
        <!-- Page Header -->
        <div class="mb-8 animate-slide-down">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 flex items-center gap-3">
                        <div class="p-3 bg-indigo-600 rounded-xl shadow-lg">
                            <i class="ri-star-line text-white text-2xl"></i>
                        </div>
                        <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                            Review Management
                        </span>
                    </h1>
                    <p class="mt-2 text-gray-600 text-sm sm:text-base flex items-center gap-2">
                        <i class="ri-information-line"></i>
                        Monitor and manage customer reviews and ratings
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-4 py-2 bg-white rounded-lg shadow-md border border-gray-200 text-sm font-semibold text-gray-700">
                        <i class="ri-star-fill text-yellow-500"></i> Total: <span class="text-indigo-600"><?php echo $totalReviews; ?></span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($success)): ?>
        <div class="mb-6 animate-slide-down">
            <div class="alert-box bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 p-5 rounded-xl shadow-lg">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <div class="p-2 bg-green-500 rounded-lg">
                            <i class="ri-checkbox-circle-line text-white text-xl"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-green-900 font-semibold text-sm mb-1">Success!</h3>
                        <p class="text-green-800 text-sm"><?php echo htmlspecialchars($success); ?></p>
                    </div>
                    <button onclick="this.parentElement.parentElement.parentElement.remove()" class="text-green-600 hover:text-green-800 transition-colors">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
        <div class="mb-6 animate-slide-down">
            <div class="alert-box bg-gradient-to-r from-red-50 to-rose-50 border-l-4 border-red-500 p-5 rounded-xl shadow-lg">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <div class="p-2 bg-red-500 rounded-lg">
                            <i class="ri-error-warning-line text-white text-xl"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-red-900 font-semibold text-sm mb-1">Error!</h3>
                        <p class="text-red-800 text-sm"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                    <button onclick="this.parentElement.parentElement.parentElement.remove()" class="text-red-600 hover:text-red-800 transition-colors">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Filter Section -->
        <div class="mb-6 animate-slide-up">
            <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
                    <h3 class="text-white font-semibold flex items-center gap-2 text-lg">
                        <i class="ri-filter-3-line text-xl"></i>
                        Advanced Filters
                    </h3>
                </div>
                <div class="p-6">
                    <form method="GET" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Search Input -->
                            <div class="group">
                                <label for="search" class="block mb-2 text-sm font-semibold text-gray-700 flex items-center gap-2">
                                    <i class="ri-search-line text-indigo-600"></i>
                                    Search Reviews
                                </label>
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        id="search" 
                                        name="search" 
                                        class="w-full pl-11 pr-4 py-3 border-2 border-gray-300 rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 hover:border-indigo-400" 
                                        placeholder="Client, freelancer, gig..." 
                                        value="<?php echo htmlspecialchars($search); ?>">
                                    <i class="ri-search-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                            
                            <!-- Status Filter -->
                            <div class="group">
                                <label for="status" class="block mb-2 text-sm font-semibold text-gray-700 flex items-center gap-2">
                                    <i class="ri-toggle-line text-indigo-600"></i>
                                    Review Status
                                </label>
                                <div class="relative">
                                    <select 
                                        id="status" 
                                        name="status" 
                                        class="w-full pl-11 pr-4 py-3 border-2 border-gray-300 rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 hover:border-indigo-400 appearance-none bg-white cursor-pointer">
                                        <option value="">All Statuses</option>
                                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>✅ Active</option>
                                        <option value="hidden" <?php echo $statusFilter === 'hidden' ? 'selected' : ''; ?>>🔒 Hidden</option>
                                    </select>
                                    <i class="ri-toggle-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <i class="ri-arrow-down-s-line absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                            
                            <!-- Rating Filter -->
                            <div class="group">
                                <label for="rating" class="block mb-2 text-sm font-semibold text-gray-700 flex items-center gap-2">
                                    <i class="ri-star-line text-indigo-600"></i>
                                    Rating
                                </label>
                                <div class="relative">
                                    <select 
                                        id="rating" 
                                        name="rating" 
                                        class="w-full pl-11 pr-4 py-3 border-2 border-gray-300 rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 hover:border-indigo-400 appearance-none bg-white cursor-pointer">
                                        <option value="">All Ratings</option>
                                        <option value="5" <?php echo $ratingFilter === 5 ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐ 5 Stars</option>
                                        <option value="4" <?php echo $ratingFilter === 4 ? 'selected' : ''; ?>>⭐⭐⭐⭐ 4 Stars</option>
                                        <option value="3" <?php echo $ratingFilter === 3 ? 'selected' : ''; ?>>⭐⭐⭐ 3 Stars</option>
                                        <option value="2" <?php echo $ratingFilter === 2 ? 'selected' : ''; ?>>⭐⭐ 2 Stars</option>
                                        <option value="1" <?php echo $ratingFilter === 1 ? 'selected' : ''; ?>>⭐ 1 Star</option>
                                    </select>
                                    <i class="ri-star-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <i class="ri-arrow-down-s-line absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex gap-3 pt-2">
                            <button 
                                type="submit" 
                                class="btn-ripple flex-1 sm:flex-none px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl transition-all duration-300 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-semibold">
                                <i class="ri-search-line text-lg"></i>
                                <span>Apply Filters</span>
                            </button>
                            <a 
                                href="manage_reviews.php" 
                                class="btn-ripple flex-1 sm:flex-none px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition-all duration-300 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-semibold">
                                <i class="ri-refresh-line text-lg"></i>
                                <span>Reset</span>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Reviews Table Card -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden animate-fade-in">
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-5 border-b border-gray-200">
                <div class="flex flex-wrap justify-between items-center gap-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-100 rounded-lg">
                            <i class="ri-star-fill text-indigo-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">All Reviews</h3>
                            <p class="text-sm text-gray-600"><?php echo $totalReviews; ?> total reviews found</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Freelancer</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Gig</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Rating</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Comment</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($reviews)): ?>
                        <tr>
                            <td colspan="9" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="mb-4 p-4 bg-gray-100 rounded-full">
                                        <i class="ri-star-line text-6xl text-gray-400"></i>
                                    </div>
                                    <h4 class="text-xl font-bold text-gray-700 mb-2">No Reviews Found</h4>
                                    <p class="text-gray-500 text-sm">Try adjusting your filters or search terms</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                        <tr class="table-row-hover">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900">#<?php echo htmlspecialchars($review['id']); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center text-white font-bold shadow-lg">
                                            <?php echo strtoupper(substr($review['client_first_name'], 0, 1) . substr($review['client_last_name'], 0, 1)); ?>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($review['client_first_name'] . ' ' . $review['client_last_name']); ?>
                                        </div>
                                        <div class="text-xs text-gray-500 flex items-center gap-1">
                                            <i class="ri-at-line"></i>
                                            <?php echo htmlspecialchars($review['client_username']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center text-white font-bold shadow-lg">
                                            <?php echo strtoupper(substr($review['freelancer_first_name'], 0, 1) . substr($review['freelancer_last_name'], 0, 1)); ?>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($review['freelancer_first_name'] . ' ' . $review['freelancer_last_name']); ?>
                                        </div>
                                        <div class="text-xs text-gray-500 flex items-center gap-1">
                                            <i class="ri-at-line"></i>
                                            <?php echo htmlspecialchars($review['freelancer_username']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="text-sm text-gray-900 max-w-xs">
                                    <i class="ri-briefcase-line text-indigo-600"></i>
                                    <?php echo htmlspecialchars($review['gig_title']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="flex text-yellow-400">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="ri-star<?php echo $i <= $review['rating'] ? '-fill' : '-line'; ?> text-lg"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($review['rating']); ?>/5</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-700 max-w-xs">
                                    <p class="line-clamp-2"><?php echo htmlspecialchars($review['comment']); ?></p>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="<?php 
                                    echo $review['status'] === 'active' 
                                        ? 'bg-green-100 text-green-800 border border-green-200' 
                                        : 'bg-red-100 text-red-800 border border-red-200';
                                ?> px-3 py-1.5 rounded-full text-xs font-bold inline-flex items-center gap-1.5 capitalize badge-dot badge-<?php echo htmlspecialchars($review['status']); ?> shadow-sm">
                                    <?php echo $review['status'] === 'active' ? '✅ Active' : '🔒 Hidden'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-center">
                                <div class="flex items-center gap-1 justify-center">
                                    <i class="ri-calendar-line text-gray-400"></i>
                                    <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-2 flex-wrap">
                                    <button 
                                        onclick="confirmToggleStatus(<?php echo $review['id']; ?>, '<?php echo $review['status']; ?>')"
                                        class="p-2 bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white border border-blue-200 hover:border-blue-600 rounded-lg transition-all duration-200 transform hover:scale-110 shadow-sm hover:shadow-md"
                                        title="Toggle Status">
                                        <i class="ri-toggle-line text-sm"></i>
                                    </button>
                                    <button 
                                        onclick="confirmDelete(<?php echo $review['id']; ?>)"
                                        class="p-2 bg-red-50 hover:bg-red-600 text-red-700 hover:text-white border border-red-200 hover:border-red-600 rounded-lg transition-all duration-200 transform hover:scale-110 shadow-sm hover:shadow-md"
                                        title="Delete Review">
                                        <i class="ri-delete-bin-line text-sm"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Mobile Card View -->
            <div class="md:hidden p-4 space-y-4">
                <?php if (empty($reviews)): ?>
                <div class="text-center py-12">
                    <div class="flex flex-col items-center justify-center">
                        <div class="mb-4 p-4 bg-gray-100 rounded-full">
                            <i class="ri-star-line text-6xl text-gray-400"></i>
                        </div>
                        <h4 class="text-xl font-bold text-gray-700 mb-2">No Reviews Found</h4>
                        <p class="text-gray-500 text-sm">Try adjusting your filters</p>
                    </div>
                </div>
                <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                <div class="card-hover bg-white rounded-xl border-2 border-gray-200 overflow-hidden shadow-lg">
                    <!-- Card Header -->
                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 p-4 border-b border-gray-200">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex items-center gap-3 flex-1">
                                <div class="h-12 w-12 rounded-full bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center text-white font-bold shadow-lg flex-shrink-0">
                                    <?php echo strtoupper(substr($review['client_first_name'], 0, 1) . substr($review['client_last_name'], 0, 1)); ?>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-bold text-gray-900 text-base truncate">
                                        <?php echo htmlspecialchars($review['client_first_name'] . ' ' . $review['client_last_name']); ?>
                                    </h4>
                                    <p class="text-sm text-gray-600 flex items-center gap-1">
                                        <i class="ri-at-line text-xs"></i>
                                        <span class="truncate"><?php echo htmlspecialchars($review['client_username']); ?></span>
                                    </p>
                                </div>
                            </div>
                            <span class="<?php 
                                echo $review['status'] === 'active' 
                                    ? 'bg-green-100 text-green-800 border border-green-200' 
                                    : 'bg-red-100 text-red-800 border border-red-200';
                            ?> px-2.5 py-1 rounded-full text-xs font-bold capitalize shadow-sm whitespace-nowrap">
                                <?php echo $review['status'] === 'active' ? '✅' : '🔒'; ?>
                                <?php echo htmlspecialchars($review['status']); ?>
                            </span>
                        </div>
                        
                        <!-- Rating -->
                        <div class="flex items-center justify-center gap-2 py-3 bg-white rounded-lg border border-gray-200">
                            <div class="flex text-yellow-400">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="ri-star<?php echo $i <= $review['rating'] ? '-fill' : '-line'; ?> text-2xl"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($review['rating']); ?>/5</span>
                        </div>
                    </div>
                    
                    <!-- Card Body -->
                    <div class="p-4 space-y-3">
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <i class="ri-user-line text-purple-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Freelancer</p>
                                <p class="text-sm text-gray-900 font-medium truncate">
                                    <?php echo htmlspecialchars($review['freelancer_first_name'] . ' ' . $review['freelancer_last_name']); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="p-2 bg-indigo-100 rounded-lg">
                                <i class="ri-briefcase-line text-indigo-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Gig</p>
                                <p class="text-sm text-gray-900 font-medium truncate"><?php echo htmlspecialchars($review['gig_title']); ?></p>
                            </div>
                        </div>
                        
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-start gap-2 mb-2">
                                <i class="ri-message-3-line text-gray-600"></i>
                                <p class="text-xs text-gray-500 font-semibold uppercase">Review Comment</p>
                            </div>
                            <p class="text-sm text-gray-900 leading-relaxed"><?php echo htmlspecialchars($review['comment']); ?></p>
                        </div>
                        
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <i class="ri-calendar-line text-blue-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Created</p>
                                <p class="text-sm text-gray-900 font-medium"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="p-2 bg-gray-100 rounded-lg">
                                <i class="ri-hashtag text-gray-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Review ID</p>
                                <p class="text-sm text-gray-900 font-bold">#<?php echo htmlspecialchars($review['id']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card Footer -->
                    <div class="p-4 bg-gray-50 border-t border-gray-200">
                        <div class="grid grid-cols-2 gap-2">
                            <button 
                                onclick="confirmToggleStatus(<?php echo $review['id']; ?>, '<?php echo $review['status']; ?>')"
                                class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200 flex items-center justify-center gap-2 font-semibold text-sm shadow-md hover:shadow-lg">
                                <i class="ri-toggle-line"></i> <?php echo $review['status'] === 'active' ? 'Hide' : 'Show'; ?>
                            </button>
                            <button 
                                onclick="confirmDelete(<?php echo $review['id']; ?>)"
                                class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-all duration-200 flex items-center justify-center gap-2 font-semibold text-sm shadow-md hover:shadow-lg">
                                <i class="ri-delete-bin-line"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="px-6 py-5 border-t border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="text-sm text-gray-600">
                        Showing page <span class="font-semibold text-gray-900"><?php echo $page; ?></span> of <span class="font-semibold text-gray-900"><?php echo $totalPages; ?></span>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap justify-center">
                        <?php if ($page > 1): ?>
                            <a 
                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                class="px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md">
                                <i class="ri-arrow-left-s-line"></i> Previous
                            </a>
                        <?php else: ?>
                            <span class="px-4 py-2 bg-gray-100 border-2 border-gray-200 text-gray-400 rounded-lg cursor-not-allowed font-semibold text-sm">
                                <i class="ri-arrow-left-s-line"></i> Previous
                            </span>
                        <?php endif; ?>
                        
                        <?php
                        $range = 2;
                        $start = max(1, $page - $range);
                        $end = min($totalPages, $page + $range);
                        
                        if ($start > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="hidden sm:block px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md">1</a>
                            <?php if ($start > 2): ?>
                                <span class="hidden sm:block px-4 py-2 text-gray-400">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 border-2 border-indigo-600 text-white rounded-lg font-bold text-sm shadow-md"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="hidden sm:block px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($end < $totalPages): ?>
                            <?php if ($end < $totalPages - 1): ?>
                                <span class="hidden sm:block px-4 py-2 text-gray-400">...</span>
                            <?php endif; ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>" class="hidden sm:block px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md"><?php echo $totalPages; ?></a>
                        <?php endif; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a 
                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                class="px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md">
                                Next <i class="ri-arrow-right-s-line"></i>
                            </a>
                        <?php else: ?>
                            <span class="px-4 py-2 bg-gray-100 border-2 border-gray-200 text-gray-400 rounded-lg cursor-not-allowed font-semibold text-sm">
                                Next <i class="ri-arrow-right-s-line"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Toggle Status Confirmation Modal -->
<div id="confirmToggleModal" class="hidden fixed inset-0 z-50 overflow-y-auto modal-backdrop" style="background-color: rgba(0, 0, 0, 0.75);">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 mt-14">
        <div class="fixed inset-0 transition-opacity" onclick="closeModal('confirmToggleModal')"></div>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full animate-scale-in">
            <div class="bg-gradient-to-r from-blue-600 to-cyan-600 px-6 py-5">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-white/20 rounded-xl">
                        <i class="ri-toggle-line text-white text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white">Toggle Review Status</h3>
                </div>
            </div>
            
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 mb-4 animate-shake">
                        <i class="ri-question-line text-4xl text-blue-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2" id="toggleModalTitle"></h3>
                    <p class="text-sm text-gray-600" id="toggleModalMessage"></p>
                </div>
                
                <div class="flex gap-3">
                    <button 
                        type="button" 
                        onclick="closeModal('confirmToggleModal')" 
                        class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        Cancel
                    </button>
                    <a 
                        id="confirmToggleBtn" 
                        href="#"
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl text-center">
                        Confirm
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="confirmDeleteModal" class="hidden fixed inset-0 z-50 overflow-y-auto modal-backdrop" style="background-color: rgba(0, 0, 0, 0.75);">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 mt-14">
        <div class="fixed inset-0 transition-opacity" onclick="closeModal('confirmDeleteModal')"></div>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full animate-scale-in">
            <div class="bg-gradient-to-r from-red-600 to-rose-600 px-6 py-5">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-white/20 rounded-xl">
                        <i class="ri-delete-bin-line text-white text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white">Delete Review</h3>
                </div>
            </div>
            
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4 animate-shake">
                        <i class="ri-alarm-warning-line text-4xl text-red-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Are you absolutely sure?</h3>
                    <p class="text-sm text-gray-600 mb-3">You are about to delete this review.</p>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                        <div class="flex items-start">
                            <i class="ri-error-warning-line text-red-600 text-xl mr-3 flex-shrink-0 mt-0.5"></i>
                            <div class="text-left">
                                <p class="text-sm font-semibold text-red-800">Warning: This action cannot be undone!</p>
                                <p class="text-xs text-red-700 mt-1">The review will be permanently deleted from the system.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-3">
                    <button 
                        type="button" 
                        onclick="closeModal('confirmDeleteModal')" 
                        class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        Cancel
                    </button>
                    <a 
                        id="confirmDeleteBtn" 
                        href="#"
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl text-center">
                        Delete Review
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Modal Management
function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

// Confirm Toggle Status
function confirmToggleStatus(reviewId, currentStatus) {
    const action = currentStatus === 'active' ? 'hide' : 'show';
    const actionText = currentStatus === 'active' ? 'Hide' : 'Show';
    
    document.getElementById('toggleModalTitle').textContent = `${actionText} this review?`;
    document.getElementById('toggleModalMessage').textContent = `Are you sure you want to ${action} this review?`;
    
    const urlParams = new URLSearchParams(window.location.search);
    const queryString = urlParams.toString();
    const url = `?toggle_status=${reviewId}${queryString ? '&' + queryString : ''}`;
    
    document.getElementById('confirmToggleBtn').href = url;
    openModal('confirmToggleModal');
}

// Confirm Delete
function confirmDelete(reviewId) {
    const urlParams = new URLSearchParams(window.location.search);
    const queryString = urlParams.toString();
    const url = `?delete=${reviewId}${queryString ? '&' + queryString : ''}`;
    
    document.getElementById('confirmDeleteBtn').href = url;
    openModal('confirmDeleteModal');
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Close on Escape Key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal('confirmToggleModal');
            closeModal('confirmDeleteModal');
        }
    });
    
    // Auto-hide alerts
    document.querySelectorAll('.alert-box').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'all 0.5s ease-out';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});
</script>

<?php include '../includes/admin_footer.php'; ?>