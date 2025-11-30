<?php
/**
 * Diagnostic script to identify registration role assignment issue
 */

// Force development mode for CLI execution
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['HTTP_HOST'] = 'localhost:8000';

require_once __DIR__ . '/../config/database.php';

// Get the PDO connection directly
function getConnection() {
    global $pdo;
    return $pdo;
}

echo "=== REGISTRATION ROLE ASSIGNMENT DIAGNOSTIC ===\n\n";

// 1. Check the actual database schema for users table
echo "1. Checking users table schema:\n";
echo "--------------------------------\n";

try {
    $pdo = getConnection();
    
    // Get column information
    $stmt = $pdo->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
    $roleColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($roleColumn) {
        echo "Role column found:\n";
        echo "  Type: " . $roleColumn['Type'] . "\n";
        echo "  Default: " . ($roleColumn['Default'] ?? 'NULL') . "\n";
        echo "  Null: " . $roleColumn['Null'] . "\n";
        echo "  Key: " . $roleColumn['Key'] . "\n";
        echo "  Extra: " . $roleColumn['Extra'] . "\n";
    } else {
        echo "ERROR: Role column not found in users table!\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. Check existing users and their roles
echo "2. Checking existing users and their roles:\n";
echo "-------------------------------------------\n";

try {
    $stmt = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($users) {
        foreach ($users as $user) {
            echo sprintf("  ID: %d | Username: %-20s | Role: %-10s | Created: %s\n", 
                $user['id'], 
                $user['username'], 
                $user['role'], 
                $user['created_at']
            );
        }
    } else {
        echo "  No users found in database.\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Test INSERT without specifying role
echo "3. Testing INSERT without specifying role:\n";
echo "------------------------------------------\n";

$testUsername = 'debug_test_' . time();
$testEmail = 'debug_' . time() . '@test.com';
$testPassword = password_hash('testpass', PASSWORD_DEFAULT);

try {
    // First, let's see what SQL is being generated
    $sql = "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)";
    echo "SQL Query: $sql\n";
    echo "Parameters: username='$testUsername', email='$testEmail'\n\n";
    
    // Execute the insert
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$testUsername, $testEmail, $testPassword]);
    $userId = $pdo->lastInsertId();
    
    echo "User inserted with ID: $userId\n";
    
    // Now check what role was assigned
    $stmt = $pdo->prepare("SELECT username, role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $newUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Result:\n";
    echo "  Username: " . $newUser['username'] . "\n";
    echo "  Role assigned: " . $newUser['role'] . "\n";
    
    if ($newUser['role'] === 'owner') {
        echo "\n⚠️  PROBLEM CONFIRMED: New users are being assigned 'owner' role!\n";
    } elseif ($newUser['role'] === 'user') {
        echo "\n✓ Good: New user correctly assigned 'user' role.\n";
    } else {
        echo "\n⚠️  Unexpected role: " . $newUser['role'] . "\n";
    }
    
    // Clean up test user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    echo "\nTest user cleaned up.\n";
    
} catch (Exception $e) {
    echo "ERROR during test: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. Check if there's an ALTER TABLE statement that might have changed the default
echo "4. Checking for any database modifications:\n";
echo "-------------------------------------------\n";

// Check if there are any SQL files that might have altered the table
$sqlFiles = [
    __DIR__ . '/../config/add_missing_columns.sql',
    __DIR__ . '/../config/fix_database.php',
    __DIR__ . '/../config/update_messages_table.sql'
];

foreach ($sqlFiles as $file) {
    if (file_exists($file)) {
        echo "Found SQL file: " . basename($file) . "\n";
        $content = file_get_contents($file);
        if (stripos($content, 'ALTER TABLE users') !== false || stripos($content, 'role') !== false) {
            echo "  ⚠️  This file contains ALTER TABLE or role-related changes!\n";
            // Show relevant lines
            $lines = explode("\n", $content);
            foreach ($lines as $lineNum => $line) {
                if (stripos($line, 'ALTER') !== false || stripos($line, 'role') !== false) {
                    echo "    Line " . ($lineNum + 1) . ": " . trim($line) . "\n";
                }
            }
        }
    }
}

echo "\n";

// 5. Proposed fix
echo "5. PROPOSED FIX:\n";
echo "----------------\n";
echo "The registerUser() function in /config/auth.php should explicitly set the role.\n";
echo "Change line 61 from:\n";
echo '  "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)"' . "\n";
echo "To:\n";
echo '  "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, \'user\')"' . "\n";
echo "\nThis ensures new users always get 'user' role regardless of database defaults.\n";

echo "\n=== END DIAGNOSTIC ===\n";
?>