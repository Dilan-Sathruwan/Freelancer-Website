<?php
/**
 * Logging Functions for Admin and User Activities
 * 
 * This file contains functions to log admin activities in admin_logs table
 * and user activities in activity_log table.
 */

/**
 * Log admin activity in admin_logs table
 * 
 * @param int $adminId The admin user ID
 * @param string $action The action performed (login, logout, edit, add, delete, etc.)
 * @param string|null $entityType The type of entity affected (user, gig, order, etc.)
 * @param int|null $entityId The ID of the entity affected
 * @param array|null $oldValues The old values before the action (for edit actions)
 * @param array|null $newValues The new values after the action (for edit actions)
 * @param string|null $details Additional details about the action
 * @return bool True on success, false on failure
 */
function logAdminActivity($adminId, $action, $entityType = null, $entityId = null, $oldValues = null, $newValues = null, $details = null) {
    global $conn;
    
    try {
        // Get IP address
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Get user agent
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Prepare the query
        $query = "INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, old_values, new_values, details, ip_address, user_agent, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($query);
        
        // Convert arrays to JSON if provided
        $oldValuesJson = $oldValues ? json_encode($oldValues) : null;
        $newValuesJson = $newValues ? json_encode($newValues) : null;
        
        // Execute the query
        $stmt->execute([
            $adminId,
            $action,
            $entityType,
            $entityId,
            $oldValuesJson,
            $newValuesJson,
            $details,
            $ipAddress,
            $userAgent
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Failed to log admin activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Log user activity in activity_log table
 * 
 * @param int $userId The user ID (client or freelancer)
 * @param string $activityType The type of activity (login, logout, edit, add, delete, etc.)
 * @param string|null $entityType The type of entity affected (profile, order, gig, etc.)
 * @param int|null $entityId The ID of the entity affected
 * @param string|null $description Description of the activity
 * @param array|null $metadata Additional metadata about the activity
 * @return bool True on success, false on failure
 */
function logUserActivity($userId, $activityType, $entityType = null, $entityId = null, $description = null, $metadata = null) {
    global $conn;
    
    try {
        // Get IP address
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Get user agent
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Get session ID
        $sessionId = session_id() ?: null;
        
        // Prepare the query
        $query = "INSERT INTO activity_log (user_id, activity_type, entity_type, entity_id, description, metadata, ip_address, user_agent, session_id, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($query);
        
        // Convert metadata array to JSON if provided
        $metadataJson = $metadata ? json_encode($metadata) : null;
        
        // Execute the query
        $stmt->execute([
            $userId,
            $activityType,
            $entityType,
            $entityId,
            $description,
            $metadataJson,
            $ipAddress,
            $userAgent,
            $sessionId
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Failed to log user activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Log admin login activity
 * 
 * @param int $adminId The admin user ID
 * @return bool True on success, false on failure
 */
function logAdminLogin($adminId) {
    return logAdminActivity($adminId, 'login', 'admin', $adminId, null, null, 'Admin logged in');
}

/**
 * Log admin logout activity
 * 
 * @param int $adminId The admin user ID
 * @return bool True on success, false on failure
 */
function logAdminLogout($adminId) {
    return logAdminActivity($adminId, 'logout', 'admin', $adminId, null, null, 'Admin logged out');
}

/**
 * Log user login activity
 * 
 * @param int $userId The user ID
 * @return bool True on success, false on failure
 */
function logUserLogin($userId) {
    return logUserActivity($userId, 'login', 'user', $userId, 'User logged in');
}

/**
 * Log user logout activity
 * 
 * @param int $userId The user ID
 * @return bool True on success, false on failure
 */
function logUserLogout($userId) {
    return logUserActivity($userId, 'logout', 'user', $userId, 'User logged out');
}

/**
 * Log admin add activity
 * 
 * @param int $adminId The admin user ID
 * @param string $entityType The type of entity affected (user, gig, order, etc.)
 * @param array|null $newValues The new values after the action
 * @param string|null $details Additional details about the action
 * @return bool True on success, false on failure
 */
function logAdminAdd($adminId, $entityType, $newValues = null, $details = null) {
    return logAdminActivity($adminId, 'add', $entityType, null, null, $newValues, $details);
}

/**
 * Log admin edit activity
 * 
 * @param int $adminId The admin user ID
 * @param string $entityType The type of entity affected (user, gig, order, etc.)
 * @param int $entityId The ID of the entity affected
 * @param array|null $oldValues The old values before the action
 * @param array|null $newValues The new values after the action
 * @param string|null $details Additional details about the action
 * @return bool True on success, false on failure
 */
function logAdminEdit($adminId, $entityType, $entityId, $oldValues = null, $newValues = null, $details = null) {
    return logAdminActivity($adminId, 'edit', $entityType, $entityId, $oldValues, $newValues, $details);
}

/**
 * Log admin delete activity
 * 
 * @param int $adminId The admin user ID
 * @param string $entityType The type of entity affected (user, gig, order, etc.)
 * @param int $entityId The ID of the entity affected
 * @param array|null $oldValues The old values before the action
 * @param string|null $details Additional details about the action
 * @return bool True on success, false on failure
 */
function logAdminDelete($adminId, $entityType, $entityId, $oldValues = null, $details = null) {
    return logAdminActivity($adminId, 'delete', $entityType, $entityId, $oldValues, null, $details);
}