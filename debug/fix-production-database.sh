#!/bin/bash

# Production Database Fix Script for User Role Issue
# This script fixes the default role value in the production database

echo "========================================="
echo "PRODUCTION DATABASE FIX - User Role Issue"
echo "========================================="
echo ""
echo "This script will fix the issue where new users"
echo "are automatically becoming owners instead of regular users."
echo ""

# Database credentials (update these for your production server)
DB_HOST="localhost"
DB_NAME="azox_network"
DB_USER="azox_user"
DB_PASS="Tomm38hcvwapZ0D1fYGq9N2EMq5BzWXXvu6SW2sd6Fc="

echo "Step 1: Checking current role column default value..."
echo "------------------------------------------------------"
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW COLUMNS FROM users WHERE Field = 'role';"

echo ""
echo "Step 2: Fixing the default value for role column..."
echo "---------------------------------------------------"
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "ALTER TABLE users ALTER COLUMN role SET DEFAULT 'user';"

echo ""
echo "Step 3: Verifying the fix..."
echo "-----------------------------"
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW COLUMNS FROM users WHERE Field = 'role';"

echo ""
echo "Step 4: Showing users with 'owner' role..."
echo "-------------------------------------------"
echo "Review this list - these users currently have owner privileges:"
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT id, username, email, created_at FROM users WHERE role = 'owner';"

echo ""
echo "Step 5: Role distribution after fix..."
echo "---------------------------------------"
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT role, COUNT(*) as count FROM users GROUP BY role;"

echo ""
echo "========================================="
echo "FIX COMPLETED!"
echo "========================================="
echo ""
echo "IMPORTANT NOTES:"
echo "1. The database default has been fixed to 'user'"
echo "2. The PHP code has also been updated to explicitly set role='user'"
echo "3. New registrations will now correctly get 'user' role"
echo ""
echo "If you need to demote incorrectly assigned owners to regular users,"
echo "run this SQL command with the appropriate user IDs:"
echo ""
echo "UPDATE users SET role = 'user' WHERE id IN (id1, id2, id3) AND role = 'owner';"
echo ""
echo "Replace id1, id2, id3 with actual user IDs that should not be owners."