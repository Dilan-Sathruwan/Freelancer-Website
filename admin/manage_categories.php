<?php
session_start();
include '../config/db.con.php';

// Set page variables
$page_title = 'Manage Categories';
$active_page = 'categories';

// Ensure only admins can access this page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Pagination variables
$categoriesPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $categoriesPerPage;

// Search and filter variables
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Add Category
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $name = sanitizeInput($_POST['name']);
        $slug = sanitizeInput($_POST['slug']);
        $parent_id = !empty($_POST['parent_id']) ? validateInteger($_POST['parent_id']) : null;
        $display_order = validateInteger($_POST['display_order']);
        $status = sanitizeInput($_POST['status']);
        
        // Validate required fields
        if (empty($name) || empty($slug)) {
            $error = "Name and slug are required.";
        } elseif (!in_array($status, ['active', 'inactive'])) {
            $error = "Invalid status selected.";
        } else {
            try {
                // Check if slug already exists
                $stmt = $conn->prepare("SELECT id FROM categories WHERE slug = ?");
                $stmt->execute([$slug]);
                if ($stmt->fetch()) {
                    $error = "Slug already exists.";
                } else {
                    // Insert new category
                    $stmt = $conn->prepare("INSERT INTO categories (name, slug, parent_id, display_order, status, gigs_count) VALUES (?, ?, ?, ?, ?, 0)");
                    $stmt->execute([$name, $slug, $parent_id, $display_order ?: 0, $status]);
                    $success = "Category added successfully.";
                }
            } catch (PDOException $e) {
                error_log("Add category error: " . $e->getMessage());
                $error = "Error adding category.";
            }
        }
    }
    
    // Handle Edit Category
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = validateInteger($_POST['id']);
        $name = sanitizeInput($_POST['name']);
        $slug = sanitizeInput($_POST['slug']);
        $parent_id = !empty($_POST['parent_id']) ? validateInteger($_POST['parent_id']) : null;
        $display_order = validateInteger($_POST['display_order']);
        $status = sanitizeInput($_POST['status']);
        
        // Validate required fields
        if (!$id || empty($name) || empty($slug)) {
            $error = "All required fields must be filled.";
        } elseif (!in_array($status, ['active', 'inactive'])) {
            $error = "Invalid status selected.";
        } else {
            try {
                // Check if slug already exists for other categories
                $stmt = $conn->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
                $stmt->execute([$slug, $id]);
                if ($stmt->fetch()) {
                    $error = "Slug already exists for another category.";
                } else {
                    // Update category
                    $stmt = $conn->prepare("UPDATE categories SET name = ?, slug = ?, parent_id = ?, display_order = ?, status = ? WHERE id = ?");
                    $stmt->execute([$name, $slug, $parent_id, $display_order ?: 0, $status, $id]);
                    $success = "Category updated successfully.";
                }
            } catch (PDOException $e) {
                error_log("Edit category error: " . $e->getMessage());
                $error = "Error updating category.";
            }
        }
    }
}

// Handle category status update
if (isset($_GET['toggle_status'])) {
    $id = validateInteger($_GET['toggle_status']);
    
    if ($id) {
        try {
            // Get current status
            $stmt = $conn->prepare("SELECT status FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $category = $stmt->fetch();
            
            if ($category) {
                $newStatus = ($category['status'] === 'active') ? 'inactive' : 'active';
                $stmt = $conn->prepare("UPDATE categories SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $id]);
                $success = "Category status updated successfully.";
            }
        } catch (PDOException $e) {
            error_log("Update category status error: " . $e->getMessage());
            $error = "Error updating category status.";
        }
    }
}

// Handle category deletion
if (isset($_GET['delete'])) {
    $id = validateInteger($_GET['delete']);
    
    if ($id) {
        try {
            // Get category info
            $stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $categoryToDelete = $stmt->fetch();
            
            if ($categoryToDelete) {
                // Delete the category
                $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Category '" . htmlspecialchars($categoryToDelete['name']) . "' deleted successfully.";
            } else {
                $error = "Category not found.";
            }
        } catch (PDOException $e) {
            error_log("Delete category error: " . $e->getMessage());
            $error = "Error deleting category. The category may have associated data.";
        }
    }
}

// Build query with filters
$categories = [];
$totalCategories = 0;
$totalPages = 1;

try {
    // Base query
    $query = "SELECT c.id, c.name, c.slug, c.status, c.gigs_count, c.display_order, c.parent_id,
                     p.name as parent_name
              FROM categories c
              LEFT JOIN categories p ON c.parent_id = p.id";
    
    $countQuery = "SELECT COUNT(*) FROM categories c";
    
    // Add filters
    $params = [];
    $whereClause = "";
    
    if (!empty($search)) {
        $whereClause .= (!empty($whereClause) ? " AND " : "") . "(c.name LIKE ? OR c.slug LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam]);
    }
    
    if (!empty($statusFilter)) {
        $whereClause .= (!empty($whereClause) ? " AND " : "") . "c.status = ?";
        $params[] = $statusFilter;
    }
    
    if (!empty($whereClause)) {
        $query .= " WHERE " . $whereClause;
        $countQuery .= " WHERE " . $whereClause;
    }
    
    // Add ordering
    $query .= " ORDER BY c.display_order, c.name LIMIT ? OFFSET ?";
    $params[] = (int)$categoriesPerPage;
    $params[] = (int)$offset;
    
    // Get total count for pagination
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute(array_slice($params, 0, count($params) - 2)); // Remove LIMIT/OFFSET params
    $totalCategories = $countStmt->fetchColumn();
    $totalPages = ceil($totalCategories / $categoriesPerPage);
    
    // Get categories
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $categories = $stmt->fetchAll();
    
    // Get all categories for parent dropdown
    $allCategoriesStmt = $conn->query("SELECT id, name FROM categories ORDER BY name");
    $allCategories = $allCategoriesStmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch categories error: " . $e->getMessage());
    $error = "Error fetching categories.";
    $categories = [];
    $totalCategories = 0;
    $allCategories = [];
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
                        <div class="p-3 bg-cyan-600 rounded-xl shadow-lg">
                            <i class="ri-folder-line text-white text-2xl"></i>
                        </div>
                        <span class="bg-gradient-to-r from-cyan-600 to-blue-600 bg-clip-text text-transparent">
                            Category Management
                        </span>
                    </h1>
                    <p class="mt-2 text-gray-600 text-sm sm:text-base flex items-center gap-2">
                        <i class="ri-information-line"></i>
                        Manage and organize all service categories
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-4 py-2 bg-white rounded-lg shadow-md border border-gray-200 text-sm font-semibold text-gray-700">
                        <i class="ri-folder-line text-cyan-600"></i> Total: <span class="text-cyan-600"><?php echo $totalCategories; ?></span>
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
                <div class="bg-gradient-to-r from-cyan-600 to-blue-600 px-6 py-4">
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
                                    <i class="ri-search-line text-cyan-600"></i>
                                    Search Categories
                                </label>
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        id="search" 
                                        name="search" 
                                        class="w-full pl-11 pr-4 py-3 border-2 border-gray-300 rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-cyan-100 focus:border-cyan-500 hover:border-cyan-400" 
                                        placeholder="Category name, slug..." 
                                        value="<?php echo htmlspecialchars($search); ?>">
                                    <i class="ri-search-line absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                            
                            <!-- Status Filter -->
                            <div class="group">
                                <label for="status" class="block mb-2 text-sm font-semibold text-gray-700 flex items-center gap-2">
                                    <i class="ri-toggle-line text-cyan-600"></i>
                                    Category Status
                                </label>
                                <div class="relative">
                                    <select 
                                        id="status" 
                                        name="status" 
                                        class="w-full pl-11 pr-4 py-3 border-2 border-gray-300 rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-cyan-100 focus:border-cyan-500 hover:border-cyan-400 appearance-none bg-white cursor-pointer">
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
                                class="btn-ripple flex-1 sm:flex-none px-6 py-3 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 text-white rounded-xl transition-all duration-300 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-semibold">
                                <i class="ri-search-line text-lg"></i>
                                <span>Apply Filters</span>
                            </button>
                            <a 
                                href="manage_categories.php" 
                                class="btn-ripple flex-1 sm:flex-none px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition-all duration-300 flex items-center justify-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-semibold">
                                <i class="ri-refresh-line text-lg"></i>
                                <span>Reset</span>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Categories Table Card -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden animate-fade-in">
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-5 border-b border-gray-200">
                <div class="flex flex-wrap justify-between items-center gap-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-cyan-100 rounded-lg">
                            <i class="ri-folder-line text-cyan-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">All Categories</h3>
                            <p class="text-sm text-gray-600"><?php echo $totalCategories; ?> total categories found</p>
                        </div>
                    </div>
                    <button 
                        id="addCategoryBtn" 
                        class="btn-ripple px-6 py-3 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 text-white rounded-xl transition-all duration-300 flex items-center gap-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-semibold">
                        <i class="ri-add-line text-lg"></i>
                        <span>Add New Category</span>
                    </button>
                </div>
            </div>
            
            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Slug</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Parent</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Gigs</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="mb-4 p-4 bg-gray-100 rounded-full">
                                        <i class="ri-folder-line text-6xl text-gray-400"></i>
                                    </div>
                                    <h4 class="text-xl font-bold text-gray-700 mb-2">No Categories Found</h4>
                                    <p class="text-gray-500 text-sm">Try adjusting your filters or add a new category</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($categories as $category): ?>
                        <tr class="table-row-hover">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900">#<?php echo htmlspecialchars($category['id']); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-cyan-100 rounded-lg">
                                        <i class="ri-folder-fill text-cyan-600"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-sm text-gray-600 font-mono bg-gray-100 px-2 py-1 rounded">
                                    <?php echo htmlspecialchars($category['slug']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="text-sm text-gray-900">
                                    <?php echo $category['parent_name'] ? htmlspecialchars($category['parent_name']) : '-'; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-bold">
                                    <?php echo htmlspecialchars($category['gigs_count'] ?? 0); ?> gigs
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-sm font-semibold text-gray-900">
                                    <?php echo htmlspecialchars($category['display_order']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="<?php 
                                    echo $category['status'] === 'active' 
                                        ? 'bg-green-100 text-green-800 border border-green-200' 
                                        : 'bg-red-100 text-red-800 border border-red-200';
                                ?> px-3 py-1.5 rounded-full text-xs font-bold shadow-sm">
                                    <?php echo $category['status'] === 'active' ? '✅ Active' : '❌ Inactive'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-2 flex-wrap">
                                    <button 
                                        onclick='editCategory(<?php echo json_encode([
                                            "id" => $category["id"],
                                            "name" => $category["name"],
                                            "slug" => $category["slug"],
                                            "parent_id" => $category["parent_id"],
                                            "display_order" => $category["display_order"],
                                            "status" => $category["status"]
                                        ]); ?>)'
                                        class="p-2 bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white border border-indigo-200 hover:border-indigo-600 rounded-lg transition-all duration-200 transform hover:scale-110 shadow-sm hover:shadow-md"
                                        title="Edit Category">
                                        <i class="ri-edit-line text-sm"></i>
                                    </button>
                                    <button 
                                        onclick="confirmToggleStatus(<?php echo $category['id']; ?>, '<?php echo $category['status']; ?>', '<?php echo htmlspecialchars($category['name'], ENT_QUOTES); ?>')"
                                        class="p-2 bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white border border-blue-200 hover:border-blue-600 rounded-lg transition-all duration-200 transform hover:scale-110 shadow-sm hover:shadow-md"
                                        title="Toggle Status">
                                        <i class="ri-toggle-line text-sm"></i>
                                    </button>
                                    <button 
                                        onclick="confirmDelete(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name'], ENT_QUOTES); ?>')"
                                        class="p-2 bg-red-50 hover:bg-red-600 text-red-700 hover:text-white border border-red-200 hover:border-red-600 rounded-lg transition-all duration-200 transform hover:scale-110 shadow-sm hover:shadow-md"
                                        title="Delete Category">
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
                <?php if (empty($categories)): ?>
                <div class="text-center py-12">
                    <div class="flex flex-col items-center justify-center">
                        <div class="mb-4 p-4 bg-gray-100 rounded-full">
                            <i class="ri-folder-line text-6xl text-gray-400"></i>
                        </div>
                        <h4 class="text-xl font-bold text-gray-700 mb-2">No Categories Found</h4>
                        <p class="text-gray-500 text-sm">Try adjusting your filters</p>
                    </div>
                </div>
                <?php else: ?>
                <?php foreach ($categories as $category): ?>
                <div class="card-hover bg-white rounded-xl border-2 border-gray-200 overflow-hidden shadow-lg">
                    <!-- Card Header -->
                    <div class="bg-gradient-to-r from-cyan-50 to-blue-50 p-4 border-b border-gray-200">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 flex-1">
                                <div class="p-2 bg-cyan-600 rounded-lg">
                                    <i class="ri-folder-fill text-white text-xl"></i>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-bold text-gray-900 text-base truncate">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </h4>
                                    <p class="text-sm text-gray-600 font-mono truncate">
                                        <?php echo htmlspecialchars($category['slug']); ?>
                                    </p>
                                </div>
                            </div>
                            <span class="<?php 
                                echo $category['status'] === 'active' 
                                    ? 'bg-green-100 text-green-800 border border-green-200' 
                                    : 'bg-red-100 text-red-800 border border-red-200';
                            ?> px-2.5 py-1 rounded-full text-xs font-bold shadow-sm whitespace-nowrap">
                                <?php echo $category['status'] === 'active' ? '✅' : '❌'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Card Body -->
                    <div class="p-4 space-y-3">
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <i class="ri-folder-2-line text-purple-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Parent Category</p>
                                <p class="text-sm text-gray-900 font-medium"><?php echo $category['parent_name'] ? htmlspecialchars($category['parent_name']) : 'None'; ?></p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                <div class="p-2 bg-blue-100 rounded-lg">
                                    <i class="ri-stack-line text-blue-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Gigs</p>
                                    <p class="text-sm text-blue-600 font-bold"><?php echo htmlspecialchars($category['gigs_count'] ?? 0); ?></p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                <div class="p-2 bg-green-100 rounded-lg">
                                    <i class="ri-sort-asc text-green-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Order</p>
                                    <p class="text-sm text-gray-900 font-bold"><?php echo htmlspecialchars($category['display_order']); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="p-2 bg-orange-100 rounded-lg">
                                <i class="ri-hashtag text-orange-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-500 font-semibold uppercase mb-0.5">Category ID</p>
                                <p class="text-sm text-gray-900 font-bold">#<?php echo htmlspecialchars($category['id']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card Footer -->
                    <div class="p-4 bg-gray-50 border-t border-gray-200">
                        <div class="grid grid-cols-3 gap-2">
                            <button 
                                onclick='editCategory(<?php echo json_encode([
                                    "id" => $category["id"],
                                    "name" => $category["name"],
                                    "slug" => $category["slug"],
                                    "parent_id" => $category["parent_id"],
                                    "display_order" => $category["display_order"],
                                    "status" => $category["status"]
                                ]); ?>)'
                                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-all duration-200 flex items-center justify-center gap-2 font-semibold text-sm shadow-md hover:shadow-lg">
                                <i class="ri-edit-line"></i> Edit
                            </button>
                            <button 
                                onclick="confirmToggleStatus(<?php echo $category['id']; ?>, '<?php echo $category['status']; ?>', '<?php echo htmlspecialchars($category['name'], ENT_QUOTES); ?>')"
                                class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200 flex items-center justify-center gap-2 font-semibold text-sm shadow-md hover:shadow-lg">
                                <i class="ri-toggle-line"></i>
                            </button>
                            <button 
                                onclick="confirmDelete(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name'], ENT_QUOTES); ?>')"
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
                                class="px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-cyan-600 hover:text-white hover:border-cyan-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md">
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
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="hidden sm:block px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-cyan-600 hover:text-white hover:border-cyan-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md">1</a>
                            <?php if ($start > 2): ?>
                                <span class="hidden sm:block px-4 py-2 text-gray-400">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="px-4 py-2 bg-gradient-to-r from-cyan-600 to-blue-600 border-2 border-cyan-600 text-white rounded-lg font-bold text-sm shadow-md"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="hidden sm:block px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-cyan-600 hover:text-white hover:border-cyan-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($end < $totalPages): ?>
                            <?php if ($end < $totalPages - 1): ?>
                                <span class="hidden sm:block px-4 py-2 text-gray-400">...</span>
                            <?php endif; ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>" class="hidden sm:block px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-cyan-600 hover:text-white hover:border-cyan-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md"><?php echo $totalPages; ?></a>
                        <?php endif; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a 
                                href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                class="px-4 py-2 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-cyan-600 hover:text-white hover:border-cyan-600 transition-all duration-200 font-semibold text-sm shadow-sm hover:shadow-md">
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

<!-- Add Category Modal -->
<div id="addCategoryModal" class="hidden fixed inset-0 z-50 overflow-y-auto modal-backdrop" style="background-color: rgba(0, 0, 0, 0.75);">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 mt-12">
        <div class="fixed inset-0 transition-opacity" onclick="closeModal('addCategoryModal')"></div>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full animate-scale-in">
            <div class="bg-gradient-to-r from-cyan-600 to-blue-600 px-6 py-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="ri-add-line text-2xl"></i>
                        Add New Category
                    </h3>
                    <button onclick="closeModal('addCategoryModal')" class="text-white hover:text-gray-200 transition-colors">
                        <i class="ri-close-line text-2xl"></i>
                    </button>
                </div>
            </div>
            
            <form method="POST" class="p-6 space-y-5">
                <input type="hidden" name="action" value="add">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-text text-cyan-600"></i> Category Name
                    </label>
                    <input 
                        type="text" 
                        name="name" 
                        required 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-cyan-100 focus:border-cyan-500 transition-all duration-200" 
                        placeholder="Enter category name">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-link text-cyan-600"></i> Slug
                    </label>
                    <input 
                        type="text" 
                        name="slug" 
                        required 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-cyan-100 focus:border-cyan-500 transition-all duration-200" 
                        placeholder="category-slug">
                    <p class="mt-1 text-xs text-gray-500">URL-friendly version (e.g., web-development)</p>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-folder-2-line text-cyan-600"></i> Parent Category
                    </label>
                    <select 
                        name="parent_id" 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-cyan-100 focus:border-cyan-500 transition-all duration-200">
                        <option value="">None (Top Level)</option>
                        <?php foreach ($allCategories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-sort-asc text-cyan-600"></i> Display Order
                        </label>
                        <input 
                            type="number" 
                            name="display_order" 
                            value="0"
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-cyan-100 focus:border-cyan-500 transition-all duration-200">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-toggle-line text-cyan-600"></i> Status
                        </label>
                        <select 
                            name="status" 
                            required 
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-cyan-100 focus:border-cyan-500 transition-all duration-200">
                            <option value="active" selected>✅ Active</option>
                            <option value="inactive">❌ Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button 
                        type="button" 
                        onclick="closeModal('addCategoryModal')" 
                        class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        Add Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editCategoryModal" class="hidden fixed inset-0 z-50 overflow-y-auto modal-backdrop" style="background-color: rgba(0, 0, 0, 0.75);">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 mt-12">
        <div class="fixed inset-0 transition-opacity" onclick="closeModal('editCategoryModal')"></div>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full animate-scale-in">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="ri-edit-line text-2xl"></i>
                        Edit Category
                    </h3>
                    <button onclick="closeModal('editCategoryModal')" class="text-white hover:text-gray-200 transition-colors">
                        <i class="ri-close-line text-2xl"></i>
                    </button>
                </div>
            </div>
            
            <form method="POST" class="p-6 space-y-5">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-text text-indigo-600"></i> Category Name
                    </label>
                    <input 
                        type="text" 
                        id="edit_name" 
                        name="name" 
                        required 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition-all duration-200">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-link text-indigo-600"></i> Slug
                    </label>
                    <input 
                        type="text" 
                        id="edit_slug" 
                        name="slug" 
                        required 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition-all duration-200">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <i class="ri-folder-2-line text-indigo-600"></i> Parent Category
                    </label>
                    <select 
                        id="edit_parent_id" 
                        name="parent_id" 
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition-all duration-200">
                        <option value="">None (Top Level)</option>
                        <?php foreach ($allCategories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="ri-sort-asc text-indigo-600"></i> Display Order
                        </label>
                        <input 
                            type="number" 
                            id="edit_display_order" 
                            name="display_order" 
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
                            <option value="active">✅ Active</option>
                            <option value="inactive">❌ Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button 
                        type="button" 
                        onclick="closeModal('editCategoryModal')" 
                        class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl transition-all duration-200 font-semibold shadow-lg hover:shadow-xl">
                        Update Category
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
                    <h3 class="text-xl font-bold text-white">Toggle Category Status</h3>
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
                    <h3 class="text-xl font-bold text-white">Delete Category</h3>
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
                                <p class="text-xs text-red-700 mt-1">All category data will be permanently deleted from the system.</p>
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
                        Delete Category
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
function confirmToggleStatus(categoryId, currentStatus, categoryName) {
    const action = currentStatus === 'active' ? 'deactivate' : 'activate';
    const actionText = currentStatus === 'active' ? 'Deactivate' : 'Activate';
    
    document.getElementById('toggleModalTitle').textContent = `${actionText} "${categoryName}"?`;
    document.getElementById('toggleModalMessage').textContent = `Are you sure you want to ${action} this category?`;
    
    const urlParams = new URLSearchParams(window.location.search);
    const queryString = urlParams.toString();
    const url = `?toggle_status=${categoryId}${queryString ? '&' + queryString : ''}`;
    
    document.getElementById('confirmToggleBtn').href = url;
    openModal('confirmToggleModal');
}

// Confirm Delete
function confirmDelete(categoryId, categoryName) {
    document.getElementById('deleteModalMessage').textContent = `You are about to delete the category "${categoryName}".`;
    
    const urlParams = new URLSearchParams(window.location.search);
    const queryString = urlParams.toString();
    const url = `?delete=${categoryId}${queryString ? '&' + queryString : ''}`;
    
    document.getElementById('confirmDeleteBtn').href = url;
    openModal('confirmDeleteModal');
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Add Category Button
    document.getElementById('addCategoryBtn')?.addEventListener('click', function() {
        openModal('addCategoryModal');
    });
    
    // Close on Escape Key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal('addCategoryModal');
            closeModal('editCategoryModal');
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

// Edit Category Function
function editCategory(categoryData) {
    document.getElementById('edit_id').value = categoryData.id;
    document.getElementById('edit_name').value = categoryData.name;
    document.getElementById('edit_slug').value = categoryData.slug;
    document.getElementById('edit_parent_id').value = categoryData.parent_id || '';
    document.getElementById('edit_display_order').value = categoryData.display_order;
    document.getElementById('edit_status').value = categoryData.status;
    openModal('editCategoryModal');
}
</script>

<?php include '../includes/admin_footer.php'; ?>