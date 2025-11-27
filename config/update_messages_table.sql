-- Add deleted_at column to messages table for consistency with other tables
ALTER TABLE messages ADD COLUMN deleted_at TIMESTAMP NULL AFTER is_deleted;

-- Add deleted_at column to forum_threads table if it doesn't exist
ALTER TABLE forum_threads ADD COLUMN deleted_at TIMESTAMP NULL AFTER is_locked;
ALTER TABLE forum_threads ADD COLUMN is_deleted BOOLEAN DEFAULT FALSE AFTER deleted_at;

-- Add is_banned and banned_at columns to users table if they don't exist
ALTER TABLE users ADD COLUMN is_banned BOOLEAN DEFAULT FALSE AFTER is_active;
ALTER TABLE users ADD COLUMN banned_at TIMESTAMP NULL AFTER is_banned;