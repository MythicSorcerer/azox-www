# Azox Network Forum & Messaging System - Installation Guide

## 🚀 Quick Start

### Prerequisites
- **PHP 7.4+** with PDO MySQL extension
- **MySQL 5.7+** or **MariaDB 10.2+**
- **Web server** (Apache, Nginx, or built-in PHP server)

### Step 1: Install MySQL

#### macOS (Recommended Methods)

**Option A: Homebrew (Recommended)**
```bash
# Install MySQL
brew install mysql

# Start MySQL service
brew services start mysql

# Secure installation (optional)
mysql_secure_installation
```

**Option B: MySQL Official Installer**
1. Download from [mysql.com](https://dev.mysql.com/downloads/mysql/)
2. Install the .dmg package
3. Start MySQL from System Preferences

**Option C: MAMP (Easy for beginners)**
1. Download [MAMP](https://www.mamp.info/)
2. Install and start MAMP
3. MySQL will be available on port 8889

#### Linux (Ubuntu/Debian)
```bash
# Update package list
sudo apt update

# Install MySQL
sudo apt install mysql-server

# Start MySQL service
sudo systemctl start mysql
sudo systemctl enable mysql

# Secure installation
sudo mysql_secure_installation
```

#### Windows
1. Download MySQL Installer from [mysql.com](https://dev.mysql.com/downloads/installer/)
2. Run installer and select "Server only" or "Full"
3. Follow setup wizard
4. Start MySQL service from Services panel

### Step 2: Configure Database Connection

Edit `config/database.php` with your MySQL settings:

```php
// For standard MySQL installation
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_USER', 'root');
define('DB_PASS', 'your_password');

// For MAMP users
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 8889);
define('DB_USER', 'root');
define('DB_PASS', 'root');
```

### Step 3: Run Setup

1. **Start your web server**
   ```bash
   # Using PHP built-in server (for testing)
   php -S localhost:8000
   
   # Or use Apache/Nginx
   ```

2. **Run the setup script**
   ```
   Visit: http://localhost:8000/setup.php
   ```

3. **Test the installation**
   ```
   Visit: http://localhost:8000/test-db.php
   ```

### Step 4: Login and Test

**Default Credentials:**
- **Admin:** username: `admin`, password: `admin123`
- **Test User:** username: `testuser`, password: `test123`

**Test URLs:**
- Homepage: `http://localhost:8000/index.php`
- Login: `http://localhost:8000/auth/login.php`
- Forum: `http://localhost:8000/forum/`
- Admin: `http://localhost:8000/admin/dashboard.php`

## 🔧 Troubleshooting

### MySQL Connection Issues

**Error: "No such file or directory"**
- MySQL is not running
- Wrong socket path

**Solutions:**
```bash
# Check if MySQL is running
brew services list | grep mysql
# or
ps aux | grep mysql

# Start MySQL
brew services start mysql
# or
sudo systemctl start mysql

# Check MySQL status
brew services info mysql
```

**Error: "Access denied for user 'root'"**
- Wrong username/password
- User doesn't exist

**Solutions:**
```bash
# Reset root password (macOS Homebrew)
brew services stop mysql
mysqld_safe --skip-grant-tables &
mysql -u root
# In MySQL prompt:
UPDATE mysql.user SET authentication_string=PASSWORD('newpassword') WHERE User='root';
FLUSH PRIVILEGES;
exit;
# Kill mysqld_safe and restart normally
brew services start mysql
```

**Error: "Unknown database 'azox_network'"**
- Database doesn't exist
- Run setup script first

**Error: "Connection refused"**
- MySQL not running on expected port
- Check port in config/database.php

### Common Port Configurations

| Installation | Host | Port | User | Pass |
|-------------|------|------|------|------|
| Homebrew MySQL | 127.0.0.1 | 3306 | root | (your password) |
| MAMP | 127.0.0.1 | 8889 | root | root |
| XAMPP | 127.0.0.1 | 3306 | root | (empty) |
| Standard Linux | 127.0.0.1 | 3306 | root | (your password) |

### File Permissions

```bash
# Make sure PHP can write to logs directory
chmod 755 logs/
chmod 644 logs/activity.log

# If using Apache, ensure proper ownership
sudo chown -R www-data:www-data /path/to/azox-www
```

## 🎯 Features Overview

### Navigation System
- **Top Bar:** Dynamic login states (replaced pills as requested)
- **Main Nav:** Clean navigation without login elements
- **Mobile:** Responsive hamburger menu

### Forum System
- **Categories:** Organized discussion areas
- **Threads:** Topic-based discussions with replies
- **Moderation:** Admin controls and user management

### Real-time Chat
- **Multi-channel:** General, PvP, Trading, Help
- **Live updates:** AJAX polling for real-time feel
- **User status:** Online/away indicators

### Authentication
- **Secure login:** Password hashing and CSRF protection
- **Role-based:** User and Admin access levels
- **Session management:** Secure token-based sessions

### Admin Dashboard
- **User management:** Monitor and manage users
- **System stats:** Activity metrics and monitoring
- **Content moderation:** Forum and chat oversight

## 🔒 Security Notes

1. **Change default passwords** immediately after setup
2. **Delete setup.php** after installation
3. **Update database credentials** in production
4. **Enable HTTPS** in production environments
5. **Regular backups** of database and files

## 📁 File Structure

```
/
├── config/
│   ├── database.php     # Database configuration
│   ├── database.sql     # Database schema
│   └── auth.php         # Authentication system
├── includes/
│   └── nav.php          # Dynamic navigation
├── auth/                # Login/register system
├── forum/               # Forum pages
├── thread/              # Thread discussions
├── messages/            # Real-time chat
├── notifications/       # Notification system
├── admin/               # Admin dashboard
├── setup.php            # Installation script
├── test-db.php          # Database tester
└── INSTALL.md           # This file
```

## 🆘 Getting Help

If you encounter issues:

1. **Check MySQL status:** Ensure MySQL is running
2. **Verify credentials:** Test database connection manually
3. **Check logs:** Look in `logs/activity.log` for errors
4. **Test connection:** Use `test-db.php` to diagnose issues
5. **Review config:** Ensure `config/database.php` is correct

## 🎉 Success!

Once setup is complete, you'll have:
- ✅ Working forum with categories and threads
- ✅ Real-time messaging system
- ✅ User authentication and admin panel
- ✅ Dynamic navigation with login states
- ✅ Mobile-responsive design
- ✅ Notification system

**Next Steps:**
1. Change admin password
2. Create forum categories
3. Customize styling if needed
4. Set up regular database backups
5. Configure production environment

---

**Azox Network Forum & Messaging System v1.0**  
*Complete PHP/MySQL forum and chat solution*