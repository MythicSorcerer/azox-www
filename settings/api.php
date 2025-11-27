<?php
require_once __DIR__ . '/../config/auth.php';

// Set JSON content type
header('Content-Type: application/json');

// Require login
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$currentUser = getCurrentUser();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'change_email':
            changeEmail($input, $currentUser);
            break;
            
        case 'change_password':
            changePassword($input, $currentUser);
            break;
            
        case 'delete_account':
            deleteAccount($input, $currentUser);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Settings API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}

/**
 * Change user email address
 */
function changeEmail($input, $currentUser) {
    $currentPassword = $input['currentPassword'] ?? '';
    $newEmail = $input['newEmail'] ?? '';
    
    // Validate input
    if (empty($currentPassword) || empty($newEmail)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    // Validate email format
    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        return;
    }
    
    // Verify current password
    if (!password_verify($currentPassword, $currentUser['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        return;
    }
    
    // Check if email is already in use
    $existingUser = fetchRow("SELECT id FROM users WHERE email = ? AND id != ?", [$newEmail, $currentUser['id']]);
    if ($existingUser) {
        echo json_encode(['success' => false, 'message' => 'Email address is already in use']);
        return;
    }
    
    // Update email
    $stmt = getPDO()->prepare("UPDATE users SET email = ? WHERE id = ?");
    $success = $stmt->execute([$newEmail, $currentUser['id']]);
    
    if ($success) {
        // Update session
        $_SESSION['email'] = $newEmail;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Email address updated successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update email address']);
    }
}

/**
 * Change user password
 */
function changePassword($input, $currentUser) {
    $currentPassword = $input['currentPassword'] ?? '';
    $newPassword = $input['newPassword'] ?? '';
    
    // Validate input
    if (empty($currentPassword) || empty($newPassword)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    // Validate password length
    if (strlen($newPassword) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
        return;
    }
    
    // Verify current password
    if (!password_verify($currentPassword, $currentUser['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        return;
    }
    
    // Check if new password is different from current
    if (password_verify($newPassword, $currentUser['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'New password must be different from current password']);
        return;
    }
    
    // Hash new password
    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update password
    $stmt = getPDO()->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $success = $stmt->execute([$newPasswordHash, $currentUser['id']]);
    
    if ($success) {
        echo json_encode([
            'success' => true, 
            'message' => 'Password updated successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password']);
    }
}

/**
 * Delete user account
 */
function deleteAccount($input, $currentUser) {
    $password = $input['password'] ?? '';
    
    // Validate input
    if (empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Password is required']);
        return;
    }
    
    // Verify password
    if (!password_verify($password, $currentUser['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Password is incorrect']);
        return;
    }
    
    // Prevent admin users from deleting their own accounts
    if ($currentUser['role'] === 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin accounts cannot be self-deleted for security reasons']);
        return;
    }
    
    $pdo = getPDO();
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        $userId = $currentUser['id'];
        
        // Delete user's forum posts
        $stmt = $pdo->prepare("UPDATE forum_posts SET is_deleted = 1 WHERE author_id = ?");
        $stmt->execute([$userId]);
        
        // Delete user's forum threads
        $stmt = $pdo->prepare("UPDATE forum_threads SET is_deleted = 1 WHERE author_id = ?");
        $stmt->execute([$userId]);
        
        // Delete user's messages
        $stmt = $pdo->prepare("UPDATE messages SET is_deleted = 1 WHERE sender_id = ?");
        $stmt->execute([$userId]);
        
        // Delete user's notifications
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Delete the user account
        $stmt = $pdo->prepare("UPDATE users SET is_active = 0, email = CONCAT('deleted_', id, '@deleted.local') WHERE id = ?");
        $stmt->execute([$userId]);
        
        // Commit transaction
        $pdo->commit();
        
        // Destroy session
        session_destroy();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Account deleted successfully'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollback();
        error_log("Account deletion error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to delete account. Please try again.']);
    }
}
?>