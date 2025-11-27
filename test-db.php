<?php
/**
 * Database Connection Test Script
 * Use this to test if the database is working properly
 */

echo "<h1>Database Connection Test</h1>";

try {
    // Test basic connection
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'azox_network';
    
    echo "<h2>Testing MySQL Connection...</h2>";
    
    // Try to connect to MySQL server
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p>✅ Connected to MySQL server</p>";
    
    // Try to use the database
    $pdo->exec("USE $database");
    echo "<p>✅ Connected to database '$database'</p>";
    
    // Test if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Users table exists</p>";
        
        // Check if admin user exists
        $stmt = $pdo->prepare("SELECT username, email, role FROM users WHERE role = 'admin'");
        $stmt->execute();
        $admins = $stmt->fetchAll();
        
        if (count($admins) > 0) {
            echo "<p>✅ Admin users found:</p>";
            echo "<ul>";
            foreach ($admins as $admin) {
                echo "<li>Username: <strong>" . htmlspecialchars($admin['username']) . "</strong>, Email: " . htmlspecialchars($admin['email']) . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>❌ No admin users found</p>";
        }
        
        // Test password verification
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE username = 'admin'");
        $stmt->execute();
        $user = $stmt->fetch();
        
        if ($user) {
            $testPassword = 'admin123';
            if (password_verify($testPassword, $user['password_hash'])) {
                echo "<p>✅ Password verification works for admin user</p>";
            } else {
                echo "<p>❌ Password verification failed for admin user</p>";
                echo "<p>Stored hash: " . substr($user['password_hash'], 0, 20) . "...</p>";
            }
        }
        
    } else {
        echo "<p>❌ Users table does not exist</p>";
        echo "<p><a href='/setup.php'>Run Setup Script</a></p>";
    }
    
    echo "<h2>Test Results</h2>";
    echo "<p>If all tests pass, the login should work with:</p>";
    echo "<p><strong>Username:</strong> admin<br><strong>Password:</strong> admin123</p>";
    echo "<p><a href='/auth/login.php'>Try Login</a></p>";
    
} catch (PDOException $e) {
    echo "<p>❌ Database Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check:</p>";
    echo "<ul>";
    echo "<li>MySQL is running</li>";
    echo "<li>Database credentials are correct</li>";
    echo "<li>Database 'azox_network' exists</li>";
    echo "</ul>";
    echo "<p><a href='/setup.php'>Run Setup Script</a></p>";
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
ul {
    margin: 10px 0;
    padding-left: 30px;
}
a {
    color: #DC143C;
    text-decoration: none;
    background: #DC143C;
    color: white;
    padding: 5px 10px;
    border-radius: 3px;
}
a:hover {
    background: #B91C3C;
}
</style>