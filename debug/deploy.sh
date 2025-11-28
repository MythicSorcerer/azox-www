#!/bin/bash

# Azox Network - Fedora Server Deployment Script
# Run this script on your Fedora server to set up the application

set -e  # Exit on any error

echo "🚀 Azox Network Deployment Script"
echo "=================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   print_error "This script should not be run as root for security reasons"
   exit 1
fi

# Check if we're on Fedora
if ! command -v dnf &> /dev/null; then
    print_error "This script is designed for Fedora systems with dnf package manager"
    exit 1
fi

echo "📋 Pre-deployment checklist:"
echo "1. Make sure you have sudo privileges"
echo "2. Have your database password ready"
echo "3. Know your domain name (or use localhost for testing)"
echo ""

read -p "Continue with deployment? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Deployment cancelled."
    exit 1
fi

# Step 1: Install required packages
echo ""
echo "📦 Installing required packages..."
sudo dnf update -y
sudo dnf install -y httpd php php-mysqlnd php-json php-mbstring php-xml mariadb-server mariadb

print_status "Packages installed"

# Step 2: Start and enable services
echo ""
echo "🔧 Starting services..."
sudo systemctl start httpd mariadb
sudo systemctl enable httpd mariadb

print_status "Services started and enabled"

# Step 3: Secure MySQL installation
echo ""
echo "🔒 MySQL Security Setup"
print_warning "You will be prompted to secure your MySQL installation"
echo "Recommended answers:"
echo "- Set root password: Y"
echo "- Remove anonymous users: Y"
echo "- Disallow root login remotely: Y"
echo "- Remove test database: Y"
echo "- Reload privilege tables: Y"
echo ""

read -p "Run mysql_secure_installation now? (Y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Nn]$ ]]; then
    sudo mysql_secure_installation
    print_status "MySQL secured"
fi

# Step 4: Database setup
echo ""
echo "🗄️  Database Setup"
read -p "Enter database name (default: azox_network): " DB_NAME
DB_NAME=${DB_NAME:-azox_network}

read -p "Enter database username (default: azox_user): " DB_USER
DB_USER=${DB_USER:-azox_user}

read -s -p "Enter database password: " DB_PASS
echo

if [[ -z "$DB_PASS" ]]; then
    print_error "Database password cannot be empty"
    exit 1
fi

# Create database and user
echo ""
echo "Creating database and user..."
mysql -u root -p << EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF

print_status "Database and user created"

# Step 5: Import database schema
echo ""
echo "📊 Importing database schema..."
if [[ -f "config/database_simple.sql" ]]; then
    mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < config/database_simple.sql
    print_status "Database schema imported"
else
    print_error "Database schema file not found: config/database_simple.sql"
    exit 1
fi

# Step 6: Configure database connection
echo ""
echo "⚙️  Configuring database connection..."
if [[ -f "config/database.production.php" ]]; then
    cp config/database.production.php config/database.php
    
    # Update database configuration
    sed -i "s/CHANGE_THIS_PASSWORD/$DB_PASS/g" config/database.php
    sed -i "s/azox_user/$DB_USER/g" config/database.php
    sed -i "s/azox_network/$DB_NAME/g" config/database.php
    
    print_status "Database configuration updated"
else
    print_error "Production database template not found"
    exit 1
fi

# Step 7: Set up web directory
echo ""
echo "🌐 Setting up web directory..."
read -p "Enter web directory path (default: /var/www/html/azox): " WEB_DIR
WEB_DIR=${WEB_DIR:-/var/www/html/azox}

sudo mkdir -p "$WEB_DIR"
sudo cp -r ./* "$WEB_DIR/"

# Set permissions
sudo chown -R apache:apache "$WEB_DIR"
sudo chmod -R 755 "$WEB_DIR"
sudo chmod -R 777 "$WEB_DIR/logs" 2>/dev/null || mkdir -p "$WEB_DIR/logs" && sudo chmod 777 "$WEB_DIR/logs"

# Secure config file
sudo chmod 600 "$WEB_DIR/config/database.php"

print_status "Files deployed to $WEB_DIR"

# Step 8: Configure Apache
echo ""
echo "🔧 Configuring Apache..."
read -p "Enter your domain name (or 'localhost' for testing): " DOMAIN
DOMAIN=${DOMAIN:-localhost}

sudo tee /etc/httpd/conf.d/azox.conf > /dev/null << EOF
<VirtualHost *:80>
    ServerName $DOMAIN
    DocumentRoot $WEB_DIR
    
    <Directory $WEB_DIR>
        AllowOverride All
        Require all granted
        DirectoryIndex index.php index.html
    </Directory>
    
    ErrorLog /var/log/httpd/azox_error.log
    CustomLog /var/log/httpd/azox_access.log combined
</VirtualHost>
EOF

sudo systemctl restart httpd
print_status "Apache configured"

# Step 9: Configure firewall
echo ""
echo "🔥 Configuring firewall..."
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload

print_status "Firewall configured"

# Step 10: SELinux configuration
echo ""
echo "🛡️  Configuring SELinux..."
sudo setsebool -P httpd_can_network_connect 1
sudo setsebool -P httpd_can_network_connect_db 1
sudo restorecon -R "$WEB_DIR"

print_status "SELinux configured"

# Final steps
echo ""
echo "🎉 Deployment Complete!"
echo "======================"
echo ""
echo "Next steps:"
echo "1. Visit: http://$DOMAIN"
echo "2. Register a new account"
echo "3. Make yourself admin:"
echo "   mysql -u $DB_USER -p $DB_NAME"
echo "   UPDATE users SET role = 'admin' WHERE username = 'your_username';"
echo ""
echo "Test URLs:"
echo "- Homepage: http://$DOMAIN/"
echo "- Forum: http://$DOMAIN/forum/"
echo "- Admin: http://$DOMAIN/admin/dashboard.php"
echo ""
echo "Logs:"
echo "- Apache errors: sudo tail -f /var/log/httpd/azox_error.log"
echo "- Application logs: sudo tail -f $WEB_DIR/logs/activity.log"
echo ""
print_status "Deployment successful! 🚀"