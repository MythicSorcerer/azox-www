<?php
require_once __DIR__ . '/../config/auth.php';

// Get thread ID from URL
$threadId = (int)($_GET['id'] ?? 0);

if (!$threadId) {
    header("Location: /forum/");
    exit;
}

// Get thread details with category and author info
$thread = fetchRow("
    SELECT 
        ft.*,
        fc.name as category_name,
        u.username as author_name,
        u.role as author_role
    FROM forum_threads ft
    JOIN forum_categories fc ON ft.category_id = fc.id
    JOIN users u ON ft.author_id = u.id
    WHERE ft.id = ?
", [$threadId]);

if (!$thread) {
    header("HTTP/1.1 404 Not Found");
    die("Thread not found");
}

// Update view count
executeQuery("UPDATE forum_threads SET view_count = view_count + 1 WHERE id = ?", [$threadId]);

// Handle new post submission
$error = '';
$success = '';

if ($_POST && isset($_POST['reply']) && isLoggedIn()) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } elseif (isBanned()) {
        $error = 'You are banned from creating posts and replies.';
    } elseif ($thread['is_locked']) {
        $error = 'This thread is locked and cannot accept new replies.';
    } else {
        $content = trim($_POST['content'] ?? '');
        
        if (empty($content)) {
            $error = 'Please enter your reply content.';
        } elseif (strlen($content) > 10000) {
            $error = 'Reply content is too long (maximum 10,000 characters).';
        } else {
            try {
                beginTransaction();
                
                // Insert the post
                $postId = insertAndGetId(
                    "INSERT INTO forum_posts (thread_id, author_id, content) VALUES (?, ?, ?)",
                    [$threadId, getCurrentUser()['id'], $content]
                );
                
                // Create notification for thread author (if not replying to own thread)
                if ($thread['author_id'] != getCurrentUser()['id']) {
                    executeQuery(
                        "INSERT INTO notifications (user_id, type, title, content, related_id, related_type) 
                         VALUES (?, 'forum_reply', ?, ?, ?, 'thread')",
                        [
                            $thread['author_id'],
                            'New reply in: ' . $thread['title'],
                            getCurrentUser()['username'] . ' replied to your thread.',
                            $threadId,
                        ]
                    );
                }
                
                commitTransaction();
                $success = 'Your reply has been posted successfully!';
                
                // Redirect to prevent double posting
                header("Location: /thread/?id=$threadId#post-$postId");
                exit;
                
            } catch (Exception $e) {
                rollbackTransaction();
                logActivity("Failed to create forum post: " . $e->getMessage(), 'error');
                $error = 'Failed to post reply. Please try again.';
            }
        }
    }
}

// Get posts with pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$postsPerPage = 20;
$offset = ($page - 1) * $postsPerPage;

$posts = fetchAll("
    SELECT 
        fp.*,
        u.username as author_name,
        u.role as author_role,
        u.created_at as user_joined
    FROM forum_posts fp
    JOIN users u ON fp.author_id = u.id
    WHERE fp.thread_id = ? AND fp.is_deleted = 0
    ORDER BY fp.created_at ASC
    LIMIT ? OFFSET ?
", [$threadId, $postsPerPage, $offset]);

// Get total post count for pagination
$totalPosts = fetchCount("SELECT COUNT(*) FROM forum_posts WHERE thread_id = ? AND is_deleted = 0", [$threadId]);
$totalPages = ceil($totalPosts / $postsPerPage);

$csrfToken = generateCSRFToken();
$pageTitle = sanitizeOutput($thread['title']) . " — Forum — Azox";
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

    <!-- Thread Section -->
    <main class="forum-container">
        <!-- Breadcrumb -->
        <div style="margin-bottom: 24px;">
            <nav style="font-size: 14px; color: var(--text-dim);">
                <a href="/forum/" style="color: var(--text-dim);">Forum</a>
                <span style="margin: 0 8px;">›</span>
                <a href="/forum/category.php?id=<?= $thread['category_id'] ?>" style="color: var(--text-dim);">
                    <?= sanitizeOutput($thread['category_name']) ?>
                </a>
                <span style="margin: 0 8px;">›</span>
                <span style="color: var(--text);"><?= sanitizeOutput($thread['title']) ?></span>
            </nav>
        </div>

        <!-- Thread Header -->
        <div class="forum-header" style="margin-bottom: 32px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <h1 class="forum-title" style="margin: 0; font-size: clamp(24px, 4vw, 36px);">
                    <?= sanitizeOutput($thread['title']) ?>
                </h1>
                
                <?php if ($thread['is_pinned']): ?>
                    <span class="thread-badge badge-pinned">Pinned</span>
                <?php endif; ?>
                
                <?php if ($thread['is_locked']): ?>
                    <span class="thread-badge badge-locked">Locked</span>
                <?php endif; ?>
                
                <?php if (isAdmin() || (isLoggedIn() && getCurrentUser()['id'] == $thread['author_id'])): ?>
                    <button onclick="deleteThread(<?= $thread['id'] ?>, '<?= sanitizeOutput($thread['title']) ?>')"
                            class="btn ghost"
                            style="font-size: 12px; padding: 6px 12px; color: var(--crimson); border-color: var(--crimson);">
                        🗑️ Delete Thread
                    </button>
                <?php endif; ?>
            </div>
            
            <div style="display: flex; align-items: center; gap: 16px; font-size: 14px; color: var(--text-dim);">
                <span>Started by <strong style="color: var(--text);"><?= sanitizeOutput($thread['author_name']) ?></strong></span>
                <span>•</span>
                <span><?= date('M j, Y \a\t g:i A', strtotime($thread['created_at'])) ?></span>
                <span>•</span>
                <span><?= number_format($thread['view_count']) ?> views</span>
                <span>•</span>
                <span><?= number_format($thread['reply_count']) ?> replies</span>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: 24px;">
                <?= sanitizeOutput($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom: 24px;">
                <?= sanitizeOutput($success) ?>
            </div>
        <?php endif; ?>

        <!-- Posts -->
        <div class="forum-posts">
            <?php foreach ($posts as $index => $post): ?>
                <div class="forum-post" id="post-<?= $post['id'] ?>">
                    <div class="post-header">
                        <div class="post-author">
                            <div class="author-avatar">
                                <?= strtoupper(substr($post['author_name'], 0, 2)) ?>
                            </div>
                            <div class="author-info">
                                <h5><?= sanitizeOutput($post['author_name']) ?></h5>
                                <div class="author-role">
                                    <?= ucfirst($post['author_role']) ?>
                                    <?php if ($post['author_id'] == $thread['author_id'] && $index > 0): ?>
                                        • Thread Author
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 11px; color: var(--text-dim); margin-top: 2px;">
                                    Joined <?= date('M Y', strtotime($post['user_joined'])) ?>
                                </div>
                            </div>
                        </div>
                        <div class="post-date">
                            <a href="#post-<?= $post['id'] ?>" style="color: var(--text-dim); text-decoration: none;">
                                #<?= $offset + $index + 1 ?>
                            </a>
                            <div style="margin-top: 4px;">
                                <?= date('M j, Y \a\t g:i A', strtotime($post['created_at'])) ?>
                            </div>
                            <?php if ($post['edited_at']): ?>
                                <div style="font-size: 10px; font-style: italic; margin-top: 2px;">
                                    Edited <?= date('M j, g:i A', strtotime($post['edited_at'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="post-content">
                        <?= nl2br(sanitizeOutput($post['content'])) ?>
                    </div>
                    
                    <?php if (isLoggedIn()): ?>
                        <div class="post-actions">
                            <?php if (getCurrentUser()['id'] == $post['author_id'] || isAdmin()): ?>
                                <a href="#" class="post-action" onclick="alert('Edit functionality coming soon')">
                                    ✏️ Edit
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!$thread['is_locked']): ?>
                                <a href="#reply-form" class="post-action" onclick="quotePost(<?= $post['id'] ?>, '<?= sanitizeOutput($post['author_name']) ?>')">
                                    💬 Quote
                                </a>
                            <?php endif; ?>
                            
                            <?php if (getCurrentUser()['id'] == $post['author_id']): ?>
                                <a href="#" class="post-action" onclick="deleteOwnPost(<?= $post['id'] ?>, 'post #<?= $offset + $index + 1 ?>')" style="color: var(--crimson);">
                                    🗑️ Delete
                                </a>
                            <?php elseif (isAdmin()): ?>
                                <a href="#" class="post-action" onclick="deletePost(<?= $post['id'] ?>, 'post #<?= $offset + $index + 1 ?>')" style="color: var(--crimson);">
                                    🗑️ Delete
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div style="display: flex; justify-content: center; align-items: center; gap: 12px; margin: 32px 0;">
                <?php if ($page > 1): ?>
                    <a href="?id=<?= $threadId ?>&page=<?= $page - 1 ?>" class="btn ghost">← Previous</a>
                <?php endif; ?>
                
                <span style="color: var(--text-dim);">
                    Page <?= $page ?> of <?= $totalPages ?>
                </span>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?id=<?= $threadId ?>&page=<?= $page + 1 ?>" class="btn ghost">Next →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Reply Form -->
        <?php if (isLoggedIn() && !$thread['is_locked'] && !isBanned()): ?>
            <div id="reply-form" style="margin-top: 48px; padding-top: 32px; border-top: 1px solid rgba(255,255,255,.08);">
                <h3 style="margin: 0 0 24px; color: var(--text);">Post Reply</h3>
                
                <form method="POST" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    
                    <div class="form-group">
                        <label for="content">Your Reply</label>
                        <textarea
                            id="content"
                            name="content"
                            rows="8"
                            required
                            placeholder="Write your reply here..."
                            style="resize: vertical; min-height: 120px;"
                        ><?= sanitizeOutput($_POST['content'] ?? '') ?></textarea>
                        <small>Maximum 10,000 characters</small>
                    </div>

                    <button type="submit" name="reply" class="btn primary">
                        Post Reply
                    </button>
                </form>
            </div>
        <?php elseif (!isLoggedIn()): ?>
            <div style="text-align: center; margin-top: 48px; padding: 32px; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 12px;">
                <p style="margin: 0 0 16px; color: var(--text-dim);">
                    <a href="/auth/login.php" style="color: var(--crimson);">Login</a> or
                    <a href="/auth/register.php" style="color: var(--crimson);">register</a> to reply to this thread.
                </p>
            </div>
        <?php elseif (isBanned()): ?>
            <div style="text-align: center; margin-top: 48px; padding: 32px; background: rgba(220,20,60,.1); border: 1px solid rgba(220,20,60,.3); border-radius: 12px;">
                <p style="margin: 0; color: var(--crimson);">
                    🚫 You are banned from posting and replying in the forum.
                </p>
            </div>
        <?php elseif ($thread['is_locked']): ?>
            <div style="text-align: center; margin-top: 48px; padding: 32px; background: rgba(108,117,125,.1); border: 1px solid rgba(108,117,125,.3); border-radius: 12px;">
                <p style="margin: 0; color: #6c757d;">
                    🔒 This thread is locked and cannot accept new replies.
                </p>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-inner">
            <p>&copy; 2025 Azox Network</p>
        </div>
    </footer>

    <!-- Admin Action Modal -->
    <div id="adminModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Confirm Action</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p id="modalMessage">Are you sure you want to perform this action?</p>
            </div>
            <div class="modal-footer">
                <button class="btn ghost" onclick="closeModal()">Cancel</button>
                <button id="confirmBtn" class="btn primary" onclick="confirmAction()">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        let currentAction = null;
        let currentId = null;
        let currentEndpoint = null;

        // Quote post functionality
        function quotePost(postId, authorName) {
            const content = document.getElementById('content');
            if (content) {
                const quote = `[quote="${authorName}"]...[/quote]\n\n`;
                content.value = quote + content.value;
                content.focus();
                content.setSelectionRange(quote.length - 10, quote.length - 7); // Select the "..." part
            }
        }

        // Auto-resize textarea
        document.getElementById('content')?.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });

        // Admin functions
        function deletePost(postId, postDescription) {
            performAction('delete_post', postId, 'Post ' + postDescription + ' will be permanently deleted.', '/admin/actions.php');
        }

        function deleteThread(threadId, threadTitle) {
            <?php if (isAdmin()): ?>
                // Admin uses admin endpoint
                performAction('delete_thread', threadId, 'Thread "' + threadTitle + '" and all its posts will be permanently deleted.', '/admin/actions.php');
            <?php else: ?>
                // Thread creator uses user endpoint
                performAction('delete_own_thread', threadId, 'Your thread "' + threadTitle + '" and all its posts will be permanently deleted.', '/api/user_actions.php');
            <?php endif; ?>
        }

        // User functions
        function deleteOwnPost(postId, postDescription) {
            performAction('delete_own_post', postId, 'Your post ' + postDescription + ' will be permanently deleted.', '/api/user_actions.php');
        }

        function performAction(action, id, message, endpoint) {
            currentAction = action;
            currentId = id;
            currentEndpoint = endpoint || '/admin/actions.php';
            
            document.getElementById('modalMessage').textContent = message + ' This action cannot be undone.';
            document.getElementById('adminModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('adminModal').style.display = 'none';
            currentAction = null;
            currentId = null;
            currentEndpoint = null;
        }

        function confirmAction() {
            if (!currentAction || !currentId) return;

            const formData = new FormData();
            formData.append('action', currentAction);
            formData.append('id', currentId);

            fetch(currentEndpoint || '/admin/actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Action completed successfully: ' + data.message);
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        location.reload(); // Refresh the page to show updated data
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Network error: ' + error.message);
            });

            closeModal();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const adminModal = document.getElementById('adminModal');
            if (event.target === adminModal) {
                closeModal();
            }
        }
    </script>

    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--bg-1);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            box-shadow: var(--shadow-lg);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }

        .modal-header h3 {
            margin: 0;
            color: var(--text);
            font-size: 18px;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-dim);
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            color: var(--text);
        }

        .modal-body {
            padding: 24px;
        }

        .modal-body p {
            margin: 0 0 16px;
            color: var(--text-dim);
            line-height: 1.5;
        }

        .modal-footer {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding: 20px 24px;
            border-top: 1px solid rgba(255,255,255,.08);
        }
    </style>
</body>
</html>