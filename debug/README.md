# 🛠️ Azox Network Debug Tools

This directory contains diagnostic and repair tools for production deployment issues.

## 🚨 Quick Fix for "Service Temporarily Unavailable"

**Most common issue**: Database connection failure after deployment.

**Quick solution**:
```bash
sudo ./debug/fix-production-simple.sh
```

## 📋 Available Tools

### 1. **fix-production-simple.sh** ⭐ *RECOMMENDED*
**Purpose**: One-click fix for database connection issues  
**When to use**: When you see "Service Temporarily Unavailable" error  
**What it does**:
- ✅ Starts MariaDB service
- ✅ Creates fresh database and user
- ✅ Generates secure production config
- ✅ Imports database schema
- ✅ Sets correct permissions
- ✅ Tests everything

**Usage**:
```bash
sudo ./debug/fix-production-simple.sh
```

### 2. **diagnose-production.php**
**Purpose**: Detailed diagnosis of production issues  
**When to use**: To understand what's wrong before fixing  
**What it shows**:
- Database configuration format
- Service status
- Connection test results
- File permissions
- Recent error logs

**Usage**:
```bash
php /var/www/azox/debug/diagnose-production.php
```

### 3. **fix-production-database.sh**
**Purpose**: Advanced fix script with config preservation  
**When to use**: When you want to preserve existing config if possible  
**What it does**:
- Tries to read existing config
- Falls back to creating new config if needed
- More complex logic for edge cases

**Usage**:
```bash
sudo ./debug/fix-production-database.sh
```

### 4. **deploy-production.sh**
**Purpose**: Full deployment from scratch  
**When to use**: Initial deployment or complete reinstall  
**What it does**:
- Installs all system packages
- Configures services and firewall
- Deploys entire application

**Usage**:
```bash
sudo ./debug/deploy-production.sh
```

## 🔍 Troubleshooting Workflow

### Step 1: Identify the Problem
```bash
# Check if website is responding
curl -I http://localhost

# If you see "Service Temporarily Unavailable", run diagnosis
php /var/www/azox/debug/diagnose-production.php
```

### Step 2: Quick Fix (90% of cases)
```bash
sudo ./debug/fix-production-simple.sh
```

### Step 3: If Quick Fix Fails
```bash
# Check what went wrong
sudo tail -f /var/log/httpd/error_log

# Try advanced fix
sudo ./debug/fix-production-database.sh

# If still failing, check service status
sudo systemctl status httpd mariadb
```

### Step 4: Nuclear Option (Complete Reinstall)
```bash
# If everything else fails
sudo ./debug/deploy-production.sh
```

## 🚨 Common Issues & Solutions

### Issue: "MariaDB won't start"
```bash
# Check what's wrong
sudo journalctl -u mariadb -f

# Force restart
sudo systemctl stop mariadb
sudo systemctl start mariadb

# If still failing, reinstall
sudo dnf remove mariadb-server mariadb
sudo dnf install mariadb-server mariadb
sudo systemctl start mariadb
sudo systemctl enable mariadb
```

### Issue: "Database connection failed"
```bash
# Run the simple fix - it recreates everything
sudo ./debug/fix-production-simple.sh
```

### Issue: "File permissions wrong"
```bash
sudo chown -R apache:apache /var/www/azox
sudo find /var/www/azox -type f -exec chmod 644 {} \;
sudo find /var/www/azox -type d -exec chmod 755 {} \;
sudo chmod 600 /var/www/azox/config/database.php
```

### Issue: "Apache won't start"
```bash
# Check configuration
sudo httpd -t

# Check what's wrong
sudo journalctl -u httpd -f

# Restart
sudo systemctl restart httpd
```

## 📊 Understanding Error Messages

### "Service Temporarily Unavailable"
- **Cause**: Database connection failure
- **Location**: `config/database.php` lines 51-67
- **Fix**: Run `fix-production-simple.sh`

### "Database connection failed"
- **Cause**: Wrong credentials or service not running
- **Fix**: Run `fix-production-simple.sh`

### "Access denied for user"
- **Cause**: Database user doesn't exist or wrong password
- **Fix**: Run `fix-production-simple.sh`

### "Can't connect to MySQL server"
- **Cause**: MariaDB service not running
- **Fix**: `sudo systemctl start mariadb`

## 🔧 Manual Commands Reference

### Database Management
```bash
# Connect to database
mysql -u azox_user -p azox_network

# Show databases
mysql -e "SHOW DATABASES;"

# Create database
mysql -e "CREATE DATABASE azox_network CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Create user
mysql -e "CREATE USER 'azox_user'@'localhost' IDENTIFIED BY 'password';"
mysql -e "GRANT ALL PRIVILEGES ON azox_network.* TO 'azox_user'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"
```

### Service Management
```bash
# Check service status
sudo systemctl status httpd mariadb

# Start services
sudo systemctl start httpd mariadb

# Enable services (start on boot)
sudo systemctl enable httpd mariadb

# Restart services
sudo systemctl restart httpd mariadb
```

### Log Monitoring
```bash
# Apache error log
sudo tail -f /var/log/httpd/error_log

# MariaDB log
sudo journalctl -u mariadb -f

# Application log
tail -f /var/www/azox/logs/activity.log
```

## 📁 File Locations

- **Web Directory**: `/var/www/azox`
- **Database Config**: `/var/www/azox/config/database.php`
- **Apache Config**: `/etc/httpd/conf/httpd.conf`
- **Apache Logs**: `/var/log/httpd/`
- **Application Logs**: `/var/www/azox/logs/`

## 🆘 Emergency Contacts

If all tools fail:

1. **Check system logs**: `sudo journalctl -xe`
2. **Check disk space**: `df -h`
3. **Check memory**: `free -h`
4. **Check processes**: `ps aux | grep -E "(httpd|mysql)"`

## 📝 Notes

- Always run scripts with `sudo`
- Save database passwords shown by scripts
- Test website after each fix
- Check logs if issues persist
- The simple fix script is usually sufficient

---

**Remember**: When in doubt, run `fix-production-simple.sh` - it solves 90% of deployment issues!