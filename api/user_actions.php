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
    // Check if user is logged in
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }

    $currentUser = getCurrentUser();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);

    if (!$id || !$action) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }

    switch ($action) {
        case 'delete_own_post':
            deleteOwnPost($id, $currentUser);
            break;
        case 'delete_own_message':
            deleteOwnMessage($id, $currentUser);
            break;
        case 'delete_own_thread':
            deleteOwnThread($id, $currentUser);
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
        'line' => $e->getLine()
    ];
    echo json_encode($errorDetails);
    exit;
}

function deleteOwnPost($postId, $currentUser) {
    global $pdo;
    
    try {
        // Check if the post belongs to the current user or if user is admin
        $post = fetchRow("SELECT author_id FROM forum_posts WHERE id = ? AND is_deleted = 0", [$postId]);
        if (!$post) {
            echo json_encode(['success' => false, 'message' => 'Post not found']);
            return;
        }
        
        // Allow deletion if user owns the post or is admin
        if ($post['author_id'] != $currentUser['id'] && $currentUser['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'You can only delete your own posts']);
            return;
        }
        
        // Just delete the individual post - don't auto-delete threads
        $stmt = $pdo->prepare("UPDATE forum_posts SET is_deleted = 1 WHERE id = ?");
        $stmt->execute([$postId]);
        
        echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function deleteOwnMessage($messageId, $currentUser) {
    global $pdo;
    
    try {
        // Check if the message belongs to the current user or if user is admin
        $message = fetchRow("SELECT sender_id FROM messages WHERE id = ? AND is_deleted = 0", [$messageId]);
        if (!$message) {
            echo json_encode(['success' => false, 'message' => 'Message not found']);
            return;
        }
        
        // Allow deletion if user owns the message or is admin
        if ($message['sender_id'] != $currentUser['id'] && $currentUser['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'You can only delete your own messages']);
            return;
        }
        
        // Soft delete message
        $stmt = $pdo->prepare("UPDATE messages SET is_deleted = 1 WHERE id = ?");
        $stmt->execute([$messageId]);
        
        echo json_encode(['success' => true, 'message' => 'Message deleted successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function deleteOwnThread($threadId, $currentUser) {
    global $pdo;
    
    try {
        // Check if the thread belongs to the current user or if user is admin
        $thread = fetchRow("SELECT author_id, title FROM forum_threads WHERE id = ?", [$threadId]);
        if (!$thread) {
            echo json_encode(['success' => false, 'message' => 'Thread not found']);
            return;
        }
        
        // Allow deletion if user owns the thread or is admin
        if ($thread['author_id'] != $currentUser['id'] && $currentUser['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'You can only delete your own threads']);
            return;
        }
        
        beginTransaction();
        
        // Delete all posts in the thread
        $stmt = $pdo->prepare("UPDATE forum_posts SET is_deleted = 1 WHERE thread_id = ?");
        $stmt->execute([$threadId]);
        
        // Check if is_locked column exists and handle accordingly
        $columns = $pdo->query("SHOW COLUMNS FROM forum_threads LIKE 'is_deleted'")->fetchAll();
        if (count($columns) > 0) {
            // Column exists, use soft delete
            $stmt = $pdo->prepare("UPDATE forum_threads SET is_deleted = 1 WHERE id = ?");
        } else {
            // Column doesn't exist, use is_locked as deletion marker
            $stmt = $pdo->prepare("UPDATE forum_threads SET is_locked = 1 WHERE id = ?");
        }
        $stmt->execute([$threadId]);
        
        commitTransaction();
        
        echo json_encode(['success' => true, 'message' => 'Thread deleted successfully', 'redirect' => '/forum/']);
    } catch (Exception $e) {
        rollbackTransaction();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>