#!/bin/bash

# Azox Network - Database Fix for Sudo Access (No Root Password)
# For systems where you can only access MySQL via sudo

set -e

echo "🔧 Azox Network - Database Fix (Sudo Access)"
echo "============================================="

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

echo "This script works when you can only access MySQL via 'sudo mysql' (no root password)."
echo "Perfect for Fedora servers with default MariaDB/MySQL installations."
echo ""

# Check if we can access MySQL via sudo
echo "Testing MySQL access..."
if ! sudo mysql -e "SELECT 1;" > /dev/null 2>&1; then
    print_error "Cannot access MySQL via sudo. Please check if MySQL/MariaDB is running:"
    echo "sudo systemctl status mariadb"
    echo "sudo systemctl start mariadb"
    exit 1
fi

print_status "MySQL access confirmed via sudo"

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
echo "Creating database and user using sudo access..."

# Create database and user using sudo mysql
sudo mysql << EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF

print_status "Database '$DB_NAME' and user '$DB_USER' created successfully"

# Test the new user connection
echo ""
echo "Testing new user connection..."
if mysql -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; SELECT 1;" > /dev/null 2>&1; then
    print_status "New user connection test successful"
else
    print_error "New user connection failed"
    exit 1
fi

# Check if database needs schema import
echo ""
echo "Checking if database needs schema import..."
TABLE_COUNT=$(mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW TABLES;" 2>/dev/null | wc -l)

if [[ $TABLE_COUNT -le 1 ]]; then
    echo "Database is empty, importing schema..."
    if [[ -f "config/database_simple.sql" ]]; then
        mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < config/database_simple.sql
        print_status "Database schema imported successfully"
    else
        print_error "Schema file not found: config/database_simple.sql"
        echo "Please make sure you're running this script from the project root directory."
        exit 1
    fi
else
    print_status "Database already has tables ($((TABLE_COUNT-1)) tables found)"
fi

# Update configuration
echo ""
echo "Updating database configuration..."

# Backup original config if it exists
if [[ -f "config/database.php" ]]; then
    cp config/database.php config/database.php.backup.$(date +%Y%m%d_%H%M%S)
    print_status "Original config backed up"
fi

# Use production template if available, otherwise create new config
if [[ -f "config/database.production.php" ]]; then
    cp config/database.production.php config/database.php
    print_status "Using production configuration template"
else
    print_warning "Production template not found, creating basic config"
    cat > config/database.php << 'EOF'
<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'azox_network');
define('DB_USER', 'azox_user');
define('DB_PASS', 'CHANGE_THIS_PASSWORD');
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT', 3306);

// PDO options
$pdo_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
];

$pdo = null;

function getDB() {
    global $pdo, $pdo_options;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    }
    return $pdo;
}

// Include other database functions from original file...
EOF
fi

# Update database credentials in config file
sed -i.bak "s/define('DB_USER', '[^']*')/define('DB_USER', '$DB_USER')/g" config/database.php
sed -i.bak "s/define('DB_PASS', '[^']*')/define('DB_PASS', '$DB_PASS')/g" config/database.php
sed -i.bak "s/define('DB_NAME', '[^']*')/define('DB_NAME', '$DB_NAME')/g" config/database.php
sed -i.bak "s/CHANGE_THIS_PASSWORD/$DB_PASS/g" config/database.php

print_status "Database configuration updated"

# Set secure permissions on config file
chmod 600 config/database.php
print_status "Config file permissions secured (600)"

# Final connection test
echo ""
echo "Final connection test..."
if mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = '$DB_NAME';" > /dev/null 2>&1; then
    print_status "Final database connection test successful!"
else
    print_error "Final connection test failed"
    exit 1
fi

echo ""
echo "🎉 Database connection fixed!"
echo "=========================="
echo ""
echo "Configuration summary:"
echo "- Database: $DB_NAME"
echo "- Username: $DB_USER"
echo "- Password: [HIDDEN]"
echo "- Host: localhost"
echo ""
echo "Next steps:"
echo "1. Test your website: http://your-domain.com"
echo "2. Register a new account"
echo "3. Make yourself admin:"
echo "   mysql -u $DB_USER -p $DB_NAME"
echo "   UPDATE users SET role = 'admin' WHERE username = 'your_username';"
echo ""
echo "Useful commands:"
echo "- Connect to database: mysql -u $DB_USER -p $DB_NAME"
echo "- Check tables: mysql -u $DB_USER -p $DB_NAME -e 'SHOW TABLES;'"
echo "- View users: mysql -u $DB_USER -p $DB_NAME -e 'SELECT username, role FROM users;'"
echo ""
print_status "Fix completed successfully! 🚀"