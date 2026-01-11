-- Fix Reply Count Issues in Forum System
-- This script corrects the reply count calculation logic

-- First, let's drop the existing triggers
DROP TRIGGER IF EXISTS update_thread_reply_count_insert;
DROP TRIGGER IF EXISTS update_thread_reply_count_delete;

-- Create improved triggers
DELIMITER //

-- Trigger for when a new post is inserted
CREATE TRIGGER update_thread_reply_count_insert
AFTER INSERT ON forum_posts
FOR EACH ROW
BEGIN
    -- Update reply count (total posts - 1 for the original post)
    -- Also update last_post_id and updated_at
    UPDATE forum_threads 
    SET reply_count = (
            SELECT COUNT(*) - 1 
            FROM forum_posts 
            WHERE thread_id = NEW.thread_id AND is_deleted = 0
        ),
        last_post_id = NEW.id,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.thread_id;
END//

-- Trigger for when a post is marked as deleted
CREATE TRIGGER update_thread_reply_count_delete
AFTER UPDATE ON forum_posts
FOR EACH ROW
BEGIN
    IF NEW.is_deleted = TRUE AND OLD.is_deleted = FALSE THEN
        -- Recalculate reply count when a post is deleted
        UPDATE forum_threads 
        SET reply_count = (
                SELECT COUNT(*) - 1 
                FROM forum_posts 
                WHERE thread_id = NEW.thread_id AND is_deleted = 0
            ),
            -- Update last_post_id to the most recent non-deleted post
            last_post_id = (
                SELECT id 
                FROM forum_posts 
                WHERE thread_id = NEW.thread_id AND is_deleted = 0 
                ORDER BY created_at DESC 
                LIMIT 1
            )
        WHERE id = NEW.thread_id;
    END IF;
END//

DELIMITER ;

-- Now let's fix all existing reply counts
UPDATE forum_threads 
SET reply_count = (
    SELECT COUNT(*) - 1 
    FROM forum_posts 
    WHERE forum_posts.thread_id = forum_threads.id AND is_deleted = 0
);

-- Update last_post_id for all threads
UPDATE forum_threads 
SET last_post_id = (
    SELECT id 
    FROM forum_posts 
    WHERE forum_posts.thread_id = forum_threads.id AND is_deleted = 0 
    ORDER BY created_at DESC 
    LIMIT 1
);

-- Clean up any threads that have no posts (shouldn't happen but just in case)
DELETE FROM forum_threads 
WHERE id NOT IN (
    SELECT DISTINCT thread_id 
    FROM forum_posts 
    WHERE is_deleted = 0
);