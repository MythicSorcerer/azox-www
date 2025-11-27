<?php
require_once __DIR__ . '/../config/auth.php';

// Get category ID from URL
$categoryId = (int)($_GET['id'] ?? 0);

if (!$categoryId) {
    header("Location: /forum/");
    exit;
}

// Get category details
$category = fetchRow("
    SELECT * FROM forum_categories 
    WHERE id = ? AND is_active = 1
", [$categoryId]);

if (!$category) {
    header("HTTP/1.1 404 Not Found");
    die("Category not found");
}

// Handle new thread creation
$error = '';
$success = '';

if ($_POST && isset($_POST['create_thread']) && isLoggedIn()) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } elseif (isBanned()) {
        $error = 'You are banned from creating threads and posts.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        
        if (empty($title) || empty($content)) {
            $error = 'Please fill in both title and content.';
        } elseif (strlen($title) > 255) {
            $error = 'Title is too long (maximum 255 characters).';
        } elseif (strlen($content) > 10000) {
            $error = 'Content is too long (maximum 10,000 characters).';
        } else {
            try {
                beginTransaction();
                
                // Create thread
                $threadId = insertAndGetId(
                    "INSERT INTO forum_threads (category_id, title, author_id) VALUES (?, ?, ?)",
                    [$categoryId, $title, getCurrentUser()['id']]
                );
                
                // Create first post
                insertAndGetId(
                    "INSERT INTO forum_posts (thread_id, author_id, content) VALUES (?, ?, ?)",
                    [$threadId, getCurrentUser()['id'], $content]
                );
                
                commitTransaction();
                
                // Redirect to new thread
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

// Get threads with pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$threadsPerPage = 20;
$offset = ($page - 1) * $threadsPerPage;

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
    WHERE ft.category_id = ?
    ORDER BY ft.is_pinned DESC, ft.updated_at DESC
    LIMIT ? OFFSET ?
", [$categoryId, $threadsPerPage, $offset]);

// Get total thread count (only threads with visible posts)
$totalThreads = fetchCount("
    SELECT COUNT(DISTINCT ft.id)
    FROM forum_threads ft
    JOIN forum_posts fp ON ft.id = fp.thread_id AND fp.is_deleted = 0
    WHERE ft.category_id = ?
", [$categoryId]);
$totalPages = ceil($totalThreads / $threadsPerPage);

$csrfToken = generateCSRFToken();
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

    <!-- Category Section -->
    <main class="forum-container">
        <!-- Breadcrumb -->
        <div style="margin-bottom: 24px;">
            <nav style="font-size: 14px; color: var(--text-dim);">
                <a href="/forum/" style="color: var(--text-dim);">Forum</a>
                <span style="margin: 0 8px;">›</span>
                <span style="color: var(--text);"><?= sanitizeOutput($category['name']) ?></span>
            </nav>
        </div>

        <!-- Category Header -->
        <div class="forum-header">
            <div class="eyebrow"><span class="dot"></span>Discussion Category</div>
            <h1 class="forum-title"><?= sanitizeOutput($category['name']) ?></h1>
            <p class="forum-subtitle"><?= sanitizeOutput($category['description']) ?></p>
        </div>

        <!-- Create Thread Button -->
        <?php if (isLoggedIn() && !isBanned()): ?>
            <div style="margin-bottom: 32px; text-align: center;">
                <button onclick="toggleNewThreadForm()" class="btn primary" id="newThreadBtn">
                    Start New Thread
                </button>
            </div>

            <!-- New Thread Form -->
            <div id="newThreadForm" style="display: none; margin-bottom: 32px; padding: 24px; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 12px;">
                <h3 style="margin: 0 0 20px; color: var(--text);">Create New Thread</h3>
                
                <?php if ($error): ?>
                    <div class="alert alert-error" style="margin-bottom: 20px;">
                        <?= sanitizeOutput($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    
                    <div class="form-group">
                        <label for="title">Thread Title</label>
                        <input
                            type="text"
                            id="title"
                            name="title"
                            value="<?= sanitizeOutput($_POST['title'] ?? '') ?>"
                            required
                            maxlength="255"
                            placeholder="Enter a descriptive title for your thread"
                        >
                        <small>Maximum 255 characters</small>
                    </div>

                    <div class="form-group">
                        <label for="content">Initial Post</label>
                        <textarea
                            id="content"
                            name="content"
                            rows="8"
                            required
                            maxlength="10000"
                            placeholder="Write your initial post content here..."
                            style="resize: vertical; min-height: 120px;"
                        ><?= sanitizeOutput($_POST['content'] ?? '') ?></textarea>
                        <small>Maximum 10,000 characters</small>
                    </div>

                    <div style="display: flex; gap: 12px;">
                        <button type="submit" name="create_thread" class="btn primary">
                            Create Thread
                        </button>
                        <button type="button" onclick="toggleNewThreadForm()" class="btn ghost">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        <?php elseif (isLoggedIn() && isBanned()): ?>
            <div style="text-align: center; margin-bottom: 32px; padding: 20px; background: rgba(220,20,60,.1); border: 1px solid rgba(220,20,60,.3); border-radius: 12px;">
                <p style="margin: 0; color: var(--crimson);">
                    🚫 You are banned from creating threads and posts.
                </p>
            </div>
        <?php else: ?>
            <div style="text-align: center; margin-bottom: 32px; padding: 20px; background: rgba(33,150,243,.1); border: 1px solid rgba(33,150,243,.3); border-radius: 12px;">
                <p style="margin: 0; color: #64b5f6;">
                    <a href="/auth/login.php" style="color: #42a5f5; text-decoration: underline;">Login</a> or
                    <a href="/auth/register.php" style="color: #42a5f5; text-decoration: underline;">register</a> to create new threads.
                </p>
            </div>
        <?php endif; ?>

        <!-- Threads List -->
        <?php if (empty($threads)): ?>
            <div style="text-align: center; padding: 64px 20px; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 12px;">
                <div style="font-size: 48px; margin-bottom: 16px;">💬</div>
                <h3 style="margin: 0 0 12px; color: var(--text);">No Threads Yet</h3>
                <p style="margin: 0; color: var(--text-dim);">
                    Be the first to start a discussion in this category!
                </p>
                <?php if (isLoggedIn()): ?>
                    <div style="margin-top: 24px;">
                        <button onclick="toggleNewThreadForm()" class="btn primary">Start First Thread</button>
                    </div>
                <?php endif; ?>
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
                                <span>by <strong><?= sanitizeOutput($thread['author_name']) ?></strong></span>
                                <span>•</span>
                                <span><?= date('M j, Y', strtotime($thread['created_at'])) ?></span>
                                <?php if ($thread['last_post_time'] && $thread['last_post_author']): ?>
                                    <span>•</span>
                                    <span>Last reply by <strong><?= sanitizeOutput($thread['last_post_author']) ?></strong></span>
                                    <span><?= date('M j, g:i A', strtotime($thread['last_post_time'])) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="thread-stats">
                            <strong><?= number_format($thread['reply_count']) ?></strong>
                            <div>replies</div>
                            <strong><?= number_format($thread['view_count']) ?></strong>
                            <div>views</div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div style="display: flex; justify-content: center; align-items: center; gap: 12px; margin: 32px 0;">
                    <?php if ($page > 1): ?>
                        <a href="?id=<?= $categoryId ?>&page=<?= $page - 1 ?>" class="btn ghost">← Previous</a>
                    <?php endif; ?>
                    
                    <span style="color: var(--text-dim);">
                        Page <?= $page ?> of <?= $totalPages ?>
                    </span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?id=<?= $categoryId ?>&page=<?= $page + 1 ?>" class="btn ghost">Next →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-inner">
            <p>&copy; 2025 Azox Network</p>
        </div>
    </footer>

    <script>
        function toggleNewThreadForm() {
            const form = document.getElementById('newThreadForm');
            const btn = document.getElementById('newThreadBtn');
            
            if (form.style.display === 'none') {
                form.style.display = 'block';
                btn.textContent = 'Cancel';
                document.getElementById('title').focus();
            } else {
                form.style.display = 'none';
                btn.textContent = 'Start New Thread';
            }
        }

        // Auto-resize textarea
        document.getElementById('content')?.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    </script>
</body>
</html>