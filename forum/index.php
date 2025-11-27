<?php
require_once __DIR__ . '/../config/auth.php';

// Get forum categories with thread counts (only count threads that have visible posts)
$categories = fetchAll("
    SELECT
        fc.*,
        COUNT(DISTINCT ft.id) as thread_count,
        SUM(ft.reply_count) as total_replies,
        MAX(ft.updated_at) as last_activity
    FROM forum_categories fc
    LEFT JOIN forum_threads ft ON fc.id = ft.category_id AND ft.is_locked = 0
    LEFT JOIN forum_posts fp ON ft.id = fp.thread_id AND fp.is_deleted = 0
    WHERE fc.is_active = 1 AND (ft.id IS NULL OR fp.id IS NOT NULL)
    GROUP BY fc.id
    ORDER BY fc.sort_order ASC
");

$pageTitle = "Forum — Azox — Trial by Fate";
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

    <!-- Forum Section -->
    <main class="forum-container">
        <div class="forum-header">
            <div class="eyebrow"><span class="dot"></span>Community Discussion</div>
            <h1 class="forum-title">Azox Forum</h1>
            <p class="forum-subtitle">Join the conversation with fellow players, share strategies, and stay updated with the latest community discussions.</p>
        </div>

        <?php if (!isLoggedIn()): ?>
            <div class="alert alert-info" style="background: rgba(33,150,243,.1); border: 1px solid rgba(33,150,243,.3); color: #64b5f6; margin-bottom: 32px;">
                <strong>Welcome to the Azox Forum!</strong> 
                <a href="/auth/register.php" style="color: #42a5f5; text-decoration: underline;">Register</a> or 
                <a href="/auth/login.php" style="color: #42a5f5; text-decoration: underline;">login</a> to participate in discussions and create new threads.
            </div>
        <?php endif; ?>

        <div class="forum-categories">
            <?php foreach ($categories as $category): ?>
                <div class="forum-category">
                    <div class="category-header">
                        <div class="category-info">
                            <h3>
                                <a href="category.php?id=<?= $category['id'] ?>" style="color: inherit; text-decoration: none;">
                                    <?= sanitizeOutput($category['name']) ?>
                                </a>
                            </h3>
                            <p><?= sanitizeOutput($category['description']) ?></p>
                        </div>
                        <div class="category-stats">
                            <strong><?= number_format($category['thread_count']) ?></strong>
                            <div>Threads</div>
                            <strong><?= number_format($category['total_replies'] ?: 0) ?></strong>
                            <div>Replies</div>
                            <?php if ($category['last_activity']): ?>
                                <div style="margin-top: 8px; font-size: 11px;">
                                    Last: <?= date('M j, Y', strtotime($category['last_activity'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($category['thread_count'] > 0): ?>
                        <?php
                        // Get recent threads for this category (only threads with visible posts)
                        $recentThreads = fetchAll("
                            SELECT DISTINCT ft.*, u.username as author_name
                            FROM forum_threads ft
                            JOIN users u ON ft.author_id = u.id
                            JOIN forum_posts fp ON ft.id = fp.thread_id AND fp.is_deleted = 0
                            WHERE ft.category_id = ? AND ft.is_locked = 0
                            ORDER BY ft.updated_at DESC
                            LIMIT 3
                        ", [$category['id']]);
                        ?>
                        
                        <?php if (!empty($recentThreads)): ?>
                            <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,.08);">
                                <div style="font-size: 12px; color: var(--text-dim); margin-bottom: 8px; font-weight: 600;">Recent Discussions:</div>
                                <?php foreach ($recentThreads as $thread): ?>
                                    <div style="margin-bottom: 4px;">
                                        <a href="/thread/?id=<?= $thread['id'] ?>" style="color: var(--text-dim); font-size: 13px; text-decoration: none;">
                                            <?= sanitizeOutput($thread['title']) ?>
                                        </a>
                                        <span style="color: var(--text-dim); font-size: 11px; margin-left: 8px;">
                                            by <?= sanitizeOutput($thread['author_name']) ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (isLoggedIn()): ?>
            <div style="text-align: center; margin-top: 32px;">
                <a href="new-thread.php" class="btn primary">Start New Discussion</a>
            </div>
        <?php endif; ?>

        <!-- Forum Stats -->
        <div style="margin-top: 48px; padding: 24px; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 12px;">
            <h3 style="margin: 0 0 16px; color: var(--text); font-size: 18px;">Forum Statistics</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; text-align: center;">
                <?php
                // Only count threads that have visible posts
                $totalThreads = fetchCount("
                    SELECT COUNT(DISTINCT ft.id)
                    FROM forum_threads ft
                    JOIN forum_posts fp ON ft.id = fp.thread_id AND fp.is_deleted = 0
                    WHERE ft.is_locked = 0
                ");
                $totalPosts = fetchCount("SELECT COUNT(*) FROM forum_posts WHERE is_deleted = 0");
                $totalUsers = fetchCount("SELECT COUNT(*) FROM users WHERE is_active = 1");
                $onlineUsers = fetchCount("SELECT COUNT(*) FROM users WHERE last_active > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
                ?>
                <div>
                    <strong style="display: block; font-size: 24px; color: var(--crimson);"><?= number_format($totalThreads) ?></strong>
                    <span style="color: var(--text-dim); font-size: 14px;">Total Threads</span>
                </div>
                <div>
                    <strong style="display: block; font-size: 24px; color: var(--crimson);"><?= number_format($totalPosts) ?></strong>
                    <span style="color: var(--text-dim); font-size: 14px;">Total Posts</span>
                </div>
                <div>
                    <strong style="display: block; font-size: 24px; color: var(--crimson);"><?= number_format($totalUsers) ?></strong>
                    <span style="color: var(--text-dim); font-size: 14px;">Registered Users</span>
                </div>
                <div>
                    <strong style="display: block; font-size: 24px; color: var(--crimson);"><?= number_format($onlineUsers) ?></strong>
                    <span style="color: var(--text-dim); font-size: 14px;">Online Now</span>
                </div>
            </div>
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