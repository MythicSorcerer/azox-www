<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Azox Network</title>
  <link rel="stylesheet" href="style.css">
  <meta name="description" content="Where the greatest and mightiest minecraft players grind and thrive. Now with the spear.">
  
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">
  <link rel="apple-touch-icon" sizes="180x180" href="/favicon.svg">
</head>
<body>
  <?php include __DIR__ . '/includes/nav.php'; ?>

  <!-- Hero -->
  <section class="hero">
    <div class="hero-inner">
      <div class="hero-content">
       <div class="eyebrow"><span class="dot"></span>Reckoning's edge</div>
          <h1 class="hero-title">Trial by fate</h1>
          <p class="hero-sub">Engage in escalating duels and survival gauntlets where every choice counts—and retreat is not an option.</p>
          <div class="hero-actions">
          <a class="btn primary" href="play-now/index.php">Join the Server</a>
          <a class="btn ghost" href="#news">News</a>
        </div>
      </div>

      <aside class="ip-card" aria-label="Server Connection">
        <div class="title">Server IP</div>
        <div class="ip-input">
          <input id="ip" value="azox.net" aria-label="Server IP" readonly />
          <button id="copy" class="copy" aria-label="Copy IP">Copy</button>
        </div>
        <div class="ip-note">Select multiplayer, then add a server with this IP. Alternativly you can use a direct connection with this same IP.</div>
      </aside>

      <div class="blurb-row">
        <div class="blurb"><b>Survival of the Fittest:</b> Grind resources and gear, granting you the ability to attack and obliterate anyone with ease.</div>
        <div class="blurb"><b>Alliance & Intrigue:</b> Forge unbreakable alliances, execute deadly betrayals, and manipulate the economy for power.</div>
        <div class="blurb"><b>Advanced Anti-cheat:</b> The most advanced of anti-cheat technology, used here to eradicate hacking and ensure fair gameplay.</div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <div class="footer-inner">
      <p>&copy; 2025 Azox Network</p>
    </div>
  </footer>

  <script>
    // Copy IP
    (function(){
      const ip = document.getElementById('ip');
      const btn = document.getElementById('copy');
      if(!ip || !btn) return;
      btn.addEventListener('click', async () => {
        try{
          await navigator.clipboard.writeText(ip.value);
          const old = btn.textContent;
          btn.textContent = 'Copied!';
          setTimeout(()=> btn.textContent = old, 1200);
        }catch(e){
          btn.textContent = 'Copy failed';
          setTimeout(()=> btn.textContent = 'Copy', 1200);
        }
      });
    })();

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
