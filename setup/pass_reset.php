<?php
// Password Reset Page for Developers - FOR DEVELOPMENT PURPOSES ONLY
// This file should be deleted in production environments

// Security check - only allow access from localhost
if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('Access denied. This page is for development purposes only.');
}

include_once dirname(__DIR__) . "/config/db.con.php";

// Initialize variables
$message = '';
$messageType = '';
$users = [];
$selectedUsers = [];

// Fetch all users from database
try {
    $stmt = $conn->prepare("SELECT id, username, role, status FROM users ORDER BY role, username");
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = "Error fetching users: " . htmlspecialchars($e->getMessage());
    $messageType = "error";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_action'])) {
    $action = $_POST['reset_action'];
    $customPassword = isset($_POST['custom_password']) ? $_POST['custom_password'] : '';
    
    // Use custom password if provided, otherwise default to '123'
    $passwordToUse = !empty($customPassword) ? $customPassword : '123';
    $hashedPassword = password_hash($passwordToUse, PASSWORD_DEFAULT);
    
    try {
        switch ($action) {
            case 'reset_all':
                // Reset password for all users
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id > 0");
                $stmt->execute([$hashedPassword]);
                $message = "Passwords reset for all users successfully! New password: " . htmlspecialchars($passwordToUse);
                $messageType = "success";
                break;
                
            case 'reset_selected':
                // Reset password for selected users
                if (isset($_POST['selected_users']) && is_array($_POST['selected_users'])) {
                    $selectedUsers = $_POST['selected_users'];
                    if (!empty($selectedUsers)) {
                        // Create placeholders for the IN clause
                        $placeholders = str_repeat('?,', count($selectedUsers) - 1) . '?';
                        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id IN ($placeholders)");
                        $params = array_merge([$hashedPassword], $selectedUsers);
                        $stmt->execute($params);
                        $message = "Passwords reset for " . count($selectedUsers) . " selected users successfully! New password: " . htmlspecialchars($passwordToUse);
                        $messageType = "success";
                    } else {
                        $message = "Please select at least one user.";
                        $messageType = "error";
                    }
                } else {
                    $message = "No users selected.";
                    $messageType = "error";
                }
                break;
                
            case 'reset_range':
                // Reset password for a range of user IDs
                $start_id = isset($_POST['start_id']) ? (int)$_POST['start_id'] : 0;
                $end_id = isset($_POST['end_id']) ? (int)$_POST['end_id'] : 0;
                
                if ($start_id > 0 && $end_id > 0 && $end_id >= $start_id) {
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id BETWEEN ? AND ?");
                    $stmt->execute([$hashedPassword, $start_id, $end_id]);
                    $message = "Passwords reset for users with IDs between $start_id and $end_id successfully! New password: " . htmlspecialchars($passwordToUse);
                    $messageType = "success";
                } else {
                    $message = "Please enter valid start and end IDs.";
                    $messageType = "error";
                }
                break;
                
            default:
                $message = "Invalid action.";
                $messageType = "error";
        }
    } catch (PDOException $e) {
        $message = "Error resetting passwords: " . htmlspecialchars($e->getMessage());
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developer Password Reset</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Remix Icon -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    
    <!-- Tailwind Custom Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                        secondary: '#8b5cf6',
                        accent: '#ec4899',
                        dark: '#0f172a',
                        light: '#f8fafc',
                    }
                }
            }
        }
    </script>
    
    <style>
        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 640px) {
            .responsive-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-10">
            <div class="flex items-center justify-center space-x-3 mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-600 to-blue-600 rounded-xl flex items-center justify-center">
                    <i class="ri-lock-password-line text-2xl text-white"></i>
                </div>
                <h1 class="text-3xl md:text-4xl font-bold bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent">
                    Developer Password Reset
                </h1>
            </div>
            <p class="text-gray-600 max-w-2xl mx-auto">
                Reset passwords for development and testing purposes. This tool is for developers only and should not be accessible in production.
            </p>
        </div>
        
        <!-- Alert Message -->
        <?php if ($message): ?>
            <div class="mb-8 max-w-4xl mx-auto fade-in">
                <div class="p-4 rounded-xl <?php echo $messageType === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
                    <div class="flex items-center">
                        <i class="ri-<?php echo $messageType === 'success' ? 'checkbox-circle' : 'error-warning'; ?>-line text-xl <?php echo $messageType === 'success' ? 'text-green-600' : 'text-red-600'; ?> mr-3"></i>
                        <span class="<?php echo $messageType === 'success' ? 'text-green-800' : 'text-red-800'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Main Content -->
        <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Reset Options -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="ri-settings-3-line mr-2 text-purple-600"></i>
                        Reset Options
                    </h2>
                    
                    <form method="POST" id="resetForm">
                        <!-- Custom Password Field -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Custom Password (optional)
                            </label>
                            <div class="relative">
                                <input type="text" name="custom_password" id="customPassword" 
                                       placeholder="Leave blank for default password (123)" 
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                <button type="button" onclick="generatePassword()" 
                                        class="absolute right-2 top-2.5 text-gray-400 hover:text-purple-600">
                                    <i class="ri-refresh-line"></i>
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                If left blank, passwords will be reset to default: <span class="font-mono font-bold">123</span>
                            </p>
                        </div>
                        
                        <!-- Hidden inputs for selected users -->
                        <div id="hiddenSelectedUsers"></div>
                        
                        <!-- Reset All Users -->
                        <div class="mb-6">
                            <button type="submit" name="reset_action" value="reset_all" 
                                    class="w-full flex items-center justify-between p-4 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-xl hover:shadow-lg transform hover:scale-[1.02] transition-all duration-300">
                                <div class="flex items-center">
                                    <i class="ri-user-star-line text-xl mr-3"></i>
                                    <span class="font-semibold">Reset All Users</span>
                                </div>
                                <i class="ri-arrow-right-line"></i>
                            </button>
                            <p class="text-gray-500 text-sm mt-2">
                                Reset passwords for all <?php echo count($users); ?> users
                            </p>
                        </div>
                        
                        <!-- Reset Selected Users -->
                        <div class="mb-6">
                            <button type="button" onclick="submitSelectedUsers()" 
                                    class="w-full flex items-center justify-between p-4 bg-white border-2 border-purple-200 text-purple-700 rounded-xl hover:bg-purple-50 hover:border-purple-300 transition-all duration-300">
                                <div class="flex items-center">
                                    <i class="ri-checkbox-multiple-line text-xl mr-3"></i>
                                    <span class="font-semibold">Reset Selected</span>
                                </div>
                                <i class="ri-arrow-right-line"></i>
                            </button>
                            <p class="text-gray-500 text-sm mt-2">
                                Reset passwords for individually selected users
                            </p>
                        </div>
                        
                        <!-- Reset User Range -->
                        <div class="mb-2">
                            <button type="button" onclick="toggleRangeForm()" 
                                    class="w-full flex items-center justify-between p-4 bg-white border-2 border-blue-200 text-blue-700 rounded-xl hover:bg-blue-50 hover:border-blue-300 transition-all duration-300">
                                <div class="flex items-center">
                                    <i class="ri-sort-number-asc text-xl mr-3"></i>
                                    <span class="font-semibold">Reset by ID Range</span>
                                </div>
                                <i class="ri-arrow-down-s-line" id="rangeToggleIcon"></i>
                            </button>
                            <p class="text-gray-500 text-sm mt-2">
                                Reset passwords for users within a specific ID range
                            </p>
                        </div>
                        
                        <!-- Range Form (Hidden by default) -->
                        <div id="rangeForm" class="mt-4 p-4 bg-blue-50 rounded-xl border border-blue-200 hidden">
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Start ID</label>
                                    <input type="number" name="start_id" min="1" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">End ID</label>
                                    <input type="number" name="end_id" min="1" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                </div>
                            </div>
                            <button type="submit" name="reset_action" value="reset_range" 
                                    class="w-full py-2.5 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg font-medium hover:shadow-md transition-all duration-300">
                                Reset Range
                            </button>
                        </div>
                        
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <div class="flex items-center p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                                <i class="ri-information-line text-yellow-600 text-xl mr-2"></i>
                                <div>
                                    <p class="text-sm font-medium text-yellow-800">Password Information</p>
                                    <p class="text-xs text-yellow-700">All passwords will be reset to: <span class="font-mono font-bold" id="displayPassword">123</span></p>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- User List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 flex items-center">
                            <i class="ri-user-line mr-2 text-purple-600"></i>
                            User Accounts
                            <span class="ml-2 bg-purple-100 text-purple-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                                <?php echo count($users); ?> users
                            </span>
                        </h2>
                        <div class="mt-4 sm:mt-0 flex space-x-3">
                            <div class="relative">
                                <select id="roleFilter" class="pl-10 pr-8 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 appearance-none bg-white">
                                    <option value="all">All Roles</option>
                                    <option value="admin">Admin</option>
                                    <option value="freelancer">Freelancer</option>
                                    <option value="client">Client</option>
                                </select>
                                <i class="ri-shield-user-line absolute left-3 top-2.5 text-gray-400"></i>
                            </div>
                            <div class="relative">
                                <input type="text" id="searchInput" placeholder="Search users..." 
                                       class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 w-full sm:w-64">
                                <i class="ri-search-line absolute left-3 top-2.5 text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($users): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="pb-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <div class="flex items-center">
                                                <input type="checkbox" id="selectAll" class="h-4 w-4 text-purple-600 rounded focus:ring-purple-500">
                                                <span class="ml-2">User</span>
                                            </div>
                                        </th>
                                        <th class="pb-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                        <th class="pb-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="pb-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    </tr>
                                </thead>
                                <tbody id="userTableBody" class="divide-y divide-gray-200">
                                    <?php foreach ($users as $user): ?>
                                        <tr class="hover:bg-gray-50 transition-colors duration-150 user-row" data-role="<?php echo $user['role']; ?>">
                                            <td class="py-4">
                                                <div class="flex items-center">
                                                    <input type="checkbox" name="selected_users[]" value="<?php echo $user['id']; ?>" 
                                                           class="h-4 w-4 text-purple-600 rounded focus:ring-purple-500 user-checkbox">
                                                    <div class="ml-3">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            <?php echo htmlspecialchars($user['username']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-4">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php 
                                                        if ($user['role'] === 'admin') echo 'bg-purple-100 text-purple-800';
                                                        elseif ($user['role'] === 'freelancer') echo 'bg-blue-100 text-blue-800';
                                                        else echo 'bg-green-100 text-green-800';
                                                    ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td class="py-4">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php 
                                                        if ($user['status'] === 'active') echo 'bg-green-100 text-green-800';
                                                        else echo 'bg-red-100 text-red-800';
                                                    ?>">
                                                    <?php echo ucfirst($user['status']); ?>
                                                </span>
                                            </td>
                                            <td class="py-4 text-sm text-gray-500">
                                                #<?php echo $user['id']; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <i class="ri-user-search-line text-4xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-1">No users found</h3>
                            <p class="text-gray-500">There are no users in the database.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Actions -->
                <div class="mt-8 bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                        <i class="ri-flashlight-line mr-2 text-purple-600"></i>
                        Quick Actions
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <a href="dev_credentials.php" 
                           class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-purple-50 to-blue-50 rounded-xl border border-purple-100 hover:shadow-md transition-all duration-300">
                            <i class="ri-key-line text-2xl text-purple-600 mb-2"></i>
                            <span class="text-sm font-medium text-gray-700">View Credentials</span>
                        </a>
                        <a href="../auth/login.php" 
                           class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-green-50 to-blue-50 rounded-xl border border-green-100 hover:shadow-md transition-all duration-300">
                            <i class="ri-login-box-line text-2xl text-green-600 mb-2"></i>
                            <span class="text-sm font-medium text-gray-700">Login Page</span>
                        </a>
                        <a href="../index.php" 
                           class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-gray-50 to-blue-50 rounded-xl border border-gray-100 hover:shadow-md transition-all duration-300">
                            <i class="ri-home-line text-2xl text-gray-600 mb-2"></i>
                            <span class="text-sm font-medium text-gray-700">Home Page</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle range form visibility
        function toggleRangeForm() {
            const rangeForm = document.getElementById('rangeForm');
            const rangeToggleIcon = document.getElementById('rangeToggleIcon');
            
            if (rangeForm.classList.contains('hidden')) {
                rangeForm.classList.remove('hidden');
                rangeToggleIcon.classList.remove('ri-arrow-down-s-line');
                rangeToggleIcon.classList.add('ri-arrow-up-s-line');
            } else {
                rangeForm.classList.add('hidden');
                rangeToggleIcon.classList.remove('ri-arrow-up-s-line');
                rangeToggleIcon.classList.add('ri-arrow-down-s-line');
            }
        }
        
        // Generate a random password
        function generatePassword() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%';
            let password = '';
            for (let i = 0; i < 12; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('customPassword').value = password;
            updateDisplayPassword();
        }
        
        // Update the displayed password
        function updateDisplayPassword() {
            const customPassword = document.getElementById('customPassword').value;
            const displayElement = document.getElementById('displayPassword');
            displayElement.textContent = customPassword || '123';
        }
        
        // Update display when custom password changes
        document.getElementById('customPassword').addEventListener('input', updateDisplayPassword);
        
        // Select all checkboxes
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Filter users by role
        document.getElementById('roleFilter').addEventListener('change', function() {
            const selectedRole = this.value;
            const rows = document.querySelectorAll('.user-row');
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            
            rows.forEach(row => {
                const role = row.getAttribute('data-role');
                const username = row.querySelector('td:first-child .text-gray-900').textContent.toLowerCase();
                
                const roleMatch = selectedRole === 'all' || role === selectedRole;
                const searchMatch = username.includes(searchTerm);
                
                if (roleMatch && searchMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const selectedRole = document.getElementById('roleFilter').value;
            const rows = document.querySelectorAll('.user-row');
            
            rows.forEach(row => {
                const role = row.getAttribute('data-role');
                const username = row.querySelector('td:first-child .text-gray-900').textContent.toLowerCase();
                
                const roleMatch = selectedRole === 'all' || role === selectedRole;
                const searchMatch = username.includes(searchTerm);
                
                if (roleMatch && searchMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Submit form with selected users
        function submitSelectedUsers() {
            const selectedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
            const selectedUsers = Array.from(selectedCheckboxes).map(cb => cb.value);
            
            if (selectedUsers.length === 0) {
                alert('Please select at least one user.');
                return;
            }
            
            // Create hidden inputs for selected users
            const hiddenContainer = document.getElementById('hiddenSelectedUsers');
            hiddenContainer.innerHTML = '';
            
            selectedUsers.forEach(userId => {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'selected_users[]';
                hiddenInput.value = userId;
                hiddenContainer.appendChild(hiddenInput);
            });
            
            // Add action
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'reset_action';
            actionInput.value = 'reset_selected';
            hiddenContainer.appendChild(actionInput);
            
            // Show confirmation
            const customPassword = document.getElementById('customPassword').value;
            const displayPassword = customPassword || '123';
            
            if (confirm(`Are you sure you want to reset passwords for ${selectedUsers.length} selected user(s)?\nNew password will be: ${displayPassword}`)) {
                document.getElementById('resetForm').submit();
            }
        }
        
        // Form submission confirmation for other actions
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            // Check if this is triggered by the range reset button
            const submitter = e.submitter || document.activeElement;
            if (submitter && submitter.value === 'reset_range') {
                const customPassword = document.getElementById('customPassword').value;
                const displayPassword = customPassword || '123';
                const startId = document.querySelector('input[name="start_id"]').value;
                const endId = document.querySelector('input[name="end_id"]').value;
                
                if (!startId || !endId) {
                    alert('Please enter both start and end IDs.');
                    e.preventDefault();
                    return;
                }
                
                if (parseInt(endId) < parseInt(startId)) {
                    alert('End ID must be greater than or equal to Start ID.');
                    e.preventDefault();
                    return;
                }
                
                if (!confirm(`Are you sure you want to reset passwords for users with IDs between ${startId} and ${endId}?\nNew password will be: ${displayPassword}`)) {
                    e.preventDefault();
                }
                return;
            }
            
            // For reset_all action
            if (submitter && submitter.value === 'reset_all') {
                const customPassword = document.getElementById('customPassword').value;
                const displayPassword = customPassword || '123';
                
                if (!confirm(`Are you sure you want to reset passwords for ALL users?\nNew password will be: ${displayPassword}\nThis action cannot be undone.`)) {
                    e.preventDefault();
                }
            }
        });
        
        // Initialize display password on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateDisplayPassword();
        });
    </script>
</body>
</html>