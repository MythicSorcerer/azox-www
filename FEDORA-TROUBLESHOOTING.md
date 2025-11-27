# Fedora Server Troubleshooting Guide

## 🚨 Common Issues and Solutions

### Issue 1: MariaDB Package Conflicts
**Error:** "Failed to resolve MariaDB citing conflicts with mariadb from fedora"

**Solution:**
```bash
# Clean up conflicting packages and reinstall
./fix-mariadb-conflicts.sh
```

**Manual Fix:**
```bash
# Stop services
sudo systemctl stop mariadb mysql 2>/dev/null || true

# Remove all MariaDB/MySQL packages
sudo dnf remove -y mariadb* mysql*

# Clean cache
sudo dnf clean all
sudo dnf makecache

# Reinstall cleanly
sudo dnf install -y mariadb-server mariadb --best --allowerasing

# Start service
sudo systemctl start mariadb
sudo systemctl enable mariadb
```

---

### Issue 2: Database Connection Error
**Error:** `SQLSTATE[HY000][1045] Access denied for user 'root'@'localhost' (using password: NO)`

**Solution:**
```bash
# Use sudo-compatible database setup
./fix-database-sudo.sh
```

**Manual Fix:**
```bash
# Access MariaDB via sudo
sudo mysql

# Create database and user
CREATE DATABASE azox_network CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'azox_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON azox_network.* TO 'azox_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import schema
mysql -u azox_user -p azox_network < config/database_simple.sql

# Update config/database.php with new credentials
```

---

### Issue 3: Apache Not Starting
**Error:** Apache fails to start or serve pages

**Check Status:**
```bash
sudo systemctl status httpd
```

**Common Fixes:**
```bash
# Start Apache
sudo systemctl start httpd
sudo systemctl enable httpd

# Check if port 80 is in use
sudo netstat -tlnp | grep :80

# Check firewall
sudo firewall-cmd --list-services
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --reload

# Check SELinux
sudo setsebool -P httpd_can_network_connect 1
sudo restorecon -R /var/www/html/
```

---

### Issue 4: PHP Not Working
**Error:** PHP files download instead of executing

**Fix:**
```bash
# Install PHP modules
sudo dnf install -y php php-mysqlnd php-json php-mbstring php-xml

# Restart Apache
sudo systemctl restart httpd

# Test PHP
echo "<?php phpinfo(); ?>" | sudo tee /var/www/html/test.php
curl http://localhost/test.php
```

---

### Issue 5: Permission Denied Errors
**Error:** Various permission denied errors

**Fix File Permissions:**
```bash
# Set correct ownership
sudo chown -R apache:apache /var/www/html/azox/

# Set correct permissions
sudo chmod -R 755 /var/www/html/azox/
sudo chmod -R 777 /var/www/html/azox/logs/

# Secure config file
sudo chmod 600 /var/www/html/azox/config/database.php

# SELinux contexts
sudo restorecon -R /var/www/html/azox/
```

---

### Issue 6: SELinux Blocking Connections
**Error:** Database connections fail due to SELinux

**Fix SELinux:**
```bash
# Allow Apache to connect to database
sudo setsebool -P httpd_can_network_connect_db 1

# Allow Apache network connections
sudo setsebool -P httpd_can_network_connect 1

# Check SELinux status
sudo getenforce

# Temporarily disable SELinux (for testing only)
sudo setenforce 0
```

---

### Issue 7: Firewall Blocking Web Traffic
**Error:** Cannot access website from external IP

**Fix Firewall:**
```bash
# Check current rules
sudo firewall-cmd --list-all

# Add HTTP/HTTPS services
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https

# Or add specific ports
sudo firewall-cmd --permanent --add-port=80/tcp
sudo firewall-cmd --permanent --add-port=443/tcp

# Reload firewall
sudo firewall-cmd --reload

# Check if rules are active
sudo firewall-cmd --list-services
```

---

## 🔧 Diagnostic Commands

### Check Service Status
```bash
# Apache
sudo systemctl status httpd

# MariaDB
sudo systemctl status mariadb

# All services
sudo systemctl list-units --failed
```

### Check Logs
```bash
# Apache error log
sudo tail -f /var/log/httpd/error_log

# Apache access log
sudo tail -f /var/log/httpd/access_log

# MariaDB log
sudo journalctl -u mariadb -f

# System log
sudo journalctl -f
```

### Test Connections
```bash
# Test web server locally
curl http://localhost

# Test PHP
curl http://localhost/test.php

# Test database connection
mysql -u azox_user -p azox_network -e "SELECT 1;"

# Test from external
curl http://your-server-ip
```

### Check Network
```bash
# Check listening ports
sudo netstat -tlnp

# Check if services are listening
sudo ss -tlnp | grep -E ':(80|443|3306)'

# Check firewall status
sudo firewall-cmd --state
sudo firewall-cmd --list-all
```

---

## 🚀 Quick Recovery Commands

### Restart All Services
```bash
sudo systemctl restart httpd mariadb
```

### Reset Permissions
```bash
sudo chown -R apache:apache /var/www/html/azox/
sudo chmod -R 755 /var/www/html/azox/
sudo restorecon -R /var/www/html/azox/
```

### Test Full Stack
```bash
# Test web server
curl -I http://localhost

# Test PHP
php -v

# Test database
sudo mysql -e "SELECT 1;"

# Test application
curl http://localhost/azox/
```

---

## 📞 Getting Help

If you're still having issues:

1. **Check the logs** first (commands above)
2. **Run diagnostic commands** to identify the problem
3. **Try the specific fix** for your error message
4. **Check SELinux and firewall** settings
5. **Verify file permissions** are correct

### Useful Log Locations
- Apache: `/var/log/httpd/error_log`
- MariaDB: `sudo journalctl -u mariadb`
- System: `sudo journalctl -f`
- Application: `/var/www/html/azox/logs/activity.log`

### Emergency Reset
If everything is broken:
```bash
# Stop services
sudo systemctl stop httpd mariadb

# Clean up
sudo dnf remove -y mariadb* mysql*
sudo rm -rf /var/www/html/azox/

# Start fresh
./install-requirements.sh
./deploy.sh