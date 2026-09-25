<?php
session_start();
include '../config/db.con.php';
include '../includes/logging_functions.php';

// Set page variables
$page_title = 'Admin Activity';
$active_page = 'admin_activity';

// Handle clear logs request
if (isset($_POST['clear_logs']) && $_POST['clear_logs'] === '1') {
    try {
        // Clear all admin logs
        $stmt = $conn->prepare("DELETE FROM admin_logs");
        $stmt->execute();
        
        // Log this action
        logAdminActivity($_SESSION['user_id'], 'clear_logs', 'admin_logs', null, null, null, 'Admin cleared all activity logs');
        
        $success = "All admin logs have been cleared successfully.";
    } catch (PDOException $e) {
        error_log("Clear admin logs error: " . $e->getMessage());
        $error = "Error clearing admin logs.";
    }
}

// Ensure only admins can access this page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Pagination variables
$logsPerPage = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $logsPerPage;

// Filter variables
$actionFilter = isset($_GET['action']) ? sanitizeInput($_GET['action']) : '';
$adminIdFilter = isset($_GET['admin_id']) ? validateInteger($_GET['admin_id']) : '';
$entityTypeFilter = isset($_GET['entity_type']) ? sanitizeInput($_GET['entity_type']) : '';
$startDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : '';
$endDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : '';

try {
    // Base query for admin logs
    $baseQuery = "
        SELECT 
            al.id,
            al.admin_id,
            al.action,
            al.entity_type,
            al.entity_id,
            al.old_values,
            al.new_values,
            al.details,
            al.ip_address,
            al.user_agent,
            al.created_at,
            u.username,
            u.first_name,
            u.last_name
        FROM admin_logs al
        JOIN users u ON al.admin_id = u.id
    ";
    
    // Build where clause
    $whereConditions = [];
    $params = [];
    $countParams = [];
    
    // Add filters
    if (!empty($actionFilter)) {
        $whereConditions[] = "al.action = ?";
        $params[] = $actionFilter;
        $countParams[] = $actionFilter;
    }
    
    if (!empty($adminIdFilter)) {
        $whereConditions[] = "al.admin_id = ?";
        $params[] = $adminIdFilter;
        $countParams[] = $adminIdFilter;
    }
    
    if (!empty($entityTypeFilter)) {
        $whereConditions[] = "al.entity_type = ?";
        $params[] = $entityTypeFilter;
        $countParams[] = $entityTypeFilter;
    }
    
    if (!empty($startDate)) {
        $whereConditions[] = "al.created_at >= ?";
        $params[] = $startDate;
        $countParams[] = $startDate;
    }
    
    if (!empty($endDate)) {
        $whereConditions[] = "al.created_at <= ?";
        $params[] = $endDate . ' 23:59:59';
        $countParams[] = $endDate . ' 23:59:59';
    }
    
    // Construct where clause
    $whereClause = !empty($whereConditions) ? " WHERE " . implode(" AND ", $whereConditions) : "";
    
    // Complete query with ordering and pagination
    $logsQuery = $baseQuery . $whereClause . " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
    
    // Count query for pagination
    $countQuery = "SELECT COUNT(*) as total FROM admin_logs al JOIN users u ON al.admin_id = u.id" . $whereClause;
    
    // Add pagination parameters
    $params[] = (int)$logsPerPage;
    $params[] = (int)$offset;
    
    // Get total count for pagination
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute($countParams);
    $totalCount = $countStmt->fetchColumn();
    
    // Get admin logs with pagination
    $logsStmt = $conn->prepare($logsQuery);
    $logsStmt->execute($params);
    $adminLogs = $logsStmt->fetchAll();
    
    // Get admins for filter dropdown
    $adminsStmt = $conn->query("SELECT id, username, first_name, last_name FROM users WHERE role = 'admin' ORDER BY username");
    $admins = $adminsStmt->fetchAll();
    
    // Get distinct actions for filter dropdown
    $actionsStmt = $conn->query("SELECT DISTINCT action FROM admin_logs ORDER BY action");
    $actions = $actionsStmt->fetchAll();
    
    // Get distinct entity types for filter dropdown
    $entityTypesStmt = $conn->query("SELECT DISTINCT entity_type FROM admin_logs WHERE entity_type IS NOT NULL ORDER BY entity_type");
    $entityTypes = $entityTypesStmt->fetchAll();
    
    // Calculate total pages
    $totalPages = ceil($totalCount / $logsPerPage);
    
} catch (PDOException $e) {
    error_log("Fetch admin logs error: " . $e->getMessage());
    $error = "Error fetching admin logs.";
    $adminLogs = [];
    $totalCount = 0;
    $admins = [];
    $actions = [];
    $entityTypes = [];
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

/* Log Type Badges */
.badge-action {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.badge-admin {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.badge-entity {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.badge-system {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
}

/* JSON Viewer */
.json-viewer {
    background-color: #f8fafc;
    border-radius: 0.5rem;
    padding: 1rem;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 0.875rem;
    max-height: 200px;
    overflow-y: auto;
    white-space: pre-wrap;
    word-break: break-all;
}

/* Modal Backdrop Blur */
.modal-backdrop {
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}
</style>

<!-- Main Container -->
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="mx-auto">
        
        <!-- Page Header -->
        <div class="mb-8 animate-slide-down">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 flex items-center gap-3">
                        <div class="p-3 bg-indigo-600 rounded-xl shadow-lg">
                            <i class="ri-file-list-line text-white text-2xl"></i>
                        </div>
                        <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                            Admin Activity
                        </span>
                    </h1>
                    <p class="mt-2 text-gray-600 text-sm sm:text-base flex items-center gap-2">
                        <i class="ri-information-line"></i>
                        Monitor all admin activities and actions
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-4 py-2 bg-white rounded-lg shadow-md border border-gray-200 text-sm font-semibold text-gray-700">
                        <i class="ri-file-list-line text-indigo-600"></i> Total: <span class="text-indigo-600"><?php echo $totalCount; ?></span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Alerts -->
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
        
        <!-- Success Message -->
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
                    <form method="GET" class="space-y-4" id="filterForm">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                            <!-- Action Filter -->
                            <div class="group">
                                <label for="action" class="block mb-2 text-sm font-semibold text-gray-700 flex items-center gap-2">
                                    <i class="ri-list-check text-indigo-600"></i>
                                    Action
                                </label>
                                <div class="relative">
                                    <select 
                                        id="action" 
                                        name="action" 
                                        class="w-full pl-11 pr-4 py-3 border-2 border-gray-300 rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 hover:border-indigo-400 appearance-none bg-white cursor-pointer">
                                        <option value="">All Actions</option>
                                        <?php foreach ($actions as $action): ?>
                                        <option value="<?php echo htmlspecialchars($action['action']); ?>" <?php echo $actionFilter === $action['action'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($action['action']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class="ri-list-check absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <i class="ri-arrow-down-s-line absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                            
                            <!-- Admin Filter -->
                            <div class="group">
                                <label for="admin_id" class="block mb-2 text-sm font-semibold text-gray-700 flex items-center gap-2">
                                    <i class="ri-user-line text-indigo-600"></i>
                                    Admin
                                </label>
                                <div class="relative">
                                    <select 
                                        id="admin_id" 
                                        name="admin_id" 
                                        class="w-full pl-11 pr-4 py-3 border-2 border-gray-300 rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 hover:border-indigo-400 appearance-none bg-white cursor-pointer">
                                        <option value="">All Admins</option>
                                        <?php foreach ($admins as $admin): ?>
                                        <option value="<?php echo $admin['id']; ?>" <?php echo $adminIdFilter == $admin['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($admin['username'] . ' (' . $admin['first_name'] . ' ' . $admin['last_name'] . ')'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class="ri-user-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <i class="ri-arrow-down-s-line absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                            
                            <!-- Entity Type Filter -->
                            <div class="group">
                                <label for="entity_type" class="block mb-2 text-sm font-semibold text-gray-700 flex items-center gap-2">
                                    <i class="ri-database-line text-indigo-600"></i>
                                    Entity Type
                                </label>
                                <div class="relative">
                                    <select 
                                        id="entity_type" 
                                        name="entity_type" 
                                        class="w-full pl-11 pr-4 py-3 border-2 border-gray-300 rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 hover:border-indigo-400 appearance-none bg-white cursor-pointer">
                                        <option value="">All Entities</option>
                                        <?php foreach ($entityTypes as $entityType): ?>
                                        <option value="<?php echo htmlspecialchars($entityType['entity_type']); ?>" <?php echo $entityTypeFilter === $entityType['entity_type'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($entityType['entity_type']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class="ri-database-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <i class="ri-arrow-down-s-line absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                            
                            <!-- Start Date -->
                            <div class="group">
                                <label for="start_date" class="block mb-2 text-sm font-semibold text-gray-700 flex items-center gap-2">
                                    <i class="ri-calendar-line text-indigo-600"></i>
                                    Start Date
                                </label>
                                <div class="relative">
                                    <input 
                                        type="date" 
                                        id="start_date" 
                                        name="start_date" 
                                        class="w-full pl-11 pr-4 py-3 border-2 border-gray-300 rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 hover:border-indigo-400" 
                                        value="<?php echo htmlspecialchars($startDate); ?>">
                                    <i class="ri-calendar-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                            
                            <!-- End Date -->
                            <div class="group">
                                <label for="end_date" class="block mb-2 text-sm font-semibold text-gray-700 flex items-center gap-2">
                                    <i class="ri-calendar-line text-indigo-600"></i>
                                    End Date
                                </label>
                                <div class="relative">
                                    <input 
                                        type="date" 
                                        id="end_date" 
                                        name="end_date" 
                                        class="w-full pl-11 pr-4 py-3 border-2 border-gray-300 rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 hover:border-indigo-400" 
                                        value="<?php echo htmlspecialchars($endDate); ?>">
                                    <i class="ri-calendar-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex items-end gap-2">
                                <button 
                                    type="submit" 
                                    class="btn-ripple flex-1 px-4 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl transition-all duration-300 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-semibold">
                                    <i class="ri-search-line text-lg"></i>                                    
                                </button>
                                <a 
                                    href="admin_activity.php" 
                                    class="btn-ripple flex-1 px-4 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition-all duration-300 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-semibold">
                                    <i class="ri-refresh-line text-lg"></i>
                                </a>
                                <button 
                                    type="button" 
                                    id="clearLogs" 
                                    class="btn-ripple flex-1 px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl transition-all duration-300 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-semibold">
                                    <i class="ri-delete-bin-line text-lg"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Logs Table Card -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden animate-fade-in">
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-5 border-b border-gray-200">
                <div class="flex flex-wrap justify-between items-center gap-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-100 rounded-lg">
                            <i class="ri-file-list-line text-indigo-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Admin Activity Logs</h3>
                            <p class="text-sm text-gray-600"><?php echo $totalCount; ?> total logs found</p>
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
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Admin</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Action</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Entity</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Details</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">IP Address</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($adminLogs)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="mb-4 p-4 bg-gray-100 rounded-full">
                                        <i class="ri-file-list-line text-6xl text-gray-400"></i>
                                    </div>
                                    <h4 class="text-xl font-bold text-gray-700 mb-2">No Logs Found</h4>
                                    <p class="text-gray-500 text-sm">Try adjusting your filters</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($adminLogs as $log): ?>
                        <tr class="table-row-hover">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900">#<?php echo htmlspecialchars($log['id']); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold shadow-lg">
                                            <?php 
                                            $name = $log['first_name'] . ' ' . $log['last_name'];
                                            echo strtoupper(substr($name, 0, 1) . substr($name, strpos($name, ' ') + 1, 1)); 
                                            ?>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?>
                                        </div>
                                        <div class="text-xs text-gray-500 flex items-center gap-1">
                                            <i class="ri-at-line"></i>
                                            <?php echo htmlspecialchars($log['username']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="badge-action px-3 py-1.5 rounded-full text-xs font-bold inline-flex items-center gap-1.5 shadow-sm">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($log['entity_type'])): ?>
                                <div class="flex items-center gap-2">
                                    <span class="badge-entity px-2 py-1 rounded text-xs font-medium">
                                        <?php echo htmlspecialchars($log['entity_type']); ?>
                                    </span>
                                    <?php if (!empty($log['entity_id'])): ?>
                                    <span class="text-xs text-gray-500">(#<?php echo htmlspecialchars($log['entity_id']); ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <span class="text-xs text-gray-500">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if (!empty($log['details'])): ?>
                                <div class="text-sm text-gray-700 max-w-xs truncate" title="<?php echo htmlspecialchars($log['details']); ?>">
                                    <?php echo htmlspecialchars($log['details']); ?>
                                </div>
                                <?php elseif (!empty($log['old_values']) || !empty($log['new_values'])): ?>
                                <button 
                                    onclick="toggleJsonView('old_<?php echo $log['id']; ?>')" 
                                    class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                    View Changes
                                </button>
                                <div id="old_<?php echo $log['id']; ?>" class="json-viewer mt-2 hidden">
                                    <?php if (!empty($log['old_values'])): ?>
                                    <div class="mb-2">
                                        <strong>Old Values:</strong>
                                        <pre><?php echo htmlspecialchars(json_encode(json_decode($log['old_values']), JSON_PRETTY_PRINT)); ?></pre>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($log['new_values'])): ?>
                                    <div>
                                        <strong>New Values:</strong>
                                        <pre><?php echo htmlspecialchars(json_encode(json_decode($log['new_values']), JSON_PRETTY_PRINT)); ?></pre>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <span class="text-xs text-gray-500">No details</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <div class="flex items-center gap-1">
                                    <i class="ri-calendar-line text-gray-400"></i>
                                    <?php echo date('M j, Y H:i:s', strtotime($log['created_at'])); ?>
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
                <?php if (empty($adminLogs)): ?>
                <div class="text-center py-12">
                    <div class="flex flex-col items-center justify-center">
                        <div class="mb-4 p-4 bg-gray-100 rounded-full">
                            <i class="ri-file-list-line text-6xl text-gray-400"></i>
                        </div>
                        <h4 class="text-xl font-bold text-gray-700 mb-2">No Logs Found</h4>
                        <p class="text-gray-500 text-sm">Try adjusting your filters</p>
                    </div>
                </div>
                <?php else: ?>
                <?php foreach ($adminLogs as $log): ?>
                <div class="card-hover bg-white rounded-xl border-2 border-gray-200 overflow-hidden shadow-lg">
                    <!-- Card Header -->
                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 p-4 border-b border-gray-200">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 flex-1">
                                <div class="h-12 w-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold shadow-lg flex-shrink-0">
                                    <?php 
                                    $name = $log['first_name'] . ' ' . $log['last_name'];
                                    echo strtoupper(substr($name, 0, 1) . substr($name, strpos($name, ' ') + 1, 1)); 
                                    ?>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-bold text-gray-900 text-base truncate">
                                        <?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?>
                                    </h4>
                                    <p class="text-sm text-gray-600 flex items-center gap-1">
                                        <i class="ri-at-line text-xs"></i>
                                        <span class="truncate"><?php echo htmlspecialchars($log['username']); ?></span>
                                    </p>
                                </div>
                            </div>
                            <span class="badge-action px-2.5 py-1 rounded-full text-xs font-bold shadow-sm whitespace-nowrap">
                                <?php echo htmlspecialchars($log['action']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Card Body -->
                    <div class="p-4 space-y-3">
                        <?php if (!empty($log['entity_type'])): ?>
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <i class="ri-database-line text-purple-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Entity</p>
                                <p class="text-sm text-gray-900 font-medium">
                                    <?php echo htmlspecialchars($log['entity_type']); ?>
                                    <?php if (!empty($log['entity_id'])): ?>
                                    (#<?php echo htmlspecialchars($log['entity_id']); ?>)
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($log['details'])): ?>
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <i class="ri-file-text-line text-blue-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Details</p>
                                <p class="text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($log['details']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <i class="ri-ip-line text-blue-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">IP Address</p>
                                <p class="text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <i class="ri-calendar-line text-green-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Date</p>
                                <p class="text-sm text-gray-900 font-medium"><?php echo date('M j, Y H:i:s', strtotime($log['created_at'])); ?></p>
                            </div>
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

<!-- Clear Logs Confirmation Modal -->
<div id="clearLogsModal" class="hidden fixed inset-0 z-50 overflow-y-auto modal-backdrop" style="background-color: rgba(0, 0, 0, 0.75);">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 mt-14">
        <div class="fixed inset-0 transition-opacity" onclick="closeModal('clearLogsModal')"></div>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full animate-scale-in">
            <div class="bg-gradient-to-r from-red-600 to-rose-600 px-6 py-5">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-white/20 rounded-xl">
                        <i class="ri-delete-bin-line text-white text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white">Clear Admin Logs</h3>
                </div>
            </div>
            
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4 animate-shake">
                        <i class="ri-alarm-warning-line text-4xl text-red-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Are you absolutely sure?</h3>
                    <p class="text-sm text-gray-600 mb-3">You are about to clear all admin activity logs.</p>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                        <div class="flex items-start">
                            <i class="ri-error-warning-line text-red-600 text-xl mr-3 flex-shrink-0 mt-0.5"></i>
                            <div class="text-left">
                                <p class="text-sm font-semibold text-red-800">Warning: This action cannot be undone!</p>
                                <p class="text-xs text-red-700 mt-1">All admin activity logs will be permanently deleted from the system.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-3">
                    <button 
                        type="button" 
                        onclick="closeModal('clearLogsModal')" 
                        class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        Cancel
                    </button>
                    <button 
                        id="confirmClearLogsBtn" 
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl text-center">
                        Clear Logs
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle JSON view
    function toggleJsonView(id) {
        const element = document.getElementById(id);
        if (element) {
            element.classList.toggle('hidden');
        }
    }
    
    // Modal Management
    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    
    // Clear logs functionality
    document.getElementById('clearLogs').addEventListener('click', function() {
        openModal('clearLogsModal');
    });
    
    // Confirm clear logs
    document.getElementById('confirmClearLogsBtn').addEventListener('click', function() {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'clear_logs';
        input.value = '1';
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    });
    
    // Close modals on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal('clearLogsModal');
        }
    });
    
    // Close modal when clicking outside
    document.getElementById('clearLogsModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal('clearLogsModal');
        }
    });
</script>

<?php include '../includes/admin_footer.php'; ?>