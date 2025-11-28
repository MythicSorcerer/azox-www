# 🛡️ Admin Management Guide

## Overview

The Azox Network admin system now includes advanced user management capabilities with both regular admin functions and super admin operations for dangerous actions.

## 🔐 Access Levels

### Regular Admin
- Ban/unban users
- Delete user content (soft delete)
- Bulk operations on non-admin users
- Forum and chat moderation

### Super Admin
- Delete admin users (requires access code)
- Hard delete users (permanent database removal)
- Purge all inactive users
- **Access Code:** `AZOX_SUPER_2025_DELETE_ADMIN`

## 📋 User Management Features

### Individual User Actions
Access via Admin Dashboard → Recent Users → Actions button

**Available Actions:**
- **Ban User:** Prevents user from posting/accessing features
- **Unban User:** Restores user access
- **Delete User:** Soft delete (marks as inactive, preserves data)

### Bulk Operations
Access via Admin Dashboard → Bulk Operations tab

#### Standard Bulk Actions
1. **Ban Inactive Users**
   - Bans users inactive for X days
   - Excludes admin users
   - Reversible action

2. **Delete Inactive Users**
   - Soft deletes users inactive for X days
   - Excludes admin users
   - Marks user and content as deleted

3. **Delete Users by Date Range**
   - Deletes users registered between two dates
   - Excludes admin users
   - Useful for removing spam registrations

#### Super Admin Actions
⚠️ **Requires Access Code:** `AZOX_SUPER_2025_DELETE_ADMIN`

1. **Delete Admin User**
   - Permanently removes admin user from database
   - Requires target username
   - Cannot be undone

2. **Hard Delete User**
   - Permanently removes any user from database
   - Deletes all related content
   - Cannot be undone

3. **Purge All Inactive Users**
   - Permanently removes all inactive users
   - Mass database cleanup
   - Cannot be undone

## 🗄️ Database Operations

### Soft Delete vs Hard Delete

**Soft Delete (Default):**
- Sets `is_active = 0` on users table
- Sets `is_deleted = 1` on content tables
- Data remains in database for recovery
- Used by regular admin functions

**Hard Delete (Super Admin Only):**
- Permanently removes records from database
- Deletes all related content (posts, messages, sessions)
- Cannot be recovered
- Uses database transactions for integrity

### Manual Database Commands

#### Create Admin User
```sql
-- Register user first, then promote
UPDATE users SET role = 'admin' WHERE username = 'your_username';
```

#### Manual User Deletion
```sql
-- Soft delete user and content
UPDATE users SET is_active = 0 WHERE username = 'username_to_delete';
UPDATE forum_posts SET is_deleted = 1 WHERE author_id = (SELECT id FROM users WHERE username = 'username_to_delete');
UPDATE messages SET is_deleted = 1 WHERE sender_id = (SELECT id FROM users WHERE username = 'username_to_delete');
```

#### Hard Delete User (Permanent)
```sql
-- Get user ID first
SET @user_id = (SELECT id FROM users WHERE username = 'username_to_delete');

-- Delete all related data
DELETE FROM notifications WHERE user_id = @user_id;
DELETE FROM user_sessions WHERE user_id = @user_id;
DELETE FROM messages WHERE sender_id = @user_id;
DELETE FROM forum_posts WHERE author_id = @user_id;
DELETE FROM forum_threads WHERE author_id = @user_id;
DELETE FROM users WHERE id = @user_id;
```

#### Bulk Operations
```sql
-- Delete users older than date
UPDATE users SET is_active = 0 
WHERE created_at < '2025-01-01' AND role != 'admin';

-- Delete users between dates
UPDATE users SET is_active = 0 
WHERE created_at BETWEEN '2025-01-01' AND '2025-01-31' AND role != 'admin';

-- Ban inactive users
UPDATE users SET is_banned = 1, banned_at = NOW() 
WHERE last_active < DATE_SUB(NOW(), INTERVAL 30 DAY) AND role != 'admin';
```

## 🔒 Security Features

### Access Control
- All admin actions require admin role verification
- Super admin actions require hardcoded access code
- Session validation on every request
- CSRF protection on forms

### Audit Trail
- All actions logged to activity log
- Database transactions ensure data integrity
- Error handling prevents partial operations
- JSON responses for clear feedback

### Protection Mechanisms
- Admin users cannot be banned by regular admins
- Super admin code required for admin deletion
- Confirmation dialogs for destructive actions
- Bulk operations exclude admin users by default

## 📊 Monitoring & Analytics

### User Statistics
- Total active users
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
1. Change super admin access code in `admin/actions.php`
2. Use super admin functions to remove compromised admin
3. Review activity logs for unauthorized actions
4. Reset all admin passwords

### Database Recovery
1. Restore from backup if available
2. Use soft delete recovery for recent deletions
3. Check activity logs for specific actions taken
4. Manually restore critical user accounts

### Mass Cleanup
1. Use "Purge All Inactive Users" for database cleanup
2. Bulk delete by date range for spam removal
3. Export user data before major operations
4. Test operations on staging environment first

## 📝 Best Practices

### Regular Maintenance
- Review inactive users monthly
- Clean up old chat messages quarterly
- Monitor admin activity logs
- Backup database before bulk operations

### User Management
- Use soft delete for regular moderation
- Reserve hard delete for spam/abuse cases
- Document reasons for admin actions
- Communicate policy changes to users

### Security
- Change super admin code periodically
- Limit admin account creation
- Monitor failed login attempts
- Regular security audits

---

**⚠️ Important:** Super admin operations are irreversible. Always backup your database before performing bulk deletions or hard deletes.

**Access Code:** `AZOX_SUPER_2025_DELETE_ADMIN`

*Last Updated: November 28, 2025*