<?php
/**
 * Authentication System for Azox Network
 * User registration, login, session management
 */

require_once __DIR__ . '/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Register a new user
 * @param string $username Username
 * @param string $email Email address
 * @param string $password Plain text password
 * @return array Result with success status and message
 */
function registerUser($username, $email, $password) {
    // Validate input
    $errors = [];
    
    if (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = "Username must be between 3 and 50 characters";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode(', ', $errors)];
    }
    
    // Check if username or email already exists
    $existingUser = fetchRow(
        "SELECT id FROM users WHERE username = ? OR email = ?",
        [$username, $email]
    );
    
    if ($existingUser) {
        return ['success' => false, 'message' => 'Username or email already exists'];
    }
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        // Insert new user with explicit 'user' role to prevent privilege escalation
        $userId = insertAndGetId(
            "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'user')",
            [$username, $email, $passwordHash]
        );
        
        logActivity("New user registered: $username (ID: $userId)");
        
        return [
            'success' => true, 
            'message' => 'Registration successful',
            'user_id' => $userId
        ];
        
    } catch (Exception $e) {
        logActivity("Registration failed for $username: " . $e->getMessage(), 'error');
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

/**
 * Authenticate user login
 * @param string $username Username or email
 * @param string $password Plain text password
 * @return array Result with success status and user data
 */
function loginUser($username, $password) {
    // Find user by username or email
    $user = fetchRow(
        "SELECT id, username, email, password_hash, role, is_active FROM users 
         WHERE (username = ? OR email = ?) AND is_active = 1",
        [$username, $username]
    );
    
    if (!$user) {
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    // Update last active timestamp
    executeQuery(
        "UPDATE users SET last_active = CURRENT_TIMESTAMP WHERE id = ?",
        [$user['id']]
    );
    
    // Create session
    createUserSession($user);
    
    logActivity("User logged in: " . $user['username'] . " (ID: " . $user['id'] . ")");
    
    return [
        'success' => true,
        'message' => 'Login successful',
        'user' => $user
    ];
}

/**
 * Create user session
 * @param array $user User data
 */
function createUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    
    // Generate session token for additional security
    $sessionToken = bin2hex(random_bytes(32));
    $_SESSION['session_token'] = $sessionToken;
    
    // Store session in database
    $expiresAt = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 days
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    executeQuery(
        "INSERT INTO user_sessions (user_id, session_token, expires_at, ip_address, user_agent) 
         VALUES (?, ?, ?, ?, ?)",
        [$user['id'], $sessionToken, $expiresAt, $ipAddress, $userAgent]
    );
    
    // Clean up old sessions
    executeQuery(
        "DELETE FROM user_sessions WHERE expires_at < NOW() OR user_id = ? AND session_token != ?",
        [$user['id'], $sessionToken]
    );
}

/**
 * Check if user is logged in
 * @return bool True if logged in
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Get current user data
 * @return array|null User data or null if not logged in
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role']
    ];
}

/**
 * Check if current user is admin
 * @return bool True if admin
 */
function isAdmin() {
    return isLoggedIn() && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'owner');
}

/**
 * Check if current user is owner
 * @return bool True if owner
 */
function isOwner() {
    return isLoggedIn() && $_SESSION['role'] === 'owner';
}

/**
 * Check if current user is banned
 * @return bool True if banned
 */
function isBanned() {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user = fetchRow(
        "SELECT is_banned FROM users WHERE id = ? AND is_active = 1",
        [$_SESSION['user_id']]
    );
    
    return $user && $user['is_banned'];
}

/**
 * Logout user
 */
function logoutUser() {
    if (isLoggedIn()) {
        $username = $_SESSION['username'];
        $sessionToken = $_SESSION['session_token'] ?? null;
        
        // Remove session from database
        if ($sessionToken) {
            executeQuery(
                "DELETE FROM user_sessions WHERE session_token = ?",
                [$sessionToken]
            );
        }
        
        logActivity("User logged out: $username");
    }
    
    // Destroy session
    session_destroy();
    session_start();
}

/**
 * Require login (redirect if not logged in)
 * @param string $redirectTo URL to redirect to after login
 */
function requireLogin($redirectTo = null) {
    if (!isLoggedIn()) {
        $redirect = $redirectTo ?: $_SERVER['REQUEST_URI'];
        header("Location: /auth/login.php?redirect=" . urlencode($redirect));
        exit;
    }
}

/**
 * Require admin access
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("HTTP/1.1 403 Forbidden");
        die("Access denied. Admin privileges required.");
    }
}

/**
 * Require owner access
 */
function requireOwner() {
    requireLogin();
    if (!isOwner()) {
        header("HTTP/1.1 403 Forbidden");
        die("Access denied. Owner privileges required.");
    }
}

/**
 * Get user by ID
 * @param int $userId User ID
 * @return array|null User data
 */
function getUserById($userId) {
    return fetchRow(
        "SELECT id, username, email, role, created_at, last_active FROM users WHERE id = ? AND is_active = 1",
        [$userId]
    );
}

/**
 * Update user password
 * @param int $userId User ID
 * @param string $newPassword New password
 * @return bool Success status
 */
function updateUserPassword($userId, $newPassword) {
    if (strlen($newPassword) < 6) {
        return false;
    }
    
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    try {
        executeQuery(
            "UPDATE users SET password_hash = ? WHERE id = ?",
            [$passwordHash, $userId]
        );
        
        logActivity("Password updated for user ID: $userId");
        return true;
        
    } catch (Exception $e) {
        logActivity("Password update failed for user ID $userId: " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Get unread notification count for user
 * @param int $userId User ID
 * @return int Unread count
 */
function getUnreadNotificationCount($userId) {
    return fetchCount(
        "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0",
        [$userId]
    );
}

/**
 * Validate session token
 * @return bool True if valid
 */
function validateSession() {
    if (!isLoggedIn() || !isset($_SESSION['session_token'])) {
        return false;
    }
    
    $session = fetchRow(
        "SELECT user_id FROM user_sessions WHERE session_token = ? AND expires_at > NOW()",
        [$_SESSION['session_token']]
    );
    
    return $session && $session['user_id'] == $_SESSION['user_id'];
}

// Auto-validate session on each request
if (isLoggedIn() && !validateSession()) {
    logoutUser();
}
?>