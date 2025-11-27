<?php
session_start();
require_once '../config/auth.php';
require_once '../includes/markdown.php';

// Read and parse the FAQ markdown file
$faqMarkdown = file_get_contents(__DIR__ . '/faq.md');
$faqContent = parseMarkdown($faqMarkdown);

// Extract title and subtitle from the markdown
$lines = explode("\n", $faqMarkdown);
$title = 'Frequently Asked Questions';
$subtitle = 'Find answers to common questions about Azox Network';

foreach ($lines as $line) {
    $line = trim($line);
    if (preg_match('/^# (.+)$/', $line, $matches)) {
        $title = $matches[1];
    } elseif (!empty($line) && !preg_match('/^#/', $line) && empty($subtitle_found)) {
        $subtitle = $line;
        $subtitle_found = true;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - Azox Network</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <?php require_once '../includes/nav.php'; ?>
    
    <main class="container">
        <div class="hero-section">
            <h1><?= htmlspecialchars($title) ?></h1>
            <p><?= htmlspecialchars($subtitle) ?></p>
        </div>

        <div class="faq-container">
            <?= $faqContent ?>
        </div>
    </main>

    <script>
        function toggleFAQ(button) {
            const faqItem = button.parentElement;
            const answer = faqItem.querySelector('.faq-answer');
            const icon = button.querySelector('.faq-icon');
            
            // Toggle the active class
            faqItem.classList.toggle('active');
            
            // Change icon
            if (faqItem.classList.contains('active')) {
                icon.textContent = '−';
                answer.style.maxHeight = answer.scrollHeight + 'px';
            } else {
                icon.textContent = '+';
                answer.style.maxHeight = '0';
            }
        }

        // Close all FAQ items by default
        document.addEventListener('DOMContentLoaded', function() {
            const answers = document.querySelectorAll('.faq-answer');
            answers.forEach(answer => {
                answer.style.maxHeight = '0';
            });
        });
    </script>
</body>
</html>