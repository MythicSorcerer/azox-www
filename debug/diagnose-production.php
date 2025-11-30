<?php
/**
 * Production Database Diagnostic Script
 * Run this on your production server to diagnose database connection issues
 */

echo "<h1>Azox Network - Production Diagnostic</h1>\n";
echo "<pre>\n";

// Check if we're running on the production server
$webDir = '/var/www/azox';
if (!is_dir($webDir)) {
    echo "❌ ERROR: Web directory $webDir not found.\n";
    echo "   This script should be run on the production server.\n";
    exit(1);
}

echo "✅ Web directory found: $webDir\n\n";

// Check database configuration file
$dbConfigFile = $webDir . '/config/database.php';
if (!file_exists($dbConfigFile)) {
    echo "❌ ERROR: Database config file not found: $dbConfigFile\n";
    exit(1);
}

echo "✅ Database config file found\n";

// Try to read database configuration
echo "📋 Reading database configuration...\n";
$configContent = file_get_contents($dbConfigFile);

// Extract database credentials from the config file
// Try production format first
if (preg_match('/\$dbname = \'([^\']+)\';/', $configContent, $matches)) {
    $dbname = $matches[1];
    echo "   Database name: $dbname (production format)\n";
} elseif (preg_match('/define\(\'DB_NAME\', \'([^\']+)\'\);/', $configContent, $matches)) {
    $dbname = $matches[1];
    echo "   Database name: $dbname (development format)\n";
} else {
    echo "❌ Could not extract database name from config\n";
    echo "   Config format not recognized. Checking content...\n";
    echo "   First 500 characters:\n";
    echo "   " . substr($configContent, 0, 500) . "\n";
    exit(1);
}

if (preg_match('/\$username = \'([^\']+)\';/', $configContent, $matches)) {
    $username = $matches[1];
    echo "   Database user: $username (production format)\n";
} elseif (preg_match('/define\(\'DB_USER\', \'([^\']*)\'\);/', $configContent, $matches)) {
    $username = $matches[1];
    echo "   Database user: $username (development format)\n";
} else {
    echo "❌ Could not extract database username from config\n";
    exit(1);
}

if (preg_match('/\$password = \'([^\']*)\';/', $configContent, $matches)) {
    $password = $matches[1];
    echo "   Database password: [" . strlen($password) . " characters] (production format)\n";
} elseif (preg_match('/define\(\'DB_PASS\', \'([^\']*)\'\);/', $configContent, $matches)) {
    $password = $matches[1];
    echo "   Database password: [" . strlen($password) . " characters] (development format)\n";
} else {
    echo "❌ Could not extract database password from config\n";
    exit(1);
}

echo "\n";

// Check if MariaDB/MySQL service is running
echo "🔍 Checking database service status...\n";
$serviceStatus = shell_exec('systemctl is-active mariadb 2>/dev/null');
if (trim($serviceStatus) === 'active') {
    echo "✅ MariaDB service is running\n";
} else {
    echo "❌ MariaDB service is not running\n";
    echo "   Status: " . trim($serviceStatus) . "\n";
    echo "   Try: sudo systemctl start mariadb\n";
    exit(1);
}

// Test database connection
echo "\n🔗 Testing database connection...\n";
try {
    $dsn = "mysql:host=localhost;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    echo "✅ Database connection successful!\n";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = '$dbname'");
    $result = $stmt->fetch();
    echo "✅ Database has " . $result['count'] . " tables\n";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed!\n";
    echo "   Error: " . $e->getMessage() . "\n";
    
    // Check if database exists
    try {
        $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", $username, $password);
        $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
        if ($stmt->rowCount() === 0) {
            echo "❌ Database '$dbname' does not exist\n";
            echo "   Create it with: CREATE DATABASE $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
        } else {
            echo "✅ Database '$dbname' exists\n";
        }
    } catch (PDOException $e2) {
        echo "❌ Cannot connect to MySQL server at all\n";
        echo "   Error: " . $e2->getMessage() . "\n";
    }
}

// Check file permissions
echo "\n📁 Checking file permissions...\n";
$owner = posix_getpwuid(fileowner($dbConfigFile));
$group = posix_getgrgid(filegroup($dbConfigFile));
$perms = substr(sprintf('%o', fileperms($dbConfigFile)), -3);

echo "   Config file owner: " . $owner['name'] . ":" . $group['name'] . "\n";
echo "   Config file permissions: $perms\n";

if ($owner['name'] !== 'apache' || $group['name'] !== 'apache') {
    echo "⚠️  WARNING: Config file should be owned by apache:apache\n";
    echo "   Fix with: sudo chown apache:apache $dbConfigFile\n";
}

if ($perms !== '600') {
    echo "⚠️  WARNING: Config file should have 600 permissions\n";
    echo "   Fix with: sudo chmod 600 $dbConfigFile\n";
}

// Check Apache error log
echo "\n📋 Recent Apache errors:\n";
$errorLog = '/var/log/httpd/error_log';
if (file_exists($errorLog)) {
    $errors = shell_exec("tail -10 $errorLog 2>/dev/null");
    if ($errors) {
        echo $errors;
    } else {
        echo "   No recent errors found\n";
    }
} else {
    echo "   Error log not found at $errorLog\n";
}

echo "\n";
echo "🎯 DIAGNOSIS COMPLETE\n";
echo "===================\n";

echo "</pre>\n";
?>