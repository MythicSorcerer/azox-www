<?php
require_once __DIR__ . '/../config/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: /index.html");
    exit;
}

$error = '';
$success = '';

// Handle registration form submission
if ($_POST && isset($_POST['register'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
            $error = 'Please fill in all fields.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            $result = registerUser($username, $email, $password);
            if ($result['success']) {
                $success = 'Registration successful! You can now login with your credentials.';
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
    <title>Register — Azox — Trial by Fate</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <!-- Top bar -->
    <div class="topbar">
        <span>Season III:</span>
        <span class="pill">Always-on PvP</span>
        <span class="pill">Hard Mode</span>
        <span class="pill">No Safe Retreat</span>
    </div>

    <!-- Main nav -->
    <header class="nav">
        <div class="nav-inner">
            <div class="brand">
                <div class="crest" aria-hidden="true"></div>
                <div>AZOX</div>
            </div>
            <nav class="links" aria-label="Primary">
                <a href="../index.html">Home</a>
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
                <a href="login.php" class="btn ghost">Login</a>
            </div>
        </div>
        <!-- Mobile menu -->
        <nav class="mobile-menu" id="mobileMenu" aria-label="Mobile navigation">
            <a href="../index.html">Home</a>
            <a href="../news/">News</a>
            <a href="../events/">Events</a>
            <a href="../map/">Map</a>
            <a href="#trials">FAQ</a>
            <a href="../contact/">Contact</a>
            <a href="../rules/">Rules</a>
            <a href="../tools/">Tools</a>
            <a href="../forum/">Forum</a>
            <a href="login.php">Login</a>
        </nav>
    </header>

    <!-- Registration Form -->
    <main class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="eyebrow"><span class="dot"></span>Join the Community</div>
                <h1 class="auth-title">Create Account</h1>
                <p class="auth-subtitle">Join the Azox Network community and participate in forums and chat.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= sanitizeOutput($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= sanitizeOutput($success) ?>
                    <p><a href="login.php">Click here to login</a></p>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        value="<?= sanitizeOutput($_POST['username'] ?? '') ?>"
                        required 
                        autocomplete="username"
                        placeholder="Choose a unique username"
                        pattern="[a-zA-Z0-9_]+"
                        title="Username can only contain letters, numbers, and underscores"
                    >
                    <small>3-50 characters, letters, numbers, and underscores only</small>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?= sanitizeOutput($_POST['email'] ?? '') ?>"
                        required 
                        autocomplete="email"
                        placeholder="Enter your email address"
                    >
                    <small>We'll never share your email with anyone</small>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        autocomplete="new-password"
                        placeholder="Create a secure password"
                        minlength="6"
                    >
                    <small>At least 6 characters</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required 
                        autocomplete="new-password"
                        placeholder="Confirm your password"
                        minlength="6"
                    >
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" required>
                        <span class="checkmark"></span>
                        I agree to the <a href="../rules/" target="_blank">Terms of Service</a> and <a href="../rules/" target="_blank">Community Rules</a>
                    </label>
                </div>

                <button type="submit" name="register" class="btn primary full-width">
                    Create Account
                </button>
            </form>

            <div class="auth-links">
                <p>Already have an account? <a href="login.php">Login here</a></p>
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
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

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