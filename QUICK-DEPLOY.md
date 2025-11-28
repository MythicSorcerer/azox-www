# 🚀 Quick Deployment Guide

## One-Command Deployment

For **fresh Fedora/RHEL servers**, simply run:

```bash
sudo ./deploy-production.sh
```

This script will:
- ✅ Install all required packages (Apache, MariaDB, PHP)
- ✅ Configure services and firewall
- ✅ Set up SELinux permissions
- ✅ Copy files with correct permissions
- ✅ Create database and user automatically
- ✅ Generate secure database configuration
- ✅ Test everything and provide feedback

## What You Get

- **Web Directory**: `/var/www/azox`
- **Database**: `azox_network` with auto-generated secure password
- **Services**: Apache and MariaDB running and enabled
- **Security**: Proper file permissions and SELinux contexts
- **Logs**: Activity logging in `/var/www/azox/logs/`

## After Deployment

1. **Save the database password** shown at the end of deployment
2. **Test your site** at `http://your-server-ip`
3. **Create admin user** via the registration page
4. **Set up SSL certificate** for production use

## Troubleshooting

If you encounter issues:

1. **Check logs**: `sudo tail -f /var/log/httpd/error_log`
2. **Verify services**: `sudo systemctl status httpd mariadb`
3. **Test database**: Use the credentials provided by the deployment script
4. **Check permissions**: Files should be `apache:apache` with `644` permissions

## Manual Deployment

If you prefer manual deployment, see [`DEPLOYMENT.md`](DEPLOYMENT.md) for detailed step-by-step instructions.

## Legacy Scripts

The following scripts are available for specific issues:
- [`fix-mariadb-startup.sh`](fix-mariadb-startup.sh) - Fix MariaDB startup failures
- [`fix-database-sudo.sh`](fix-database-sudo.sh) - Set up database with sudo access
- [`FEDORA-TROUBLESHOOTING.md`](FEDORA-TROUBLESHOOTING.md) - Common deployment issues

---

**That's it!** The new deployment script handles everything automatically and provides clear feedback at each step.