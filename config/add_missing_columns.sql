-- Add missing columns for admin functionality
USE azox_network;

-- Add missing columns to users table
ALTER TABLE users 
ADD COLUMN deleted_at TIMESTAMP NULL,
ADD COLUMN is_banned BOOLEAN DEFAULT FALSE,
ADD COLUMN banned_at TIMESTAMP NULL;

-- Add missing column to forum_threads table
ALTER TABLE forum_threads 
ADD COLUMN is_deleted BOOLEAN DEFAULT FALSE;

-- Show the updated structure
DESCRIBE users;
DESCRIBE forum_threads;