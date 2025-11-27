<?php
// Test script to debug admin actions
session_start();

// Simulate admin login with all required session data
$_SESSION['user_id'] = 1; // Admin user ID
$_SESSION['username'] = 'admin';
$_SESSION['email'] = 'admin@azox.net';
$_SESSION['role'] = 'admin';
$_SESSION['logged_in'] = true;
$_SESSION['session_token'] = 'test_token_123'; // Fake token

echo "Session before including admin actions:\n";
print_r($_SESSION);

// Simulate POST request
$_POST['action'] = 'delete_user';
$_POST['id'] = '2'; // Test user ID
$_SERVER['REQUEST_METHOD'] = 'POST';

// Include the admin actions file
include 'admin/actions.php';

echo "\nSession after including admin actions:\n";
print_r($_SESSION);
?>