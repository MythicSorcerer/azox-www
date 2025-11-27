#!/bin/bash

# Azox Network - Fix MariaDB Package Conflicts on Fedora
# Resolves common MariaDB installation conflicts

set -e

echo "🔧 Azox Network - Fix MariaDB Package Conflicts"
echo "==============================================="

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

echo "This script will resolve MariaDB package conflicts on Fedora."
echo "Common issues:"
echo "- Conflicting MariaDB packages from different repositories"
echo "- Incomplete previous installations"
echo "- Repository conflicts"
echo ""

# Check if MariaDB is already installed
if systemctl is-active --quiet mariadb 2>/dev/null; then
    print_status "MariaDB is already running!"
    echo "Testing connection..."
    if sudo mysql -e "SELECT 1;" > /dev/null 2>&1; then
        print_status "MariaDB is working correctly"
        echo ""
        echo "You can now run the database setup:"
        echo "./fix-database-sudo.sh"
        exit 0
    fi
fi

print_info "Checking current MariaDB installation status..."

# Check what's installed
MARIADB_PACKAGES=$(rpm -qa | grep -i mariadb || true)
if [[ -n "$MARIADB_PACKAGES" ]]; then
    echo "Found MariaDB packages:"
    echo "$MARIADB_PACKAGES"
    echo ""
fi

print_warning "This will clean up conflicting MariaDB packages and reinstall cleanly."
read -p "Continue? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Operation cancelled."
    exit 0
fi

echo ""
print_info "Step 1: Stopping any running MariaDB services..."
sudo systemctl stop mariadb 2>/dev/null || true
sudo systemctl stop mysql 2>/dev/null || true
print_status "Services stopped"

echo ""
print_info "Step 2: Removing conflicting MariaDB packages..."
# Remove all MariaDB packages
sudo dnf remove -y mariadb* mysql* 2>/dev/null || true
print_status "Conflicting packages removed"

echo ""
print_info "Step 3: Cleaning package cache..."
sudo dnf clean all
print_status "Package cache cleaned"

echo ""
print_info "Step 4: Updating package database..."
sudo dnf makecache
print_status "Package database updated"

echo ""
print_info "Step 5: Installing MariaDB cleanly..."
# Install MariaDB with specific version to avoid conflicts
sudo dnf install -y mariadb-server mariadb --best --allowerasing
print_status "MariaDB installed successfully"

echo ""
print_info "Step 6: Starting and enabling MariaDB..."
sudo systemctl start mariadb
sudo systemctl enable mariadb
print_status "MariaDB started and enabled"

echo ""
print_info "Step 7: Testing MariaDB installation..."
if systemctl is-active --quiet mariadb; then
    print_status "MariaDB service is running"
else
    print_error "MariaDB service failed to start"
    echo "Checking logs..."
    sudo journalctl -u mariadb --no-pager -n 20
    exit 1
fi

# Test connection
if sudo mysql -e "SELECT 1;" > /dev/null 2>&1; then
    print_status "MariaDB connection test successful"
else
    print_error "MariaDB connection test failed"
    exit 1
fi

echo ""
print_info "Step 8: Securing MariaDB installation..."
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
    print_warning "Skipped MariaDB security setup. Run 'sudo mysql_secure_installation' later."
fi

echo ""
print_info "Step 9: Final verification..."
# Check version
MARIADB_VERSION=$(mysql --version | cut -d' ' -f6 | cut -d',' -f1)
print_status "MariaDB $MARIADB_VERSION installed and running"

# Check if we can create databases
if sudo mysql -e "CREATE DATABASE test_connection; DROP DATABASE test_connection;" > /dev/null 2>&1; then
    print_status "Database creation test successful"
else
    print_warning "Database creation test failed - may need authentication setup"
fi

echo ""
echo "🎉 MariaDB Installation Fixed!"
echo "============================="
echo ""
echo "MariaDB is now installed and running properly."
echo ""
echo "Next steps:"
echo "1. Set up your Azox Network database:"
echo "   ./fix-database-sudo.sh"
echo ""
echo "2. Or run the full deployment:"
echo "   ./deploy.sh"
echo ""
echo "Useful commands:"
echo "- Check MariaDB status: sudo systemctl status mariadb"
echo "- Access MariaDB: sudo mysql"
echo "- View MariaDB logs: sudo journalctl -u mariadb"
echo ""
print_status "Ready for database setup! 🚀"