<?php
/**
 * Database Configuration for Azox Network
 * MySQL Connection and Database Helper Functions
 */

// Database configuration - Update these settings for your environment
define('DB_HOST', '127.0.0.1'); // Use 127.0.0.1 instead of localhost for socket issues
define('DB_NAME', 'azox_network');
define('DB_USER', 'root'); // Change this to your MySQL username
define('DB_PASS', ''); // Change this to your MySQL password
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT', 3306); // Default MySQL port

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
        // Try multiple connection methods
        $connectionAttempts = [
            "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            "mysql:host=localhost;dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            "mysql:host=127.0.0.1;dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            "mysql:unix_socket=/tmp/mysql.sock;dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=" . DB_NAME . ";charset=" . DB_CHARSET
        ];
        
        $lastError = null;
        
        foreach ($connectionAttempts as $dsn) {
            try {
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
                error_log("Database connected successfully with DSN: $dsn");
                return $pdo;
            } catch (PDOException $e) {
                $lastError = $e;
                continue;
            }
        }
        
        // If all attempts failed, try to create database
        $createAttempts = [
            "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET,
            "mysql:host=localhost;charset=" . DB_CHARSET,
            "mysql:host=127.0.0.1;charset=" . DB_CHARSET,
            "mysql:unix_socket=/tmp/mysql.sock;charset=" . DB_CHARSET,
            "mysql:unix_socket=/var/run/mysqld/mysqld.sock;charset=" . DB_CHARSET
        ];
        
        foreach ($createAttempts as $dsn) {
            try {
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
                $pdo->exec("USE " . DB_NAME);
                error_log("Database created successfully with DSN: $dsn");
                return $pdo;
            } catch (PDOException $e) {
                $lastError = $e;
                continue;
            }
        }
        
        // All attempts failed
        error_log("All database connection attempts failed. Last error: " . $lastError->getMessage());
        
        // Show helpful error message
        die('
        <div style="font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; border-radius: 10px;">
            <h2 style="color: #DC143C;">MySQL Connection Failed</h2>
            <p><strong>Error:</strong> ' . htmlspecialchars($lastError->getMessage()) . '</p>
            
            <h3>Troubleshooting Steps:</h3>
            <ol>
                <li><strong>Check if MySQL is running:</strong>
                    <pre style="background: #eee; padding: 10px; border-radius: 5px;">
# On macOS with Homebrew:
brew services start mysql

# On macOS with MAMP:
Start MAMP application

# On Linux:
sudo systemctl start mysql
# or
sudo service mysql start</pre>
                </li>
                
                <li><strong>Test MySQL connection:</strong>
                    <pre style="background: #eee; padding: 10px; border-radius: 5px;">mysql -u root -p</pre>
                </li>
                
                <li><strong>Update database settings in config/database.php:</strong>
                    <ul>
                        <li>Change DB_HOST to your MySQL host</li>
                        <li>Update DB_USER and DB_PASS with your credentials</li>
                        <li>Verify DB_PORT (usually 3306)</li>
                    </ul>
                </li>
                
                <li><strong>Common MySQL socket locations:</strong>
                    <ul>
                        <li>/tmp/mysql.sock (macOS Homebrew)</li>
                        <li>/var/run/mysqld/mysqld.sock (Linux)</li>
                        <li>/Applications/MAMP/tmp/mysql/mysql.sock (MAMP)</li>
                    </ul>
                </li>
            </ol>
            
            <p><strong>Quick Fix for macOS:</strong></p>
            <p>If using Homebrew MySQL, try: <code>brew services restart mysql</code></p>
            
            <p>Once MySQL is running, <a href="/setup.php" style="background: #DC143C; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;">run the setup script</a></p>
        </div>
        ');
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
    error_log($logMessage, 3, __DIR__ . '/../logs/activity.log');
}

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

// Test database connection on include
try {
    getDB();
    logActivity("Database connection established successfully");
} catch (Exception $e) {
    logActivity("Database connection failed: " . $e->getMessage(), 'error');
}
?>