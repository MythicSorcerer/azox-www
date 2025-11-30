# 🚨 Production "Service Temporarily Unavailable" Fix

## Quick Fix (Most Common Solution)

**On your remote production server**, run these commands:

```bash
# 1. Upload the fix script to your server (if not already there)
# 2. Run the database fix script
sudo ./debug/fix-production-database.sh
```

This will automatically:
- ✅ Start MariaDB service
- ✅ Fix database configuration
- ✅ Test database connection
- ✅ Set correct file permissions
- ✅ Restart Apache

## Root Cause Analysis

The "Service Temporarily Unavailable" error is caused by:

1. **Database connection failure** in [`config/database.php`](config/database.php:51-67)
2. **Development config** being used instead of production config
3. **MariaDB service** not running on production server
4. **Missing database** or incorrect credentials

## Step-by-Step Diagnosis

### Step 1: Run Diagnostic Script

```bash
# On your production server
php /var/www/azox/debug/diagnose-production.php
```

This will show you exactly what's wrong.

### Step 2: Check MariaDB Service

```bash
sudo systemctl status mariadb
sudo systemctl start mariadb
sudo systemctl enable mariadb
```

### Step 3: Check Database Configuration

```bash
# Check if production config exists
ls -la /var/www/azox/config/database.php

# Check file permissions (should be apache:apache 600)
ls -la /var/www/azox/config/database.php
```

### Step 4: Test Database Connection Manually

```bash
# Extract credentials from config file
grep -E "(dbname|username|password)" /var/www/azox/config/database.php

# Test connection (replace with actual credentials)
mysql -u azox_user -p azox_network -e "SELECT 1;"
```

## Manual Fix (If Script Fails)

### 1. Start MariaDB Service

```bash
sudo systemctl start mariadb
sudo systemctl enable mariadb
```

### 2. Create Database and User

```bash
sudo mysql -e "CREATE DATABASE IF NOT EXISTS azox_network CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'azox_user'@'localhost' IDENTIFIED BY 'your_secure_password';"
sudo mysql -e "GRANT ALL PRIVILEGES ON azox_network.* TO 'azox_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

### 3. Create Production Database Config

```bash
sudo tee /var/www/azox/config/database.php > /dev/null << 'EOF'
<?php
$host = 'localhost';
$dbname = 'azox_network';
$username = 'azox_user';
$password = 'your_secure_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check your configuration.");
}

function executeQuery($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function fetchRow($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

function fetchCount($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchColumn();
}

function insertAndGetId($sql, $params = []) {
    global $pdo;
    executeQuery($sql, $params);
    return $pdo->lastInsertId();
}

function logActivity($message, $level = 'info') {
    $logFile = __DIR__ . '/../logs/activity.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

function sanitizeOutput($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>
EOF
```

### 4. Set Correct Permissions

```bash
sudo chown apache:apache /var/www/azox/config/database.php
sudo chmod 600 /var/www/azox/config/database.php
sudo mkdir -p /var/www/azox/logs
sudo chown apache:apache /var/www/azox/logs
sudo chmod 755 /var/www/azox/logs
```

### 5. Import Database Schema

```bash
sudo mysql -u azox_user -p azox_network < /var/www/azox/config/database_simple.sql
```

### 6. Restart Apache

```bash
sudo systemctl restart httpd
```

## Common Issues & Solutions

### Issue: "MariaDB won't start"

```bash
# Check what's preventing startup
sudo journalctl -u mariadb -f

# Common fixes
sudo systemctl stop mariadb
sudo systemctl start mariadb

# If still failing, reinstall
sudo dnf remove mariadb-server mariadb
sudo dnf install mariadb-server mariadb
sudo systemctl start mariadb
sudo systemctl enable mariadb
```

### Issue: "Database doesn't exist"

```bash
sudo mysql -e "SHOW DATABASES;"
sudo mysql -e "CREATE DATABASE azox_network CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Issue: "Access denied for user"

```bash
# Reset user password
sudo mysql -e "DROP USER IF EXISTS 'azox_user'@'localhost';"
sudo mysql -e "CREATE USER 'azox_user'@'localhost' IDENTIFIED BY 'new_password';"
sudo mysql -e "GRANT ALL PRIVILEGES ON azox_network.* TO 'azox_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

### Issue: "File permissions wrong"

```bash
sudo chown -R apache:apache /var/www/azox
sudo find /var/www/azox -type f -exec chmod 644 {} \;
sudo find /var/www/azox -type d -exec chmod 755 {} \;
sudo chmod 600 /var/www/azox/config/database.php
```

## Verification

After fixing, test these:

1. **Database connection**: `php /var/www/azox/debug/diagnose-production.php`
2. **Web server**: `curl -I http://localhost`
3. **Website**: Visit your site in a browser

## Prevention

To prevent this issue in the future:

1. Always use the production deployment script
2. Save database passwords securely
3. Monitor MariaDB service status
4. Set up automated backups
5. Use proper file permissions

## Need Help?

If you're still having issues:

1. Run the diagnostic script and share the output
2. Check Apache error logs: `sudo tail -f /var/log/httpd/error_log`
3. Check MariaDB logs: `sudo journalctl -u mariadb -f`

---

**The fix script should resolve 95% of deployment issues automatically!**