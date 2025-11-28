#!/bin/bash

# Azox Network - Install Required Packages on Fedora
# Installs Apache, PHP, and MariaDB for the Azox Network website

set -e

echo "📦 Azox Network - Installing Requirements on Fedora"
echo "=================================================="

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if running on Fedora
if ! command -v dnf &> /dev/null; then
    print_error "This script is designed for Fedora systems with dnf package manager"
    exit 1
fi

# Check if user has sudo privileges
if ! sudo -n true 2>/dev/null; then
    print_error "This script requires sudo privileges. Please run with a user that has sudo access."
    exit 1
fi

print_info "This script will install:"
echo "- Apache HTTP Server (httpd)"
echo "- PHP 8.x with required extensions"
echo "- MariaDB (MySQL-compatible database)"
echo "- Required PHP modules for the website"
echo ""

read -p "Continue with installation? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Installation cancelled."
    exit 0
fi

echo ""
print_info "Updating system packages..."
sudo dnf update -y

echo ""
print_info "Installing Apache HTTP Server..."
sudo dnf install -y httpd

echo ""
print_info "Installing PHP and required extensions..."
sudo dnf install -y php php-mysqlnd php-json php-mbstring php-xml php-gd php-curl php-zip

echo ""
print_info "Installing MariaDB (MySQL-compatible database)..."
sudo dnf install -y mariadb-server mariadb

print_status "All packages installed successfully!"

echo ""
print_info "Starting and enabling services..."

# Start and enable Apache
sudo systemctl start httpd
sudo systemctl enable httpd
print_status "Apache started and enabled"

# Start and enable MariaDB
sudo systemctl start mariadb
sudo systemctl enable mariadb
print_status "MariaDB started and enabled"

echo ""
print_info "Configuring firewall..."
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
print_status "Firewall configured for HTTP/HTTPS"

echo ""
print_info "Securing MariaDB installation..."
print_warning "You will be prompted to secure your MariaDB installation."
echo "Recommended answers:"
echo "- Set root password: Y (choose a strong password)"
echo "- Remove anonymous users: Y"
echo "- Disallow root login remotely: Y"
echo "- Remove test database: Y"
echo "- Reload privilege tables: Y"
echo ""

read -p "Run mysql_secure_installation now? (Y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Nn]$ ]]; then
    sudo mysql_secure_installation
    print_status "MariaDB secured"
else
    print_warning "Skipped MariaDB security setup. You should run 'sudo mysql_secure_installation' later."
fi

echo ""
print_info "Testing installations..."

# Test Apache
if systemctl is-active --quiet httpd; then
    print_status "Apache is running"
else
    print_error "Apache is not running"
fi

# Test MariaDB
if systemctl is-active --quiet mariadb; then
    print_status "MariaDB is running"
else
    print_error "MariaDB is not running"
fi

# Test PHP
PHP_VERSION=$(php -v | head -n1 | cut -d' ' -f2)
print_status "PHP $PHP_VERSION installed"

# Test database connection
if sudo mysql -e "SELECT 1;" > /dev/null 2>&1; then
    print_status "MariaDB connection test successful"
else
    print_warning "MariaDB connection test failed - you may need to set up authentication"
fi

echo ""
echo "🎉 Installation Complete!"
echo "========================"
echo ""
echo "Services installed and running:"
echo "- Apache HTTP Server: http://localhost"
echo "- MariaDB Database Server"
echo "- PHP $PHP_VERSION with required extensions"
echo ""
echo "Next steps:"
echo "1. Run the database setup script:"
echo "   ./fix-database-sudo.sh"
echo ""
echo "2. Or run the full deployment script:"
echo "   ./deploy.sh"
echo ""
echo "3. Test your web server:"
echo "   curl http://localhost"
echo ""
echo "Useful commands:"
echo "- Check Apache status: sudo systemctl status httpd"
echo "- Check MariaDB status: sudo systemctl status mariadb"
echo "- Access MariaDB: sudo mysql"
echo "- View Apache logs: sudo tail -f /var/log/httpd/error_log"
echo ""
print_status "Ready to deploy Azox Network! 🚀"