# Azox Network - Fedora Server Deployment Guide

## 🚨 Database Connection Error Fix

**Error:** `SQLSTATE[HY000][1045] Access denied for user 'root'@'localhost' (using password: NO)`

**Cause:** The database configuration is set to use an empty password, but Fedora server MySQL requires proper authentication.

## 🔧 Quick Fix

### Step 1: Update Database Configuration

Edit `config/database.php` and update these lines:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'azox_network');
define('DB_USER', 'azox_user');        // Change from 'root'
define('DB_PASS', 'your_secure_password'); // Add your password
```

### Step 2: Set Up MySQL Database and User

Run these commands on your Fedora server:

```bash
# 1. Connect to MySQL as root
sudo mysql -u root -p

# 2. Create database and user (run these in MySQL prompt)
CREATE DATABASE azox_network CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'azox_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON azox_network.* TO 'azox_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# 3. Import database schema
mysql -u azox_user -p azox_network < config/database_simple.sql

# 4. Apply any missing columns (if upgrading)
mysql -u azox_user -p azox_network < config/add_missing_columns.sql
```

### Step 3: Test Connection

```bash
# Test the connection
mysql -u azox_user -p azox_network -e "SHOW TABLES;"
```

## 🐧 Complete Fedora Server Setup

### Install Required Packages

```bash
# Update system
sudo dnf update -y

# Install Apache, PHP, and MySQL
sudo dnf install -y httpd php php-mysqlnd php-json php-mbstring php-xml mariadb-server mariadb

# Start and enable services
sudo systemctl start httpd mariadb
sudo systemctl enable httpd mariadb

# Secure MySQL installation
sudo mysql_secure_installation
```

### Configure Apache

```bash
# Create virtual host configuration
sudo tee /etc/httpd/conf.d/azox.conf << 'EOF'
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/html/azox
    
    <Directory /var/www/html/azox>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog /var/log/httpd/azox_error.log
    CustomLog /var/log/httpd/azox_access.log combined
</VirtualHost>
EOF

# Create directory and set permissions
sudo mkdir -p /var/www/html/azox
sudo chown -R apache:apache /var/www/html/azox
sudo chmod -R 755 /var/www/html/azox

# Restart Apache
sudo systemctl restart httpd
```

### Deploy Application

```bash
# Copy files to web directory
sudo cp -r /path/to/azox-www/* /var/www/html/azox/

# Set proper permissions
sudo chown -R apache:apache /var/www/html/azox
sudo chmod -R 755 /var/www/html/azox
sudo chmod -R 777 /var/www/html/azox/logs  # For log files

# Set SELinux contexts (if SELinux is enabled)
sudo setsebool -P httpd_can_network_connect 1
sudo setsebool -P httpd_can_network_connect_db 1
sudo restorecon -R /var/www/html/azox
```

## 🔒 Security Recommendations

### 1. Create Dedicated Database User

```sql
-- Don't use root for web applications
CREATE USER 'azox_user'@'localhost' IDENTIFIED BY 'strong_random_password_here';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX ON azox_network.* TO 'azox_user'@'localhost';
```

### 2. Secure File Permissions

```bash
# Set restrictive permissions on config files
sudo chmod 600 /var/www/html/azox/config/database.php
sudo chown apache:apache /var/www/html/azox/config/database.php
```

### 3. Configure Firewall

```bash
# Allow HTTP and HTTPS
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

## 🧪 Testing

### 1. Test Database Connection

Visit: `http://your-domain.com/test-db.php`

### 2. Test Application

1. Visit: `http://your-domain.com/`
2. Register a new account
3. Test forum and chat functionality

### 3. Create Admin User

```sql
-- Connect to database
mysql -u azox_user -p azox_network

-- Update user role to admin
UPDATE users SET role = 'admin' WHERE username = 'your_username';
```

## 🚨 Common Issues

### Issue: "Access denied for user"
**Solution:** Check database credentials in `config/database.php`

### Issue: "Can't connect to MySQL server"
**Solution:** Ensure MariaDB/MySQL is running:
```bash
sudo systemctl status mariadb
sudo systemctl start mariadb
```

### Issue: "Permission denied" errors
**Solution:** Check file permissions and SELinux:
```bash
sudo chown -R apache:apache /var/www/html/azox
sudo setsebool -P httpd_can_network_connect_db 1
```

### Issue: "Table doesn't exist"
**Solution:** Import database schema:
```bash
mysql -u azox_user -p azox_network < config/database_simple.sql
```

## 📞 Support

If you encounter issues:

1. Check Apache error logs: `sudo tail -f /var/log/httpd/azox_error.log`
2. Check PHP error logs: `sudo tail -f /var/log/httpd/error_log`
3. Check database connectivity: `mysql -u azox_user -p azox_network`
4. Verify file permissions: `ls -la /var/www/html/azox/config/`

---

**Last Updated:** November 27, 2025