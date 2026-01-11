<?php
session_start();
require_once '../config/auth.php';
require_once '../includes/content.php';

// Get play now guide from markdown files
$playNowArticles = getContentFiles('play-now', 'play-now');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Play Now | Azox Network</title>
  <link rel="stylesheet" href="../style.css">
  <meta name="description" content="A detailed guide on now to join the azox network and get started immediately">
  
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">
  <link rel="apple-touch-icon" sizes="180x180" href="/favicon.svg">
</head>
<body>
  <?php require_once '../includes/nav.php'; ?>

  <!-- Play Now Section -->
  <main class="container">
    <div class="hero-section">
      <div class="eyebrow"><span class="dot"></span>Join the Battle</div>
      <h1>How to Play</h1>
      <p>Step-by-step instructions to connect to the Azox Network and begin your Trial by Fate.</p>
    </div>

    <div class="news-grid">
      <?php if (empty($playNowArticles)): ?>
        <div class="loading">No connection guide found.</div>
      <?php else: ?>
        <?php foreach ($playNowArticles as $article): ?>
          <article class="news-article">
            <div class="news-meta">
              <span class="news-date">Latest Info</span>
              <span class="news-category">Connection Guide</span>
            </div>
            <div class="news-content">
              <?= parseContentMarkdown($article['content']) ?>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </main>

  <!-- Footer -->
  <footer class="footer">
    <div class="footer-inner">
      <p>&copy; 2025 Azox Network</p>
    </div>
  </footer>
</body>
</html>

