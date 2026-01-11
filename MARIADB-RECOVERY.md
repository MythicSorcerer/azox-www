# MariaDB Recovery Guide for Azox Network

## Overview
This guide provides instructions for recovering from lost owner/admin account credentials using direct MariaDB database access. This should only be used in emergency situations when all administrative access has been lost.

## Prerequisites
- Root access to the server running MariaDB
- MariaDB/MySQL command line access
- Basic knowledge of SQL commands

## Emergency Recovery Procedures

### 1. Access MariaDB as Root

```bash
# Connect to MariaDB as root
sudo mysql -u root -p

# Or if using socket authentication
sudo mysql
```

### 2. Select the Azox Database

```sql
USE azox_network;
```

### 3. View Current Users and Roles

```sql
-- Check existing users and their roles
SELECT id, username, email, role, is_active, is_banned, created_at, last_active 
FROM users 
ORDER BY role DESC, created_at ASC;
```

### 4. Recovery Options

#### Option A: Create New Owner Account

```sql
-- Create a new owner account with emergency credentials
INSERT INTO users (username, email, password_hash, role, is_active, created_at, last_active) 
VALUES (
    'emergency_owner', 
    'owner@azox.net', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: 'password'
    'owner', 
    1, 
    NOW(), 
    NOW()
);
```

#### Option B: Promote Existing User to Owner

```sql
-- Promote an existing user to owner role
UPDATE users 
SET role = 'owner', is_active = 1, is_banned = 0 
WHERE username = 'existing_username';
```

#### Option C: Reset Existing Owner Password

```sql
-- Reset password for existing owner (password will be 'newpassword123')
UPDATE users 
SET password_hash = '$2y$10$8K1p/a0dqbVXYUxs7TPHUOxSzlkeHMTeJ4d6oHseZH8WBXoz4B/Gy',
    is_active = 1,
    is_banned = 0,
    last_active = NOW()
WHERE role = 'owner' 
LIMIT 1;
```

### 5. Verify Changes

```sql
-- Verify the changes were applied
SELECT id, username, email, role, is_active, is_banned 
FROM users 
WHERE role IN ('owner', 'admin') 
ORDER BY role DESC;
```

### 6. Clean Up Old Sessions (Optional)

```sql
-- Clear all existing sessions to force re-login
DELETE FROM user_sessions;
```

## Password Hash Generation

If you need to generate a new password hash, you can use PHP:

```bash
# Generate password hash using PHP
php -r "echo password_hash('your_new_password', PASSWORD_DEFAULT);"
```

Or create a temporary PHP script:

```php
<?php
// save as generate_hash.php
$password = 'your_new_password';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password hash: " . $hash . "\n";
?>
```

## Common Password Hashes

For emergency access, here are some pre-generated hashes:

| Password | Hash |
|----------|------|
| `password` | `$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi` |
| `admin123` | `$2y$10$8K1p/a0dqbVXYUxs7TPHUOxSzlkeHMTeJ4d6oHseZH8WBXoz4B/Gy` |
| `emergency` | `$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm` |

## Role Hierarchy

The Azox Network uses the following role hierarchy:

1. **Owner** (`owner`)
   - Highest level access
   - Can manage admin and other owner accounts
   - Can perform all admin functions
   - Full system control

2. **Admin** (`admin`)
   - Can manage regular users only
   - Cannot manage other admins or owners
   - Can moderate forums and chat
   - Can access admin dashboard

3. **User** (`user`)
   - Standard user account
   - No administrative privileges

## Security Best Practices

### After Recovery:

1. **Change Default Passwords Immediately**
   ```sql
   -- After logging in through the web interface, change the password
   -- This should be done through the settings page, not directly in the database
   ```

2. **Create Backup Owner Account**
   ```sql
   -- Create a secondary owner account for future emergencies
   INSERT INTO users (username, email, password_hash, role, is_active, created_at, last_active) 
   VALUES (
       'backup_owner', 
       'backup@azox.net', 
       'your_secure_hash_here',
       'owner', 
       1, 
       NOW(), 
       NOW()
   );
   ```

3. **Document Credentials Securely**
   - Store emergency credentials in a secure password manager
   - Keep database access credentials in a secure location
   - Document the recovery process for your team

4. **Regular Backups**
   ```bash
   # Create regular database backups
   mysqldump -u root -p azox_network > azox_backup_$(date +%Y%m%d).sql
   ```

## Troubleshooting

### Cannot Connect to MariaDB

```bash
# Check if MariaDB is running
sudo systemctl status mariadb

# Start MariaDB if stopped
sudo systemctl start mariadb

# Check MariaDB logs
sudo journalctl -u mariadb -f
```

### Permission Denied Errors

```bash
# Ensure you're running as root or with sudo
sudo mysql -u root -p

# Check MariaDB user permissions
SELECT User, Host FROM mysql.user WHERE User = 'root';
```

### Database Not Found

```sql
-- List all databases
SHOW DATABASES;

-- The database might be named differently
USE azox;  -- or whatever the actual database name is
```

## Emergency Contact Information

If you cannot resolve the issue using this guide:

1. Check the main project documentation
2. Review the deployment logs in `/var/log/`
3. Consult the MariaDB error logs
4. Consider restoring from a recent backup

## Important Notes

- **Always backup the database before making changes**
- **Test changes in a development environment first if possible**
- **Change emergency passwords immediately after recovery**
- **This guide should only be used when all other access methods have failed**
- **Keep this documentation secure and up-to-date**

## Example Recovery Session

```bash
# 1. Connect to MariaDB
sudo mysql -u root -p

# 2. Select database
USE azox_network;

# 3. Check current situation
SELECT username, role, is_active FROM users WHERE role IN ('owner', 'admin');

# 4. Create emergency owner (if no owners exist)
INSERT INTO users (username, email, password_hash, role, is_active, created_at, last_active)
VALUES ('emergency_owner', 'owner@azox.net', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner', 1, NOW(), NOW());

# 5. Verify
SELECT username, role FROM users WHERE role = 'owner';

# 6. Exit
EXIT;
```

Now you can log in to the web interface with:
- Username: `emergency_admin`
- Password: `password`

**Remember to change this password immediately after logging in!**