<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Tools — Azox — Trial by Fate</title>
  <link rel="stylesheet" href="../style.css">
</head>
<body>
  <?php include __DIR__ . '/../includes/nav.php'; ?>

  <!-- Tools Section -->
  <main class="news-container">
    <div class="news-header">
      <div class="eyebrow"><span class="dot"></span>Utilities</div>
      <h1 class="news-title">Server Tools</h1>
      <p class="news-subtitle">Helpful tools and utilities for Azox Network players and general productivity.</p>
    </div>

    <div class="news-grid">
      <!-- Claim Items Tool -->
      <article class="news-article">
        <div class="news-meta">
          <span class="news-date">Server Tool</span>
          <span class="news-category">Game Utility</span>
        </div>
        <h2><a href="claim/" style="color: inherit; text-decoration: none;">Claim Items</a></h2>
        <div class="news-content">
          <p>Claim your in-game items and rewards through this web interface. Perfect for retrieving items when you're away from the game or need to manage your inventory remotely.</p>
          <p><strong>Features:</strong></p>
          <ul>
            <li>View available items to claim</li>
            <li>Claim rewards from events and achievements</li>
            <li>Manage item delivery to your in-game inventory</li>
            <li>Track claim history and status</li>
          </ul>
          <p><em>Note: Full PHP implementation coming soon. Currently in development.</em></p>
          <p><a href="claim/" class="btn primary" style="display: inline-block; margin-top: 1rem;">Access Claim Tool →</a></p>
        </div>
      </article>

      <!-- Pomodoro Timer Tool -->
      <article class="news-article">
        <div class="news-meta">
          <span class="news-date">Productivity Tool</span>
          <span class="news-category">Focus Utility</span>
        </div>
        <h2><a href="timer/" style="color: inherit; text-decoration: none;">Pomodoro Focus Timer</a></h2>
        <div class="news-content">
          <p>Boost your productivity with this Pomodoro technique timer. Perfect for studying, working, or any task that requires focused attention.</p>
          <p><strong>Features:</strong></p>
          <ul>
            <li>25-minute focus sessions with 5-minute breaks</li>
            <li>Customizable timer intervals</li>
            <li>Audio notifications for session changes</li>
            <li>Session tracking and statistics</li>
            <li>Clean, distraction-free interface</li>
          </ul>
          <p>Use this timer while grinding, studying, or working on projects to maintain peak focus and productivity.</p>
          <p><a href="timer/" class="btn primary" style="display: inline-block; margin-top: 1rem;">Start Focus Timer →</a></p>
        </div>
      </article>

      <!-- Coming Soon -->
      <article class="news-article">
        <div class="news-meta">
          <span class="news-date">Coming Soon</span>
          <span class="news-category">Future Tools</span>
        </div>
        <h2>More Tools Coming</h2>
        <div class="news-content">
          <p>We're constantly working on new tools and utilities to enhance your Azox Network experience. Stay tuned for:</p>
          <ul>
            <li>Player statistics dashboard</li>
            <li>Faction management tools</li>
            <li>Market price tracker</li>
            <li>Build planning utilities</li>
            <li>Event calendar integration</li>
          </ul>
          <p>Have suggestions for new tools? Let us know through our <a href="../contact/">contact page</a> or Discord server!</p>
        </div>
      </article>
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