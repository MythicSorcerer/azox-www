<?php
require_once __DIR__ . '/../config/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $redirect = $_GET['redirect'] ?? '/index.php';
    header("Location: $redirect");
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_POST && isset($_POST['login'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            $result = loginUser($username, $password);
            if ($result['success']) {
                $redirect = $_GET['redirect'] ?? '/index.php';
                header("Location: $redirect");
                exit;
            } else {
                $error = $result['message'];
            }
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Login | Azox Network</title>
    <link rel="stylesheet" href="../style.css">
    <meta name="description" content="Login to the azox network">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon.svg">
</head>
<body>
    <!-- Top bar 
    <div class="topbar">
        <span>Season III:</span>
        <span class="pill">Always-on PvP</span>
        <span class="pill">Hard Mode</span>
        <span class="pill">No Safe Retreat</span>
    </div> -->

    <!-- Main nav -->
    <header class="nav">
        <div class="nav-inner">
            <div class="brand">
                <div class="crest" aria-hidden="true"></div>
                <div>AZOX</div>
            </div>
            <nav class="links" aria-label="Primary">
                <a href="../index.php">Home</a>
                <a href="../news/">News</a>
                <a href="../events/">Events</a>
                <a href="../map/">Map</a>
                <a href="#trials">FAQ</a>
                <a href="../contact/">Contact</a>
                <a href="../rules/">Rules</a>
                <a href="../tools/">Tools</a>
                <a href="../forum/">Forum</a>
            </nav>
            <button class="hamburger" id="hamburger" aria-label="Toggle navigation menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="auth-section">
                <a href="register.php" class="btn ghost">Register</a>
            </div>
        </div>
        <!-- Mobile menu -->
        <nav class="mobile-menu" id="mobileMenu" aria-label="Mobile navigation">
            <a href="../index.php">Home</a>
            <a href="../news/">News</a>
            <a href="../events/">Events</a>
            <a href="../map/">Map</a>
            <a href="#trials">FAQ</a>
            <a href="../contact/">Contact</a>
            <a href="../rules/">Rules</a>
            <a href="../tools/">Tools</a>
            <a href="../forum/">Forum</a>
            <a href="register.php">Register</a>
        </nav>
    </header>

    <!-- Login Form -->
    <main class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="eyebrow"><span class="dot"></span>Authentication</div>
                <h1 class="auth-title">Login to Azox</h1>
                <p class="auth-subtitle">Access your account to join the community discussions and chat.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= sanitizeOutput($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= sanitizeOutput($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        value="<?= sanitizeOutput($_POST['username'] ?? '') ?>"
                        required 
                        autocomplete="username"
                        placeholder="Enter your username or email"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        autocomplete="current-password"
                        placeholder="Enter your password"
                    >
                </div>

                <button type="submit" name="login" class="btn primary full-width">
                    Login to Azox
                </button>
            </form>

            <div class="auth-links">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <p><a href="#" onclick="alert('Contact support@azox.net for password reset')">Forgot your password?</a></p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-inner">
            <p>&copy; 2025 Azox Network</p>
        </div>
    </footer>

    <script>
        // Hamburger menu
        (function(){
            const hamburger = document.getElementById('hamburger');
            const mobileMenu = document.getElementById('mobileMenu');
            if(!hamburger || !mobileMenu) return;
            
            hamburger.addEventListener('click', () => {
                hamburger.classList.toggle('active');
                mobileMenu.classList.toggle('active');
            });

            // Close menu when clicking on a link
            mobileMenu.addEventListener('click', (e) => {
                if(e.target.tagName === 'A') {
                    hamburger.classList.remove('active');
                    mobileMenu.classList.remove('active');
                }
            });

            // Close menu when clicking outside
            document.addEventListener('click', (e) => {
                if(!hamburger.contains(e.target) && !mobileMenu.contains(e.target)) {
                    hamburger.classList.remove('active');
                    mobileMenu.classList.remove('active');
                }
            });
        })();
    </script>
</body>
</html>