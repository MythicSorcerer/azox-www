-- Migration script to add missing columns to existing database
-- Run this if you already have a database set up and need to add the missing columns

-- Add missing columns to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS is_banned BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS banned_at TIMESTAMP NULL;

-- Add missing column to forum_threads table
ALTER TABLE forum_threads 
ADD COLUMN IF NOT EXISTS is_deleted BOOLEAN DEFAULT FALSE;

-- Add triggers for reply count management (drop existing ones first if they exist)
DROP TRIGGER IF EXISTS update_thread_reply_count_insert;
DROP TRIGGER IF EXISTS update_thread_reply_count_delete;

DELIMITER //

CREATE TRIGGER update_thread_reply_count_insert
AFTER INSERT ON forum_posts
FOR EACH ROW
BEGIN
    UPDATE forum_threads 
    SET reply_count = reply_count + 1,
        last_post_id = NEW.id,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.thread_id;
END//

CREATE TRIGGER update_thread_reply_count_delete
AFTER UPDATE ON forum_posts
FOR EACH ROW
BEGIN
    IF NEW.is_deleted = TRUE AND OLD.is_deleted = FALSE THEN
        UPDATE forum_threads 
        SET reply_count = reply_count - 1
        WHERE id = NEW.thread_id;
    END IF;
END//

DELIMITER ;