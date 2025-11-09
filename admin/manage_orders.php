<?php
session_start();
include '../config/db.con.php';

// Set page variables
$page_title = 'Manage Orders';
$active_page = 'orders';

// Ensure only admins can access this page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Pagination variables
$ordersPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $ordersPerPage;

// Search and filter variables
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Add Order
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $client_id = validateInteger($_POST['client_id']);
        $freelancer_id = validateInteger($_POST['freelancer_id']);
        $gig_id = validateInteger($_POST['gig_id']);
        $amount = floatval($_POST['amount']);
        $status = sanitizeInput($_POST['status']);
        
        // Generate order number
        $order_number = 'ORD-' . strtoupper(uniqid());
        
        // Validate required fields
        if (!$client_id || !$freelancer_id || !$gig_id || $amount <= 0) {
            $error = "All fields are required and amount must be greater than 0.";
        } elseif (!in_array($status, ['pending', 'in_progress', 'delivered', 'completed', 'cancelled', 'disputed'])) {
            $error = "Invalid status selected.";
        } else {
            try {
                // Calculate total amount (you can add fee calculation here)
                $total_amount = $amount;
                
                // Insert new order
                $stmt = $conn->prepare("INSERT INTO orders (order_number, client_id, freelancer_id, gig_id, amount, total_amount, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$order_number, $client_id, $freelancer_id, $gig_id, $amount, $total_amount, $status]);
                $success = "Order added successfully.";
            } catch (PDOException $e) {
                error_log("Add order error: " . $e->getMessage());
                $error = "Error adding order.";
            }
        }
    }
    
    // Handle Edit Order
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = validateInteger($_POST['id']);
        $amount = floatval($_POST['amount']);
        $status = sanitizeInput($_POST['status']);
        
        // Validate required fields
        if (!$id || $amount <= 0) {
            $error = "All fields are required and amount must be greater than 0.";
        } elseif (!in_array($status, ['pending', 'in_progress', 'delivered', 'completed', 'cancelled', 'disputed'])) {
            $error = "Invalid status selected.";
        } else {
            try {
                // Calculate total amount
                $total_amount = $amount;
                
                // Update order
                $stmt = $conn->prepare("UPDATE orders SET amount = ?, total_amount = ?, status = ? WHERE id = ?");
                $stmt->execute([$amount, $total_amount, $status, $id]);
                $success = "Order updated successfully.";
            } catch (PDOException $e) {
                error_log("Edit order error: " . $e->getMessage());
                $error = "Error updating order.";
            }
        }
    }
}

// Handle order status update
if (isset($_GET['update_status'])) {
    $id = validateInteger($_GET['update_status']);
    $status = $_GET['status'] ?? '';
    
    if ($id && in_array($status, ['pending', 'in_progress', 'delivered', 'completed', 'cancelled', 'disputed'])) {
        try {
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            $success = "Order status updated successfully.";
        } catch (PDOException $e) {
            error_log("Update order status error: " . $e->getMessage());
            $error = "Error updating order status.";
        }
    }
}

// Handle order deletion
if (isset($_GET['delete'])) {
    $id = validateInteger($_GET['delete']);
    
    if ($id) {
        try {
            // Get order info
            $stmt = $conn->prepare("SELECT order_number FROM orders WHERE id = ?");
            $stmt->execute([$id]);
            $orderToDelete = $stmt->fetch();
            
            if ($orderToDelete) {
                // Delete the order
                $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Order '" . htmlspecialchars($orderToDelete['order_number']) . "' deleted successfully.";
            } else {
                $error = "Order not found.";
            }
        } catch (PDOException $e) {
            error_log("Delete order error: " . $e->getMessage());
            $error = "Error deleting order. The order may have associated data.";
        }
    }
}

// Build query with filters
$orders = [];
$totalOrders = 0;
$totalPages = 1;

try {
    // Base query
    $query = "SELECT o.id, o.order_number, o.status, o.amount, o.total_amount, o.created_at,
                     c.username as client_username, c.first_name as client_first_name, c.last_name as client_last_name,
                     f.username as freelancer_username, f.first_name as freelancer_first_name, f.last_name as freelancer_last_name,
                     g.title as gig_title, o.client_id, o.freelancer_id, o.gig_id
              FROM orders o
              JOIN users c ON o.client_id = c.id
              JOIN users f ON o.freelancer_id = f.id
              JOIN gigs g ON o.gig_id = g.id";
    
    $countQuery = "SELECT COUNT(*) FROM orders o
                   JOIN users c ON o.client_id = c.id
                   JOIN users f ON o.freelancer_id = f.id
                   JOIN gigs g ON o.gig_id = g.id";
    
    // Add filters
    $params = [];
    $whereClause = "";
    
    if (!empty($search)) {
        $whereClause .= (!empty($whereClause) ? " AND " : "") . "(o.order_number LIKE ? OR c.username LIKE ? OR f.username LIKE ? OR g.title LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    }
    
    if (!empty($statusFilter)) {
        $whereClause .= (!empty($whereClause) ? " AND " : "") . "o.status = ?";
        $params[] = $statusFilter;
    }
    
    if (!empty($whereClause)) {
        $query .= " WHERE " . $whereClause;
        $countQuery .= " WHERE " . $whereClause;
    }
    
    // Add ordering
    $query .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
    $params[] = (int)$ordersPerPage;
    $params[] = (int)$offset;
    
    // Get total count for pagination
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute(array_slice($params, 0, count($params) - 2)); // Remove LIMIT/OFFSET params
    $totalOrders = $countStmt->fetchColumn();
    $totalPages = ceil($totalOrders / $ordersPerPage);
    
    // Get orders
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
    
    // Get clients for dropdown
    $clientsStmt = $conn->query("SELECT id, username, first_name, last_name FROM users WHERE role = 'client' AND status = 'active' ORDER BY username");
    $clients = $clientsStmt->fetchAll();
    
    // Get freelancers for dropdown
    $freelancersStmt = $conn->query("SELECT id, username, first_name, last_name FROM users WHERE role = 'freelancer' AND status = 'active' ORDER BY username");
    $freelancers = $freelancersStmt->fetchAll();
    
    // Get gigs for dropdown
    $gigsStmt = $conn->query("SELECT id, title, price FROM gigs WHERE status = 'active' ORDER BY title");
    $gigs = $gigsStmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch orders error: " . $e->getMessage());
    $error = "Error fetching orders.";
    $orders = [];
    $totalOrders = 0;
    $clients = [];
    $freelancers = [];
    $gigs = [];
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
                        <div class="p-3 bg-orange-600 rounded-xl shadow-lg">
                            <i class="ri-shopping-cart-line text-white text-2xl"></i>
                        </div>
                        <span class="bg-gradient-to-r from-orange-600 to-red-600 bg-clip-text text-transparent">
                            Order Management
                        </span>
                    </h1>
                    <p class="mt-2 text-gray-600 text-sm sm:text-base flex items-center gap-2">
                        <i class="ri-information-line"></i>
                        Manage and monitor all orders
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-4 py-2 bg-white rounded-lg shadow-md border border-gray-200 text-sm font-semibold text-gray-700">
                        <i class="ri-shopping-cart-line text-orange-600"></i> Total: <span class="text-orange-600"><?php echo $totalOrders; ?></span>
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
                <div class="bg-gradient-to-r from-orange-600 to-red-600 px-6 py-4">
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
                                    <i class="ri-search-line text-orange-600"></i>
                                    Search Orders
                                </label>
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        id="search" 
                                        name="search" 
                                        class="w-full pl-11 pr-4 py-3 border-2 border-gray-300 rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-orange-100 focus:border-orange-500 hover:border-orange-400" 
                                        placeholder="Order number, client, freelancer..." 
                                        value="<?php echo htmlspecialchars($search); ?>">
                                    <i class="ri-search-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                            
                            <!-- Status Filter -->
                            <div class="group">
                                <label for="status" class="block mb-2 text-sm font-semibold text-gray-700 flex items-center gap-2">
                                    <i class="ri-toggle-line text-orange-600"></i>
                                    Order Status
                                </label>
                                <div class="relative">
                                    <select 
                                        id="status" 
                                        name="status" 
                                        class="w-full pl-11 pr-4 py-3 border-2 border-gray-300 rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-orange-100 focus:border-orange-500 hover:border-orange-400 appearance-none bg-white cursor-pointer">
                                        <option value="">All Statuses</option>
                                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>⏳ Pending</option>
                                        <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>🔄 In Progress</option>
                                        <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>📦 Delivered</option>
                                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>✅ Completed</option>
                                        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>❌ Cancelled</option>
                                        <option value="disputed" <?php echo $statusFilter === 'disputed' ? 'selected' : ''; ?>>⚠️ Disputed</option>
                                    </select>
                                    <i class="ri-toggle-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <i class="ri-arrow-down-s-line absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex gap-3 pt-2">
                            <button 
                                type="submit" 
                                class="btn-ripple flex-1 sm:flex-none px-6 py-3 bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 text-white rounded-xl transition-all duration-300 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-semibold">
                                <i class="ri-search-line text-lg"></i>
                                <span>Apply Filters</span>
                            </button>
                            <a 
                                href="manage_orders.php" 
                                class="btn-ripple flex-1 sm:flex-none px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition-all duration-300 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-semibold">
                                <i class="ri-refresh-line text-lg"></i>
                                <span>Reset</span>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Orders Table Card -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden animate-fade-in">
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-5 border-b border-gray-200">
                <div class="flex flex-wrap justify-between items-center gap-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-orange-100 rounded-lg">
                            <i class="ri-shopping-cart-line text-orange-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">All Orders</h3>
                            <p class="text-sm text-gray-600"><?php echo $totalOrders; ?> total orders found</p>
                        </div>
                    </div>
                    <button 
                        id="addOrderBtn" 
                        class="btn-ripple px-6 py-3 bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 text-white rounded-xl transition-all duration-300 flex items-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-semibold">
                        <i class="ri-add-line text-lg"></i>
                        <span>Add New Order</span>
                    </button>
                </div>
            </div>
            
            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Order #</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Freelancer</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Gig</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="mb-4 p-4 bg-gray-100 rounded-full">
                                        <i class="ri-shopping-cart-line text-6xl text-gray-400"></i>
                                    </div>
                                    <h4 class="text-xl font-bold text-gray-700 mb-2">No Orders Found</h4>
                                    <p class="text-gray-500 text-sm">Try adjusting your filters or search terms</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <tr class="table-row-hover">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900">#<?php echo htmlspecialchars($order['id']); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900">
                                    <?php echo htmlspecialchars($order['order_number']); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <i class="ri-calendar-line"></i>
                                    <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="text-sm text-gray-900 font-medium">
                                    <?php echo htmlspecialchars($order['client_first_name'] . ' ' . $order['client_last_name']); ?>
                                </div>
                                <div class="text-xs text-gray-500">@<?php echo htmlspecialchars($order['client_username']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="text-sm text-gray-900 font-medium">
                                    <?php echo htmlspecialchars($order['freelancer_first_name'] . ' ' . $order['freelancer_last_name']); ?>
                                </div>
                                <div class="text-xs text-gray-500">@<?php echo htmlspecialchars($order['freelancer_username']); ?></div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="text-sm text-gray-900 max-w-xs truncate">
                                    <?php echo htmlspecialchars($order['gig_title']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-sm font-bold text-orange-600">
                                    $<?php echo number_format($order['total_amount'], 2); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="<?php 
                                    switch ($order['status']) {
                                        case 'pending': echo 'bg-yellow-100 text-yellow-800 border border-yellow-200'; break;
                                        case 'in_progress': echo 'bg-blue-100 text-blue-800 border border-blue-200'; break;
                                        case 'delivered': echo 'bg-indigo-100 text-indigo-800 border border-indigo-200'; break;
                                        case 'completed': echo 'bg-green-100 text-green-800 border border-green-200'; break;
                                        case 'cancelled': echo 'bg-red-100 text-red-800 border border-red-200'; break;
                                        case 'disputed': echo 'bg-purple-100 text-purple-800 border border-purple-200'; break;
                                        default: echo 'bg-gray-100 text-gray-800 border border-gray-200';
                                    }
                                ?> px-3 py-1.5 rounded-full text-xs font-bold shadow-sm capitalize">
                                    <?php 
                                    switch ($order['status']) {
                                        case 'pending': echo '⏳ Pending'; break;
                                        case 'in_progress': echo '🔄 In Progress'; break;
                                        case 'delivered': echo '📦 Delivered'; break;
                                        case 'completed': echo '✅ Completed'; break;
                                        case 'cancelled': echo '❌ Cancelled'; break;
                                        case 'disputed': echo '⚠️ Disputed'; break;
                                        default: echo htmlspecialchars($order['status']);
                                    }
                                    ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-2 flex-wrap">
                                    <button 
                                        onclick='editOrder(<?php echo json_encode([
                                            "id" => $order["id"],
                                            "order_number" => $order["order_number"],
                                            "amount" => $order["amount"],
                                            "status" => $order["status"]
                                        ]); ?>)'
                                        class="p-2 bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white border border-indigo-200 hover:border-indigo-600 rounded-lg transition-all duration-200 transform hover:scale-110 shadow-sm hover:shadow-md"
                                        title="Edit Order">
                                        <i class="ri-edit-line text-sm"></i>
                                    </button>
                                    <button 
                                        onclick="confirmDelete(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['order_number'], ENT_QUOTES); ?>')"
                                        class="p-2 bg-red-50 hover:bg-red-600 text-red-700 hover:text-white border border-red-200 hover:border-red-600 rounded-lg transition-all duration-200 transform hover:scale-110 shadow-sm hover:shadow-md"
                                        title="Delete Order">
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
                <?php if (empty($orders)): ?>
                <div class="text-center py-12">
                    <div class="flex flex-col items-center justify-center">
                        <div class="mb-4 p-4 bg-gray-100 rounded-full">
                            <i class="ri-shopping-cart-line text-6xl text-gray-400"></i>
                        </div>
                        <h4 class="text-xl font-bold text-gray-700 mb-2">No Orders Found</h4>
                        <p class="text-gray-500 text-sm">Try adjusting your filters</p>
                    </div>
                </div>
                <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <div class="card-hover bg-white rounded-xl border-2 border-gray-200 overflow-hidden shadow-lg">
                    <!-- Card Header -->
                    <div class="bg-gradient-to-r from-orange-50 to-red-50 p-4 border-b border-gray-200">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-gray-900 text-base mb-1">
                                    <?php echo htmlspecialchars($order['order_number']); ?>
                                </h4>
                                <p class="text-sm text-gray-600">
                                    <i class="ri-calendar-line text-xs"></i>
                                    <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                </p>
                            </div>
                            <span class="<?php 
                                switch ($order['status']) {
                                    case 'pending': echo 'bg-yellow-100 text-yellow-800 border border-yellow-200'; break;
                                    case 'in_progress': echo 'bg-blue-100 text-blue-800 border border-blue-200'; break;
                                    case 'delivered': echo 'bg-indigo-100 text-indigo-800 border border-indigo-200'; break;
                                    case 'completed': echo 'bg-green-100 text-green-800 border border-green-200'; break;
                                    case 'cancelled': echo 'bg-red-100 text-red-800 border border-red-200'; break;
                                    case 'disputed': echo 'bg-purple-100 text-purple-800 border border-purple-200'; break;
                                    default: echo 'bg-gray-100 text-gray-800 border border-gray-200';
                                }
                            ?> px-2.5 py-1 rounded-full text-xs font-bold shadow-sm whitespace-nowrap capitalize">
                                <?php 
                                switch ($order['status']) {
                                    case 'pending': echo '⏳'; break;
                                    case 'in_progress': echo '🔄'; break;
                                    case 'delivered': echo '📦'; break;
                                    case 'completed': echo '✅'; break;
                                    case 'cancelled': echo '❌'; break;
                                    case 'disputed': echo '⚠️'; break;
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Card Body -->
                    <div class="p-4 space-y-3">
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <i class="ri-user-line text-blue-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Client</p>
                                <p class="text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($order['client_first_name'] . ' ' . $order['client_last_name']); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <i class="ri-user-star-line text-purple-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Freelancer</p>
                                <p class="text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($order['freelancer_first_name'] . ' ' . $order['freelancer_last_name']); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <i class="ri-stack-line text-green-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Gig</p>
                                <p class="text-sm text-gray-900 font-medium truncate"><?php echo htmlspecialchars($order['gig_title']); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="p-2 bg-orange-100 rounded-lg">
                                <i class="ri-money-dollar-circle-line text-orange-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Amount</p>
                                <p class="text-sm text-orange-600 font-bold">$<?php echo number_format($order['total_amount'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card Footer -->
                    <div class="p-4 bg-gray-50 border-t border-gray-200">
                        <div class="grid grid-cols-2 gap-2">
                            <button 
                                onclick='editOrder(<?php echo json_encode([
                                    "id" => $order["id"],
                                    "order_number" => $order["order_number"],
                                    "amount" => $order["amount"],
                                    "status" => $order["status"]
                                ]); ?>)'
                                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-all duration-200 flex items-center justify-center gap-2 font-semibold text-sm shadow-md hover:shadow-lg">
                                <i class="ri-edit-line"></i> Edit
                            </button>
                            <button 
                                onclick="confirmDelete(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['order_number'], ENT_QUOTES); ?>')"
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
                                class="px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-orange-600 hover:text-white hover:border-orange-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md">
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
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="hidden sm:block px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-orange-600 hover:text-white hover:border-orange-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md">1</a>
                            <?php if ($start > 2): ?>
                                <span class="hidden sm:block px-4 py-2 text-gray-400">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="px-4 py-2 bg-gradient-to-r from-orange-600 to-red-600 border-2 border-orange-600 text-white rounded-lg font-bold text-sm shadow-md"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="hidden sm:block px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-orange-600 hover:text-white hover:border-orange-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($end < $totalPages): ?>
                            <?php if ($end < $totalPages - 1): ?>
                                <span class="hidden sm:block px-4 py-2 text-gray-400">...</span>
                            <?php endif; ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>" class="hidden sm:block px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-orange-600 hover:text-white hover:border-orange-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md"><?php echo $totalPages; ?></a>
                        <?php endif; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a 
                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                class="px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-orange-600 hover:text-white hover:border-orange-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md">
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

<!-- Add Order Modal -->
<div id="addOrderModal" class="hidden fixed inset-0 z-50 overflow-y-auto modal-backdrop" style="background-color: rgba(0, 0, 0, 0.75);">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 mt-12">
        <div class="fixed inset-0 transition-opacity" onclick="closeModal('addOrderModal')"></div>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full animate-scale-in">
            <div class="bg-gradient-to-r from-orange-600 to-red-600 px-6 py-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="ri-add-line text-2xl"></i>
                        Add New Order
                    </h3>
                    <button onclick="closeModal('addOrderModal')" class="text-white hover:text-gray-200 transition-colors">
                        <i class="ri-close-line text-2xl"></i>
                    </button>
                </div>
            </div>
            
            <form method="POST" class="p-6 space-y-5">
                <input type="hidden" name="action" value="add">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-user-line text-orange-600"></i> Client
                    </label>
                    <select 
                        name="client_id" 
                        required 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-orange-100 focus:border-orange-500 transition-all duration-200">
                        <option value="">Select Client</option>
                        <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['id']; ?>">
                            <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name'] . ' (@' . $client['username'] . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-user-star-line text-orange-600"></i> Freelancer
                    </label>
                    <select 
                        name="freelancer_id" 
                        required 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-orange-100 focus:border-orange-500 transition-all duration-200">
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
                        <i class="ri-stack-line text-orange-600"></i> Gig
                    </label>
                    <select 
                        name="gig_id" 
                        id="add_gig_id"
                        required 
                        onchange="updateAmount('add_gig_id', 'add_amount')"
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-orange-100 focus:border-orange-500 transition-all duration-200">
                        <option value="">Select Gig</option>
                        <?php foreach ($gigs as $gig): ?>
                        <option value="<?php echo $gig['id']; ?>" data-price="<?php echo $gig['price']; ?>">
                            <?php echo htmlspecialchars($gig['title']) . ' - $' . number_format($gig['price'], 2); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-money-dollar-circle-line text-orange-600"></i> Amount ($)
                        </label>
                        <input 
                            type="number" 
                            id="add_amount"
                            name="amount" 
                            step="0.01" 
                            min="0" 
                            required 
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-orange-100 focus:border-orange-500 transition-all duration-200" 
                            placeholder="0.00">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-toggle-line text-orange-600"></i> Status
                        </label>
                        <select 
                            name="status" 
                            required 
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-orange-100 focus:border-orange-500 transition-all duration-200">
                            <option value="pending" selected>⏳ Pending</option>
                            <option value="in_progress">🔄 In Progress</option>
                            <option value="delivered">📦 Delivered</option>
                            <option value="completed">✅ Completed</option>
                            <option value="cancelled">❌ Cancelled</option>
                            <option value="disputed">⚠️ Disputed</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button 
                        type="button" 
                        onclick="closeModal('addOrderModal')" 
                        class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        Add Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Order Modal -->
<div id="editOrderModal" class="hidden fixed inset-0 z-50 overflow-y-auto modal-backdrop" style="background-color: rgba(0, 0, 0, 0.75);">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 mt-12">
        <div class="fixed inset-0 transition-opacity" onclick="closeModal('editOrderModal')"></div>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full animate-scale-in">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="ri-edit-line text-2xl"></i>
                        Edit Order
                    </h3>
                    <button onclick="closeModal('editOrderModal')" class="text-white hover:text-gray-200 transition-colors">
                        <i class="ri-close-line text-2xl"></i>
                    </button>
                </div>
            </div>
            
            <form method="POST" class="p-6 space-y-5">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-file-list-line text-indigo-600"></i> Order Number
                    </label>
                    <input 
                        type="text" 
                        id="edit_order_number" 
                        disabled
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl bg-gray-100 text-gray-600">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-money-dollar-circle-line text-indigo-600"></i> Amount ($)
                    </label>
                    <input 
                        type="number" 
                        id="edit_amount" 
                        name="amount" 
                        step="0.01" 
                        min="0" 
                        required 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition-all duration-200">
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
                        <option value="pending">⏳ Pending</option>
                        <option value="in_progress">🔄 In Progress</option>
                        <option value="delivered">📦 Delivered</option>
                        <option value="completed">✅ Completed</option>
                        <option value="cancelled">❌ Cancelled</option>
                        <option value="disputed">⚠️ Disputed</option>
                    </select>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button 
                        type="button" 
                        onclick="closeModal('editOrderModal')" 
                        class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        Update Order
                    </button>
                </div>
            </form>
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
                    <h3 class="text-xl font-bold text-white">Delete Order</h3>
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
                                <p class="text-xs text-red-700 mt-1">All order data will be permanently deleted from the system.</p>
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
                        Delete Order
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

// Update amount when gig is selected
function updateAmount(gigSelectId, amountInputId) {
    const gigSelect = document.getElementById(gigSelectId);
    const amountInput = document.getElementById(amountInputId);
    const selectedOption = gigSelect.options[gigSelect.selectedIndex];
    const price = selectedOption.getAttribute('data-price');
    
    if (price) {
        amountInput.value = parseFloat(price).toFixed(2);
    }
}

// Confirm Delete
function confirmDelete(orderId, orderNumber) {
    document.getElementById('deleteModalMessage').textContent = `You are about to delete the order "${orderNumber}".`;
    
    const urlParams = new URLSearchParams(window.location.search);
    const queryString = urlParams.toString();
    const url = `?delete=${orderId}${queryString ? '&' + queryString : ''}`;
    
    document.getElementById('confirmDeleteBtn').href = url;
    openModal('confirmDeleteModal');
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Add Order Button
    document.getElementById('addOrderBtn')?.addEventListener('click', function() {
        openModal('addOrderModal');
    });
    
    // Close on Escape Key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal('addOrderModal');
            closeModal('editOrderModal');
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

// Edit Order Function
function editOrder(orderData) {
    document.getElementById('edit_id').value = orderData.id;
    document.getElementById('edit_order_number').value = orderData.order_number;
    document.getElementById('edit_amount').value = orderData.amount;
    document.getElementById('edit_status').value = orderData.status;
    openModal('editOrderModal');
}
</script>

<?php include '../includes/admin_footer.php'; ?>