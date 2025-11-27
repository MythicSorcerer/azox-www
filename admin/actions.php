<?php
// Start output buffering to prevent any accidental output
ob_start();

// Disable error reporting to prevent PHP errors from corrupting JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/auth.php';

// Clear any output that might have been generated
ob_clean();

// Set JSON header
header('Content-Type: application/json');

try {
    // Check if user is logged in and is admin
    // First check basic session variables
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required - no session']);
        exit;
    }

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required - not admin role']);
        exit;
    }

    // Get user data from session (don't rely on getCurrentUser which might fail due to session validation)
    $currentUser = [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role']
    ];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);

    // For bulk operations, we don't need an ID
    $bulkActions = ['bulk_delete_threads', 'clear_chat_channel', 'bulk_user_action'];
    
    if (!$action || (!$id && !in_array($action, $bulkActions))) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }

    switch ($action) {
        case 'delete_user':
            deleteUser($id);
            break;
        case 'delete_thread':
            deleteThread($id);
            break;
        case 'delete_post':
            deletePost($id);
            break;
        case 'ban_user':
            banUser($id);
            break;
        case 'unban_user':
            unbanUser($id);
            break;
        case 'bulk_delete_threads':
            bulkDeleteThreads();
            break;
        case 'clear_chat_channel':
            clearChatChannel();
            break;
        case 'bulk_user_action':
            bulkUserAction();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            exit;
    }
} catch (Exception $e) {
    $errorDetails = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    echo json_encode($errorDetails);
    exit;
}

function deleteUser($userId) {
    global $pdo;
    
    try {
        // Don't allow deleting admin users
        $user = fetchRow("SELECT role FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        if ($user['role'] === 'admin') {
            echo json_encode(['success' => false, 'message' => 'Cannot delete admin users']);
            return;
        }
        
        // Soft delete - mark as inactive instead of hard delete to preserve data integrity
        $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        $stmt->execute([$userId]);
        
        // Also soft delete their content
        $pdo->prepare("UPDATE forum_threads SET is_deleted = 1 WHERE author_id = ?")->execute([$userId]);
        $pdo->prepare("UPDATE forum_posts SET is_deleted = 1 WHERE author_id = ?")->execute([$userId]);
        $pdo->prepare("UPDATE messages SET is_deleted = 1 WHERE sender_id = ?")->execute([$userId]);
        
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function deleteThread($threadId) {
    global $pdo;
    
    try {
        // Check if forum_threads has is_deleted column, if not, use a different approach
        $columns = $pdo->query("DESCRIBE forum_threads")->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('is_deleted', $columns)) {
            // Soft delete thread and all its posts
            $stmt = $pdo->prepare("UPDATE forum_threads SET is_deleted = 1 WHERE id = ?");
            $stmt->execute([$threadId]);
            
            $stmt = $pdo->prepare("UPDATE forum_posts SET is_deleted = 1 WHERE thread_id = ?");
            $stmt->execute([$threadId]);
        } else {
            // If is_deleted column doesn't exist, just delete all posts (effectively hiding the thread)
            $stmt = $pdo->prepare("UPDATE forum_posts SET is_deleted = 1 WHERE thread_id = ?");
            $stmt->execute([$threadId]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Thread deleted successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function deletePost($postId) {
    global $pdo;
    
    try {
        // Just delete the individual post - don't auto-delete threads
        $stmt = $pdo->prepare("UPDATE forum_posts SET is_deleted = 1 WHERE id = ?");
        $stmt->execute([$postId]);
        
        echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function banUser($userId) {
    global $pdo;
    
    try {
        // Don't allow banning admin users
        $user = fetchRow("SELECT role FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        if ($user['role'] === 'admin') {
            echo json_encode(['success' => false, 'message' => 'Cannot ban admin users']);
            return;
        }
        
        $stmt = $pdo->prepare("UPDATE users SET is_banned = 1, banned_at = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
        
        echo json_encode(['success' => true, 'message' => 'User banned successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function unbanUser($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_banned = 0, banned_at = NULL WHERE id = ?");
        $stmt->execute([$userId]);
        
        echo json_encode(['success' => true, 'message' => 'User unbanned successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function bulkDeleteThreads() {
    global $pdo;
    
    try {
        $category = $_POST['category'] ?? '';
        $date = $_POST['date'] ?? '';
        
        $sql = "UPDATE forum_posts SET is_deleted = 1 WHERE thread_id IN (SELECT id FROM forum_threads WHERE 1=1";
        $params = [];
        
        if ($category) {
            $sql .= " AND category_id = ?";
            $params[] = $category;
        }
        
        if ($date) {
            $sql .= " AND created_at < ?";
            $params[] = $date;
        }
        
        $sql .= ")";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $postsDeleted = $stmt->rowCount();
        
        // Also try to delete threads if the column exists
        $columns = $pdo->query("DESCRIBE forum_threads")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('is_deleted', $columns)) {
            $threadSql = "UPDATE forum_threads SET is_deleted = 1 WHERE 1=1";
            if ($category) {
                $threadSql .= " AND category_id = ?";
            }
            if ($date) {
                $threadSql .= " AND created_at < ?";
            }
            
            $threadStmt = $pdo->prepare($threadSql);
            $threadStmt->execute($params);
            $threadsDeleted = $threadStmt->rowCount();
        } else {
            $threadsDeleted = 0;
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Bulk deletion completed: {$threadsDeleted} threads and {$postsDeleted} posts deleted"
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function clearChatChannel() {
    global $pdo;
    
    try {
        $channel = $_POST['channel'] ?? '';
        $date = $_POST['date'] ?? '';
        
        $sql = "UPDATE messages SET is_deleted = 1 WHERE 1=1";
        $params = [];
        
        if ($channel && $channel !== 'all') {
            $sql .= " AND channel = ?";
            $params[] = $channel;
        }
        
        if ($date) {
            $sql .= " AND created_at < ?";
            $params[] = $date;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $messagesDeleted = $stmt->rowCount();
        
        $channelText = ($channel === 'all') ? 'all channels' : "#{$channel}";
        $dateText = $date ? " older than {$date}" : '';
        
        echo json_encode([
            'success' => true,
            'message' => "Chat cleared: {$messagesDeleted} messages deleted from {$channelText}{$dateText}"
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function bulkUserAction() {
    global $pdo;
    
    try {
        $action = $_POST['action'] ?? '';
        $days = intval($_POST['days'] ?? 0);
        
        if (!$action || !$days) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            return;
        }
        
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        if ($action === 'ban_inactive') {
            $stmt = $pdo->prepare("
                UPDATE users
                SET is_banned = 1, banned_at = NOW()
                WHERE last_active < ? AND role != 'admin' AND is_banned = 0
            ");
            $stmt->execute([$cutoffDate]);
            $affected = $stmt->rowCount();
            
            echo json_encode([
                'success' => true,
                'message' => "Bulk ban completed: {$affected} inactive users banned"
            ]);
        } elseif ($action === 'delete_inactive') {
            // Soft delete inactive users and their content
            $stmt = $pdo->prepare("
                UPDATE users
                SET is_active = 0
                WHERE last_active < ? AND role != 'admin' AND is_active = 1
            ");
            $stmt->execute([$cutoffDate]);
            $affected = $stmt->rowCount();
            
            // Also delete their content
            $pdo->prepare("
                UPDATE forum_posts SET is_deleted = 1
                WHERE author_id IN (
                    SELECT id FROM users
                    WHERE last_active < ? AND role != 'admin' AND is_active = 0
                )
            ")->execute([$cutoffDate]);
            
            $pdo->prepare("
                UPDATE messages SET is_deleted = 1
                WHERE sender_id IN (
                    SELECT id FROM users
                    WHERE last_active < ? AND role != 'admin' AND is_active = 0
                )
            ")->execute([$cutoffDate]);
            
            echo json_encode([
                'success' => true,
                'message' => "Bulk deletion completed: {$affected} inactive users and their content deleted"
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Unknown bulk user action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>