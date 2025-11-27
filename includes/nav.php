<?php
// Include authentication system
if (!function_exists('getCurrentUser')) {
    require_once __DIR__ . '/../config/auth.php';
}

// Get current user and notification count
$currentUser = getCurrentUser();
$notificationCount = $currentUser ? getUnreadNotificationCount($currentUser['id']) : 0;

// Determine current page for active state
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

// Debug: Add HTML comment to see what's being detected
// echo "<!-- Debug: currentPage='$currentPage', currentDir='$currentDir', SCRIPT_NAME='{$_SERVER['SCRIPT_NAME']}' -->";

if (!function_exists('isActivePage')) {
    function isActivePage($page, $dir = '') {
        global $currentPage, $currentDir;
        
        // Handle directory-based pages
        if ($dir) {
            return $currentDir === $dir;
        }
        
        // Handle homepage specifically - only active if we're in root directory
        if ($page === 'index') {
            $scriptName = $_SERVER['SCRIPT_NAME'];
            return $scriptName === '/index.php' || ($currentPage === 'index' && $currentDir === '');
        }
        
        return $currentPage === $page;
    }
}

if (!function_exists('getActiveClass')) {
    function getActiveClass($page, $dir = '') {
        return isActivePage($page, $dir) ? 'style="color: var(--crimson);"' : '';
    }
}
?>

<!-- Top bar -->
<div class="topbar">
    <div style="display: flex; align-items: center; justify-content: flex-end; width: 100%;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <?php if ($currentUser): ?>
                <!-- Logged in user info -->
                <?php if (isAdmin()): ?>
                    <a href="/admin/dashboard.php" style="color: var(--crimson); font-weight: 600; text-decoration: none;">Admin Dashboard</a>
                    <span style="color: var(--text-dim);">|</span>
                <?php endif; ?>
                
                <span style="color: var(--text); font-weight: 600;">
                    <?= sanitizeOutput($currentUser['username']) ?>
                </span>
                
                <a href="/notifications/" style="color: var(--text); text-decoration: none; position: relative;" title="Notifications">
                    🔔
                    <?php if ($notificationCount > 0): ?>
                        <span style="position: absolute; top: -4px; right: -4px; background: var(--crimson); color: white; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 10px; min-width: 16px; text-align: center; line-height: 1.2;"><?= $notificationCount ?></span>
                    <?php endif; ?>
                </a>
                
                <span style="color: var(--text-dim);">|</span>
                <a href="/settings/" style="color: var(--text-dim); text-decoration: none;">Settings</a>
                <span style="color: var(--text-dim);">|</span>
                <a href="/auth/logout.php" style="color: var(--text-dim); text-decoration: none;">Log out</a>
            <?php else: ?>
                <!-- Not logged in -->
                <a href="/auth/login.php" style="background: rgba(0,0,0,.65); color: var(--text); padding: 4px 10px; margin: 2px 0; border-radius: 999px; border: 1px solid rgba(255,255,255,.08); backdrop-filter: blur(6px); text-decoration: none; font-size: 12px; letter-spacing: .08em; text-transform: uppercase;">Enter</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Main nav -->
<header class="nav">
    <div class="nav-inner">
        <div class="brand">
            <div class="crest" aria-hidden="true"></div>
            <div>AZOX</div>
        </div>
        <nav class="links" aria-label="Primary">
            <a href="/index.php" <?= getActiveClass('index') ?>>Home</a>
            <a href="/news/" <?= getActiveClass('', 'news') ?>>News</a>
            <a href="/events/" <?= getActiveClass('', 'events') ?>>Events</a>
            <a href="/map/" <?= getActiveClass('', 'map') ?>>Map</a>
            <a href="/faq/" <?= getActiveClass('', 'faq') ?>>FAQ</a>
            <a href="/contact/" <?= getActiveClass('', 'contact') ?>>Contact</a>
            <a href="/rules/" <?= getActiveClass('', 'rules') ?>>Rules</a>
            <a href="/tools/" <?= getActiveClass('', 'tools') ?>>Tools</a>
            <a href="/forum/" <?= getActiveClass('', 'forum') ?>>Forum</a>
            <?php if ($currentUser): ?>
                <a href="/messages/" <?= getActiveClass('', 'messages') ?>>Chat</a>
            <?php endif; ?>
        </nav>
        <button class="hamburger" id="hamburger" aria-label="Toggle navigation menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <a href="/play-now/index.php" class="cta">Play Now</a>
    </div>
    
    <!-- Mobile menu -->
    <nav class="mobile-menu" id="mobileMenu" aria-label="Mobile navigation">
        <a href="/index.php" <?= getActiveClass('index') ?>>Home</a>
        <a href="/news/" <?= getActiveClass('', 'news') ?>>News</a>
        <a href="/events/" <?= getActiveClass('', 'events') ?>>Events</a>
        <a href="/map/" <?= getActiveClass('', 'map') ?>>Map</a>
        <a href="/faq/" <?= getActiveClass('', 'faq') ?>>FAQ</a>
        <a href="/contact/" <?= getActiveClass('', 'contact') ?>>Contact</a>
        <a href="/rules/" <?= getActiveClass('', 'rules') ?>>Rules</a>
        <a href="/tools/" <?= getActiveClass('', 'tools') ?>>Tools</a>
        <a href="/forum/" <?= getActiveClass('', 'forum') ?>>Forum</a>
        
        <?php if ($currentUser): ?>
            <a href="/messages/" <?= getActiveClass('', 'messages') ?>>Chat</a>
        <?php endif; ?>
    </nav>
</header>

<script>
// Hamburger menu functionality
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