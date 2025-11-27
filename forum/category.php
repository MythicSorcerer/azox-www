<?php
require_once __DIR__ . '/../config/auth.php';

// Get category ID from URL
$categoryId = intval($_GET['id'] ?? 0);

if (!$categoryId) {
    header('Location: /forum/');
    exit;
}

// Get category information
$category = fetchRow("
    SELECT * FROM forum_categories 
    WHERE id = ? AND is_active = 1
", [$categoryId]);

if (!$category) {
    header('Location: /forum/');
    exit;
}

// Handle new thread creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrfToken)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (empty($title) || empty($content)) {
        $error = 'Title and content are required.';
    } elseif (strlen($title) > 255) {
        $error = 'Title is too long (max 255 characters).';
    } elseif (strlen($content) > 10000) {
        $error = 'Content is too long (max 10,000 characters).';
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

// Get threads in this category (only threads with visible posts)
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$threads = fetchAll("
    SELECT DISTINCT
        ft.*,
        u.username as author_name,
        u.role as author_role,
        lp.created_at as last_post_time,
        lpu.username as last_post_author
    FROM forum_threads ft
    JOIN users u ON ft.author_id = u.id
    JOIN forum_posts fp ON ft.id = fp.thread_id AND fp.is_deleted = 0
    LEFT JOIN forum_posts lp ON ft.last_post_id = lp.id
    LEFT JOIN users lpu ON lp.author_id = lpu.id
    WHERE ft.category_id = ? AND ft.is_deleted = 0
    ORDER BY ft.is_pinned DESC, ft.updated_at DESC
    LIMIT ? OFFSET ?
", [$categoryId, $perPage, $offset]);

// Get total thread count for pagination
$totalThreads = fetchCount("
    SELECT COUNT(DISTINCT ft.id)
    FROM forum_threads ft
    JOIN forum_posts fp ON ft.id = fp.thread_id AND fp.is_deleted = 0
    WHERE ft.category_id = ? AND ft.is_deleted = 0
", [$categoryId]);

$totalPages = ceil($totalThreads / $perPage);

$pageTitle = sanitizeOutput($category['name']) . " — Forum — Azox";
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
                <?= sanitizeOutput($category['name']) ?>
            </div>
            <h1 class="forum-title"><?= sanitizeOutput($category['name']) ?></h1>
            <p class="forum-subtitle"><?= sanitizeOutput($category['description']) ?></p>
            
            <?php if (isLoggedIn()): ?>
                <div style="margin-top: 24px;">
                    <button onclick="toggleNewThreadForm()" class="btn primary">Start New Thread</button>
                </div>
            <?php endif; ?>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= sanitizeOutput($error) ?></div>
        <?php endif; ?>

        <?php if (!isLoggedIn()): ?>
            <div class="alert alert-info" style="background: rgba(33,150,243,.1); border: 1px solid rgba(33,150,243,.3); color: #64b5f6;">
                <strong>Join the Discussion!</strong> 
                <a href="/auth/register.php" style="color: #42a5f5; text-decoration: underline;">Register</a> or 
                <a href="/auth/login.php" style="color: #42a5f5; text-decoration: underline;">login</a> to create new threads and participate in discussions.
            </div>
        <?php endif; ?>

        <!-- New Thread Form -->
        <?php if (isLoggedIn()): ?>
            <div id="newThreadForm" style="display: none; margin-bottom: 32px;">
                <div style="background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 12px; padding: 24px;">
                    <h3 style="margin: 0 0 20px; color: var(--text);">Create New Thread</h3>
                    <form method="POST" class="auth-form">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="form-group">
                            <label for="title">Thread Title</label>
                            <input type="text" id="title" name="title" required maxlength="255" 
                                   value="<?= sanitizeOutput($_POST['title'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="content">Content</label>
                            <textarea id="content" name="content" required rows="8" maxlength="10000"><?= sanitizeOutput($_POST['content'] ?? '') ?></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 12px;">
                            <button type="submit" class="btn primary">Create Thread</button>
                            <button type="button" onclick="toggleNewThreadForm()" class="btn ghost">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Threads List -->
        <?php if (empty($threads)): ?>
            <div style="text-align: center; padding: 48px; color: var(--text-dim);">
                <h3>No threads yet</h3>
                <p>Be the first to start a discussion in this category!</p>
            </div>
        <?php else: ?>
            <div class="forum-threads">
                <?php foreach ($threads as $thread): ?>
                    <a href="/thread/?id=<?= $thread['id'] ?>" class="forum-thread">
                        <div class="thread-info">
                            <h4>
                                <?php if ($thread['is_pinned']): ?>
                                    <span class="thread-badge badge-pinned">Pinned</span>
                                <?php endif; ?>
                                <?php if ($thread['is_locked']): ?>
                                    <span class="thread-badge badge-locked">Locked</span>
                                <?php endif; ?>
                                <?= sanitizeOutput($thread['title']) ?>
                            </h4>
                            <div class="thread-meta">
                                <span>by <?= sanitizeOutput($thread['author_name']) ?></span>
                                <?php if ($thread['author_role'] === 'admin'): ?>
                                    <span style="color: var(--crimson); font-weight: 600;">[Admin]</span>
                                <?php endif; ?>
                                <span>•</span>
                                <span><?= date('M j, Y', strtotime($thread['created_at'])) ?></span>
                                <?php if ($thread['last_post_time'] && $thread['last_post_author']): ?>
                                    <span>•</span>
                                    <span>Last: <?= sanitizeOutput($thread['last_post_author']) ?> on <?= date('M j', strtotime($thread['last_post_time'])) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="thread-stats">
                            <strong><?= number_format($thread['reply_count']) ?></strong>
                            <div>Replies</div>
                            <strong><?= number_format($thread['view_count']) ?></strong>
                            <div>Views</div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div style="display: flex; justify-content: center; gap: 8px; margin-top: 32px;">
                    <?php if ($page > 1): ?>
                        <a href="?id=<?= $categoryId ?>&page=<?= $page - 1 ?>" class="btn ghost">← Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="btn primary"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?id=<?= $categoryId ?>&page=<?= $i ?>" class="btn ghost"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?id=<?= $categoryId ?>&page=<?= $page + 1 ?>" class="btn ghost">Next →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <div class="footer-inner">
            <p>&copy; 2025 Azox Network</p>
        </div>
    </footer>

    <script>
    function toggleNewThreadForm() {
        const form = document.getElementById('newThreadForm');
        if (form.style.display === 'none') {
            form.style.display = 'block';
            document.getElementById('title').focus();
        } else {
            form.style.display = 'none';
        }
    }
    </script>
</body>
</html>