#!/bin/bash

# Quick Database Fix for Azox Network
# Fixes the "Access denied for user 'root'@'localhost' (using password: NO)" error

set -e

echo "🔧 Azox Network - Database Connection Fix"
echo "========================================="

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

echo "This script will fix the database connection error by:"
echo "1. Creating a dedicated database user (not root)"
echo "2. Setting up proper database credentials"
echo "3. Updating the configuration file"
echo ""

# Get database credentials
read -p "Enter database name (default: azox_network): " DB_NAME
DB_NAME=${DB_NAME:-azox_network}

read -p "Enter new database username (default: azox_user): " DB_USER
DB_USER=${DB_USER:-azox_user}

read -s -p "Enter new database password: " DB_PASS
echo

if [[ -z "$DB_PASS" ]]; then
    print_error "Database password cannot be empty"
    exit 1
fi

echo ""
print_warning "You will be prompted for the MySQL root password"

# Create database and user
echo "Creating database and user..."
mysql -u root -p << EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF

print_status "Database and user created successfully"

# Import schema if database is empty
echo ""
echo "Checking if database needs schema import..."
TABLE_COUNT=$(mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW TABLES;" | wc -l)

if [[ $TABLE_COUNT -le 1 ]]; then
    echo "Database is empty, importing schema..."
    if [[ -f "config/database_simple.sql" ]]; then
        mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < config/database_simple.sql
        print_status "Database schema imported"
    else
        print_error "Schema file not found: config/database_simple.sql"
        exit 1
    fi
else
    print_status "Database already has tables"
fi

# Update configuration
echo ""
echo "Updating database configuration..."

# Backup original config
if [[ -f "config/database.php" ]]; then
    cp config/database.php config/database.php.backup
    print_status "Original config backed up to config/database.php.backup"
fi

# Use production template if available, otherwise update existing
if [[ -f "config/database.production.php" ]]; then
    cp config/database.production.php config/database.php
    print_status "Using production configuration template"
else
    print_warning "Production template not found, updating existing config"
fi

# Update database credentials in config file
sed -i.bak "s/define('DB_USER', '[^']*')/define('DB_USER', '$DB_USER')/g" config/database.php
sed -i.bak "s/define('DB_PASS', '[^']*')/define('DB_PASS', '$DB_PASS')/g" config/database.php
sed -i.bak "s/define('DB_NAME', '[^']*')/define('DB_NAME', '$DB_NAME')/g" config/database.php
sed -i.bak "s/CHANGE_THIS_PASSWORD/$DB_PASS/g" config/database.php

print_status "Database configuration updated"

# Test connection
echo ""
echo "Testing database connection..."
if mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT 1;" > /dev/null 2>&1; then
    print_status "Database connection test successful!"
else
    print_error "Database connection test failed"
    exit 1
fi

# Set secure permissions on config file
chmod 600 config/database.php
print_status "Config file permissions secured"

echo ""
echo "🎉 Database connection fixed!"
echo "=========================="
echo ""
echo "Configuration updated:"
echo "- Database: $DB_NAME"
echo "- Username: $DB_USER"
echo "- Password: [HIDDEN]"
echo ""
echo "Next steps:"
echo "1. Test your website: http://your-domain.com"
echo "2. Register a new account"
echo "3. Make yourself admin:"
echo "   mysql -u $DB_USER -p $DB_NAME"
echo "   UPDATE users SET role = 'admin' WHERE username = 'your_username';"
echo ""
print_status "Fix completed successfully! 🚀"