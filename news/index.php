<?php
session_start();
require_once '../config/auth.php';
require_once '../includes/content.php';

// Get news articles from markdown files
$newsArticles = getContentFiles('news', 'news');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>News — Azox Network</title>
  <link rel="stylesheet" href="../style.css">
  
  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">
  <link rel="apple-touch-icon" sizes="180x180" href="/favicon.svg">
</head>
<body>
  <?php require_once '../includes/nav.php'; ?>

  <!-- News Section -->
  <main class="container">
    <div class="hero-section">
      <div class="eyebrow"><span class="dot"></span>Latest Updates</div>
      <h1>Server News</h1>
      <p>Stay updated with the latest events, tournaments, and community announcements from the Azox Network.</p>
    </div>

    <div class="news-grid">
      <?php if (empty($newsArticles)): ?>
        <div class="loading">No news articles found.</div>
      <?php else: ?>
        <?php foreach ($newsArticles as $article): ?>
          <article class="news-article">
            <div class="news-meta">
              <span class="news-date"><?= sanitizeOutput($article['date']) ?></span>
              <span class="news-category">News</span>
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
