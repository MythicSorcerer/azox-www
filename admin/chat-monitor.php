<?php
require_once __DIR__ . '/../config/auth.php';

// Require admin access
requireAdmin();

$currentUser = getCurrentUser();
$selectedUser = $_GET['user'] ?? null;
$selectedConversation = $_GET['conversation'] ?? null;

// Get all users for monitoring
$users = fetchAll("
    SELECT id, username, role, last_active,
           CASE WHEN last_active > DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 1 ELSE 0 END as is_online
    FROM users
    WHERE is_active = 1
    ORDER BY
        is_online DESC,
        CASE WHEN role = 'admin' THEN 0 ELSE 1 END,
        username ASC
");

// Get recent DM conversations for overview
$recentConversations = fetchAll("
    SELECT 
        CASE 
            WHEN m.sender_id < m.receiver_id 
            THEN CONCAT(u1.username, ':', u2.username)
            ELSE CONCAT(u2.username, ':', u1.username)
        END as conversation_key,
        CASE 
            WHEN m.sender_id < m.receiver_id 
            THEN CONCAT(u1.username, ' ↔ ', u2.username)
            ELSE CONCAT(u2.username, ' ↔ ', u1.username)
        END as conversation_display,
        MAX(m.created_at) as last_message_time,
        COUNT(*) as message_count
    FROM messages m
    JOIN users u1 ON m.sender_id = u1.id
    JOIN users u2 ON m.receiver_id = u2.id
    WHERE m.receiver_id IS NOT NULL
    AND m.is_deleted = 0
    AND m.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY conversation_key
    ORDER BY last_message_time DESC
    LIMIT 20
");

$pageTitle = "Chat Monitor — Admin — Azox";
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="../style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon.svg">
    
    <style>
        .monitor-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
            height: calc(100vh - 140px);
        }
        
        .monitor-sidebar {
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 12px;
            padding: 16px;
            overflow-y: auto;
        }
        
        .monitor-main {
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 12px;
            padding: 20px;
            overflow-y: auto;
        }
        
        .monitor-section {
            margin-bottom: 24px;
        }
        
        .monitor-section h3 {
            margin: 0 0 12px;
            font-size: 14px;
            font-weight: 700;
            color: var(--text);
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        
        .conversation-item, .user-item {
            padding: 8px 12px;
            border-radius: 6px;
            color: var(--text-dim);
            text-decoration: none;
            font-size: 13px;
            transition: all .2s ease;
            display: block;
            margin-bottom: 4px;
            cursor: pointer;
        }
        
        .conversation-item:hover, .user-item:hover {
            background: rgba(255,255,255,.08);
            color: var(--text);
        }
        
        .conversation-item.active, .user-item.active {
            background: rgba(220,20,60,.15);
            color: var(--crimson);
        }
        
        .conversation-meta {
            font-size: 11px;
            color: var(--text-dim);
            margin-top: 2px;
        }
        
        .admin-warning {
            background: rgba(220,20,60,.1);
            border: 1px solid rgba(220,20,60,.3);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
            color: var(--crimson);
            font-size: 14px;
        }
        
        .admin-warning strong {
            display: block;
            margin-bottom: 8px;
        }
        
        .monitor-placeholder {
            text-align: center;
            color: var(--text-dim);
            padding: 60px 20px;
        }
        
        .monitor-placeholder h3 {
            margin-bottom: 12px;
            color: var(--text);
        }
        
        .user-status {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #4CAF50;
            display: inline-block;
            margin-right: 8px;
        }
        
        .user-status.offline {
            background: #6c757d;
        }
        
        .admin-badge {
            color: var(--crimson);
            font-size: 10px;
            margin-left: 4px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/nav.php'; ?>

    <main class="monitor-container">
        <!-- Monitor Sidebar -->
        <aside class="monitor-sidebar">
            <div class="monitor-section">
                <h3>Recent Conversations</h3>
                <?php if (empty($recentConversations)): ?>
                    <div style="color: var(--text-dim); font-size: 12px; font-style: italic;">
                        No recent DM conversations
                    </div>
                <?php else: ?>
                    <?php foreach ($recentConversations as $conv): ?>
                        <div class="conversation-item" 
                             onclick="loadConversation('<?= sanitizeOutput($conv['conversation_key']) ?>')">
                            <?= sanitizeOutput($conv['conversation_display']) ?>
                            <div class="conversation-meta">
                                <?= $conv['message_count'] ?> messages • 
                                <?= date('M j, g:i A', strtotime($conv['last_message_time'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="monitor-section">
                <h3>All Users</h3>
                <?php foreach ($users as $user): ?>
                    <div class="user-item" onclick="loadUserActivity('<?= sanitizeOutput($user['username']) ?>')">
                        <span class="user-status <?= $user['is_online'] ? 'online' : 'offline' ?>"></span>
                        <?= sanitizeOutput($user['username']) ?>
                        <?php if ($user['role'] === 'admin' || $user['role'] === 'owner'): ?>
                            <span class="admin-badge">●</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </aside>

        <!-- Monitor Main Area -->
        <div class="monitor-main">
            <div class="admin-warning">
                <strong>🔒 Admin Monitoring Interface</strong>
                This is a read-only monitoring tool for administrative oversight. 
                You cannot send messages or participate in conversations from this interface.
                All monitoring activity is logged for audit purposes.
            </div>
            
            <div class="monitor-placeholder" id="monitorContent">
                <h3>👁️ Chat Monitoring</h3>
                <p>Select a conversation or user from the sidebar to view their chat activity.</p>
                <p style="font-size: 12px; margin-top: 16px;">
                    This interface provides read-only access for moderation purposes only.
                </p>
            </div>
        </div>
    </main>

    <script>
        function loadConversation(conversationKey) {
            const [user1, user2] = conversationKey.split(':');
            
            // Update active state
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
            event.target.closest('.conversation-item').classList.add('active');
            
            // Load conversation messages
            fetch(`monitor-api.php?action=get_conversation&user1=${encodeURIComponent(user1)}&user2=${encodeURIComponent(user2)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayConversation(data.messages, user1, user2);
                    } else {
                        document.getElementById('monitorContent').innerHTML = `
                            <div style="color: var(--crimson); text-align: center; padding: 40px;">
                                Error loading conversation: ${data.message}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading conversation:', error);
                });
        }
        
        function loadUserActivity(username) {
            // Update active state
            document.querySelectorAll('.user-item').forEach(item => {
                item.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Load user's recent activity
            fetch(`monitor-api.php?action=get_user_activity&username=${encodeURIComponent(username)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayUserActivity(data.activity, username);
                    } else {
                        document.getElementById('monitorContent').innerHTML = `
                            <div style="color: var(--crimson); text-align: center; padding: 40px;">
                                Error loading user activity: ${data.message}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading user activity:', error);
                });
        }
        
        function displayConversation(messages, user1, user2) {
            const content = document.getElementById('monitorContent');
            
            let html = `
                <div style="border-bottom: 1px solid rgba(255,255,255,.08); padding-bottom: 16px; margin-bottom: 20px;">
                    <h3>💬 Conversation: ${escapeHtml(user1)} ↔ ${escapeHtml(user2)}</h3>
                    <p style="color: var(--text-dim); font-size: 13px;">
                        Read-only view • ${messages.length} messages shown
                    </p>
                </div>
                <div style="max-height: 500px; overflow-y: auto;">
            `;
            
            if (messages.length === 0) {
                html += '<div style="text-align: center; color: var(--text-dim); padding: 40px;">No messages in this conversation</div>';
            } else {
                messages.forEach(message => {
                    const time = new Date(message.created_at).toLocaleString();
                    const isAdmin = message.author_role === 'admin' || message.author_role === 'owner';
                    
                    html += `
                        <div style="display: flex; gap: 12px; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,.03);">
                            <div style="width: 32px; height: 32px; border-radius: 6px; background: var(--ring); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; color: #0b0b0c; flex-shrink: 0;">
                                ${message.author_name.substring(0, 2).toUpperCase()}
                            </div>
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: baseline; gap: 8px; margin-bottom: 4px;">
                                    <span style="font-weight: 600; color: ${isAdmin ? 'var(--crimson)' : 'var(--text)'}; font-size: 14px;">
                                        ${escapeHtml(message.author_name)}
                                    </span>
                                    ${isAdmin ? '<span style="color: var(--crimson); font-size: 10px;">●</span>' : ''}
                                    <span style="font-size: 11px; color: var(--text-dim);">${time}</span>
                                </div>
                                <div style="color: var(--text-dim); line-height: 1.4; word-wrap: break-word;">
                                    ${escapeHtml(message.content).replace(/\n/g, '<br>')}
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
            
            html += '</div>';
            content.innerHTML = html;
        }
        
        function displayUserActivity(activity, username) {
            const content = document.getElementById('monitorContent');
            
            let html = `
                <div style="border-bottom: 1px solid rgba(255,255,255,.08); padding-bottom: 16px; margin-bottom: 20px;">
                    <h3>👤 User Activity: ${escapeHtml(username)}</h3>
                    <p style="color: var(--text-dim); font-size: 13px;">
                        Recent messages and DM activity
                    </p>
                </div>
                <div style="max-height: 500px; overflow-y: auto;">
            `;
            
            if (activity.length === 0) {
                html += '<div style="text-align: center; color: var(--text-dim); padding: 40px;">No recent activity</div>';
            } else {
                activity.forEach(item => {
                    const time = new Date(item.created_at).toLocaleString();
                    const isDM = item.receiver_id !== null;
                    
                    html += `
                        <div style="padding: 12px; margin-bottom: 8px; background: rgba(255,255,255,.02); border-radius: 6px; border-left: 3px solid ${isDM ? 'var(--crimson)' : 'var(--ring)'};">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <span style="font-size: 12px; color: var(--text); font-weight: 600;">
                                    ${isDM ? `DM to ${escapeHtml(item.receiver_name)}` : `#${item.channel}`}
                                </span>
                                <span style="font-size: 11px; color: var(--text-dim);">${time}</span>
                            </div>
                            <div style="color: var(--text-dim); font-size: 13px; line-height: 1.4;">
                                ${escapeHtml(item.content).replace(/\n/g, '<br>')}
                            </div>
                        </div>
                    `;
                });
            }
            
            html += '</div>';
            content.innerHTML = html;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>