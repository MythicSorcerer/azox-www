<?php
/**
 * Production Database Configuration for Azox Network
 * Copy this file to database.php and update with your production settings
 */

// Development Database Configuration - UPDATE FOR PRODUCTION
define('DB_HOST', 'localhost');           // Your MySQL host
define('DB_NAME', 'azox_network');        // Database name
define('DB_USER', 'root');                // MySQL username (root for development)
define('DB_PASS', '');                    // MySQL password (empty for development)
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT', 3306);                  // MySQL port

// Enable development mode
define('DEVELOPMENT_MODE', true);

// PDO options for security and performance
$pdo_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
];

// Global database connection
$pdo = null;

/**
 * Get database connection
 * @return PDO Database connection
 */
function getDB() {
    global $pdo, $pdo_options;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
            
            // Log successful connection (remove in production)
            error_log("Database connected successfully");
            return $pdo;
            
        } catch (PDOException $e) {
            // Log the error
            error_log("Database connection failed: " . $e->getMessage());
            
            // Show user-friendly error page
            http_response_code(503);
            die('
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 100px auto; padding: 40px; background: #f8f9fa; border-radius: 10px; text-align: center;">
                <h2 style="color: #DC143C; margin-bottom: 20px;">🚫 Service Temporarily Unavailable</h2>
                <p style="color: #666; font-size: 16px; line-height: 1.6;">
                    We are experiencing technical difficulties with our database connection. 
                    Please try again in a few minutes.
                </p>
                <p style="color: #999; font-size: 14px; margin-top: 30px;">
                    If you are the administrator, please check the server logs and database configuration.
                </p>
                <div style="margin-top: 30px;">
                    <a href="/" style="background: #DC143C; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                        Return to Homepage
                    </a>
                </div>
            </div>
            ');
        }
    }
    
    return $pdo;
}

/**
 * Execute a prepared statement with parameters
 * @param string $sql SQL query
 * @param array $params Parameters for the query
 * @return PDOStatement
 */
function executeQuery($sql, $params = []) {
    $db = getDB();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Get a single row from database
 * @param string $sql SQL query
 * @param array $params Parameters for the query
 * @return array|false
 */
function fetchRow($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Get all rows from database
 * @param string $sql SQL query
 * @param array $params Parameters for the query
 * @return array
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Get count of rows
 * @param string $sql SQL query
 * @param array $params Parameters for the query
 * @return int
 */
function fetchCount($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchColumn();
}

/**
 * Insert data and return last insert ID
 * @param string $sql SQL query
 * @param array $params Parameters for the query
 * @return string Last insert ID
 */
function insertAndGetId($sql, $params = []) {
    $db = getDB();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $db->lastInsertId();
}

/**
 * Begin database transaction
 */
function beginTransaction() {
    $db = getDB();
    $db->beginTransaction();
}

/**
 * Commit database transaction
 */
function commitTransaction() {
    $db = getDB();
    $db->commit();
}

/**
 * Rollback database transaction
 */
function rollbackTransaction() {
    $db = getDB();
    $db->rollback();
}

/**
 * Sanitize input for display (prevent XSS)
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
function sanitizeOutput($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token Token to verify
 * @return bool True if valid
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Log activity for debugging
 * @param string $message Message to log
 * @param string $level Log level (info, warning, error)
 */
function logActivity($message, $level = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Create logs directory if it doesn't exist
    $logDir = __DIR__ . '/../logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    error_log($logMessage, 3, $logDir . '/activity.log');
}

// Test database connection on include (only in development)
if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
    try {
        getDB();
        logActivity("Database connection established successfully");
    } catch (Exception $e) {
        logActivity("Database connection failed: " . $e->getMessage(), 'error');
    }
}
?>