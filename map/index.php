<?php
session_start();
require_once '../config/auth.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Server Map - Azox Network</title>
  <link rel="stylesheet" href="../style.css">
  <meta name="description" content="A complete map of every inch of the world explored on the azox network.">  
  
   <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">
  <link rel="apple-touch-icon" sizes="180x180" href="/favicon.svg">
  
  <style>
    .map-container {
      width: 100%;
      height: calc(100vh - 200px);
      min-height: 600px;
      background: #1a1a1a;
      border: 2px solid rgba(220, 38, 127, 0.3);
      border-radius: 8px;
      position: relative;
      overflow: hidden;
    }
    
    .map-frame {
      width: 100%;
      height: 100%;
      border: none;
      background: #000;
    }
    
    .map-overlay {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      text-align: center;
      color: rgba(255, 255, 255, 0.7);
      z-index: 10;
      pointer-events: none;
    }
    
    .map-overlay h2 {
      color: var(--crimson);
      margin-bottom: 1rem;
      font-size: 2rem;
    }
    
    .map-overlay p {
      font-size: 1.1rem;
      margin-bottom: 0.5rem;
    }
    
    .map-info {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 2rem;
      margin: 2rem 0;
    }
    
    .info-card {
      background: rgba(255, 255, 255, 0.05);
      padding: 1.5rem;
      border-radius: 8px;
      border: 1px solid rgba(220, 38, 127, 0.2);
    }
    
    .info-card h3 {
      color: var(--crimson);
      margin-bottom: 1rem;
    }
    
    .info-card ul {
      list-style: none;
      padding: 0;
    }
    
    .info-card li {
      padding: 0.25rem 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .info-card li:last-child {
      border-bottom: none;
    }
  </style>
</head>
<body>
  <?php require_once '../includes/nav.php'; ?>

  <!-- Map Section -->
  <main class="container">
    <div class="hero-section">
      <div class="eyebrow"><span class="dot"></span>Interactive World</div>
      <h1>Server Map</h1>
      <a href = "/maps/">Click Here for Fullscreen</a>
    </div>

    <div class="map-container">
      <iframe class="map-frame" src="/maps/" title="Server Map"></iframe>
    </div>

    <div style="background: rgba(255, 255, 255, 0.05); padding: 2rem; border-radius: 8px; margin: 2rem 0; text-align: center;">
      <h3 style="color: var(--crimson); margin-bottom: 1rem;">Map Integration Status</h3>
      <p>Map updates every 5 minutes, providing continuous live updates, loading everywhere players have been. Live data loaded from bluemap directly to apache2 for the best experience possible</p>
      <p><strong>Note:</strong> If showing black screen, contact support at support@azox.net </p>
    </div>
  </main>

  <!-- Footer -->
  <footer class="footer">
    <div class="footer-inner">
      <p>&copy; 2025 Azox Network</p>
    </div>
  </footer>

  <script>
    // Placeholder for future BlueMap integration
    document.addEventListener('DOMContentLoaded', function() {
      const mapFrame = document.querySelector('.map-frame');
      const overlay = document.querySelector('.map-overlay');
      
      // In production, this would load the actual BlueMap
      // For now, we show the placeholder overlay
      console.log('BlueMap integration ready for deployment');
      
      // Example of how the map would be loaded in production:
      // mapFrame.src = '/bluemap/';
      // overlay.style.display = 'none';
    });
  </script>
</body>
</html>
