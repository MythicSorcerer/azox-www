<?php
require_once __DIR__ . '/../config/auth.php';

// Require login to create threads
requireLogin();

// Get available categories
$categories = fetchAll("
    SELECT id, name, description 
    FROM forum_categories 
    WHERE is_active = 1 
    ORDER BY sort_order ASC
");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = intval($_POST['category_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrfToken)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (!$categoryId) {
        $error = 'Please select a category.';
    } elseif (empty($title) || empty($content)) {
        $error = 'Title and content are required.';
    } elseif (strlen($title) > 255) {
        $error = 'Title is too long (max 255 characters).';
    } elseif (strlen($content) > 10000) {
        $error = 'Content is too long (max 10,000 characters).';
    } else {
        // Verify category exists
        $category = fetchRow("SELECT id FROM forum_categories WHERE id = ? AND is_active = 1", [$categoryId]);
        if (!$category) {
            $error = 'Invalid category selected.';
        } else {
            try {
                beginTransaction();
                
                // Create thread
                $threadId = insertAndGetId("
                    INSERT INTO forum_threads (category_id, title, author_id) 
                    VALUES (?, ?, ?)
                ", [$categoryId, $title, getCurrentUser()['id']]);
                
                // Create first post
                insertAndGetId("
                    INSERT INTO forum_posts (thread_id, author_id, content) 
                    VALUES (?, ?, ?)
                ", [$threadId, getCurrentUser()['id'], $content]);
                
                commitTransaction();
                
                logActivity("New thread created: '$title' by " . getCurrentUser()['username']);
                
                // Redirect to the new thread
                header("Location: /thread/?id=$threadId");
                exit;
                
            } catch (Exception $e) {
                rollbackTransaction();
                logActivity("Failed to create thread: " . $e->getMessage(), 'error');
                $error = 'Failed to create thread. Please try again.';
            }
        }
    }
}

$pageTitle = "Create New Thread — Forum — Azox";
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/nav.php'; ?>

    <main class="forum-container">
        <div class="forum-header">
            <div class="eyebrow">
                <span class="dot"></span>
                <a href="/forum/" style="color: var(--text-dim); text-decoration: none;">Forum</a>
                <span style="color: var(--text-dim); margin: 0 8px;">›</span>
                Create New Thread
            </div>
            <h1 class="forum-title">Create New Thread</h1>
            <p class="forum-subtitle">Start a new discussion in the Azox Network community.</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= sanitizeOutput($error) ?></div>
        <?php endif; ?>

        <div style="max-width: 800px; margin: 0 auto;">
            <div style="background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 16px; padding: 32px; box-shadow: var(--shadow-md); backdrop-filter: blur(8px);">
                <form method="POST" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" required style="padding: 12px 16px; border-radius: 10px; border: 1px solid rgba(255,255,255,.12); background: rgba(0,0,0,.3); color: var(--text); font-size: 16px; width: 100%;">
                            <option value="">Select a category...</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                    <?= sanitizeOutput($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small>Choose the most appropriate category for your discussion.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="title">Thread Title</label>
                        <input type="text" id="title" name="title" required maxlength="255" 
                               placeholder="Enter a descriptive title for your thread"
                               value="<?= sanitizeOutput($_POST['title'] ?? '') ?>">
                        <small>Be specific and descriptive to help others understand your topic.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Content</label>
                        <textarea id="content" name="content" required rows="12" maxlength="10000" 
                                  placeholder="Write your post content here..."><?= sanitizeOutput($_POST['content'] ?? '') ?></textarea>
                        <small>Provide detailed information about your topic. You can use basic formatting.</small>
                    </div>
                    
                    <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                        <a href="/forum/" class="btn ghost">Cancel</a>
                        <button type="submit" class="btn primary">Create Thread</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Forum Guidelines -->
        <div style="max-width: 800px; margin: 32px auto 0; padding: 24px; background: rgba(255,255,255,.02); border: 1px solid rgba(255,255,255,.06); border-radius: 12px;">
            <h3 style="margin: 0 0 16px; color: var(--text); font-size: 18px;">Forum Guidelines</h3>
            <ul style="margin: 0; padding-left: 20px; color: var(--text-dim); line-height: 1.6;">
                <li>Choose the most appropriate category for your thread</li>
                <li>Use clear, descriptive titles that explain your topic</li>
                <li>Search existing threads before creating a new one</li>
                <li>Be respectful and constructive in your discussions</li>
                <li>Follow the <a href="/rules/" style="color: var(--crimson); text-decoration: none;">server rules</a></li>
                <li>Avoid spam, off-topic posts, or duplicate threads</li>
            </ul>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-inner">
            <p>&copy; 2025 Azox Network</p>
        </div>
    </footer>

    <script>
    // Auto-resize textarea
    const textarea = document.getElementById('content');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 400) + 'px';
        });
    }

    // Category selection helper
    const categorySelect = document.getElementById('category_id');
    const categories = <?= json_encode($categories) ?>;
    
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            const selectedCategory = categories.find(cat => cat.id == this.value);
            if (selectedCategory && selectedCategory.description) {
                // You could show category description here if needed
                console.log('Selected category:', selectedCategory.name, '-', selectedCategory.description);
            }
        });
    }
    </script>
</body>
</html>