<?php
require_once __DIR__ . '/../config/auth.php';

// Logout the user
logoutUser();

// Redirect to home page
header("Location: /index.html");
exit;
?>