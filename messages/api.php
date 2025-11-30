<?php
require_once __DIR__ . '/../config/auth.php';

// Set JSON header
header('Content-Type: application/json');

// Require login for all API calls
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Check if user is banned (only for sending messages)
if (isBanned() && ($_GET['action'] === 'send_message' || ($_POST && json_decode(file_get_contents('php://input'), true)['action'] === 'send_message'))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You are banned from sending messages']);
    exit;
}

$currentUser = getCurrentUser();

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Handle POST requests
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
}

try {
    switch ($action) {
        case 'get_messages':
            handleGetMessages();
            break;
            
        case 'send_message':
            handleSendMessage($input);
            break;
            
        case 'delete_message':
            handleDeleteMessage($input);
            break;
            
        case 'get_online_users':
            handleGetOnlineUsers();
            break;
            
        case 'update_activity':
            handleUpdateActivity();
            break;
            
        case 'get_all_users':
            handleGetAllUsers();
            break;
            
        case 'check_new_dms':
            handleCheckNewDMs();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    logActivity("Chat API error: " . $e->getMessage(), 'error');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

/**
 * Get messages for a channel
 */
function handleGetMessages() {
    global $currentUser;
    
    $channel = $_GET['channel'] ?? null;
    $dmUser = $_GET['dm_user'] ?? null;
    $after = (int)($_GET['after'] ?? 0);
    $limit = min(50, (int)($_GET['limit'] ?? 50));
    
    if ($dmUser) {
        // Handle direct messages
        $dmUserData = fetchRow("SELECT id, username FROM users WHERE username = ? AND is_active = 1", [$dmUser]);
        if (!$dmUserData) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        // Check if current user can view these DMs (user is participant or admin)
        $canView = ($currentUser['username'] === $dmUser) ||
                   ($currentUser['role'] === 'admin' || $currentUser['role'] === 'owner');
        
        if (!$canView) {
            // Get messages where current user is sender or receiver
            $messages = fetchAll("
                SELECT
                    m.*,
                    u.username as author_name,
                    u.role as author_role
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE m.receiver_id IS NOT NULL
                AND ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
                AND m.id > ?
                AND m.is_deleted = 0
                ORDER BY m.created_at ASC
                LIMIT ?
            ", [$currentUser['id'], $dmUserData['id'], $dmUserData['id'], $currentUser['id'], $after, $limit]);
        } else {
            // Admin view - can see all DMs involving this user
            $messages = fetchAll("
                SELECT
                    m.*,
                    u.username as author_name,
                    u.role as author_role
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE m.receiver_id IS NOT NULL
                AND (m.sender_id = ? OR m.receiver_id = ?)
                AND m.id > ?
                AND m.is_deleted = 0
                ORDER BY m.created_at ASC
                LIMIT ?
            ", [$dmUserData['id'], $dmUserData['id'], $after, $limit]);
        }
        
        // Format timestamps for JavaScript
        foreach ($messages as &$message) {
            $message['created_at'] = date('c', strtotime($message['created_at']));
        }
        
        echo json_encode([
            'success' => true,
            'messages' => $messages,
            'dm_user' => $dmUser
        ]);
        
    } else {
        // Handle channel messages
        $channel = $channel ?? 'general';
        
        // Validate channel
        $validChannels = ['general', 'pvp', 'trading', 'help'];
        if (!in_array($channel, $validChannels)) {
            $channel = 'general';
        }
        
        // Get messages
        $messages = fetchAll("
            SELECT
                m.*,
                u.username as author_name,
                u.role as author_role
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.channel = ?
            AND m.receiver_id IS NULL
            AND m.id > ?
            AND m.is_deleted = 0
            ORDER BY m.created_at ASC
            LIMIT ?
        ", [$channel, $after, $limit]);
        
        // Format timestamps for JavaScript
        foreach ($messages as &$message) {
            $message['created_at'] = date('c', strtotime($message['created_at']));
        }
        
        echo json_encode([
            'success' => true,
            'messages' => $messages,
            'channel' => $channel
        ]);
    }
}

/**
 * Send a new message
 */
function handleSendMessage($input) {
    global $currentUser;
    
    $channel = $input['channel'] ?? null;
    $dmUser = $input['dm_user'] ?? null;
    $content = trim($input['content'] ?? '');
    
    // Validate input
    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Message content is required']);
        return;
    }
    
    if (strlen($content) > 1000) {
        echo json_encode(['success' => false, 'message' => 'Message is too long (max 1000 characters)']);
        return;
    }
    
    // Rate limiting - max 10 messages per minute
    $recentMessages = fetchCount("
        SELECT COUNT(*)
        FROM messages
        WHERE sender_id = ?
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
    ", [$currentUser['id']]);
    
    if ($recentMessages >= 10) {
        echo json_encode(['success' => false, 'message' => 'Rate limit exceeded. Please slow down.']);
        return;
    }
    
    if ($dmUser) {
        // Handle direct message
        $dmUserData = fetchRow("SELECT id, username FROM users WHERE username = ? AND is_active = 1", [$dmUser]);
        if (!$dmUserData) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        if ($dmUserData['id'] == $currentUser['id']) {
            echo json_encode(['success' => false, 'message' => 'Cannot send message to yourself']);
            return;
        }
        
        try {
            // Insert direct message
            $messageId = insertAndGetId("
                INSERT INTO messages (sender_id, receiver_id, content, message_type)
                VALUES (?, ?, ?, 'text')
            ", [$currentUser['id'], $dmUserData['id'], $content]);
            
            // Update user activity
            executeQuery("UPDATE users SET last_active = NOW() WHERE id = ?", [$currentUser['id']]);
            
            logActivity("DM sent by {$currentUser['username']} to {$dmUser}");
            
            echo json_encode([
                'success' => true,
                'message_id' => $messageId,
                'message' => 'Direct message sent successfully'
            ]);
            
        } catch (Exception $e) {
            logActivity("Failed to send DM: " . $e->getMessage(), 'error');
            echo json_encode(['success' => false, 'message' => 'Failed to send direct message']);
        }
        
    } else {
        // Handle channel message
        $channel = $channel ?? 'general';
        
        // Validate channel
        $validChannels = ['general', 'pvp', 'trading', 'help'];
        if (!in_array($channel, $validChannels)) {
            echo json_encode(['success' => false, 'message' => 'Invalid channel']);
            return;
        }
        
        try {
            // Insert message
            $messageId = insertAndGetId("
                INSERT INTO messages (sender_id, channel, content, message_type)
                VALUES (?, ?, ?, 'text')
            ", [$currentUser['id'], $channel, $content]);
            
            // Update user activity
            executeQuery("UPDATE users SET last_active = NOW() WHERE id = ?", [$currentUser['id']]);
            
            logActivity("Message sent by {$currentUser['username']} in #{$channel}");
            
            echo json_encode([
                'success' => true,
                'message_id' => $messageId,
                'message' => 'Message sent successfully'
            ]);
            
        } catch (Exception $e) {
            logActivity("Failed to send message: " . $e->getMessage(), 'error');
            echo json_encode(['success' => false, 'message' => 'Failed to send message']);
        }
    }
}

/**
 * Get online users
 */
function handleGetOnlineUsers() {
    $users = fetchAll("
        SELECT username, role, last_active
        FROM users 
        WHERE last_active > DATE_SUB(NOW(), INTERVAL 15 MINUTE) 
        AND is_active = 1
        ORDER BY 
            CASE WHEN role = 'admin' THEN 0 ELSE 1 END,
            username ASC
    ");
    
    // Format timestamps
    foreach ($users as &$user) {
        $user['last_active'] = date('c', strtotime($user['last_active']));
    }
    
    echo json_encode([
        'success' => true,
        'users' => $users,
        'count' => count($users)
    ]);
}

/**
 * Update user activity timestamp
 */
function handleUpdateActivity() {
    global $currentUser;
    
    try {
        executeQuery("UPDATE users SET last_active = NOW() WHERE id = ?", [$currentUser['id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Activity updated'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update activity']);
    }
}

/**
 * Delete a message (user can delete own messages, admin can delete any)
 */
function handleDeleteMessage($input) {
    global $currentUser;
    
    $messageId = intval($input['message_id'] ?? 0);
    
    if (!$messageId) {
        echo json_encode(['success' => false, 'message' => 'Message ID is required']);
        return;
    }
    
    try {
        // Check if the message exists and get its details
        $message = fetchRow("SELECT sender_id FROM messages WHERE id = ? AND is_deleted = 0", [$messageId]);
        if (!$message) {
            echo json_encode(['success' => false, 'message' => 'Message not found']);
            return;
        }
        
        // Allow deletion if user owns the message or is admin/owner
        if ($message['sender_id'] != $currentUser['id'] &&
            $currentUser['role'] !== 'admin' &&
            $currentUser['role'] !== 'owner') {
            echo json_encode(['success' => false, 'message' => 'You can only delete your own messages']);
            return;
        }
        
        // Soft delete message
        executeQuery("UPDATE messages SET is_deleted = 1 WHERE id = ?", [$messageId]);
        
        logActivity("Message deleted by {$currentUser['username']}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);
        
    } catch (Exception $e) {
        logActivity("Failed to delete message: " . $e->getMessage(), 'error');
        echo json_encode(['success' => false, 'message' => 'Failed to delete message']);
    }
}

/**
 * Create system message (for admin use)
 */
function createSystemMessage($channel, $content) {
    try {
        insertAndGetId("
            INSERT INTO messages (sender_id, channel, content, message_type) 
            VALUES (1, ?, ?, 'system')
        ", [$channel, $content]);
        
        return true;
    } catch (Exception $e) {
        logActivity("Failed to create system message: " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Handle user join/leave messages
 */
function handleUserJoinLeave($userId, $action) {
    $user = getUserById($userId);
    if (!$user) return;
    
    $message = $action === 'join' 
        ? "{$user['username']} joined the chat"
        : "{$user['username']} left the chat";
    
    createSystemMessage('general', $message);
}

// Auto-cleanup old messages (keep last 1000 messages per channel)
function cleanupOldMessages() {
    $channels = ['general', 'pvp', 'trading', 'help'];
    
    foreach ($channels as $channel) {
        try {
            // Get the ID of the 1000th most recent message
            $cutoffId = fetchRow("
                SELECT id 
                FROM messages 
                WHERE channel = ? AND is_deleted = 0
                ORDER BY created_at DESC 
                LIMIT 1 OFFSET 999
            ", [$channel]);
            
            if ($cutoffId) {
                // Mark older messages as deleted
                executeQuery("
                    UPDATE messages 
                    SET is_deleted = 1 
                    WHERE channel = ? AND id < ? AND is_deleted = 0
                ", [$channel, $cutoffId['id']]);
            }
        } catch (Exception $e) {
            logActivity("Failed to cleanup old messages for channel $channel: " . $e->getMessage(), 'error');
        }
    }
}

/**
 * Get all users for offline messaging dropdown
 */
function handleGetAllUsers() {
    global $currentUser;
    
    try {
        $users = fetchAll("
            SELECT id, username, role, last_active,
                   CASE WHEN last_active > DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 1 ELSE 0 END as is_online
            FROM users
            WHERE is_active = 1
            AND id != ?
            ORDER BY
                is_online DESC,
                CASE WHEN role = 'admin' THEN 0 ELSE 1 END,
                username ASC
        ", [$currentUser['id']]);
        
        // Format timestamps
        foreach ($users as &$user) {
            $user['last_active'] = date('c', strtotime($user['last_active']));
            $user['is_online'] = (bool)$user['is_online'];
        }
        
        echo json_encode([
            'success' => true,
            'users' => $users
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch users']);
    }
}

/**
 * Check for new DMs for notification system
 */
function handleCheckNewDMs() {
    global $currentUser;
    
    $lastCheck = $_GET['last_check'] ?? null;
    
    try {
        $query = "
            SELECT
                m.id,
                m.content,
                m.created_at,
                u.username as sender_name,
                u.role as sender_role
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.receiver_id = ?
            AND m.is_deleted = 0
        ";
        
        $params = [$currentUser['id']];
        
        if ($lastCheck) {
            $query .= " AND m.created_at > ?";
            $params[] = date('Y-m-d H:i:s', strtotime($lastCheck));
        } else {
            // If no last check, only get messages from last 5 minutes to avoid spam
            $query .= " AND m.created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
        }
        
        $query .= " ORDER BY m.created_at DESC LIMIT 10";
        
        $newDMs = fetchAll($query, $params);
        
        // Format timestamps
        foreach ($newDMs as &$dm) {
            $dm['created_at'] = date('c', strtotime($dm['created_at']));
        }
        
        echo json_encode([
            'success' => true,
            'new_dms' => $newDMs,
            'count' => count($newDMs)
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to check for new DMs']);
    }
}

// Run cleanup occasionally (1% chance per request)
if (rand(1, 100) === 1) {
    cleanupOldMessages();
}
?>