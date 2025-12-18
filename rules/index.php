<?php
session_start();
require_once '../config/auth.php';
require_once '../includes/content.php';

// Get rules from markdown files
$rulesArticles = getContentFiles('rules', 'rules');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Rules | Azox Network</title>
  <meta name="description" content="Stay up to date with essential rules and guidelines that ensure fair play and standards on the azox network.">
  <link rel="stylesheet" href="../style.css">
  
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">
  <link rel="apple-touch-icon" sizes="180x180" href="/favicon.svg">
</head>
<body>
  <?php require_once '../includes/nav.php'; ?>

  <!-- Rules Section -->
  <main class="container">
    <div class="hero-section">
      <div class="eyebrow"><span class="dot"></span>Server Guidelines</div>
      <h1>Server Rules</h1>
      <p>Essential rules and guidelines for fair play and community standards on the Azox Network.</p>
    </div>

    <div class="news-grid">
      <?php if (empty($rulesArticles)): ?>
        <div class="loading">No rules found.</div>
      <?php else: ?>
        <?php foreach ($rulesArticles as $article): ?>
          <article class="news-article">
            <div class="news-meta">
              <span class="news-date">Latest Info</span>
              <span class="news-category">Server Guidelines</span>
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

