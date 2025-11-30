# 🚨 Azox Network Troubleshooting Guide

## Quick Fix for "Service Temporarily Unavailable"

**If your website shows "Service Temporarily Unavailable" after deployment:**

```bash
# On your production server, run:
sudo ./debug/fix-production-simple.sh
```

This fixes 90% of deployment issues automatically.

## 📋 Debug Tools Overview

All debug tools are located in the [`debug/`](debug/) directory:

| Tool | Purpose | When to Use |
|------|---------|-------------|
| **[fix-production-simple.sh](debug/fix-production-simple.sh)** ⭐ | One-click database fix | "Service Temporarily Unavailable" error |
| **[diagnose-production.php](debug/diagnose-production.php)** | Detailed diagnosis | To understand what's wrong |
| **[fix-production-database.sh](debug/fix-production-database.sh)** | Advanced database fix | When simple fix fails |
| **[deploy-production.sh](debug/deploy-production.sh)** | Full deployment | Initial setup or complete reinstall |

**📖 Full documentation**: See [`debug/README.md`](debug/README.md)

## 🔍 Common Issues & Quick Fixes

### 1. "Service Temporarily Unavailable"
**Cause**: Database connection failure  
**Fix**: `sudo ./debug/fix-production-simple.sh`

### 2. "Database connection failed"
**Cause**: MariaDB not running or wrong credentials  
**Fix**: `sudo ./debug/fix-production-simple.sh`

### 3. "Can't connect to MySQL server"
**Cause**: MariaDB service stopped  
**Fix**: 
```bash
sudo systemctl start mariadb
sudo systemctl enable mariadb
```

### 4. "Access denied for user"
**Cause**: Database user doesn't exist  
**Fix**: `sudo ./debug/fix-production-simple.sh`

### 5. "Apache won't start"
**Cause**: Configuration error  
**Fix**: 
```bash
sudo httpd -t  # Check config
sudo systemctl restart httpd
```

## 🛠️ Step-by-Step Troubleshooting

### Step 1: Quick Diagnosis
```bash
# Test if website responds
curl -I http://localhost

# If it fails, run diagnosis
php /var/www/azox/debug/diagnose-production.php
```

### Step 2: Apply Fix
```bash
# For most issues (recommended)
sudo ./debug/fix-production-simple.sh

# For complex cases
sudo ./debug/fix-production-database.sh
```

### Step 3: Verify Fix
```bash
# Test website
curl -I http://your-server-ip

# Check logs if still failing
sudo tail -f /var/log/httpd/error_log
```

### Step 4: Nuclear Option
```bash
# Complete reinstall if everything else fails
sudo ./debug/deploy-production.sh
```

## 📊 Log Locations

- **Apache Errors**: `/var/log/httpd/error_log`
- **MariaDB Logs**: `sudo journalctl -u mariadb -f`
- **Application Logs**: `/var/www/azox/logs/activity.log`

## 🔧 Manual Commands

### Service Management
```bash
# Check status
sudo systemctl status httpd mariadb

# Start services
sudo systemctl start httpd mariadb

# Restart services
sudo systemctl restart httpd mariadb
```

### Database Management
```bash
# Connect to database
mysql -u azox_user -p azox_network

# Show databases
mysql -e "SHOW DATABASES;"
```

### File Permissions
```bash
# Fix permissions
sudo chown -R apache:apache /var/www/azox
sudo chmod 600 /var/www/azox/config/database.php
```

## 🆘 Emergency Checklist

If nothing works, check these:

1. **Disk space**: `df -h`
2. **Memory**: `free -h`
3. **System logs**: `sudo journalctl -xe`
4. **Process status**: `ps aux | grep -E "(httpd|mysql)"`
5. **Network**: `netstat -tlnp | grep -E "(80|3306)"`

## 📞 Getting Help

1. **Run diagnosis first**: `php /var/www/azox/debug/diagnose-production.php`
2. **Check recent logs**: `sudo tail -20 /var/log/httpd/error_log`
3. **Try the simple fix**: `sudo ./debug/fix-production-simple.sh`
4. **Share the output** if you need help

---

**Remember**: The debug tools are designed to be foolproof. When in doubt, run `fix-production-simple.sh`!

For detailed documentation on each tool, see [`debug/README.md`](debug/README.md).