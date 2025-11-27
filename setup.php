<?php
/**
 * Azox Network Forum Setup Script
 * Run this file once to set up the database and initial data
 */

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'azox_network';

echo "<h1>Azox Network Forum Setup</h1>";

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Connected to MySQL server</p>";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $database");
    echo "<p>✅ Database '$database' created/verified</p>";
    
    // Use the database
    $pdo->exec("USE $database");
    
    // Read and execute the SQL schema
    $sql = file_get_contents(__DIR__ . '/config/database_simple.sql');
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^(--|\/\*)/', $statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Ignore table already exists errors
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "<p>⚠️ Warning: " . $e->getMessage() . "</p>";
                }
            }
        }
    }
    
    echo "<p>✅ Database schema created/updated</p>";
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    $adminCount = $stmt->fetchColumn();
    
    if ($adminCount == 0) {
        // Create default admin user
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@azox.net', $adminPassword, 'admin']);
        echo "<p>✅ Default admin user created</p>";
        echo "<p><strong>Login Credentials:</strong></p>";
        echo "<p>Username: <code>admin</code><br>Password: <code>admin123</code></p>";
        echo "<p><strong>⚠️ IMPORTANT: Change the admin password immediately after first login!</strong></p>";
    } else {
        echo "<p>✅ Admin user already exists</p>";
    }
    
    // Also create a test regular user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'testuser'");
    $stmt->execute();
    $testUserCount = $stmt->fetchColumn();
    
    if ($testUserCount == 0) {
        $testPassword = password_hash('test123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['testuser', 'test@azox.net', $testPassword, 'user']);
        echo "<p>✅ Test user created</p>";
        echo "<p><strong>Test User Credentials:</strong></p>";
        echo "<p>Username: <code>testuser</code><br>Password: <code>test123</code></p>";
    }
    
    // Test forum categories
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM forum_categories");
    $stmt->execute();
    $categoryCount = $stmt->fetchColumn();
    
    echo "<p>✅ Forum categories: $categoryCount</p>";
    
    echo "<h2>Setup Complete!</h2>";
    echo "<p>Your Azox Network forum is ready to use:</p>";
    echo "<ul>";
    echo "<li><a href='/index.php'>Homepage</a></li>";
    echo "<li><a href='/auth/register.php'>Register New Account</a></li>";
    echo "<li><a href='/auth/login.php'>Login</a> (admin/admin123)</li>";
    echo "<li><a href='/forum/'>Forum</a></li>";
    echo "<li><a href='/admin/dashboard.php'>Admin Dashboard</a></li>";
    echo "</ul>";
    
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Change the admin password</li>";
    echo "<li>Update database credentials in config/database.php if needed</li>";
    echo "<li>Delete this setup.php file for security</li>";
    echo "</ol>";
    
} catch (PDOException $e) {
    echo "<p>❌ Database Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration in config/database.php</p>";
    echo "<p>Make sure MySQL is running and the credentials are correct.</p>";
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    background: #f5f5f5;
}
h1, h2 {
    color: #DC143C;
}
p {
    margin: 10px 0;
}
ul, ol {
    margin: 10px 0;
    padding-left: 30px;
}
a {
    color: #DC143C;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
</style>