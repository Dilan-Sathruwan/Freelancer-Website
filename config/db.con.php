<?php
/**
 * Secure Database Configuration and Connection
 * 
 * Enhanced security measures to prevent SQL injection and other vulnerabilities
 * Combined with security utility functions for comprehensive protection
 */

// Database configuration with environment-based settings
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'freelancerwebsite');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Enhanced security options for PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // Critical for preventing SQL injection
    PDO::ATTR_PERSISTENT         => false, // Disabled for better security
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci",
    PDO::ATTR_TIMEOUT            => 5,
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false, // For better memory management
];

try {
    // Create PDO connection with full DSN
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        $options
    );
    
    // Test connection with a simple query
    $conn->query("SELECT 1");
    
} catch (PDOException $e) {
    // Log detailed error for administrators only
    error_log("Database Connection Failed [DB_CON]: " . $e->getMessage());
    
    // Generic error for users
    http_response_code(500);
    die("Database connection error. Please try again later.");
}

/**
 * Get database connection instance
 * 
 * @return PDO The database connection object
 */
function getDBConnection() {
    global $conn;
    return $conn;
}

/**
 * Execute a secure prepared statement with parameter binding
 * 
 * @param string $sql The SQL query with placeholders
 * @param array $params The parameters to bind
 * @return PDOStatement The executed statement
 */
function executePreparedQuery($sql, $params = []) {
    global $conn;
    
    // Rate limiting to prevent abuse
    if (!rateLimit('db_query_' . $_SERVER['REMOTE_ADDR'], 100, 60)) {
        error_log("Query rate limit exceeded for IP: " . $_SERVER['REMOTE_ADDR']);
        throw new Exception("Too many requests");
    }
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query Execution Failed: " . $e->getMessage());
        throw new Exception("Query execution failed");
    }
}

/**
 * Sanitize input data to prevent injection
 * 
 * @param mixed $data The data to sanitize
 * @return mixed The sanitized data
 */
function sanitizeInput($data) {
    return sanitize($data);
}

/**
 * Validate and sanitize integer input
 * 
 * @param mixed $input The input to validate
 * @return int|null The validated integer or null
 */
function validateInteger($input) {
    return validateInt($input);
}

/**
 * Validate and sanitize email input
 * 
 * @param string $email The email to validate
 * @return string|null The validated email or null
 */
function validateEmailInput($email) {
    return validateEmail($email);
}

// ================================================================
// SECURITY UTILITY FUNCTIONS
// ================================================================

/**
 * Comprehensive input sanitization
 * 
 * @param mixed $input The input to sanitize
 * @param bool $allowHtml Whether to allow HTML tags (default: false)
 * @return mixed The sanitized input
 */
function sanitize($input, $allowHtml = false) {
    if (is_array($input)) {
        return array_map(function($item) use ($allowHtml) {
            return sanitize($item, $allowHtml);
        }, $input);
    }
    
    // Remove extra whitespace
    $input = trim($input);
    
    // Remove backslashes
    $input = stripslashes($input);
    
    // Handle HTML based on parameter
    if (!$allowHtml) {
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    } else {
        // If HTML is allowed, use a more restrictive approach
        $input = strip_tags($input, '<p><br><strong><em><ul><ol><li><a><img>');
    }
    
    return $input;
}

/**
 * Validate and sanitize integer values
 * 
 * @param mixed $input The input to validate
 * @param int $min Minimum allowed value
 * @param int $max Maximum allowed value
 * @return int|null Validated integer or null
 */
function validateInt($input, $min = null, $max = null) {
    $options = [];
    if ($min !== null) {
        $options['options']['min_range'] = $min;
    }
    if ($max !== null) {
        $options['options']['max_range'] = $max;
    }
    
    return filter_var($input, FILTER_VALIDATE_INT, $options);
}

/**
 * Validate email addresses
 * 
 * @param string $email The email to validate
 * @return string|null Validated email or null
 */
function validateEmail($email) {
    $filtered = filter_var($email, FILTER_VALIDATE_EMAIL);
    return $filtered ? strtolower(trim($filtered)) : null;
}

/**
 * Validate URL
 * 
 * @param string $url The URL to validate
 * @return string|null Validated URL or null
 */
function validateUrl($url) {
    $filtered = filter_var($url, FILTER_VALIDATE_URL);
    return $filtered ? $filtered : null;
}

/**
 * Generate secure CSRF token
 * 
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    return $token;
}

/**
 * Validate CSRF token
 * 
 * @param string $token The token to validate
 * @return bool Whether the token is valid
 */
function validateCSRFToken($token) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Escape output for HTML display
 * 
 * @param string $output The output to escape
 * @return string Escaped output
 */
function escapeHtml($output) {
    return htmlspecialchars($output, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Escape output for JavaScript
 * 
 * @param string $output The output to escape
 * @return string Escaped output
 */
function escapeJs($output) {
    return json_encode($output, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

/**
 * Escape output for URL parameters
 * 
 * @param string $output The output to escape
 * @return string Escaped output
 */
function escapeUrl($output) {
    return urlencode($output);
}

/**
 * Prevent SQL injection by validating table/column names
 * 
 * @param string $identifier The identifier to validate
 * @param array $allowed List of allowed identifiers
 * @return string|null Validated identifier or null
 */
function validateIdentifier($identifier, $allowed = []) {
    // Check if identifier is in allowed list
    if (!empty($allowed) && !in_array($identifier, $allowed)) {
        return null;
    }
    
    // Validate identifier format (alphanumeric and underscore only)
    if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier)) {
        return $identifier;
    }
    
    return null;
}

/**
 * Rate limiting function to prevent abuse
 * 
 * @param string $identifier Unique identifier for the user/action
 * @param int $limit Maximum requests allowed
 * @param int $window Time window in seconds
 * @return bool Whether the action is allowed
 */
function rateLimit($identifier, $limit = 10, $window = 60) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $key = 'rate_limit_' . md5($identifier);
    $time = time();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 1, 'time' => $time];
        return true;
    }
    
    // Reset counter if window has passed
    if ($_SESSION[$key]['time'] < ($time - $window)) {
        $_SESSION[$key] = ['count' => 1, 'time' => $time];
        return true;
    }
    
    // Increment counter
    $_SESSION[$key]['count']++;
    
    // Check if limit exceeded
    if ($_SESSION[$key]['count'] > $limit) {
        return false;
    }
    
    return true;
}