#!/bin/bash

# Azox Network - Fix MariaDB Startup Issues on Fedora
# Diagnoses and fixes common MariaDB startup failures

set -e

echo "🔧 Azox Network - Fix MariaDB Startup Issues"
echo "============================================"

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

echo "This script will diagnose and fix MariaDB startup failures."
echo "Common causes:"
echo "- Corrupted data directory"
echo "- Permission issues"
echo "- Configuration problems"
echo "- Incomplete installation"
echo ""

print_info "Step 1: Checking current MariaDB status..."
if systemctl is-active --quiet mariadb 2>/dev/null; then
    print_status "MariaDB is already running!"
    exit 0
else
    print_warning "MariaDB is not running"
fi

print_info "Step 2: Checking MariaDB service status..."
echo "Service status:"
sudo systemctl status mariadb --no-pager -l || true
echo ""

print_info "Step 3: Checking MariaDB logs..."
echo "Recent MariaDB logs:"
sudo journalctl -u mariadb --no-pager -n 20 || true
echo ""

print_info "Step 4: Checking for common issues..."

# Check if MariaDB is installed
if ! rpm -q mariadb-server > /dev/null 2>&1; then
    print_error "MariaDB server is not installed!"
    echo "Installing MariaDB..."
    sudo dnf install -y mariadb-server mariadb
    print_status "MariaDB installed"
fi

# Check data directory
DATADIR="/var/lib/mysql"
if [[ ! -d "$DATADIR" ]]; then
    print_warning "MariaDB data directory doesn't exist: $DATADIR"
    echo "Creating data directory..."
    sudo mkdir -p "$DATADIR"
    sudo chown mysql:mysql "$DATADIR"
    sudo chmod 755 "$DATADIR"
    print_status "Data directory created"
fi

# Check permissions
print_info "Step 5: Checking and fixing permissions..."
sudo chown -R mysql:mysql "$DATADIR"
sudo chmod -R 755 "$DATADIR"
print_status "Permissions fixed"

# Check if database needs initialization
if [[ ! -f "$DATADIR/mysql/user.frm" ]] && [[ ! -d "$DATADIR/mysql" ]]; then
    print_warning "MariaDB database not initialized"
    echo "Initializing MariaDB database..."
    sudo mysql_install_db --user=mysql --datadir="$DATADIR"
    print_status "Database initialized"
fi

# Check SELinux contexts
print_info "Step 6: Fixing SELinux contexts..."
sudo restorecon -R "$DATADIR" 2>/dev/null || true
sudo setsebool -P mysql_connect_any 1 2>/dev/null || true
print_status "SELinux contexts fixed"

# Check for port conflicts
print_info "Step 7: Checking for port conflicts..."
if sudo netstat -tlnp | grep :3306 > /dev/null 2>&1; then
    print_warning "Port 3306 is already in use:"
    sudo netstat -tlnp | grep :3306
    echo ""
    print_info "Killing processes using port 3306..."
    sudo pkill -f mysql || true
    sudo pkill -f mariadb || true
    sleep 2
fi

# Remove any lock files
print_info "Step 8: Removing lock files..."
sudo rm -f "$DATADIR"/*.pid 2>/dev/null || true
sudo rm -f /var/run/mariadb/mariadb.pid 2>/dev/null || true
sudo rm -f /tmp/mysql.sock 2>/dev/null || true
print_status "Lock files removed"

# Check disk space
print_info "Step 9: Checking disk space..."
DISK_USAGE=$(df "$DATADIR" | tail -1 | awk '{print $5}' | sed 's/%//')
if [[ $DISK_USAGE -gt 90 ]]; then
    print_error "Disk space is critically low: ${DISK_USAGE}% used"
    echo "Free up some disk space and try again."
    exit 1
else
    print_status "Disk space OK: ${DISK_USAGE}% used"
fi

# Try to start MariaDB
print_info "Step 10: Attempting to start MariaDB..."
if sudo systemctl start mariadb; then
    print_status "MariaDB started successfully!"
    
    # Enable auto-start
    sudo systemctl enable mariadb
    print_status "MariaDB enabled for auto-start"
    
    # Test connection
    sleep 2
    if sudo mysql -e "SELECT 1;" > /dev/null 2>&1; then
        print_status "MariaDB connection test successful"
    else
        print_warning "MariaDB started but connection test failed"
    fi
    
else
    print_error "MariaDB failed to start"
    echo ""
    print_info "Checking detailed error logs..."
    sudo journalctl -u mariadb --no-pager -n 50
    echo ""
    
    print_info "Trying alternative startup methods..."
    
    # Try safe mode startup
    print_info "Attempting safe mode startup..."
    sudo mysqld_safe --user=mysql --datadir="$DATADIR" &
    SAFE_PID=$!
    sleep 5
    
    if sudo mysql -e "SELECT 1;" > /dev/null 2>&1; then
        print_status "Safe mode startup successful"
        sudo kill $SAFE_PID 2>/dev/null || true
        
        # Try normal startup again
        sleep 2
        if sudo systemctl start mariadb; then
            print_status "Normal startup now working"
        else
            print_error "Still failing to start normally"
        fi
    else
        print_error "Safe mode startup also failed"
        sudo kill $SAFE_PID 2>/dev/null || true
        
        # Last resort: reinitialize database
        print_warning "Attempting database reinitialization..."
        read -p "This will DELETE all existing data. Continue? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            sudo systemctl stop mariadb 2>/dev/null || true
            sudo rm -rf "$DATADIR"/*
            sudo mysql_install_db --user=mysql --datadir="$DATADIR"
            sudo chown -R mysql:mysql "$DATADIR"
            
            if sudo systemctl start mariadb; then
                print_status "Database reinitialized and started successfully"
            else
                print_error "Even after reinitialization, MariaDB won't start"
                echo "Please check the system logs and consider manual troubleshooting."
                exit 1
            fi
        else
            print_error "Database reinitialization cancelled"
            exit 1
        fi
    fi
fi

# Final verification
print_info "Step 11: Final verification..."
if systemctl is-active --quiet mariadb; then
    print_status "MariaDB is running"
    
    # Show version
    MARIADB_VERSION=$(mysql --version 2>/dev/null | cut -d' ' -f6 | cut -d',' -f1 || echo "Unknown")
    print_status "MariaDB version: $MARIADB_VERSION"
    
    # Test database operations
    if sudo mysql -e "CREATE DATABASE test_startup; DROP DATABASE test_startup;" > /dev/null 2>&1; then
        print_status "Database operations test successful"
    else
        print_warning "Database operations test failed"
    fi
    
else
    print_error "MariaDB is still not running"
    exit 1
fi

echo ""
echo "🎉 MariaDB Startup Fixed!"
echo "========================"
echo ""
echo "MariaDB is now running successfully."
echo ""
echo "Next steps:"
echo "1. Secure your MariaDB installation:"
echo "   sudo mysql_secure_installation"
echo ""
echo "2. Set up your Azox Network database:"
echo "   ./fix-database-sudo.sh"
echo ""
echo "Useful commands:"
echo "- Check status: sudo systemctl status mariadb"
echo "- View logs: sudo journalctl -u mariadb -f"
echo "- Connect: sudo mysql"
echo ""
print_status "MariaDB is ready! 🚀"