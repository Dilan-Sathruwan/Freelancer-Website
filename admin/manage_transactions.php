<?php
session_start();
include '../config/db.con.php';

// Set page variables
$page_title = 'Manage Transactions';
$active_page = 'transactions';

// Ensure only admins can access this page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Pagination variables
$transactionsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $transactionsPerPage;

// Search and filter variables
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$typeFilter = isset($_GET['type']) ? sanitizeInput($_GET['type']) : '';
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Add Transaction
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $user_id = validateInteger($_POST['user_id']);
        $transaction_type = sanitizeInput($_POST['transaction_type']);
        $amount = floatval($_POST['amount']);
        $currency = sanitizeInput($_POST['currency']);
        $payment_method = sanitizeInput($_POST['payment_method']);
        $status = sanitizeInput($_POST['status']);
        
        // Generate transaction ID
        $transaction_id = 'TXN-' . strtoupper(uniqid());
        
        // Validate required fields
        if (!$user_id || $amount <= 0) {
            $error = "All required fields must be filled and amount must be greater than 0.";
        } elseif (!in_array($transaction_type, ['deposit', 'withdrawal', 'payment', 'refund'])) {
            $error = "Invalid transaction type selected.";
        } elseif (!in_array($status, ['completed', 'pending', 'failed', 'cancelled'])) {
            $error = "Invalid status selected.";
        } else {
            try {
                // Insert new transaction
                $stmt = $conn->prepare("INSERT INTO transactions (transaction_id, user_id, transaction_type, amount, currency, payment_method, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$transaction_id, $user_id, $transaction_type, $amount, $currency, $payment_method, $status]);
                $success = "Transaction added successfully.";
            } catch (PDOException $e) {
                error_log("Add transaction error: " . $e->getMessage());
                $error = "Error adding transaction.";
            }
        }
    }
    
    // Handle Edit Transaction
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = validateInteger($_POST['id']);
        $transaction_type = sanitizeInput($_POST['transaction_type']);
        $amount = floatval($_POST['amount']);
        $currency = sanitizeInput($_POST['currency']);
        $payment_method = sanitizeInput($_POST['payment_method']);
        $status = sanitizeInput($_POST['status']);
        
        // Validate required fields
        if (!$id || $amount <= 0) {
            $error = "All required fields must be filled and amount must be greater than 0.";
        } elseif (!in_array($transaction_type, ['deposit', 'withdrawal', 'payment', 'refund'])) {
            $error = "Invalid transaction type selected.";
        } elseif (!in_array($status, ['completed', 'pending', 'failed', 'cancelled'])) {
            $error = "Invalid status selected.";
        } else {
            try {
                // Update transaction
                $stmt = $conn->prepare("UPDATE transactions SET transaction_type = ?, amount = ?, currency = ?, payment_method = ?, status = ? WHERE id = ?");
                $stmt->execute([$transaction_type, $amount, $currency, $payment_method, $status, $id]);
                $success = "Transaction updated successfully.";
            } catch (PDOException $e) {
                error_log("Edit transaction error: " . $e->getMessage());
                $error = "Error updating transaction.";
            }
        }
    }
}

// Handle transaction deletion
if (isset($_GET['delete'])) {
    $id = validateInteger($_GET['delete']);
    
    if ($id) {
        try {
            // Get transaction info
            $stmt = $conn->prepare("SELECT transaction_id FROM transactions WHERE id = ?");
            $stmt->execute([$id]);
            $transactionToDelete = $stmt->fetch();
            
            if ($transactionToDelete) {
                // Delete the transaction
                $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Transaction '" . htmlspecialchars($transactionToDelete['transaction_id']) . "' deleted successfully.";
            } else {
                $error = "Transaction not found.";
            }
        } catch (PDOException $e) {
            error_log("Delete transaction error: " . $e->getMessage());
            $error = "Error deleting transaction.";
        }
    }
}

// Build query with filters
$transactions = [];
$totalTransactions = 0;
$totalPages = 1;

try {
    // Base query
    $query = "SELECT t.id, t.transaction_id, t.transaction_type, t.amount, t.currency, t.status, t.payment_method, t.created_at,
                     t.user_id, u.username as user_username, u.first_name, u.last_name
              FROM transactions t
              JOIN users u ON t.user_id = u.id";
    
    $countQuery = "SELECT COUNT(*) FROM transactions t
                   JOIN users u ON t.user_id = u.id";
    
    // Add filters
    $params = [];
    $whereClause = "";
    
    if (!empty($search)) {
        $whereClause .= (!empty($whereClause) ? " AND " : "") . "(t.transaction_id LIKE ? OR u.username LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam]);
    }
    
    if (!empty($typeFilter)) {
        $whereClause .= (!empty($whereClause) ? " AND " : "") . "t.transaction_type = ?";
        $params[] = $typeFilter;
    }
    
    if (!empty($statusFilter)) {
        $whereClause .= (!empty($whereClause) ? " AND " : "") . "t.status = ?";
        $params[] = $statusFilter;
    }
    
    if (!empty($whereClause)) {
        $query .= " WHERE " . $whereClause;
        $countQuery .= " WHERE " . $whereClause;
    }
    
    // Add ordering
    $query .= " ORDER BY t.created_at DESC LIMIT ? OFFSET ?";
    $params[] = (int)$transactionsPerPage;
    $params[] = (int)$offset;
    
    // Get total count for pagination
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute(array_slice($params, 0, count($params) - 2)); // Remove LIMIT/OFFSET params
    $totalTransactions = $countStmt->fetchColumn();
    $totalPages = ceil($totalTransactions / $transactionsPerPage);
    
    // Get transactions
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
    
    // Get all users for dropdown
    $usersStmt = $conn->query("SELECT id, username, first_name, last_name FROM users WHERE status = 'active' ORDER BY username");
    $users = $usersStmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch transactions error: " . $e->getMessage());
    $error = "Error fetching transactions.";
    $transactions = [];
    $totalTransactions = 0;
    $users = [];
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
                        <div class="p-3 bg-emerald-600 rounded-xl shadow-lg">
                            <i class="ri-exchange-dollar-line text-white text-2xl"></i>
                        </div>
                        <span class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">
                            Transaction Management
                        </span>
                    </h1>
                    <p class="mt-2 text-gray-600 text-sm sm:text-base flex items-center gap-2">
                        <i class="ri-information-line"></i>
                        Monitor and manage all financial transactions
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-4 py-2 bg-white rounded-lg shadow-md border border-gray-200 text-sm font-semibold text-gray-700">
                        <i class="ri-exchange-dollar-line text-emerald-600"></i> Total: <span class="text-emerald-600"><?php echo $totalTransactions; ?></span>
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
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-4">
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
                                    <i class="ri-search-line text-emerald-600"></i>
                                    Search Transactions
                                </label>
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        id="search" 
                                        name="search" 
                                        class="w-full pl-11 pr-4 py-3 border-2 border-gray-300 rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-emerald-100 focus:border-emerald-500 hover:border-emerald-400" 
                                        placeholder="Transaction ID, username..." 
                                        value="<?php echo htmlspecialchars($search); ?>">
                                    <i class="ri-search-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                            
                            <!-- Type Filter -->
                            <div class="group">
                                <label for="type" class="block mb-2 text-sm font-semibold text-gray-700 flex items-center gap-2">
                                    <i class="ri-exchange-line text-emerald-600"></i>
                                    Transaction Type
                                </label>
                                <div class="relative">
                                    <select 
                                        id="type" 
                                        name="type" 
                                        class="w-full pl-11 pr-4 py-3 border-2 border-gray-300 rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-emerald-100 focus:border-emerald-500 hover:border-emerald-400 appearance-none bg-white cursor-pointer">
                                        <option value="">All Types</option>
                                        <option value="deposit" <?php echo $typeFilter === 'deposit' ? 'selected' : ''; ?>>💰 Deposit</option>
                                        <option value="withdrawal" <?php echo $typeFilter === 'withdrawal' ? 'selected' : ''; ?>>💸 Withdrawal</option>
                                        <option value="payment" <?php echo $typeFilter === 'payment' ? 'selected' : ''; ?>>💳 Payment</option>
                                        <option value="refund" <?php echo $typeFilter === 'refund' ? 'selected' : ''; ?>>🔄 Refund</option>
                                    </select>
                                    <i class="ri-exchange-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <i class="ri-arrow-down-s-line absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                            
                            <!-- Status Filter -->
                            <div class="group">
                                <label for="status" class="block mb-2 text-sm font-semibold text-gray-700 flex items-center gap-2">
                                    <i class="ri-toggle-line text-emerald-600"></i>
                                    Transaction Status
                                </label>
                                <div class="relative">
                                    <select 
                                        id="status" 
                                        name="status" 
                                        class="w-full pl-11 pr-4 py-3 border-2 border-gray-300 rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-emerald-100 focus:border-emerald-500 hover:border-emerald-400 appearance-none bg-white cursor-pointer">
                                        <option value="">All Statuses</option>
                                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>✅ Completed</option>
                                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>⏳ Pending</option>
                                        <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>❌ Failed</option>
                                        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>🚫 Cancelled</option>
                                    </select>
                                    <i class="ri-toggle-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <i class="ri-arrow-down-s-line absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex gap-3 pt-2">
                            <button 
                                type="submit" 
                                class="btn-ripple flex-1 sm:flex-none px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl transition-all duration-300 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-semibold">
                                <i class="ri-search-line text-lg"></i>
                                <span>Apply Filters</span>
                            </button>
                            <a 
                                href="manage_transactions.php" 
                                class="btn-ripple flex-1 sm:flex-none px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition-all duration-300 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-semibold">
                                <i class="ri-refresh-line text-lg"></i>
                                <span>Reset</span>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Transactions Table Card -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden animate-fade-in">
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-5 border-b border-gray-200">
                <div class="flex flex-wrap justify-between items-center gap-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-emerald-100 rounded-lg">
                            <i class="ri-exchange-dollar-line text-emerald-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">All Transactions</h3>
                            <p class="text-sm text-gray-600"><?php echo $totalTransactions; ?> total transactions found</p>
                        </div>
                    </div>
                    <button 
                        id="addTransactionBtn" 
                        class="btn-ripple px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl transition-all duration-300 flex items-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-semibold">
                        <i class="ri-add-line text-lg"></i>
                        <span>Add New Transaction</span>
                    </button>
                </div>
            </div>
            
            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Transaction ID</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">User</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Payment</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="mb-4 p-4 bg-gray-100 rounded-full">
                                        <i class="ri-exchange-dollar-line text-6xl text-gray-400"></i>
                                    </div>
                                    <h4 class="text-xl font-bold text-gray-700 mb-2">No Transactions Found</h4>
                                    <p class="text-gray-500 text-sm">Try adjusting your filters or search terms</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($transactions as $transaction): ?>
                        <tr class="table-row-hover">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900">#<?php echo htmlspecialchars($transaction['id']); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <i class="ri-file-text-line text-emerald-600"></i>
                                    <span class="text-sm font-mono font-semibold text-gray-900">
                                        <?php echo htmlspecialchars($transaction['transaction_id']); ?>
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500 flex items-center gap-1 mt-1">
                                    <i class="ri-calendar-line"></i>
                                    <?php echo date('M j, Y H:i', strtotime($transaction['created_at'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="text-sm text-gray-900 font-medium">
                                    <?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?>
                                </div>
                                <div class="text-xs text-gray-500">@<?php echo htmlspecialchars($transaction['user_username']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="<?php 
                                    switch ($transaction['transaction_type']) {
                                        case 'deposit': echo 'bg-green-100 text-green-800 border border-green-200'; break;
                                        case 'withdrawal': echo 'bg-yellow-100 text-yellow-800 border border-yellow-200'; break;
                                        case 'payment': echo 'bg-blue-100 text-blue-800 border border-blue-200'; break;
                                        case 'refund': echo 'bg-purple-100 text-purple-800 border border-purple-200'; break;
                                        default: echo 'bg-gray-100 text-gray-800 border border-gray-200';
                                    }
                                ?> px-3 py-1.5 rounded-full text-xs font-bold shadow-sm capitalize">
                                    <?php 
                                    switch ($transaction['transaction_type']) {
                                        case 'deposit': echo '💰 Deposit'; break;
                                        case 'withdrawal': echo '💸 Withdrawal'; break;
                                        case 'payment': echo '💳 Payment'; break;
                                        case 'refund': echo '🔄 Refund'; break;
                                        default: echo htmlspecialchars($transaction['transaction_type']);
                                    }
                                    ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="text-sm font-bold text-emerald-600">
                                    <?php echo htmlspecialchars($transaction['currency']); ?> <?php echo number_format($transaction['amount'], 2); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-sm text-gray-900 capitalize">
                                    <?php echo htmlspecialchars($transaction['payment_method']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="<?php 
                                    switch ($transaction['status']) {
                                        case 'completed': echo 'bg-green-100 text-green-800 border border-green-200'; break;
                                        case 'pending': echo 'bg-yellow-100 text-yellow-800 border border-yellow-200'; break;
                                        case 'failed': echo 'bg-red-100 text-red-800 border border-red-200'; break;
                                        case 'cancelled': echo 'bg-gray-100 text-gray-800 border border-gray-200'; break;
                                        default: echo 'bg-gray-100 text-gray-800 border border-gray-200';
                                    }
                                ?> px-3 py-1.5 rounded-full text-xs font-bold shadow-sm capitalize">
                                    <?php 
                                    switch ($transaction['status']) {
                                        case 'completed': echo '✅ Completed'; break;
                                        case 'pending': echo '⏳ Pending'; break;
                                        case 'failed': echo '❌ Failed'; break;
                                        case 'cancelled': echo '🚫 Cancelled'; break;
                                        default: echo htmlspecialchars($transaction['status']);
                                    }
                                    ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-2 flex-wrap">
                                    <button 
                                        onclick='editTransaction(<?php echo json_encode([
                                            "id" => $transaction["id"],
                                            "transaction_id" => $transaction["transaction_id"],
                                            "transaction_type" => $transaction["transaction_type"],
                                            "amount" => $transaction["amount"],
                                            "currency" => $transaction["currency"],
                                            "payment_method" => $transaction["payment_method"],
                                            "status" => $transaction["status"]
                                        ]); ?>)'
                                        class="p-2 bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white border border-indigo-200 hover:border-indigo-600 rounded-lg transition-all duration-200 transform hover:scale-110 shadow-sm hover:shadow-md"
                                        title="Edit Transaction">
                                        <i class="ri-edit-line text-sm"></i>
                                    </button>
                                    <button 
                                        onclick="confirmDelete(<?php echo $transaction['id']; ?>, '<?php echo htmlspecialchars($transaction['transaction_id'], ENT_QUOTES); ?>')"
                                        class="p-2 bg-red-50 hover:bg-red-600 text-red-700 hover:text-white border border-red-200 hover:border-red-600 rounded-lg transition-all duration-200 transform hover:scale-110 shadow-sm hover:shadow-md"
                                        title="Delete Transaction">
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
                <?php if (empty($transactions)): ?>
                <div class="text-center py-12">
                    <div class="flex flex-col items-center justify-center">
                        <div class="mb-4 p-4 bg-gray-100 rounded-full">
                            <i class="ri-exchange-dollar-line text-6xl text-gray-400"></i>
                        </div>
                        <h4 class="text-xl font-bold text-gray-700 mb-2">No Transactions Found</h4>
                        <p class="text-gray-500 text-sm">Try adjusting your filters</p>
                    </div>
                </div>
                <?php else: ?>
                <?php foreach ($transactions as $transaction): ?>
                <div class="card-hover bg-white rounded-xl border-2 border-gray-200 overflow-hidden shadow-lg">
                    <!-- Card Header -->
                    <div class="bg-gradient-to-r from-emerald-50 to-teal-50 p-4 border-b border-gray-200">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-gray-900 text-base font-mono mb-1">
                                    <?php echo htmlspecialchars($transaction['transaction_id']); ?>
                                </h4>
                                <p class="text-sm text-gray-600">
                                    <i class="ri-user-line text-xs"></i>
                                    <?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?>
                                </p>
                            </div>
                            <div class="flex flex-col gap-2 items-end flex-shrink-0">
                                <span class="<?php 
                                    switch ($transaction['transaction_type']) {
                                        case 'deposit': echo 'bg-green-100 text-green-800 border border-green-200'; break;
                                        case 'withdrawal': echo 'bg-yellow-100 text-yellow-800 border border-yellow-200'; break;
                                        case 'payment': echo 'bg-blue-100 text-blue-800 border border-blue-200'; break;
                                        case 'refund': echo 'bg-purple-100 text-purple-800 border border-purple-200'; break;
                                        default: echo 'bg-gray-100 text-gray-800 border border-gray-200';
                                    }
                                ?> px-2.5 py-1 rounded-full text-xs font-bold shadow-sm whitespace-nowrap capitalize">
                                    <?php 
                                    switch ($transaction['transaction_type']) {
                                        case 'deposit': echo '💰'; break;
                                        case 'withdrawal': echo '💸'; break;
                                        case 'payment': echo '💳'; break;
                                        case 'refund': echo '🔄'; break;
                                    }
                                    ?>
                                    <?php echo htmlspecialchars($transaction['transaction_type']); ?>
                                </span>
                                <span class="<?php 
                                    switch ($transaction['status']) {
                                        case 'completed': echo 'bg-green-100 text-green-800 border border-green-200'; break;
                                        case 'pending': echo 'bg-yellow-100 text-yellow-800 border border-yellow-200'; break;
                                        case 'failed': echo 'bg-red-100 text-red-800 border border-red-200'; break;
                                        case 'cancelled': echo 'bg-gray-100 text-gray-800 border border-gray-200'; break;
                                        default: echo 'bg-gray-100 text-gray-800 border border-gray-200';
                                    }
                                ?> px-2.5 py-1 rounded-full text-xs font-bold shadow-sm whitespace-nowrap capitalize">
                                    <?php 
                                    switch ($transaction['status']) {
                                        case 'completed': echo '✅'; break;
                                        case 'pending': echo '⏳'; break;
                                        case 'failed': echo '❌'; break;
                                        case 'cancelled': echo '🚫'; break;
                                    }
                                    ?>
                                    <?php echo htmlspecialchars($transaction['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card Body -->
                    <div class="p-4 space-y-3">
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="p-2 bg-emerald-100 rounded-lg">
                                <i class="ri-money-dollar-circle-line text-emerald-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Amount</p>
                                <p class="text-sm text-emerald-600 font-bold">
                                    <?php echo htmlspecialchars($transaction['currency']); ?> <?php echo number_format($transaction['amount'], 2); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <i class="ri-bank-card-line text-blue-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Payment Method</p>
                                <p class="text-sm text-gray-900 font-medium capitalize"><?php echo htmlspecialchars($transaction['payment_method']); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <i class="ri-calendar-line text-purple-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Created</p>
                                <p class="text-sm text-gray-900 font-medium"><?php echo date('M j, Y H:i', strtotime($transaction['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card Footer -->
                    <div class="p-4 bg-gray-50 border-t border-gray-200">
                        <div class="grid grid-cols-2 gap-2">
                            <button 
                                onclick='editTransaction(<?php echo json_encode([
                                    "id" => $transaction["id"],
                                    "transaction_id" => $transaction["transaction_id"],
                                    "transaction_type" => $transaction["transaction_type"],
                                    "amount" => $transaction["amount"],
                                    "currency" => $transaction["currency"],
                                    "payment_method" => $transaction["payment_method"],
                                    "status" => $transaction["status"]
                                ]); ?>)'
                                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-all duration-200 flex items-center justify-center gap-2 font-semibold text-sm shadow-md hover:shadow-lg">
                                <i class="ri-edit-line"></i> Edit
                            </button>
                            <button 
                                onclick="confirmDelete(<?php echo $transaction['id']; ?>, '<?php echo htmlspecialchars($transaction['transaction_id'], ENT_QUOTES); ?>')"
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
                                class="px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-emerald-600 hover:text-white hover:border-emerald-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md">
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
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="hidden sm:block px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-emerald-600 hover:text-white hover:border-emerald-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md">1</a>
                            <?php if ($start > 2): ?>
                                <span class="hidden sm:block px-4 py-2 text-gray-400">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 border-2 border-emerald-600 text-white rounded-lg font-bold text-sm shadow-md"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="hidden sm:block px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-emerald-600 hover:text-white hover:border-emerald-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($end < $totalPages): ?>
                            <?php if ($end < $totalPages - 1): ?>
                                <span class="hidden sm:block px-4 py-2 text-gray-400">...</span>
                            <?php endif; ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>" class="hidden sm:block px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-emerald-600 hover:text-white hover:border-emerald-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md"><?php echo $totalPages; ?></a>
                        <?php endif; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a 
                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                class="px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-emerald-600 hover:text-white hover:border-emerald-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md">
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

<!-- Add Transaction Modal -->
<div id="addTransactionModal" class="hidden fixed inset-0 z-50 overflow-y-auto modal-backdrop" style="background-color: rgba(0, 0, 0, 0.75);">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 mt-12">
        <div class="fixed inset-0 transition-opacity" onclick="closeModal('addTransactionModal')"></div>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full animate-scale-in">
            <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="ri-add-line text-2xl"></i>
                        Add New Transaction
                    </h3>
                    <button onclick="closeModal('addTransactionModal')" class="text-white hover:text-gray-200 transition-colors">
                        <i class="ri-close-line text-2xl"></i>
                    </button>
                </div>
            </div>
            
            <form method="POST" class="p-6 space-y-5">
                <input type="hidden" name="action" value="add">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-user-line text-emerald-600"></i> User
                    </label>
                    <select 
                        name="user_id" 
                        required 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-emerald-100 focus:border-emerald-500 transition-all duration-200">
                        <option value="">Select User</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>">
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (@' . $user['username'] . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-exchange-line text-emerald-600"></i> Type
                        </label>
                        <select 
                            name="transaction_type" 
                            required 
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-emerald-100 focus:border-emerald-500 transition-all duration-200">
                            <option value="deposit">💰 Deposit</option>
                            <option value="withdrawal">💸 Withdrawal</option>
                            <option value="payment">💳 Payment</option>
                            <option value="refund">🔄 Refund</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-toggle-line text-emerald-600"></i> Status
                        </label>
                        <select 
                            name="status" 
                            required 
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-emerald-100 focus:border-emerald-500 transition-all duration-200">
                            <option value="completed">✅ Completed</option>
                            <option value="pending" selected>⏳ Pending</option>
                            <option value="failed">❌ Failed</option>
                            <option value="cancelled">🚫 Cancelled</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-money-dollar-circle-line text-emerald-600"></i> Amount
                        </label>
                        <input 
                            type="number" 
                            name="amount" 
                            step="0.01" 
                            min="0" 
                            required 
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-emerald-100 focus:border-emerald-500 transition-all duration-200" 
                            placeholder="0.00">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-currency-line text-emerald-600"></i> Currency
                        </label>
                        <select 
                            name="currency" 
                            required 
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-emerald-100 focus:border-emerald-500 transition-all duration-200">
                            <option value="USD" selected>USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                            <option value="BDT">BDT</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-bank-card-line text-emerald-600"></i> Payment Method
                    </label>
                    <select 
                        name="payment_method" 
                        required 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-emerald-100 focus:border-emerald-500 transition-all duration-200">
                        <option value="paypal">PayPal</option>
                        <option value="stripe">Stripe</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cash">Cash</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button 
                        type="button" 
                        onclick="closeModal('addTransactionModal')" 
                        class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        Add Transaction
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Transaction Modal -->
<div id="editTransactionModal" class="hidden fixed inset-0 z-50 overflow-y-auto modal-backdrop" style="background-color: rgba(0, 0, 0, 0.75);">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 mt-12">
        <div class="fixed inset-0 transition-opacity" onclick="closeModal('editTransactionModal')"></div>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full animate-scale-in">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="ri-edit-line text-2xl"></i>
                        Edit Transaction
                    </h3>
                    <button onclick="closeModal('editTransactionModal')" class="text-white hover:text-gray-200 transition-colors">
                        <i class="ri-close-line text-2xl"></i>
                    </button>
                </div>
            </div>
            
            <form method="POST" class="p-6 space-y-5">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-file-text-line text-indigo-600"></i> Transaction ID
                    </label>
                    <input 
                        type="text" 
                        id="edit_transaction_id" 
                        disabled
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl bg-gray-100 text-gray-600 font-mono">
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-exchange-line text-indigo-600"></i> Type
                        </label>
                        <select 
                            id="edit_transaction_type" 
                            name="transaction_type" 
                            required 
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition-all duration-200">
                            <option value="deposit">💰 Deposit</option>
                            <option value="withdrawal">💸 Withdrawal</option>
                            <option value="payment">💳 Payment</option>
                            <option value="refund">🔄 Refund</option>
                        </select>
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
                            <option value="completed">✅ Completed</option>
                            <option value="pending">⏳ Pending</option>
                            <option value="failed">❌ Failed</option>
                            <option value="cancelled">🚫 Cancelled</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-money-dollar-circle-line text-indigo-600"></i> Amount
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
                            <i class="ri-currency-line text-indigo-600"></i> Currency
                        </label>
                        <select 
                            id="edit_currency" 
                            name="currency" 
                            required 
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition-all duration-200">
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                            <option value="BDT">BDT</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-bank-card-line text-indigo-600"></i> Payment Method
                    </label>
                    <select 
                        id="edit_payment_method" 
                        name="payment_method" 
                        required 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition-all duration-200">
                        <option value="paypal">PayPal</option>
                        <option value="stripe">Stripe</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cash">Cash</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button 
                        type="button" 
                        onclick="closeModal('editTransactionModal')" 
                        class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        Update Transaction
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
                    <h3 class="text-xl font-bold text-white">Delete Transaction</h3>
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
                                <p class="text-xs text-red-700 mt-1">All transaction data will be permanently deleted from the system.</p>
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
                        Delete Transaction
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

// Confirm Delete
function confirmDelete(transactionId, transactionNumber) {
    document.getElementById('deleteModalMessage').textContent = `You are about to delete the transaction "${transactionNumber}".`;
    
    const urlParams = new URLSearchParams(window.location.search);
    const queryString = urlParams.toString();
    const url = `?delete=${transactionId}${queryString ? '&' + queryString : ''}`;
    
    document.getElementById('confirmDeleteBtn').href = url;
    openModal('confirmDeleteModal');
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Add Transaction Button
    document.getElementById('addTransactionBtn')?.addEventListener('click', function() {
        openModal('addTransactionModal');
    });
    
    // Close on Escape Key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal('addTransactionModal');
            closeModal('editTransactionModal');
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

// Edit Transaction Function
function editTransaction(transactionData) {
    document.getElementById('edit_id').value = transactionData.id;
    document.getElementById('edit_transaction_id').value = transactionData.transaction_id;
    document.getElementById('edit_transaction_type').value = transactionData.transaction_type;
    document.getElementById('edit_amount').value = transactionData.amount;
    document.getElementById('edit_currency').value = transactionData.currency;
    document.getElementById('edit_payment_method').value = transactionData.payment_method;
    document.getElementById('edit_status').value = transactionData.status;
    openModal('editTransactionModal');
}
</script>

<?php include '../includes/admin_footer.php'; ?>