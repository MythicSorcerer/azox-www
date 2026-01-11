<?php
require_once __DIR__ . '/../config/auth.php';

// Set JSON header
header('Content-Type: application/json');

// Require admin access
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

$currentUser = getCurrentUser();
$action = $_GET['action'] ?? '';

// Log all monitoring activity for audit
function logMonitoringActivity($action, $details = '') {
    global $currentUser;
    logActivity("Admin monitoring: {$currentUser['username']} - $action $details", 'admin');
}

try {
    switch ($action) {
        case 'get_conversation':
            handleGetConversation();
            break;
            
        case 'get_user_activity':
            handleGetUserActivity();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    logActivity("Monitor API error: " . $e->getMessage(), 'error');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

/**
 * Get conversation between two users (read-only)
 */
function handleGetConversation() {
    $user1 = $_GET['user1'] ?? '';
    $user2 = $_GET['user2'] ?? '';
    
    if (empty($user1) || empty($user2)) {
        echo json_encode(['success' => false, 'message' => 'Both users required']);
        return;
    }
    
    // Get user IDs
    $user1Data = fetchRow("SELECT id, username FROM users WHERE username = ? AND is_active = 1", [$user1]);
    $user2Data = fetchRow("SELECT id, username FROM users WHERE username = ? AND is_active = 1", [$user2]);
    
    if (!$user1Data || !$user2Data) {
        echo json_encode(['success' => false, 'message' => 'One or both users not found']);
        return;
    }
    
    // Log monitoring activity
    logMonitoringActivity('viewed_conversation', "between $user1 and $user2");
    
    // Get conversation messages (last 100 messages)
    $messages = fetchAll("
        SELECT
            m.*,
            u.username as author_name,
            u.role as author_role
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.receiver_id IS NOT NULL
        AND ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
        AND m.is_deleted = 0
        ORDER BY m.created_at DESC
        LIMIT 100
    ", [$user1Data['id'], $user2Data['id'], $user2Data['id'], $user1Data['id']]);
    
    // Reverse to show chronological order
    $messages = array_reverse($messages);
    
    // Format timestamps
    foreach ($messages as &$message) {
        $message['created_at'] = date('c', strtotime($message['created_at']));
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'user1' => $user1,
        'user2' => $user2,
        'count' => count($messages)
    ]);
}

/**
 * Get user's recent activity (read-only)
 */
function handleGetUserActivity() {
    $username = $_GET['username'] ?? '';
    
    if (empty($username)) {
        echo json_encode(['success' => false, 'message' => 'Username required']);
        return;
    }
    
    // Get user data
    $userData = fetchRow("SELECT id, username FROM users WHERE username = ? AND is_active = 1", [$username]);
    if (!$userData) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        return;
    }
    
    // Log monitoring activity
    logMonitoringActivity('viewed_user_activity', "for $username");
    
    // Get user's recent messages (last 50 messages from past 7 days)
    $activity = fetchAll("
        SELECT
            m.*,
            u2.username as receiver_name
        FROM messages m
        LEFT JOIN users u2 ON m.receiver_id = u2.id
        WHERE m.sender_id = ?
        AND m.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
        AND m.is_deleted = 0
        ORDER BY m.created_at DESC
        LIMIT 50
    ", [$userData['id']]);
    
    // Format timestamps
    foreach ($activity as &$item) {
        $item['created_at'] = date('c', strtotime($item['created_at']));
    }
    
    echo json_encode([
        'success' => true,
        'activity' => $activity,
        'username' => $username,
        'count' => count($activity)
    ]);
}
?>