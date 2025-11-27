<?php
require_once __DIR__ . '/database.php';

try {
    echo "Adding missing columns to database...\n";
    
    // Add missing columns to users table
    $pdo->exec("ALTER TABLE users ADD COLUMN deleted_at TIMESTAMP NULL");
    echo "Added deleted_at column to users table\n";
    
    $pdo->exec("ALTER TABLE users ADD COLUMN is_banned BOOLEAN DEFAULT FALSE");
    echo "Added is_banned column to users table\n";
    
    $pdo->exec("ALTER TABLE users ADD COLUMN banned_at TIMESTAMP NULL");
    echo "Added banned_at column to users table\n";
    
    // Add missing column to forum_threads table
    $pdo->exec("ALTER TABLE forum_threads ADD COLUMN is_deleted BOOLEAN DEFAULT FALSE");
    echo "Added is_deleted column to forum_threads table\n";
    
    echo "All columns added successfully!\n";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Columns already exist, skipping...\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>