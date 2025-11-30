# 🛠️ Debug Tools Summary

## 📁 What's in this directory

This directory contains **4 essential tools** for production deployment and troubleshooting:

| File | Purpose | Priority |
|------|---------|----------|
| **[fix-production-simple.sh](fix-production-simple.sh)** ⭐ | One-click fix for database issues | **HIGH** |
| **[diagnose-production.php](diagnose-production.php)** | Detailed system diagnosis | **MEDIUM** |
| **[fix-production-database.sh](fix-production-database.sh)** | Advanced database repair | **MEDIUM** |
| **[deploy-production.sh](deploy-production.sh)** | Full system deployment | **LOW** |

## 🚨 Quick Fix for "Service Temporarily Unavailable"

**Most common issue**: Database connection failure after deployment

**Solution**:
```bash
sudo ./debug/fix-production-simple.sh
```

This solves **90% of production issues** automatically.

## 📖 Documentation

- **[README.md](README.md)** - Complete tool documentation
- **[../TROUBLESHOOTING.md](../TROUBLESHOOTING.md)** - Main troubleshooting guide
- **[../README.md](../README.md)** - Project overview with deployment section

## 🔄 Troubleshooting Workflow

1. **Identify**: Run `php diagnose-production.php`
2. **Fix**: Run `sudo ./fix-production-simple.sh`
3. **Verify**: Test your website
4. **Escalate**: Use advanced tools if needed

## 🎯 Tool Selection Guide

### Use `fix-production-simple.sh` when:
- ✅ Website shows "Service Temporarily Unavailable"
- ✅ Database connection errors
- ✅ After initial deployment
- ✅ You want a quick, reliable fix

### Use `diagnose-production.php` when:
- 🔍 You want to understand what's wrong
- 🔍 Before applying fixes
- 🔍 For detailed system information

### Use `fix-production-database.sh` when:
- 🔧 Simple fix didn't work
- 🔧 You have complex database issues
- 🔧 You want to preserve existing config

### Use `deploy-production.sh` when:
- 🚀 Fresh server deployment
- 🚀 Complete system reinstall
- 🚀 Starting from scratch

## ✅ Success Indicators

After running any fix script, you should see:
- ✅ "Database connection successful"
- ✅ "Apache restarted"
- ✅ "Website is responding"
- ✅ Your website loads without errors

## 🆘 If Nothing Works

1. Check system resources: `df -h` and `free -h`
2. Check service status: `sudo systemctl status httpd mariadb`
3. Check logs: `sudo tail -f /var/log/httpd/error_log`
4. Try complete reinstall: `sudo ./deploy-production.sh`

---

**Remember**: These tools are designed to be foolproof. When in doubt, run `fix-production-simple.sh`!