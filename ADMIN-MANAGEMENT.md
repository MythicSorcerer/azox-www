# 🛡️ Admin Management Guide

## Overview

The Azox Network uses a three-tier role system: **users**, **admins**, and **owners**. This guide covers administrative capabilities and user management features.

## 🔐 Role System

### User Role (`user`)
- Default role for all new registrations
- Can post in forums and chat
- Can manage their own content
- Cannot access admin functions

### Admin Role (`admin`)
- Administrative privileges for user management
- Can ban/unban regular users
- Can delete user content
- Can access admin dashboard
- Cannot ban/delete other admins or owners

### Owner Role (`owner`)
- Highest level access
- Can ban/delete admins and other owners
- Can perform all admin functions
- Can access owner-only operations
- Full system control

## 📋 Admin Features

### Individual User Management
Access via Admin Dashboard → Recent Users → Actions button

**Available Actions:**
- **Ban User:** Prevents user from posting/accessing features
- **Unban User:** Restores user access
- **Delete User:** Soft delete (marks as inactive, preserves data)

**Role Restrictions:**
- Admins can only manage regular users
- Owners can manage all users including admins

### Bulk Operations
Access via Admin Dashboard → Bulk Operations tab

#### Standard Operations (Admin & Owner)
1. **Ban Inactive Users**
   - Bans users inactive for X days
   - Excludes admin/owner users
   - Reversible action

2. **Delete Inactive Users**
   - Soft deletes users inactive for X days
   - Excludes admin/owner users
   - Marks user and content as deleted

3. **Delete Users by Date Range**
   - Deletes users registered between two dates
   - Excludes admin/owner users
   - Useful for removing spam registrations

#### Owner-Only Operations
1. **Delete Admin User**
   - Removes admin user from system
   - Requires owner privileges
   - Soft delete preserves data

2. **Manage Admin Roles**
   - Promote users to admin
   - Demote admins to users
   - Owner-only capability

## 🗄️ Database Operations

### Role Hierarchy
```
Owner > Admin > User
```

### Soft Delete System
- Sets `is_active = 0` on users table
- Sets `is_deleted = 1` on content tables
- Data remains in database for recovery
- Used by all admin functions

### Manual Database Commands

#### Create Admin User
```sql
-- Register user first through website, then promote
UPDATE users SET role = 'admin' WHERE username = 'your_username';
```

#### Create Owner User
```sql
-- Register user first through website, then promote
UPDATE users SET role = 'owner' WHERE username = 'your_username';
```

#### Manual User Management
```sql
-- Soft delete user and content
UPDATE users SET is_active = 0 WHERE username = 'username_to_delete';
UPDATE forum_posts SET is_deleted = 1 WHERE author_id = (SELECT id FROM users WHERE username = 'username_to_delete');
UPDATE messages SET is_deleted = 1 WHERE sender_id = (SELECT id FROM users WHERE username = 'username_to_delete');
```

#### Bulk Operations
```sql
-- Delete users older than date (excludes admins/owners)
UPDATE users SET is_active = 0 
WHERE created_at < '2025-01-01' AND role = 'user';

-- Delete users between dates (excludes admins/owners)
UPDATE users SET is_active = 0 
WHERE created_at BETWEEN '2025-01-01' AND '2025-01-31' AND role = 'user';

-- Ban inactive users (excludes admins/owners)
UPDATE users SET is_banned = 1, banned_at = NOW() 
WHERE last_active < DATE_SUB(NOW(), INTERVAL 30 DAY) AND role = 'user';
```

## 🔒 Security Features

### Access Control
- Role-based permissions enforced in code
- Session validation on every request
- CSRF protection on forms
- Hierarchical role system prevents privilege escalation

### Audit Trail
- All actions logged to activity log
- Database transactions ensure data integrity
- Error handling prevents partial operations
- JSON responses for clear feedback

### Protection Mechanisms
- Admins cannot ban/delete other admins or owners
- Only owners can manage admin users
- Bulk operations exclude admin/owner users by default
- Confirmation dialogs for destructive actions

## 📊 Monitoring & Analytics

### User Statistics
- Total active users by role
- Online user count
- Registration trends
- Activity patterns

### Content Statistics
- Forum threads and posts
- Chat message volume
- User engagement metrics
- Moderation actions taken

## 🚨 Emergency Procedures

### Compromised Admin Account
1. Use owner account to ban compromised admin
2. Review activity logs for unauthorized actions
3. Create new admin account if needed
4. Reset all admin passwords

### Lost Owner Access
1. Access database directly (see MARIADB-RECOVERY.md)
2. Create emergency owner account
3. Use emergency account to restore access
4. Review and secure all accounts

### Database Recovery
1. Restore from backup if available
2. Use soft delete recovery for recent deletions
3. Check activity logs for specific actions taken
4. Manually restore critical user accounts

## 📝 Best Practices

### Role Management
- Keep owner accounts to minimum (1-2 maximum)
- Create admin accounts for day-to-day moderation
- Regular users for normal community participation
- Document role changes and reasons

### Regular Maintenance
- Review inactive users monthly
- Clean up old chat messages quarterly
- Monitor admin activity logs
- Backup database before bulk operations

### Security
- Limit owner account creation
- Monitor failed login attempts
- Regular security audits
- Keep admin/owner accounts secure

## 🛠️ Creating Admin/Owner Users

### Method 1: Database Promotion (Recommended)
```sql
-- User registers normally first, then promote
UPDATE users SET role = 'admin' WHERE username = 'new_admin_username';
UPDATE users SET role = 'owner' WHERE username = 'new_owner_username';
```

### Method 2: Direct Database Insert
```sql
-- Create admin directly (use strong password hash)
INSERT INTO users (username, email, password_hash, role) VALUES
('admin_username', 'admin@example.com', '$2y$10$your_password_hash_here', 'admin');

-- Create owner directly (use strong password hash)
INSERT INTO users (username, email, password_hash, role) VALUES
('owner_username', 'owner@example.com', '$2y$10$your_password_hash_here', 'owner');
```

## 📞 Support

### Common Issues
- **Cannot access admin dashboard:** Check user role in database
- **Actions not working:** Verify session and role permissions
- **Cannot ban admin:** Only owners can manage admin users

### Database Queries for Troubleshooting
```sql
-- Check user roles
SELECT username, role, is_active FROM users WHERE role IN ('admin', 'owner');

-- Check recent admin activity
SELECT username, role, last_active FROM users WHERE role IN ('admin', 'owner') ORDER BY last_active DESC;

-- Count users by role
SELECT role, COUNT(*) as count FROM users GROUP BY role;
```

---

**⚠️ Important:** Always backup your database before performing bulk operations. The role hierarchy ensures system security - owners have ultimate control, admins handle day-to-day moderation.

*Last Updated: November 30, 2025*