<?php
/**
 * Production Diagnostic Script for Registration Role Issue
 * This script checks the production database schema and tests registration
 */

// Force production configuration
$_SERVER['SERVER_NAME'] = 'azox.net'; // This will trigger production config
require_once __DIR__ . '/../config/database.php';

echo "=== PRODUCTION DATABASE DIAGNOSTIC ===\n\n";

// 1. Check the actual database schema for users table
echo "1. Checking PRODUCTION users table schema:\n";
echo "-------------------------------------------\n";

try {
    // Get column information
    $stmt = $pdo->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
    $roleColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($roleColumn) {
        echo "Role column configuration:\n";
        echo "  Type: " . $roleColumn['Type'] . "\n";
        echo "  Default: " . ($roleColumn['Default'] ?? 'NULL') . "\n";
        echo "  Null: " . $roleColumn['Null'] . "\n";
        
        // Check if default is 'owner'
        if ($roleColumn['Default'] === 'owner') {
            echo "\n⚠️  PROBLEM FOUND: Default role is set to 'owner' in production!\n";
            echo "This is causing all new users to become owners.\n";
        }
    } else {
        echo "ERROR: Role column not found in users table!\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. Check existing users and their roles
echo "2. Recent users in PRODUCTION database:\n";
echo "----------------------------------------\n";

try {
    $stmt = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $ownerCount = 0;
    if ($users) {
        foreach ($users as $user) {
            echo sprintf("  ID: %d | Username: %-20s | Role: %-10s | Created: %s\n", 
                $user['id'], 
                $user['username'], 
                $user['role'], 
                $user['created_at']
            );
            if ($user['role'] === 'owner') {
                $ownerCount++;
            }
        }
        
        if ($ownerCount > 0) {
            echo "\n⚠️  Found $ownerCount users with 'owner' role in recent registrations!\n";
        }
    } else {
        echo "  No users found in database.\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Show role distribution
echo "3. Role distribution in PRODUCTION:\n";
echo "------------------------------------\n";

try {
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($roles as $role) {
        echo sprintf("  %s: %d users\n", $role['role'], $role['count']);
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. SQL commands to fix the issue
echo "4. SQL COMMANDS TO FIX THE ISSUE:\n";
echo "==================================\n";
echo "Run these commands on your production database:\n\n";

echo "-- Step 1: Fix the default value for the role column\n";
echo "ALTER TABLE users ALTER COLUMN role SET DEFAULT 'user';\n\n";

echo "-- Step 2: Update existing 'owner' users that shouldn't be owners\n";
echo "-- (Review this list first - keep legitimate owners!)\n";
echo "SELECT id, username, email, created_at FROM users WHERE role = 'owner';\n\n";

echo "-- Step 3: Update incorrectly assigned owners to regular users\n";
echo "-- (Replace the IDs with actual user IDs that should not be owners)\n";
echo "-- UPDATE users SET role = 'user' WHERE id IN (id1, id2, id3) AND role = 'owner';\n\n";

echo "-- Step 4: Verify the fix\n";
echo "SHOW COLUMNS FROM users WHERE Field = 'role';\n";
echo "SELECT role, COUNT(*) FROM users GROUP BY role;\n";

echo "\n=== END DIAGNOSTIC ===\n";
?>