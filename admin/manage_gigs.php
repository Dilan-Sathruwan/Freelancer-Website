<?php
session_start();
include '../config/db.con.php';
include '../includes/logging_functions.php';

// Set page variables
$page_title = 'Manage Gigs';
$active_page = 'gigs';

// Ensure only admins can access this page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Pagination variables
$gigsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $gigsPerPage;

// Search and filter variables
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? validateInteger($_GET['category']) : '';
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$freelancerFilter = isset($_GET['freelancer']) ? validateInteger($_GET['freelancer']) : '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Add Gig
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $freelancer_id = validateInteger($_POST['freelancer_id']);
        $category_id = validateInteger($_POST['category_id']);
        $price = floatval($_POST['price']);
        $status = sanitizeInput($_POST['status']);
        
        // Validate required fields
        if (empty($title) || empty($description) || !$freelancer_id || !$category_id || $price <= 0) {
            $error = "All fields are required and price must be greater than 0.";
        } elseif (!in_array($status, ['active', 'inactive'])) {
            $error = "Invalid status selected.";
        } else {
            try {
                // Insert new gig
                $stmt = $conn->prepare("INSERT INTO gigs (freelancer_id, category_id, title, description, price, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$freelancer_id, $category_id, $title, $description, $price, $status]);
                
                // Log admin add activity
                $newValues = [
                    'freelancer_id' => $freelancer_id,
                    'category_id' => $category_id,
                    'title' => $title,
                    'description' => $description,
                    'price' => $price,
                    'status' => $status
                ];
                logAdminAdd($_SESSION['user_id'], 'gig', $newValues, 'Admin added new gig');
                
                $success = "Gig added successfully.";
            } catch (PDOException $e) {
                error_log("Add gig error: " . $e->getMessage());
                $error = "Error adding gig.";
            }
        }
    }
    
    // Handle Edit Gig
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = validateInteger($_POST['id']);
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $category_id = validateInteger($_POST['category_id']);
        $price = floatval($_POST['price']);
        $status = sanitizeInput($_POST['status']);
        
        // Validate required fields
        if (!$id || empty($title) || empty($description) || !$category_id || $price <= 0) {
            $error = "All fields are required and price must be greater than 0.";
        } elseif (!in_array($status, ['active', 'inactive'])) {
            $error = "Invalid status selected.";
        } else {
            try {
                // Get old values for logging
                $oldStmt = $conn->prepare("SELECT category_id, title, description, price, status FROM gigs WHERE id = ?");
                $oldStmt->execute([$id]);
                $oldValues = $oldStmt->fetch();
                
                // Update gig
                $stmt = $conn->prepare("UPDATE gigs SET category_id = ?, title = ?, description = ?, price = ?, status = ? WHERE id = ?");
                $stmt->execute([$category_id, $title, $description, $price, $status, $id]);
                
                // Log admin edit activity
                $newValues = [
                    'category_id' => $category_id,
                    'title' => $title,
                    'description' => $description,
                    'price' => $price,
                    'status' => $status
                ];
                logAdminEdit($_SESSION['user_id'], 'gig', $id, $oldValues, $newValues, 'Admin updated gig information');
                
                $success = "Gig updated successfully.";
            } catch (PDOException $e) {
                error_log("Edit gig error: " . $e->getMessage());
                $error = "Error updating gig.";
            }
        }
    }
}

// Handle gig status update
if (isset($_GET['toggle_status'])) {
    $id = validateInteger($_GET['toggle_status']);
    
    if ($id) {
        try {
            // Get current status
            $stmt = $conn->prepare("SELECT status FROM gigs WHERE id = ?");
            $stmt->execute([$id]);
            $gig = $stmt->fetch();
            
            if ($gig) {
                $newStatus = ($gig['status'] === 'active') ? 'inactive' : 'active';
                
                // Log admin edit activity for status change
                $oldValues = ['status' => $gig['status']];
                $newValues = ['status' => $newStatus];
                logAdminEdit($_SESSION['user_id'], 'gig', $id, $oldValues, $newValues, 'Admin changed gig status');
                
                $stmt = $conn->prepare("UPDATE gigs SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $id]);
                $success = "Gig status updated successfully.";
            }
        } catch (PDOException $e) {
            error_log("Update gig status error: " . $e->getMessage());
            $error = "Error updating gig status.";
        }
    }
}

// Handle gig deletion
if (isset($_GET['delete'])) {
    $id = validateInteger($_GET['delete']);
    
    if ($id) {
        try {
            // Get gig info
            $stmt = $conn->prepare("SELECT title FROM gigs WHERE id = ?");
            $stmt->execute([$id]);
            $gigToDelete = $stmt->fetch();
            
            if ($gigToDelete) {
                // Get old values for logging
                $oldStmt = $conn->prepare("SELECT title, description, price, status FROM gigs WHERE id = ?");
                $oldStmt->execute([$id]);
                $oldValues = $oldStmt->fetch();
                
                // Delete the gig
                $stmt = $conn->prepare("DELETE FROM gigs WHERE id = ?");
                $stmt->execute([$id]);
                
                // Log admin delete activity
                logAdminDelete($_SESSION['user_id'], 'gig', $id, $oldValues, 'Admin deleted gig');
                
                $success = "Gig '" . htmlspecialchars($gigToDelete['title']) . "' deleted successfully.";
            } else {
                $error = "Gig not found.";
            }
        } catch (PDOException $e) {
            error_log("Delete gig error: " . $e->getMessage());
            $error = "Error deleting gig. The gig may have associated data.";
        }
    }
}

// Build query with filters
$gigs = [];
$totalGigs = 0;
$totalPages = 1;

try {
    // Base query
    $query = "SELECT g.id, g.title, g.description, g.price, g.status, g.created_at, g.avg_rating, g.reviews_count,
                     g.freelancer_id, g.category_id,
                     u.username as freelancer_username, u.first_name, u.last_name, c.name as category_name
              FROM gigs g
              JOIN users u ON g.freelancer_id = u.id
              JOIN categories c ON g.category_id = c.id";
    $countQuery = "SELECT COUNT(*) FROM gigs g JOIN users u ON g.freelancer_id = u.id JOIN categories c ON g.category_id = c.id";
    
    // Add filters
    $params = [];
    $whereClause = "";
    
    if (!empty($search)) {
        $whereClause .= (!empty($whereClause) ? " AND " : "") . "(g.title LIKE ? OR g.description LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam]);
    }
    
    if (!empty($categoryFilter)) {
        $whereClause .= (!empty($whereClause) ? " AND " : "") . "g.category_id = ?";
        $params[] = $categoryFilter;
    }
    
    if (!empty($statusFilter)) {
        $whereClause .= (!empty($whereClause) ? " AND " : "") . "g.status = ?";
        $params[] = $statusFilter;
    }
    
    if (!empty($freelancerFilter)) {
        $whereClause .= (!empty($whereClause) ? " AND " : "") . "g.freelancer_id = ?";
        $params[] = $freelancerFilter;
    }
    
    if (!empty($whereClause)) {
        $query .= " WHERE " . $whereClause;
        $countQuery .= " WHERE " . $whereClause;
    }
    
    // Add ordering
    $query .= " ORDER BY g.created_at DESC LIMIT ? OFFSET ?";
    $params[] = (int)$gigsPerPage;
    $params[] = (int)$offset;
    
    // Get total count for pagination
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute(array_slice($params, 0, count($params) - 2)); // Remove LIMIT/OFFSET params
    $totalGigs = $countStmt->fetchColumn();
    $totalPages = ceil($totalGigs / $gigsPerPage);
    
    // Get gigs
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $gigs = $stmt->fetchAll();
    
    // Get freelancers for dropdown
    $freelancersStmt = $conn->query("SELECT id, username, first_name, last_name FROM users WHERE role = 'freelancer' AND status = 'active' ORDER BY username");
    $freelancers = $freelancersStmt->fetchAll();
    
    // Get categories for dropdown
    $categoriesStmt = $conn->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");
    $categories = $categoriesStmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch gigs error: " . $e->getMessage());
    $error = "Error fetching gigs.";
    $gigs = [];
    $totalGigs = 0;
    $freelancers = [];
    $categories = [];
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
                        <div class="p-3 bg-purple-600 rounded-xl shadow-lg">
                            <i class="ri-stack-line text-white text-2xl"></i>
                        </div>
                        <span class="bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                            Gig Management
                        </span>
                    </h1>
                    <p class="mt-2 text-gray-600 text-sm sm:text-base flex items-center gap-2">
                        <i class="ri-information-line"></i>
                        Manage and monitor all gig listings
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-4 py-2 bg-white rounded-lg shadow-md border border-gray-200 text-sm font-semibold text-gray-700">
                        <i class="ri-stack-line text-purple-600"></i> Total: <span class="text-purple-600"><?php echo $totalGigs; ?></span>
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
                <div class="bg-gradient-to-r from-purple-600 to-pink-600 px-6 py-4">
                    <h3 class="text-white font-semibold flex items-center gap-2 text-lg">
                        <i class="ri-filter-3-line text-xl"></i>
                        Advanced Filters
                    </h3>
                </div>
                <div class="p-6">
                    <form method="GET" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Search Input -->
                            <div class="group">
                                <label for="search" class="block mb-2 text-sm font-semibold text-gray-700 flex items-center gap-2">
                                    <i class="ri-search-line text-purple-600"></i>
                                    Search Gigs
                                </label>
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        id="search" 
                                        name="search" 
                                        class="w-full pl-11 pr-4 py-3 border-2 border-gray-300 rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-purple-100 focus:border-purple-500 hover:border-purple-400" 
                                        placeholder="Title, description..." 
                                        value="<?php echo htmlspecialchars($search); ?>">
                                    <i class="ri-search-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                            
                            <!-- Category Filter -->
                            <div class="group">
                                <label for="category" class="block mb-2 text-sm font-semibold text-gray-700 flex items-center gap-2">
                                    <i class="ri-price-tag-3-line text-purple-600"></i>
                                    Category
                                </label>
                                <div class="relative">
                                    <select 
                                        id="category" 
                                        name="category" 
                                        class="w-full pl-11 pr-4 py-3 border-2 border-gray-300 rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-purple-100 focus:border-purple-500 hover:border-purple-400 appearance-none bg-white cursor-pointer">
                                        <option value="">All Categories</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo $categoryFilter == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class="ri-price-tag-3-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <i class="ri-arrow-down-s-line absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                            
                            <!-- Freelancer Filter -->
                            <div class="group">
                                <label for="freelancer" class="block mb-2 text-sm font-semibold text-gray-700 flex items-center gap-2">
                                    <i class="ri-user-line text-purple-600"></i>
                                    Freelancer
                                </label>
                                <div class="relative">
                                    <select 
                                        id="freelancer" 
                                        name="freelancer" 
                                        class="w-full pl-11 pr-4 py-3 border-2 border-gray-300 rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-purple-100 focus:border-purple-500 hover:border-purple-400 appearance-none bg-white cursor-pointer">
                                        <option value="">All Freelancers</option>
                                        <?php foreach ($freelancers as $freelancer): ?>
                                        <option value="<?php echo $freelancer['id']; ?>" <?php echo $freelancerFilter == $freelancer['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($freelancer['first_name'] . ' ' . $freelancer['last_name'] . ' (@' . $freelancer['username'] . ')'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class="ri-user-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <i class="ri-arrow-down-s-line absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                            
                            <!-- Status Filter -->
                            <div class="group">
                                <label for="status" class="block mb-2 text-sm font-semibold text-gray-700 flex items-center gap-2">
                                    <i class="ri-toggle-line text-purple-600"></i>
                                    Gig Status
                                </label>
                                <div class="relative">
                                    <select 
                                        id="status" 
                                        name="status" 
                                        class="w-full pl-11 pr-4 py-3 border-2 border-gray-300 rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-purple-100 focus:border-purple-500 hover:border-purple-400 appearance-none bg-white cursor-pointer">
                                        <option value="">All Statuses</option>
                                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>✅ Active</option>
                                        <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>❌ Inactive</option>
                                    </select>
                                    <i class="ri-toggle-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <i class="ri-arrow-down-s-line absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex gap-3 pt-2">
                            <button 
                                type="submit" 
                                class="btn-ripple flex-1 sm:flex-none px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl transition-all duration-300 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-semibold">
                                <i class="ri-search-line text-lg"></i>
                                <span>Apply Filters</span>
                            </button>
                            <a 
                                href="manage_gigs.php" 
                                class="btn-ripple flex-1 sm:flex-none px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition-all duration-300 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-semibold">
                                <i class="ri-refresh-line text-lg"></i>
                                <span>Reset</span>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Gigs Table Card -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden animate-fade-in">
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-5 border-b border-gray-200">
                <div class="flex flex-wrap justify-between items-center gap-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <i class="ri-stack-line text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">All Gigs</h3>
                            <p class="text-sm text-gray-600"><?php echo $totalGigs; ?> total gigs found</p>
                        </div>
                    </div>
                    <button 
                        id="addGigBtn" 
                        class="btn-ripple px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl transition-all duration-300 flex items-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-semibold">
                        <i class="ri-add-line text-lg"></i>
                        <span>Add New Gig</span>
                    </button>
                </div>
            </div>
            
            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Freelancer</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Rating</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($gigs)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="mb-4 p-4 bg-gray-100 rounded-full">
                                        <i class="ri-stack-line text-6xl text-gray-400"></i>
                                    </div>
                                    <h4 class="text-xl font-bold text-gray-700 mb-2">No Gigs Found</h4>
                                    <p class="text-gray-500 text-sm">Start by adding your first gig</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($gigs as $gig): ?>
                        <tr class="table-row-hover">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900">#<?php echo htmlspecialchars($gig['id']); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-semibold text-gray-900 max-w-xs truncate">
                                    <?php echo htmlspecialchars($gig['title']); ?>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    <i class="ri-calendar-line"></i>
                                    <?php echo date('M j, Y', strtotime($gig['created_at'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="text-sm text-gray-900 font-medium">
                                    <?php echo htmlspecialchars($gig['first_name'] . ' ' . $gig['last_name']); ?>
                                </div>
                                <div class="text-xs text-gray-500">@<?php echo htmlspecialchars($gig['freelancer_username']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-3 py-1.5 bg-blue-100 text-blue-800 border border-blue-200 rounded-full text-xs font-bold shadow-sm">
                                    <?php echo htmlspecialchars($gig['category_name']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-sm font-bold text-purple-600">
                                    $<?php echo number_format($gig['price'], 2); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <i class="ri-star-fill text-yellow-500"></i>
                                    <span class="text-sm font-semibold text-gray-900">
                                        <?php echo $gig['avg_rating'] ? number_format($gig['avg_rating'], 1) : 'N/A'; ?>
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($gig['reviews_count'] ?? 0); ?> reviews</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="<?php 
                                    echo $gig['status'] === 'active' 
                                        ? 'bg-green-100 text-green-800 border border-green-200' 
                                        : 'bg-red-100 text-red-800 border border-red-200';
                                ?> px-3 py-1.5 rounded-full text-xs font-bold shadow-sm">
                                    <?php echo $gig['status'] === 'active' ? '✅ Active' : '❌ Inactive'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-2 flex-wrap">
                                    <button 
                                        onclick='editGig(<?php echo json_encode([
                                            "id" => $gig["id"],
                                            "title" => $gig["title"],
                                            "description" => $gig["description"],
                                            "category_id" => $gig["category_id"],
                                            "price" => $gig["price"],
                                            "status" => $gig["status"]
                                        ]); ?>)'
                                        class="p-2 bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white border border-indigo-200 hover:border-indigo-600 rounded-lg transition-all duration-200 transform hover:scale-110 shadow-sm hover:shadow-md"
                                        title="Edit Gig">
                                        <i class="ri-edit-line text-sm"></i>
                                    </button>
                                    <button 
                                        onclick="confirmToggleStatus(<?php echo $gig['id']; ?>, '<?php echo $gig['status']; ?>', '<?php echo htmlspecialchars($gig['title'], ENT_QUOTES); ?>')"
                                        class="p-2 bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white border border-blue-200 hover:border-blue-600 rounded-lg transition-all duration-200 transform hover:scale-110 shadow-sm hover:shadow-md"
                                        title="Toggle Status">
                                        <i class="ri-toggle-line text-sm"></i>
                                    </button>
                                    <button 
                                        onclick="confirmDelete(<?php echo $gig['id']; ?>, '<?php echo htmlspecialchars($gig['title'], ENT_QUOTES); ?>')"
                                        class="p-2 bg-red-50 hover:bg-red-600 text-red-700 hover:text-white border border-red-200 hover:border-red-600 rounded-lg transition-all duration-200 transform hover:scale-110 shadow-sm hover:shadow-md"
                                        title="Delete Gig">
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
                <?php if (empty($gigs)): ?>
                <div class="text-center py-12">
                    <div class="flex flex-col items-center justify-center">
                        <div class="mb-4 p-4 bg-gray-100 rounded-full">
                            <i class="ri-stack-line text-6xl text-gray-400"></i>
                        </div>
                        <h4 class="text-xl font-bold text-gray-700 mb-2">No Gigs Found</h4>
                        <p class="text-gray-500 text-sm">Start by adding your first gig</p>
                    </div>
                </div>
                <?php else: ?>
                <?php foreach ($gigs as $gig): ?>
                <div class="card-hover bg-white rounded-xl border-2 border-gray-200 overflow-hidden shadow-lg">
                    <!-- Card Header -->
                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 p-4 border-b border-gray-200">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-gray-900 text-base mb-1">
                                    <?php echo htmlspecialchars($gig['title']); ?>
                                </h4>
                                <p class="text-sm text-gray-600">
                                    <i class="ri-user-line text-xs"></i>
                                    <?php echo htmlspecialchars($gig['first_name'] . ' ' . $gig['last_name']); ?>
                                </p>
                            </div>
                            <span class="<?php 
                                echo $gig['status'] === 'active' 
                                    ? 'bg-green-100 text-green-800 border border-green-200' 
                                    : 'bg-red-100 text-red-800 border border-red-200';
                            ?> px-2.5 py-1 rounded-full text-xs font-bold shadow-sm whitespace-nowrap">
                                <?php echo $gig['status'] === 'active' ? '✅' : '❌'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Card Body -->
                    <div class="p-4 space-y-3">
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <i class="ri-price-tag-3-line text-blue-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Category</p>
                                <p class="text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($gig['category_name']); ?></p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                <div class="p-2 bg-purple-100 rounded-lg">
                                    <i class="ri-money-dollar-circle-line text-purple-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Price</p>
                                    <p class="text-sm text-purple-600 font-bold">$<?php echo number_format($gig['price'], 2); ?></p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                <div class="p-2 bg-yellow-100 rounded-lg">
                                    <i class="ri-star-fill text-yellow-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Rating</p>
                                    <p class="text-sm text-gray-900 font-bold">
                                        <?php echo $gig['avg_rating'] ? number_format($gig['avg_rating'], 1) : 'N/A'; ?>
                                        <span class="text-xs text-gray-500">(<?php echo $gig['reviews_count'] ?? 0; ?>)</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <i class="ri-calendar-line text-green-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Created</p>
                                <p class="text-sm text-gray-900 font-medium"><?php echo date('M j, Y', strtotime($gig['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card Footer -->
                    <div class="p-4 bg-gray-50 border-t border-gray-200">
                        <div class="grid grid-cols-3 gap-2">
                            <button 
                                onclick='editGig(<?php echo json_encode([
                                    "id" => $gig["id"],
                                    "title" => $gig["title"],
                                    "description" => $gig["description"],
                                    "category_id" => $gig["category_id"],
                                    "price" => $gig["price"],
                                    "status" => $gig["status"]
                                ]); ?>)'
                                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-all duration-200 flex items-center justify-center gap-2 font-semibold text-sm shadow-md hover:shadow-lg">
                                <i class="ri-edit-line"></i> Edit
                            </button>
                            <button 
                                onclick="confirmToggleStatus(<?php echo $gig['id']; ?>, '<?php echo $gig['status']; ?>', '<?php echo htmlspecialchars($gig['title'], ENT_QUOTES); ?>')"
                                class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200 flex items-center justify-center gap-2 font-semibold text-sm shadow-md hover:shadow-lg">
                                <i class="ri-toggle-line"></i>
                            </button>
                            <button 
                                onclick="confirmDelete(<?php echo $gig['id']; ?>, '<?php echo htmlspecialchars($gig['title'], ENT_QUOTES); ?>')"
                                class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-all duration-200 flex items-center justify-center gap-2 font-semibold text-sm shadow-md hover:shadow-lg">
                                <i class="ri-delete-bin-line"></i>
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
                                class="px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-purple-600 hover:text-white hover:border-purple-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md">
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
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="hidden sm:block px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-purple-600 hover:text-white hover:border-purple-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md">1</a>
                            <?php if ($start > 2): ?>
                                <span class="hidden sm:block px-4 py-2 text-gray-400">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 border-2 border-purple-600 text-white rounded-lg font-bold text-sm shadow-md"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="hidden sm:block px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-purple-600 hover:text-white hover:border-purple-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($end < $totalPages): ?>
                            <?php if ($end < $totalPages - 1): ?>
                                <span class="hidden sm:block px-4 py-2 text-gray-400">...</span>
                            <?php endif; ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>" class="hidden sm:block px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-purple-600 hover:text-white hover:border-purple-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md"><?php echo $totalPages; ?></a>
                        <?php endif; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a 
                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                class="px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-purple-600 hover:text-white hover:border-purple-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md">
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

<!-- Add Gig Modal -->
<div id="addGigModal" class="hidden fixed inset-0 z-50 overflow-y-auto modal-backdrop" style="background-color: rgba(0, 0, 0, 0.75);">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 mt-12">
        <div class="fixed inset-0 transition-opacity" onclick="closeModal('addGigModal')"></div>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full animate-scale-in">
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 px-6 py-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="ri-add-line text-2xl"></i>
                        Add New Gig
                    </h3>
                    <button onclick="closeModal('addGigModal')" class="text-white hover:text-gray-200 transition-colors">
                        <i class="ri-close-line text-2xl"></i>
                    </button>
                </div>
            </div>
            
            <form method="POST" class="p-6 space-y-5">
                <input type="hidden" name="action" value="add">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-text text-purple-600"></i> Gig Title
                    </label>
                    <input 
                        type="text" 
                        name="title" 
                        required 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-purple-100 focus:border-purple-500 transition-all duration-200" 
                        placeholder="Enter gig title">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-file-text-line text-purple-600"></i> Description
                    </label>
                    <textarea 
                        name="description" 
                        required 
                        rows="4"
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-purple-100 focus:border-purple-500 transition-all duration-200" 
                        placeholder="Enter gig description"></textarea>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-user-line text-purple-600"></i> Freelancer
                        </label>
                        <select 
                            name="freelancer_id" 
                            required 
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-purple-100 focus:border-purple-500 transition-all duration-200">
                            <option value="">Select Freelancer</option>
                            <?php foreach ($freelancers as $freelancer): ?>
                            <option value="<?php echo $freelancer['id']; ?>">
                                <?php echo htmlspecialchars($freelancer['first_name'] . ' ' . $freelancer['last_name'] . ' (@' . $freelancer['username'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-price-tag-3-line text-purple-600"></i> Category
                        </label>
                        <select 
                            name="category_id" 
                            required 
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-purple-100 focus:border-purple-500 transition-all duration-200">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-money-dollar-circle-line text-purple-600"></i> Price ($)
                        </label>
                        <input 
                            type="number" 
                            name="price" 
                            step="0.01" 
                            min="0" 
                            required 
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-purple-100 focus:border-purple-500 transition-all duration-200" 
                            placeholder="0.00">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-toggle-line text-purple-600"></i> Status
                        </label>
                        <select 
                            name="status" 
                            required 
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-purple-100 focus:border-purple-500 transition-all duration-200">
                            <option value="active" selected>✅ Active</option>
                            <option value="inactive">❌ Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button 
                        type="button" 
                        onclick="closeModal('addGigModal')" 
                        class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        Add Gig
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Gig Modal -->
<div id="editGigModal" class="hidden fixed inset-0 z-50 overflow-y-auto modal-backdrop" style="background-color: rgba(0, 0, 0, 0.75);">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 mt-12">
        <div class="fixed inset-0 transition-opacity" onclick="closeModal('editGigModal')"></div>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full animate-scale-in">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="ri-edit-line text-2xl"></i>
                        Edit Gig
                    </h3>
                    <button onclick="closeModal('editGigModal')" class="text-white hover:text-gray-200 transition-colors">
                        <i class="ri-close-line text-2xl"></i>
                    </button>
                </div>
            </div>
            
            <form method="POST" class="p-6 space-y-5">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-text text-indigo-600"></i> Gig Title
                    </label>
                    <input 
                        type="text" 
                        id="edit_title" 
                        name="title" 
                        required 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition-all duration-200">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-file-text-line text-indigo-600"></i> Description
                    </label>
                    <textarea 
                        id="edit_description" 
                        name="description" 
                        required 
                        rows="4"
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition-all duration-200"></textarea>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-price-tag-3-line text-indigo-600"></i> Category
                        </label>
                        <select 
                            id="edit_category_id" 
                            name="category_id" 
                            required 
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition-all duration-200">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-money-dollar-circle-line text-indigo-600"></i> Price ($)
                        </label>
                        <input 
                            type="number" 
                            id="edit_price" 
                            name="price" 
                            step="0.01" 
                            min="0" 
                            required 
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition-all duration-200">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-toggle-line text-indigo-600"></i> Status
                    </label>
                    <select 
                        id="edit_status" 
                        name="status" 
                        required 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition-all duration-200">
                        <option value="active">✅ Active</option>
                        <option value="inactive">❌ Inactive</option>
                    </select>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button 
                        type="button" 
                        onclick="closeModal('editGigModal')" 
                        class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        Update Gig
                    </button>
                </div>
            </form>
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
                    <h3 class="text-xl font-bold text-white">Toggle Gig Status</h3>
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
                    <h3 class="text-xl font-bold text-white">Delete Gig</h3>
                </div>
            </div>
            
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4 animate-shake">
                        <i class="ri-alarm-warning-line text-4xl text-red-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Are you absolutely sure?</h3>
                    <p class="text-sm text-gray-600 mb-3" id="deleteModalMessage"></p>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                        <div class="flex items-start">
                            <i class="ri-error-warning-line text-red-600 text-xl mr-3 flex-shrink-0 mt-0.5"></i>
                            <div class="text-left">
                                <p class="text-sm font-semibold text-red-800">Warning: This action cannot be undone!</p>
                                <p class="text-xs text-red-700 mt-1">All gig data will be permanently deleted from the system.</p>
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
                        Delete Gig
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
function confirmToggleStatus(gigId, currentStatus, gigTitle) {
    const action = currentStatus === 'active' ? 'deactivate' : 'activate';
    const actionText = currentStatus === 'active' ? 'Deactivate' : 'Activate';
    
    document.getElementById('toggleModalTitle').textContent = `${actionText} "${gigTitle}"?`;
    document.getElementById('toggleModalMessage').textContent = `Are you sure you want to ${action} this gig?`;
    document.getElementById('confirmToggleBtn').href = `?toggle_status=${gigId}`;
    openModal('confirmToggleModal');
}

// Confirm Delete
function confirmDelete(gigId, gigTitle) {
    document.getElementById('deleteModalMessage').textContent = `You are about to delete the gig "${gigTitle}".`;
    document.getElementById('confirmDeleteBtn').href = `?delete=${gigId}`;
    openModal('confirmDeleteModal');
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Add Gig Button
    document.getElementById('addGigBtn')?.addEventListener('click', function() {
        openModal('addGigModal');
    });
    
    // Close on Escape Key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal('addGigModal');
            closeModal('editGigModal');
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

// Edit Gig Function
function editGig(gigData) {
    document.getElementById('edit_id').value = gigData.id;
    document.getElementById('edit_title').value = gigData.title;
    document.getElementById('edit_description').value = gigData.description;
    document.getElementById('edit_category_id').value = gigData.category_id;
    document.getElementById('edit_price').value = gigData.price;
    document.getElementById('edit_status').value = gigData.status;
    openModal('editGigModal');
}
</script>

<?php include '../includes/admin_footer.php'; ?>