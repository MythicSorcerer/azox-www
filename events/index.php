<?php
session_start();
require_once '../config/auth.php';
require_once '../includes/content.php';

// Get events from markdown files
$eventsData = getContentFiles('events', 'events');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Events | Azox Network</title>
  <link rel="stylesheet" href="../style.css">
  <meta name="description" content="Stay tuned for the latest events on the azox network. Tournaments, changes, new seasons, even special gamerules right here.">
</head>
<body>
  <?php require_once '../includes/nav.php'; ?>

  <!-- Events Section -->
  <main class="container">
    <div class="hero-section">
      <div class="eyebrow"><span class="dot"></span>Upcoming Events</div>
      <h1>Server Events</h1>
      <p>Join exciting tournaments, competitions, and special events happening on the Azox Network.</p>
    </div>

    <div class="news-grid">
      <?php if (empty($eventsData)): ?>
        <div class="loading">No events found.</div>
      <?php else: ?>
        <?php foreach ($eventsData as $event): ?>
          <article class="news-article">
            <div class="news-meta">
              <span class="news-date"><?= sanitizeOutput($event['date']) ?></span>
              <span class="news-category">Events</span>
            </div>
            <div class="news-content">
              <?= parseContentMarkdown($event['content']) ?>
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