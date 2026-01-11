<?php
session_start();
require_once '../config/auth.php';
require_once '../includes/content.php';

// Get contact info from markdown files
$contactArticles = getContentFiles('contact', 'contact');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Contact Info | Azox Network</title>
  <link rel="stylesheet" href="../style.css">
  <meta name="description" content="Contact a real person and get a rapid response using the contact info here.">

  <!-- Favicon -->
  <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">
  <link rel="apple-touch-icon" sizes="180x180" href="/favicon.svg">
</head>
<body>
  <?php require_once '../includes/nav.php'; ?>

  <!-- Contact Section -->
  <main class="container">
    <div class="hero-section">
      <div class="eyebrow"><span class="dot"></span>Get in Touch</div>
      <h1>Contact Us</h1>
      <p>Need help or want to connect with the community? Reach out through Discord or email for support and assistance.</p>
    </div>

    <div class="news-grid">
      <?php if (empty($contactArticles)): ?>
        <div class="loading">No contact information found.</div>
      <?php else: ?>
        <?php foreach ($contactArticles as $article): ?>
          <article class="news-article">
            <div class="news-meta">
              <span class="news-date">Latest Info</span>
              <span class="news-category">Contact</span>
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

