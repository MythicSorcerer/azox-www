<?php
require_once __DIR__ . '/../config/auth.php';

// Require login
requireLogin();

$currentUser = getCurrentUser();

// Handle mark as read action
if ($_POST && isset($_POST['mark_read'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $notificationId = (int)($_POST['notification_id'] ?? 0);
        if ($notificationId) {
            executeQuery(
                "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?",
                [$notificationId, $currentUser['id']]
            );
        }
    }
    header("Location: /notifications/");
    exit;
}

// Handle mark all as read
if ($_POST && isset($_POST['mark_all_read'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        executeQuery(
            "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0",
            [$currentUser['id']]
        );
    }
    header("Location: /notifications/");
    exit;
}

// Get notifications with pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$notificationsPerPage = 20;
$offset = ($page - 1) * $notificationsPerPage;

$notifications = fetchAll("
    SELECT *
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
", [$currentUser['id'], $notificationsPerPage, $offset]);

// Get total notification count
$totalNotifications = fetchCount("SELECT COUNT(*) FROM notifications WHERE user_id = ?", [$currentUser['id']]);
$unreadCount = fetchCount("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0", [$currentUser['id']]);
$totalPages = ceil($totalNotifications / $notificationsPerPage);

$csrfToken = generateCSRFToken();
$pageTitle = "Notifications — Azox — Trial by Fate";
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

    <!-- Notifications Section -->
    <main class="forum-container">
        <div class="forum-header">
            <div class="eyebrow"><span class="dot"></span>Stay Updated</div>
            <h1 class="forum-title">Notifications</h1>
            <p class="forum-subtitle">Keep track of replies, mentions, and important updates from the Azox community.</p>
        </div>

        <!-- Notification Actions -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; padding: 16px; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 12px;">
            <div>
                <strong style="color: var(--text);"><?= number_format($totalNotifications) ?></strong>
                <span style="color: var(--text-dim); margin-left: 8px;">total notifications</span>
                
                <?php if ($unreadCount > 0): ?>
                    <span style="margin-left: 16px;">
                        <strong style="color: var(--crimson);"><?= number_format($unreadCount) ?></strong>
                        <span style="color: var(--text-dim); margin-left: 8px;">unread</span>
                    </span>
                <?php endif; ?>
            </div>
            
            <?php if ($unreadCount > 0): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <button type="submit" name="mark_all_read" class="btn ghost" style="font-size: 14px; padding: 8px 16px;">
                        Mark All Read
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Notifications List -->
        <?php if (empty($notifications)): ?>
            <div style="text-align: center; padding: 64px 20px; background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.08); border-radius: 12px;">
                <div style="font-size: 48px; margin-bottom: 16px;">🔔</div>
                <h3 style="margin: 0 0 12px; color: var(--text);">No Notifications Yet</h3>
                <p style="margin: 0; color: var(--text-dim);">
                    When you receive replies, mentions, or other updates, they'll appear here.
                </p>
                <div style="margin-top: 24px;">
                    <a href="/forum/" class="btn primary">Explore Forum</a>
                </div>
            </div>
        <?php else: ?>
            <div class="notifications-list">
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?= $notification['is_read'] ? 'read' : 'unread' ?>">
                        <div class="notification-content">
                            <div class="notification-header">
                                <div class="notification-type">
                                    <?php
                                    $icon = '📢';
                                    switch ($notification['type']) {
                                        case 'forum_reply':
                                            $icon = '💬';
                                            break;
                                        case 'forum_mention':
                                            $icon = '👤';
                                            break;
                                        case 'message':
                                            $icon = '✉️';
                                            break;
                                        case 'system':
                                            $icon = '⚙️';
                                            break;
                                    }
                                    echo $icon;
                                    ?>
                                </div>
                                <div class="notification-info">
                                    <h4><?= sanitizeOutput($notification['title']) ?></h4>
                                    <?php if ($notification['content']): ?>
                                        <p><?= sanitizeOutput($notification['content']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="notification-actions">
                                    <div class="notification-time">
                                        <?= date('M j, Y \a\t g:i A', strtotime($notification['created_at'])) ?>
                                    </div>
                                    <?php if (!$notification['is_read']): ?>
                                        <form method="POST" style="display: inline; margin-top: 8px;">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                            <button type="submit" name="mark_read" class="btn ghost" style="font-size: 12px; padding: 4px 8px;">
                                                Mark Read
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($notification['related_id'] && $notification['related_type']): ?>
                                <div class="notification-link">
                                    <?php
                                    $linkUrl = '#';
                                    $linkText = 'View';
                                    
                                    switch ($notification['related_type']) {
                                        case 'thread':
                                            $linkUrl = "/thread/?id=" . $notification['related_id'];
                                            $linkText = 'View Thread';
                                            break;
                                        case 'post':
                                            // Get thread ID from post
                                            $post = fetchRow("SELECT thread_id FROM forum_posts WHERE id = ?", [$notification['related_id']]);
                                            if ($post) {
                                                $linkUrl = "/thread/?id=" . $post['thread_id'] . "#post-" . $notification['related_id'];
                                                $linkText = 'View Post';
                                            }
                                            break;
                                        case 'message':
                                            $linkUrl = "/messages/";
                                            $linkText = 'View Message';
                                            break;
                                    }
                                    ?>
                                    <a href="<?= $linkUrl ?>" class="btn ghost" style="font-size: 14px; padding: 8px 16px;">
                                        <?= $linkText ?> →
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!$notification['is_read']): ?>
                            <div class="unread-indicator"></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div style="display: flex; justify-content: center; align-items: center; gap: 12px; margin: 32px 0;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" class="btn ghost">← Previous</a>
                    <?php endif; ?>
                    
                    <span style="color: var(--text-dim);">
                        Page <?= $page ?> of <?= $totalPages ?>
                    </span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>" class="btn ghost">Next →</a>
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

    <style>
        /* Notification-specific styles */
        .notifications-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .notification-item {
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 12px;
            padding: 20px;
            position: relative;
            transition: all .2s ease;
        }

        .notification-item.unread {
            background: rgba(220,20,60,.05);
            border-color: rgba(220,20,60,.15);
        }

        .notification-item:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .notification-header {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 16px;
            align-items: flex-start;
        }

        .notification-type {
            font-size: 24px;
            line-height: 1;
        }

        .notification-info h4 {
            margin: 0 0 8px;
            font-size: 16px;
            font-weight: 600;
            color: var(--text);
        }

        .notification-info p {
            margin: 0;
            color: var(--text-dim);
            font-size: 14px;
            line-height: 1.4;
        }

        .notification-actions {
            text-align: right;
            font-size: 12px;
            color: var(--text-dim);
        }

        .notification-time {
            margin-bottom: 8px;
        }

        .notification-link {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid rgba(255,255,255,.08);
        }

        .unread-indicator {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 8px;
            height: 8px;
            background: var(--crimson);
            border-radius: 50%;
        }

        @media (max-width: 640px) {
            .notification-header {
                grid-template-columns: auto 1fr;
                grid-template-rows: auto auto;
                gap: 12px;
            }

            .notification-actions {
                grid-column: 1 / -1;
                text-align: left;
                margin-top: 8px;
            }
        }
    </style>
</body>
</html>