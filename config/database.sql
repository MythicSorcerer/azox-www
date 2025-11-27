-- Azox Network Forum & Messaging System Database Schema
-- MySQL Database Setup

-- Create database (run this first)
-- CREATE DATABASE azox_network;
-- USE azox_network;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    avatar_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_last_active (last_active)
);

-- Forum categories
CREATE TABLE forum_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sort_order (sort_order)
);

-- Forum threads
CREATE TABLE forum_threads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    author_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_pinned BOOLEAN DEFAULT FALSE,
    is_locked BOOLEAN DEFAULT FALSE,
    view_count INT DEFAULT 0,
    reply_count INT DEFAULT 0,
    last_post_id INT DEFAULT NULL,
    FOREIGN KEY (category_id) REFERENCES forum_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_category_id (category_id),
    INDEX idx_author_id (author_id),
    INDEX idx_updated_at (updated_at),
    INDEX idx_pinned_updated (is_pinned, updated_at)
);

-- Forum posts
CREATE TABLE forum_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    author_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    edited_at TIMESTAMP NULL,
    edited_by INT NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (thread_id) REFERENCES forum_threads(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (edited_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_thread_id (thread_id),
    INDEX idx_author_id (author_id),
    INDEX idx_created_at (created_at)
);

-- Messages for chat system
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NULL, -- NULL for public chat
    channel VARCHAR(50) DEFAULT 'general',
    content TEXT NOT NULL,
    message_type ENUM('text', 'system', 'join', 'leave') DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_sender_id (sender_id),
    INDEX idx_receiver_id (receiver_id),
    INDEX idx_channel_created (channel, created_at),
    INDEX idx_created_at (created_at)
);

-- Notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('forum_reply', 'forum_mention', 'message', 'system') NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    related_id INT NULL, -- thread_id, post_id, message_id, etc.
    related_type VARCHAR(50) NULL, -- 'thread', 'post', 'message'
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_user_unread (user_id, is_read),
    INDEX idx_created_at (created_at)
);

-- User sessions for authentication
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(128) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_token (session_token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);

-- Insert default forum categories
INSERT INTO forum_categories (name, description, sort_order) VALUES
('General Discussion', 'General chat about Azox Network and Minecraft', 1),
('Server Updates', 'Official announcements and server updates', 2),
('PvP & Combat', 'Discuss PvP strategies, battles, and combat tips', 3),
('Factions & Alliances', 'Faction recruitment, diplomacy, and alliance discussions', 4),
('Trading & Economy', 'Buy, sell, and trade items with other players', 5),
('Bug Reports', 'Report server bugs and technical issues', 6),
('Suggestions', 'Suggest new features and improvements', 7),
('Off-Topic', 'Non-Minecraft related discussions', 8);

-- Create default admin user (password: admin123 - CHANGE THIS!)
INSERT INTO users (username, email, password_hash, role) VALUES
('admin', 'admin@azox.net', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Add triggers to update thread reply counts
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