<?php
session_start();
include '../config/db.con.php';
include '../includes/logging_functions.php';

// Set page variables
$page_title = 'Manage Freelancers';
$active_page = 'freelancers';

// Ensure only admins can access this page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Pagination variables
$freelancersPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $freelancersPerPage;

// Search and filter variables
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Add Freelancer
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $username = sanitizeInput($_POST['username']);
        $first_name = sanitizeInput($_POST['first_name']);
        $last_name = sanitizeInput($_POST['last_name']);
        $email = validateEmailInput($_POST['email']);
        $password = $_POST['password'];
        $title = sanitizeInput($_POST['title']);
        $hourly_rate = validateInteger($_POST['hourly_rate']);
        $status = sanitizeInput($_POST['status']);
        
        // Validate required fields
        if (empty($username) || empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
            $error = "All fields are required.";
        } elseif (!in_array($status, ['active', 'inactive'])) {
            $error = "Invalid status selected.";
        } else {
            try {
                // Check if username or email already exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                if ($stmt->fetch()) {
                    $error = "Username or email already exists.";
                } else {
                    // Hash password
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Begin transaction
                    $conn->beginTransaction();
                    
                    // Insert new user
                    $stmt = $conn->prepare("INSERT INTO users (username, password, role, first_name, last_name, email, status) VALUES (?, ?, 'freelancer', ?, ?, ?, ?)");
                    $stmt->execute([$username, $hashedPassword, $first_name, $last_name, $email, $status]);
                    $userId = $conn->lastInsertId();
                    
                    // Insert freelancer profile
                    $stmt = $conn->prepare("INSERT INTO freelancer_profiles (user_id, title, hourly_rate) VALUES (?, ?, ?)");
                    $stmt->execute([$userId, $title, $hourly_rate]);
                    
                    $conn->commit();
                    
                    // Log admin add activity
                    $newValues = [
                        'username' => $username,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email,
                        'title' => $title,
                        'hourly_rate' => $hourly_rate,
                        'status' => $status
                    ];
                    logAdminAdd($_SESSION['user_id'], 'freelancer', $newValues, 'Admin added new freelancer');
                    
                    $success = "Freelancer added successfully.";
                }
            } catch (PDOException $e) {
                $conn->rollBack();
                error_log("Add freelancer error: " . $e->getMessage());
                $error = "Error adding freelancer.";
            }
        }
    }
    
    // Handle Edit Freelancer
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = validateInteger($_POST['id']);
        $username = sanitizeInput($_POST['username']);
        $first_name = sanitizeInput($_POST['first_name']);
        $last_name = sanitizeInput($_POST['last_name']);
        $email = validateEmailInput($_POST['email']);
        $title = sanitizeInput($_POST['title']);
        $hourly_rate = validateInteger($_POST['hourly_rate']);
        $status = sanitizeInput($_POST['status']);
        
        // Validate required fields
        if (!$id || empty($username) || empty($first_name) || empty($last_name) || empty($email)) {
            $error = "All fields are required.";
        } elseif (!in_array($status, ['active', 'inactive'])) {
            $error = "Invalid status selected.";
        } else {
            try {
                // Check if username or email already exists for other users
                $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                $stmt->execute([$username, $email, $id]);
                if ($stmt->fetch()) {
                    $error = "Username or email already exists for another user.";
                } else {
                    // Begin transaction
                    $conn->beginTransaction();
                    
                    // Update user
                    $stmt = $conn->prepare("UPDATE users SET username = ?, first_name = ?, last_name = ?, email = ?, status = ? WHERE id = ? AND role = 'freelancer'");
                    $stmt->execute([$username, $first_name, $last_name, $email, $status, $id]);
                    
                    // Update or insert freelancer profile
                    $stmt = $conn->prepare("SELECT id FROM freelancer_profiles WHERE user_id = ?");
                    $stmt->execute([$id]);
                    if ($stmt->fetch()) {
                        $stmt = $conn->prepare("UPDATE freelancer_profiles SET title = ?, hourly_rate = ? WHERE user_id = ?");
                        $stmt->execute([$title, $hourly_rate, $id]);
                    } else {
                        $stmt = $conn->prepare("INSERT INTO freelancer_profiles (user_id, title, hourly_rate) VALUES (?, ?, ?)");
                        $stmt->execute([$id, $title, $hourly_rate]);
                    }
                    
                    $conn->commit();
                    
                    // Log admin edit activity
                    $newValues = [
                        'username' => $username,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email,
                        'title' => $title,
                        'hourly_rate' => $hourly_rate,
                        'status' => $status
                    ];
                    logAdminEdit($_SESSION['user_id'], 'freelancer', $id, null, $newValues, 'Admin updated freelancer information');
                    
                    $success = "Freelancer updated successfully.";
                }
            } catch (PDOException $e) {
                $conn->rollBack();
                error_log("Edit freelancer error: " . $e->getMessage());
                $error = "Error updating freelancer.";
            }
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
                
                // Log admin edit activity for status change
                $oldValues = ['status' => $freelancer['status']];
                $newValues = ['status' => $newStatus];
                logAdminEdit($_SESSION['user_id'], 'freelancer', $id, $oldValues, $newValues, 'Admin changed freelancer status');
                
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

// Handle freelancer deletion
if (isset($_GET['delete'])) {
    $id = validateInteger($_GET['delete']);
    
    if ($id) {
        try {
            $stmt = $conn->prepare("SELECT username FROM users WHERE id = ? AND role = 'freelancer'");
            $stmt->execute([$id]);
            $freelancerToDelete = $stmt->fetch();
            
            if ($freelancerToDelete) {
                // Get old values for logging
                $oldStmt = $conn->prepare("SELECT u.username, u.first_name, u.last_name, u.email, u.status, fp.title, fp.hourly_rate FROM users u LEFT JOIN freelancer_profiles fp ON u.id = fp.user_id WHERE u.id = ?");
                $oldStmt->execute([$id]);
                $oldValues = $oldStmt->fetch();
                
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'freelancer'");
                $stmt->execute([$id]);
                
                // Log admin delete activity
                logAdminDelete($_SESSION['user_id'], 'freelancer', $id, $oldValues, 'Admin deleted freelancer');
                
                $success = "Freelancer '" . htmlspecialchars($freelancerToDelete['username']) . "' deleted successfully.";
            } else {
                $error = "Freelancer not found.";
            }
        } catch (PDOException $e) {
            error_log("Delete freelancer error: " . $e->getMessage());
            $error = "Error deleting freelancer.";
        }
    }
}

// Build query with filters
$freelancers = [];
$totalFreelancers = 0;
$totalPages = 1;

try {
    // Base query
    $query = "SELECT u.id, u.username, u.first_name, u.last_name, u.email, u.status, u.created_at,
                     fp.title, fp.hourly_rate, fp.completed_jobs, fp.success_rate
              FROM users u
              LEFT JOIN freelancer_profiles fp ON u.id = fp.user_id
              WHERE u.role = 'freelancer'";
    $countQuery = "SELECT COUNT(*) FROM users u WHERE u.role = 'freelancer'";
    
    // Add filters
    $params = [];
    $whereClause = "";
    
    if (!empty($search)) {
        $whereClause .= (!empty($whereClause) ? " AND " : "") . "(u.username LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    }
    
    if (!empty($statusFilter)) {
        $whereClause .= (!empty($whereClause) ? " AND " : "") . "u.status = ?";
        $params[] = $statusFilter;
    }
    
    if (!empty($whereClause)) {
        $query .= " AND " . $whereClause;
        $countQuery .= " AND " . $whereClause;
    }
    
    // Add ordering
    $query .= " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
    $params[] = (int)$freelancersPerPage;
    $params[] = (int)$offset;
    
    // Get total count for pagination
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute(array_slice($params, 0, count($params) - 2));
    $totalFreelancers = $countStmt->fetchColumn();
    $totalPages = ceil($totalFreelancers / $freelancersPerPage);
    
    // Get freelancers
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $freelancers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch freelancers error: " . $e->getMessage());
    $error = "Error fetching freelancers.";
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

.badge-inactive::before {
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
                            <i class="ri-user-star-line text-white text-2xl"></i>
                        </div>
                        <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                            Freelancer Management
                        </span>
                    </h1>
                    <p class="mt-2 text-gray-600 text-sm sm:text-base flex items-center gap-2">
                        <i class="ri-information-line"></i>
                        Manage and monitor all freelancers in the system
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-4 py-2 bg-white rounded-lg shadow-md border border-gray-200 text-sm font-semibold text-gray-700">
                        <i class="ri-group-line text-indigo-600"></i> Total: <span class="text-indigo-600"><?php echo $totalFreelancers; ?></span>
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
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Search Input -->
                            <div class="group">
                                <label for="search" class="block mb-2 text-sm font-semibold text-gray-700 flex items-center gap-2">
                                    <i class="ri-search-line text-indigo-600"></i>
                                    Search Freelancers
                                </label>
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        id="search" 
                                        name="search" 
                                        class="w-full pl-11 pr-4 py-3 border-2 border-gray-300 rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 hover:border-indigo-400" 
                                        placeholder="Username, name, email..." 
                                        value="<?php echo htmlspecialchars($search); ?>">
                                    <i class="ri-search-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                            
                            <!-- Status Filter -->
                            <div class="group">
                                <label for="status" class="block mb-2 text-sm font-semibold text-gray-700 flex items-center gap-2">
                                    <i class="ri-toggle-line text-indigo-600"></i>
                                    Account Status
                                </label>
                                <div class="relative">
                                    <select 
                                        id="status" 
                                        name="status" 
                                        class="w-full pl-11 pr-4 py-3 border-2 border-gray-300 rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 hover:border-indigo-400 appearance-none bg-white cursor-pointer">
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
                                class="btn-ripple flex-1 sm:flex-none px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl transition-all duration-300 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-semibold">
                                <i class="ri-search-line text-lg"></i>
                                <span>Apply Filters</span>
                            </button>
                            <a 
                                href="manage_freelancers.php" 
                                class="btn-ripple flex-1 sm:flex-none px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition-all duration-300 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-semibold">
                                <i class="ri-refresh-line text-lg"></i>
                                <span>Reset</span>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Freelancers Table Card -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden animate-fade-in">
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-5 border-b border-gray-200">
                <div class="flex flex-wrap justify-between items-center gap-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-100 rounded-lg">
                            <i class="ri-user-star-line text-indigo-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">All Freelancers</h3>
                            <p class="text-sm text-gray-600"><?php echo $totalFreelancers; ?> total freelancers found</p>
                        </div>
                    </div>
                    <button 
                        id="addFreelancerBtn" 
                        class="btn-ripple px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl transition-all duration-300 flex items-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-semibold">
                        <i class="ri-user-add-line text-lg"></i>
                        <span>Add Freelancer</span>
                    </button>
                </div>
            </div>
            
            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Freelancer</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Rate/Hr</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Jobs</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Success</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($freelancers)): ?>
                        <tr>
                            <td colspan="9" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="mb-4 p-4 bg-gray-100 rounded-full">
                                        <i class="ri-user-search-line text-6xl text-gray-400"></i>
                                    </div>
                                    <h4 class="text-xl font-bold text-gray-700 mb-2">No Freelancers Found</h4>
                                    <p class="text-gray-500 text-sm">Try adjusting your filters or search terms</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($freelancers as $freelancer): ?>
                        <tr class="table-row-hover">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900">#<?php echo htmlspecialchars($freelancer['id']); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold shadow-lg">
                                            <?php echo strtoupper(substr($freelancer['first_name'], 0, 1) . substr($freelancer['last_name'], 0, 1)); ?>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($freelancer['first_name'] . ' ' . $freelancer['last_name']); ?>
                                        </div>
                                        <div class="text-xs text-gray-500 flex items-center gap-1">
                                            <i class="ri-at-line"></i>
                                            <?php echo htmlspecialchars($freelancer['username']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="text-sm text-gray-900 flex items-center gap-2 justify-center">
                                    <i class="ri-mail-line text-gray-400"></i>
                                    <?php echo htmlspecialchars($freelancer['email']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-sm text-gray-700 font-medium">
                                    <?php echo $freelancer['title'] ? htmlspecialchars($freelancer['title']) : '-'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-3 py-1.5 bg-emerald-100 text-emerald-800 rounded-full text-xs font-bold">
                                    <?php echo $freelancer['hourly_rate'] ? '$' . number_format($freelancer['hourly_rate'], 2) : '-'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-sm font-semibold text-gray-900">
                                    <?php echo htmlspecialchars($freelancer['completed_jobs'] ?? 0); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-3 py-1.5 bg-blue-100 text-blue-800 rounded-full text-xs font-bold">
                                    <?php echo $freelancer['success_rate'] ? number_format($freelancer['success_rate'], 1) . '%' : '-'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="<?php 
                                    echo $freelancer['status'] === 'active' 
                                        ? 'bg-green-100 text-green-800 border border-green-200' 
                                        : 'bg-red-100 text-red-800 border border-red-200';
                                ?> px-3 py-1.5 rounded-full text-xs font-bold inline-flex items-center gap-1.5 capitalize badge-dot badge-<?php echo htmlspecialchars($freelancer['status']); ?> shadow-sm">
                                    <?php echo $freelancer['status'] === 'active' ? '✅ Active' : '❌ Inactive'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-2 flex-wrap">
                                    <button 
                                        onclick="editFreelancer(<?php echo $freelancer['id']; ?>, '<?php echo htmlspecialchars($freelancer['username'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($freelancer['first_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($freelancer['last_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($freelancer['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($freelancer['title'] ?? '', ENT_QUOTES); ?>', <?php echo $freelancer['hourly_rate'] ?? 0; ?>, '<?php echo htmlspecialchars($freelancer['status']); ?>')"
                                        class="p-2 bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white border border-indigo-200 hover:border-indigo-600 rounded-lg transition-all duration-200 transform hover:scale-110 shadow-sm hover:shadow-md"
                                        title="Edit Freelancer">
                                        <i class="ri-edit-line text-sm"></i>
                                    </button>
                                    <button 
                                        onclick="confirmToggleStatus(<?php echo $freelancer['id']; ?>, '<?php echo $freelancer['status']; ?>', '<?php echo htmlspecialchars($freelancer['first_name'] . ' ' . $freelancer['last_name'], ENT_QUOTES); ?>')"
                                        class="p-2 bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white border border-blue-200 hover:border-blue-600 rounded-lg transition-all duration-200 transform hover:scale-110 shadow-sm hover:shadow-md"
                                        title="Toggle Status">
                                        <i class="ri-toggle-line text-sm"></i>
                                    </button>
                                    <button 
                                        onclick="confirmDelete(<?php echo $freelancer['id']; ?>, '<?php echo htmlspecialchars($freelancer['username'], ENT_QUOTES); ?>')"
                                        class="p-2 bg-red-50 hover:bg-red-600 text-red-700 hover:text-white border border-red-200 hover:border-red-600 rounded-lg transition-all duration-200 transform hover:scale-110 shadow-sm hover:shadow-md"
                                        title="Delete Freelancer">
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
                <?php if (empty($freelancers)): ?>
                <div class="text-center py-12">
                    <div class="flex flex-col items-center justify-center">
                        <div class="mb-4 p-4 bg-gray-100 rounded-full">
                            <i class="ri-user-search-line text-6xl text-gray-400"></i>
                        </div>
                        <h4 class="text-xl font-bold text-gray-700 mb-2">No Freelancers Found</h4>
                        <p class="text-gray-500 text-sm">Try adjusting your filters</p>
                    </div>
                </div>
                <?php else: ?>
                <?php foreach ($freelancers as $freelancer): ?>
                <div class="card-hover bg-white rounded-xl border-2 border-gray-200 overflow-hidden shadow-lg">
                    <!-- Card Header -->
                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 p-4 border-b border-gray-200">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 flex-1">
                                <div class="h-12 w-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold shadow-lg flex-shrink-0">
                                    <?php echo strtoupper(substr($freelancer['first_name'], 0, 1) . substr($freelancer['last_name'], 0, 1)); ?>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-bold text-gray-900 text-base truncate">
                                        <?php echo htmlspecialchars($freelancer['first_name'] . ' ' . $freelancer['last_name']); ?>
                                    </h4>
                                    <p class="text-sm text-gray-600 flex items-center gap-1">
                                        <i class="ri-at-line text-xs"></i>
                                        <span class="truncate"><?php echo htmlspecialchars($freelancer['username']); ?></span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col gap-2 items-end flex-shrink-0">
                                <span class="<?php 
                                    echo $freelancer['status'] === 'active' 
                                        ? 'bg-green-100 text-green-800 border border-green-200' 
                                        : 'bg-red-100 text-red-800 border border-red-200';
                                ?> px-2.5 py-1 rounded-full text-xs font-bold capitalize shadow-sm whitespace-nowrap">
                                    <?php echo $freelancer['status'] === 'active' ? '✅' : '❌'; ?>
                                    <?php echo htmlspecialchars($freelancer['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card Body -->
                    <div class="p-4 space-y-3">
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="p-2 bg-indigo-100 rounded-lg">
                                <i class="ri-mail-line text-indigo-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Email</p>
                                <p class="text-sm text-gray-900 font-medium truncate"><?php echo htmlspecialchars($freelancer['email']); ?></p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                <div class="p-2 bg-purple-100 rounded-lg">
                                    <i class="ri-briefcase-line text-purple-600"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Title</p>
                                    <p class="text-sm text-gray-900 font-medium truncate"><?php echo $freelancer['title'] ? htmlspecialchars($freelancer['title']) : '-'; ?></p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                <div class="p-2 bg-emerald-100 rounded-lg">
                                    <i class="ri-money-dollar-circle-line text-emerald-600"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Rate/Hr</p>
                                    <p class="text-sm text-gray-900 font-bold"><?php echo $freelancer['hourly_rate'] ? '$' . number_format($freelancer['hourly_rate'], 2) : '-'; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                <div class="p-2 bg-blue-100 rounded-lg">
                                    <i class="ri-task-line text-blue-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Jobs</p>
                                    <p class="text-sm text-gray-900 font-bold"><?php echo htmlspecialchars($freelancer['completed_jobs'] ?? 0); ?></p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                <div class="p-2 bg-cyan-100 rounded-lg">
                                    <i class="ri-trophy-line text-cyan-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Success</p>
                                    <p class="text-sm text-gray-900 font-bold"><?php echo $freelancer['success_rate'] ? number_format($freelancer['success_rate'], 1) . '%' : '-'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card Footer -->
                    <div class="p-4 bg-gray-50 border-t border-gray-200">
                        <div class="grid grid-cols-3 gap-2">
                            <button 
                                onclick="editFreelancer(<?php echo $freelancer['id']; ?>, '<?php echo htmlspecialchars($freelancer['username'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($freelancer['first_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($freelancer['last_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($freelancer['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($freelancer['title'] ?? '', ENT_QUOTES); ?>', <?php echo $freelancer['hourly_rate'] ?? 0; ?>, '<?php echo htmlspecialchars($freelancer['status']); ?>')"
                                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-all duration-200 flex items-center justify-center gap-2 font-semibold text-sm shadow-md hover:shadow-lg">
                                <i class="ri-edit-line"></i> 
                            </button>
                            <button 
                                onclick="confirmToggleStatus(<?php echo $freelancer['id']; ?>, '<?php echo $freelancer['status']; ?>', '<?php echo htmlspecialchars($freelancer['first_name'] . ' ' . $freelancer['last_name'], ENT_QUOTES); ?>')"
                                class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200 flex items-center justify-center gap-2 font-semibold text-sm shadow-md hover:shadow-lg">
                                <i class="ri-toggle-line"></i>
                            </button>
                            <button 
                                onclick="confirmDelete(<?php echo $freelancer['id']; ?>, '<?php echo htmlspecialchars($freelancer['username'], ENT_QUOTES); ?>')"
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

<!-- Add Freelancer Modal -->
<div id="addFreelancerModal" class="hidden fixed inset-0 z-50 overflow-y-auto modal-backdrop" style="background-color: rgba(0, 0, 0, 0.75);">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 mt-12">
        <div class="fixed inset-0 transition-opacity" onclick="closeModal('addFreelancerModal')"></div>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full animate-scale-in">
            <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="ri-user-add-line text-2xl"></i>
                        Add New Freelancer
                    </h3>
                    <button onclick="closeModal('addFreelancerModal')" class="text-white hover:text-gray-200 transition-colors">
                        <i class="ri-close-line text-2xl"></i>
                    </button>
                </div>
            </div>
            
            <form method="POST" class="p-6 space-y-5">
                <input type="hidden" name="action" value="add">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-user-line text-indigo-600"></i> Username
                    </label>
                    <input 
                        type="text" 
                        name="username" 
                        required 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-green-100 focus:border-green-500 transition-all duration-200" 
                        placeholder="Enter username">
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-user-line text-indigo-600"></i> First Name
                        </label>
                        <input 
                            type="text" 
                            name="first_name" 
                            required 
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-green-100 focus:border-green-500 transition-all duration-200" 
                            placeholder="First name">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-user-line text-indigo-600"></i> Last Name
                        </label>
                        <input 
                            type="text" 
                            name="last_name" 
                            required 
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-green-100 focus:border-green-500 transition-all duration-200" 
                            placeholder="Last name">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-mail-line text-indigo-600"></i> Email
                    </label>
                    <input 
                        type="email" 
                        name="email" 
                        required 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-green-100 focus:border-green-500 transition-all duration-200" 
                        placeholder="Enter email address">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-lock-password-line text-indigo-600"></i> Password
                    </label>
                    <input 
                        type="password" 
                        name="password" 
                        required 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-green-100 focus:border-green-500 transition-all duration-200" 
                        placeholder="Enter password">
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-briefcase-line text-indigo-600"></i> Job Title
                        </label>
                        <input 
                            type="text" 
                            name="title" 
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-green-100 focus:border-green-500 transition-all duration-200" 
                            placeholder="e.g., Web Developer">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-money-dollar-circle-line text-indigo-600"></i> Hourly Rate ($)
                        </label>
                        <input 
                            type="number" 
                            name="hourly_rate" 
                            min="0" 
                            step="0.01"
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-green-100 focus:border-green-500 transition-all duration-200" 
                            placeholder="0.00">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-toggle-line text-indigo-600"></i> Status
                    </label>
                    <select 
                        name="status" 
                        required 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-green-100 focus:border-green-500 transition-all duration-200">
                        <option value="active" selected>✅ Active</option>
                        <option value="inactive">❌ Inactive</option>
                    </select>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button 
                        type="button" 
                        onclick="closeModal('addFreelancerModal')" 
                        class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        Add Freelancer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Freelancer Modal -->
<div id="editFreelancerModal" class="hidden fixed inset-0 z-50 overflow-y-auto modal-backdrop" style="background-color: rgba(0, 0, 0, 0.75);">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 mt-12">
        <div class="fixed inset-0 transition-opacity" onclick="closeModal('editFreelancerModal')"></div>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full animate-scale-in">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="ri-edit-line text-2xl"></i>
                        Edit Freelancer
                    </h3>
                    <button onclick="closeModal('editFreelancerModal')" class="text-white hover:text-gray-200 transition-colors">
                        <i class="ri-close-line text-2xl"></i>
                    </button>
                </div>
            </div>
            
            <form method="POST" class="p-6 space-y-5">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-user-line text-indigo-600"></i> Username
                    </label>
                    <input 
                        type="text" 
                        id="edit_username" 
                        name="username" 
                        required 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition-all duration-200">
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-user-line text-indigo-600"></i> First Name
                        </label>
                        <input 
                            type="text" 
                            id="edit_first_name" 
                            name="first_name" 
                            required 
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition-all duration-200">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-user-line text-indigo-600"></i> Last Name
                        </label>
                        <input 
                            type="text" 
                            id="edit_last_name" 
                            name="last_name" 
                            required 
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition-all duration-200">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-mail-line text-indigo-600"></i> Email
                    </label>
                    <input 
                        type="email" 
                        id="edit_email" 
                        name="email" 
                        required 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition-all duration-200">
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-briefcase-line text-indigo-600"></i> Job Title
                        </label>
                        <input 
                            type="text" 
                            id="edit_title" 
                            name="title" 
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition-all duration-200">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-money-dollar-circle-line text-indigo-600"></i> Hourly Rate ($)
                        </label>
                        <input 
                            type="number" 
                            id="edit_hourly_rate" 
                            name="hourly_rate" 
                            min="0" 
                            step="0.01"
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
                        onclick="closeModal('editFreelancerModal')" 
                        class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        Update Freelancer
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
                    <h3 class="text-xl font-bold text-white">Toggle Freelancer Status</h3>
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
                    <h3 class="text-xl font-bold text-white">Delete Freelancer</h3>
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
                                <p class="text-xs text-red-700 mt-1">All freelancer data will be permanently deleted.</p>
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
                        Delete Freelancer
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
function confirmToggleStatus(userId, currentStatus, userName) {
    const action = currentStatus === 'active' ? 'deactivate' : 'activate';
    const actionText = currentStatus === 'active' ? 'Deactivate' : 'Activate';
    
    document.getElementById('toggleModalTitle').textContent = `${actionText} ${userName}?`;
    document.getElementById('toggleModalMessage').textContent = `Are you sure you want to ${action} this freelancer account?`;
    
    const urlParams = new URLSearchParams(window.location.search);
    const queryString = urlParams.toString();
    const url = `?toggle_status=${userId}${queryString ? '&' + queryString : ''}`;
    
    document.getElementById('confirmToggleBtn').href = url;
    openModal('confirmToggleModal');
}

// Confirm Delete
function confirmDelete(userId, username) {
    document.getElementById('deleteModalMessage').textContent = `You are about to delete the freelancer "${username}".`;
    
    const urlParams = new URLSearchParams(window.location.search);
    const queryString = urlParams.toString();
    const url = `?delete=${userId}${queryString ? '&' + queryString : ''}`;
    
    document.getElementById('confirmDeleteBtn').href = url;
    openModal('confirmDeleteModal');
}

// Edit Freelancer Function
function editFreelancer(id, username, firstName, lastName, email, title, hourlyRate, status) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_username').value = username;
    document.getElementById('edit_first_name').value = firstName;
    document.getElementById('edit_last_name').value = lastName;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_title').value = title || '';
    document.getElementById('edit_hourly_rate').value = hourlyRate || '';
    document.getElementById('edit_status').value = status;
    openModal('editFreelancerModal');
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Add Freelancer Button
    document.getElementById('addFreelancerBtn')?.addEventListener('click', function() {
        openModal('addFreelancerModal');
    });
    
    // Close on Escape Key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal('addFreelancerModal');
            closeModal('editFreelancerModal');
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