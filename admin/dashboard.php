<?php
require_once __DIR__ . '/../config/auth.php';

// Require admin access
requireAdmin();

$currentUser = getCurrentUser();

// Get system statistics
$stats = [
    'total_users' => fetchCount("SELECT COUNT(*) FROM users WHERE is_active = 1"),
    'online_users' => fetchCount("SELECT COUNT(*) FROM users WHERE last_active > DATE_SUB(NOW(), INTERVAL 15 MINUTE) AND is_active = 1"),
    'total_threads' => fetchCount("SELECT COUNT(*) FROM forum_threads"),
    'total_posts' => fetchCount("SELECT COUNT(*) FROM forum_posts WHERE is_deleted = 0"),
    'total_messages' => fetchCount("SELECT COUNT(*) FROM messages WHERE is_deleted = 0"),
    'unread_notifications' => fetchCount("SELECT COUNT(*) FROM notifications WHERE is_read = 0")
];

// Get recent activity
$recentUsers = fetchAll("
    SELECT id, username, email, created_at, last_active, role, is_banned, banned_at
    FROM users
    WHERE is_active = 1
    ORDER BY created_at DESC
    LIMIT 10
");

$recentThreads = fetchAll("
    SELECT ft.*, u.username as author_name, fc.name as category_name
    FROM forum_threads ft
    JOIN users u ON ft.author_id = u.id
    JOIN forum_categories fc ON ft.category_id = fc.id
    ORDER BY ft.created_at DESC
    LIMIT 10
");

$recentMessages = fetchAll("
    SELECT m.*, u.username as author_name
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.is_deleted = 0
    ORDER BY m.created_at DESC
    LIMIT 20
");

$pageTitle = "Admin Dashboard — Azox — Trial by Fate";
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
</head>
<body>
    <?php include __DIR__ . '/../includes/nav.php'; ?>

    <!-- Admin Dashboard -->
    <main class="forum-container">
        <div class="forum-header">
            <div class="eyebrow"><span class="dot"></span>Administration</div>
            <h1 class="forum-title">Admin Dashboard</h1>
            <p class="forum-subtitle">Monitor and manage the Azox Network community platform.</p>
        </div>

        <!-- System Statistics -->
        <div class="admin-stats">
            <h2 style="margin: 0 0 24px; color: var(--text); font-size: 24px;">System Overview</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($stats['total_users']) ?></div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-sublabel"><?= number_format($stats['online_users']) ?> online now</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($stats['total_threads']) ?></div>
                    <div class="stat-label">Forum Threads</div>
                    <div class="stat-sublabel"><?= number_format($stats['total_posts']) ?> total posts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($stats['total_messages']) ?></div>
                    <div class="stat-label">Chat Messages</div>
                    <div class="stat-sublabel">All channels combined</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($stats['unread_notifications']) ?></div>
                    <div class="stat-label">Unread Notifications</div>
                    <div class="stat-sublabel">Across all users</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="admin-actions" style="margin: 48px 0;">
            <h2 style="margin: 0 0 24px; color: var(--text); font-size: 24px;">Quick Actions</h2>
            <div class="action-buttons">
                <button onclick="showTab('users')" class="btn primary">Manage Users</button>
                <button onclick="showTab('threads')" class="btn ghost">Moderate Forum</button>
                <button onclick="showTab('bulk')" class="btn ghost">Bulk Operations</button>
                <button onclick="alert('System settings coming soon')" class="btn ghost">System Settings</button>
            </div>
        </div>

        <!-- Recent Activity Tabs -->
        <div class="admin-tabs">
            <div class="tab-headers">
                <button class="tab-header active" onclick="showTab('users')">Recent Users</button>
                <button class="tab-header" onclick="showTab('threads')">Recent Threads</button>
                <button class="tab-header" onclick="showTab('messages')">Recent Messages</button>
                <button class="tab-header" onclick="showTab('bulk')">Bulk Operations</button>
            </div>

            <!-- Recent Users Tab -->
            <div id="tab-users" class="tab-content active">
                <h3 style="margin: 0 0 16px; color: var(--text);">Recently Registered Users</h3>
                <div class="admin-table">
                    <div class="table-header">
                        <div>Username</div>
                        <div>Email</div>
                        <div>Role</div>
                        <div>Registered</div>
                        <div>Last Active</div>
                        <div>Actions</div>
                    </div>
                    <?php foreach ($recentUsers as $user): ?>
                        <div class="table-row">
                            <div class="user-info">
                                <strong><?= sanitizeOutput($user['username']) ?></strong>
                                <?php if ($user['is_banned']): ?>
                                    <span style="color: var(--crimson); font-size: 11px; margin-left: 8px;">🚫 BANNED</span>
                                <?php endif; ?>
                            </div>
                            <div><?= sanitizeOutput($user['email']) ?></div>
                            <div>
                                <span class="role-badge <?= $user['role'] ?>">
                                    <?= ucfirst($user['role']) ?>
                                </span>
                            </div>
                            <div><?= date('M j, Y', strtotime($user['created_at'])) ?></div>
                            <div>
                                <?php
                                $lastActive = strtotime($user['last_active']);
                                $now = time();
                                $diff = $now - $lastActive;
                                
                                if ($diff < 300) echo '<span style="color: #4CAF50;">Online</span>';
                                elseif ($diff < 3600) echo '<span style="color: #FF9800;">' . floor($diff/60) . 'm ago</span>';
                                elseif ($diff < 86400) echo '<span style="color: var(--text-dim);">' . floor($diff/3600) . 'h ago</span>';
                                else echo '<span style="color: var(--text-dim);">' . floor($diff/86400) . 'd ago</span>';
                                ?>
                            </div>
                            <div>
                                <button onclick="showUserActions(<?= $user['id'] ?>, '<?= sanitizeOutput($user['username']) ?>', <?= $user['is_banned'] ? 'true' : 'false' ?>, '<?= $user['role'] ?>')" class="btn ghost" style="font-size: 12px; padding: 4px 8px;">
                                    Actions
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent Threads Tab -->
            <div id="tab-threads" class="tab-content">
                <h3 style="margin: 0 0 16px; color: var(--text);">Recently Created Threads</h3>
                <div class="admin-list">
                    <?php foreach ($recentThreads as $thread): ?>
                        <div class="list-item">
                            <div class="item-content">
                                <h4>
                                    <a href="/thread/?id=<?= $thread['id'] ?>" style="color: var(--text); text-decoration: none;">
                                        <?= sanitizeOutput($thread['title']) ?>
                                    </a>
                                </h4>
                                <div class="item-meta">
                                    <span>by <strong><?= sanitizeOutput($thread['author_name']) ?></strong></span>
                                    <span>in <strong><?= sanitizeOutput($thread['category_name']) ?></strong></span>
                                    <span><?= date('M j, Y \a\t g:i A', strtotime($thread['created_at'])) ?></span>
                                </div>
                            </div>
                            <div class="item-actions">
                                <button onclick="deleteThread(<?= $thread['id'] ?>, '<?= sanitizeOutput($thread['title']) ?>')" class="btn ghost" style="font-size: 12px; padding: 4px 8px; color: #ff6b6b;">
                                    Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent Messages Tab -->
            <div id="tab-messages" class="tab-content">
                <h3 style="margin: 0 0 16px; color: var(--text);">Recent Chat Messages</h3>
                <div class="admin-messages">
                    <?php foreach ($recentMessages as $message): ?>
                        <div class="message-item">
                            <div class="message-header">
                                <strong><?= sanitizeOutput($message['author_name']) ?></strong>
                                <span class="channel-tag">#<?= sanitizeOutput($message['channel']) ?></span>
                                <span class="message-time"><?= date('M j, g:i A', strtotime($message['created_at'])) ?></span>
                            </div>
                            <div class="message-content">
                                <?= nl2br(sanitizeOutput($message['content'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Bulk Operations Tab -->
            <div id="tab-bulk" class="tab-content">
                <h3 style="margin: 0 0 24px; color: var(--text);">Bulk Operations</h3>
                
                <!-- Forum Bulk Operations -->
                <div class="bulk-section">
                    <h4 style="margin: 0 0 16px; color: var(--text); font-size: 18px;">Forum Management</h4>
                    <div class="bulk-actions">
                        <div class="bulk-action-card">
                            <h5>Bulk Delete Threads</h5>
                            <p>Delete multiple threads at once by category or date range.</p>
                            <div class="bulk-controls">
                                <select id="bulkThreadCategory" class="bulk-select">
                                    <option value="">All Categories</option>
                                    <?php
                                    $categories = fetchAll("SELECT id, name FROM forum_categories WHERE is_active = 1 ORDER BY name");
                                    foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= sanitizeOutput($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="date" id="bulkThreadDate" class="bulk-input" placeholder="Older than date">
                                <button onclick="bulkDeleteThreads()" class="btn" style="background: #ff6b6b; color: white;">
                                    Delete Threads
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat Bulk Operations -->
                <div class="bulk-section">
                    <h4 style="margin: 0 0 16px; color: var(--text); font-size: 18px;">Chat Management</h4>
                    <div class="bulk-actions">
                        <div class="bulk-action-card">
                            <h5>Clear Chat Channels</h5>
                            <p>Clear all messages from specific channels or all channels.</p>
                            <div class="bulk-controls">
                                <select id="bulkChatChannel" class="bulk-select">
                                    <option value="all">All Channels</option>
                                    <option value="general">General</option>
                                    <option value="pvp">PvP Discussion</option>
                                    <option value="trading">Trading</option>
                                    <option value="help">Help & Support</option>
                                </select>
                                <input type="date" id="bulkChatDate" class="bulk-input" placeholder="Older than date">
                                <button onclick="clearChatChannel()" class="btn" style="background: #ff6b6b; color: white;">
                                    Clear Messages
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Bulk Operations -->
                <div class="bulk-section">
                    <h4 style="margin: 0 0 16px; color: var(--text); font-size: 18px;">User Management</h4>
                    <div class="bulk-actions">
                        <div class="bulk-action-card">
                            <h5>Bulk User Actions</h5>
                            <p>Perform actions on multiple users based on criteria.</p>
                            <div class="bulk-controls">
                                <select id="bulkUserAction" class="bulk-select">
                                    <option value="">Select Action</option>
                                    <option value="ban_inactive">Ban Inactive Users</option>
                                    <option value="delete_inactive">Delete Inactive Users</option>
                                    <option value="delete_date_range">Delete Users by Date Range</option>
                                </select>
                                <input type="number" id="bulkUserDays" class="bulk-input" placeholder="Days inactive" min="1" max="365">
                                <button onclick="bulkUserAction()" class="btn" style="background: #ff6b6b; color: white;">
                                    Execute Action
                                </button>
                            </div>
                            <div id="dateRangeControls" class="bulk-controls" style="display: none; margin-top: 12px;">
                                <input type="date" id="bulkUserStartDate" class="bulk-input" placeholder="Start date">
                                <input type="date" id="bulkUserEndDate" class="bulk-input" placeholder="End date">
                            </div>
                        </div>
                        
                        <!-- Owner Only Section -->
                        <?php if (isOwner()): ?>
                        <div class="bulk-action-card" style="border: 2px solid var(--crimson); background: rgba(220,20,60,.05);">
                            <h5 style="color: var(--crimson);">👑 Owner Operations</h5>
                            <p style="color: var(--crimson);">Owner-only operations for managing admins and advanced user management.</p>
                            <div class="bulk-controls">
                                <select id="ownerAction" class="bulk-select">
                                    <option value="">Select Owner Action</option>
                                    <option value="delete_admin">Delete Admin/Owner User</option>
                                    <option value="hard_delete_user">Hard Delete User (Permanent)</option>
                                    <option value="purge_all_inactive">Purge All Inactive Users</option>
                                </select>
                                <button onclick="ownerAction()" class="btn" style="background: var(--crimson); color: white;">
                                    Execute Owner Action
                                </button>
                            </div>
                            <div id="ownerTarget" class="bulk-controls" style="display: none; margin-top: 12px;">
                                <input type="text" id="targetUsername" class="bulk-input" placeholder="Target username" style="border-color: var(--crimson);">
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
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

    <style>
        /* Admin Dashboard Styles */
        .admin-stats {
            margin-bottom: 48px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            box-shadow: var(--shadow-sm);
        }

        .stat-number {
            font-size: 32px;
            font-weight: 900;
            color: var(--crimson);
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 16px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 4px;
        }

        .stat-sublabel {
            font-size: 12px;
            color: var(--text-dim);
        }

        .action-buttons {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .admin-tabs {
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 12px;
            overflow: hidden;
        }

        .tab-headers {
            display: flex;
            background: rgba(255,255,255,.02);
            border-bottom: 1px solid rgba(255,255,255,.08);
        }

        .tab-header {
            flex: 1;
            padding: 16px 24px;
            background: transparent;
            border: none;
            color: var(--text-dim);
            font-weight: 600;
            cursor: pointer;
            transition: all .2s ease;
        }

        .tab-header:hover,
        .tab-header.active {
            color: var(--text);
            background: rgba(255,255,255,.04);
        }

        .tab-header.active {
            border-bottom: 2px solid var(--crimson);
        }

        .tab-content {
            display: none;
            padding: 24px;
        }

        .tab-content.active {
            display: block;
        }

        .admin-table {
            display: grid;
            gap: 1px;
            background: rgba(255,255,255,.08);
            border-radius: 8px;
            overflow: hidden;
        }

        .table-header {
            display: grid;
            grid-template-columns: 1fr 1fr 80px 100px 100px 80px;
            gap: 16px;
            padding: 12px 16px;
            background: rgba(255,255,255,.1);
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--text-dim);
        }

        .table-row {
            display: grid;
            grid-template-columns: 1fr 1fr 80px 100px 100px 80px;
            gap: 16px;
            padding: 12px 16px;
            background: rgba(255,255,255,.03);
            align-items: center;
            font-size: 14px;
        }

        .role-badge {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .role-badge.owner {
            background: rgba(255,215,0,.2);
            color: #ffd700;
            border: 1px solid rgba(255,215,0,.3);
        }

        .role-badge.admin {
            background: rgba(220,20,60,.2);
            color: var(--crimson);
        }

        .role-badge.user {
            background: rgba(255,255,255,.1);
            color: var(--text-dim);
        }

        .admin-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .list-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 16px;
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 8px;
        }

        .item-content h4 {
            margin: 0 0 8px;
            font-size: 16px;
        }

        .item-meta {
            display: flex;
            gap: 12px;
            font-size: 12px;
            color: var(--text-dim);
        }

        .admin-messages {
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-height: 400px;
            overflow-y: auto;
        }

        .message-item {
            padding: 12px;
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 8px;
        }

        .message-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 12px;
        }

        .channel-tag {
            background: rgba(220,20,60,.2);
            color: var(--crimson);
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
        }

        .message-time {
            color: var(--text-dim);
            margin-left: auto;
        }

        .message-content {
            font-size: 14px;
            color: var(--text-dim);
            line-height: 1.4;
        }

        @media (max-width: 768px) {
            .table-header,
            .table-row {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .tab-headers {
                flex-direction: column;
            }
        }
    </style>

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

    <!-- User Actions Modal -->
    <div id="userActionsModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="userModalTitle">User Actions</h3>
                <button class="modal-close" onclick="closeUserModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p id="userModalMessage">Choose an action for this user:</p>
                <div class="user-actions-buttons">
                    <button id="promoteUserBtn" class="btn ghost" onclick="promoteUser()" style="background: #4CAF50; color: white;">Promote User</button>
                    <button id="demoteUserBtn" class="btn ghost" onclick="demoteUser()" style="background: #FF9800; color: white;">Demote User</button>
                    <button id="banUserBtn" class="btn ghost" onclick="banUser()">Ban User</button>
                    <button id="deleteUserBtn" class="btn" style="background: #ff6b6b; color: white;" onclick="deleteUser()">Delete User</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentAction = null;
        let currentId = null;
        let currentUserId = null;
        let currentUserBanned = false;
        let currentUserRole = '';

        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all headers
            document.querySelectorAll('.tab-header').forEach(header => {
                header.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById('tab-' + tabName).classList.add('active');
            
            // Add active class to clicked header
            document.querySelectorAll('.tab-header').forEach(header => {
                if (header.textContent.toLowerCase().includes(tabName.toLowerCase())) {
                    header.classList.add('active');
                }
            });
        }

        function showUserActions(userId, username, isBanned, userRole) {
            currentUserId = userId;
            currentUserBanned = isBanned;
            currentUserRole = userRole;
            
            document.getElementById('userModalTitle').textContent = 'Actions for ' + username;
            document.getElementById('userModalMessage').textContent = 'Choose an action for ' + username + ':';
            
            // Update buttons based on ban status and role
            const promoteBtn = document.getElementById('promoteUserBtn');
            const demoteBtn = document.getElementById('demoteUserBtn');
            const banBtn = document.getElementById('banUserBtn');
            const deleteBtn = document.getElementById('deleteUserBtn');
            
            // Show/hide promote/demote buttons based on role
            if (currentUserRole === 'owner') {
                promoteBtn.style.display = 'none';
                demoteBtn.style.display = 'inline-block';
                demoteBtn.textContent = 'Demote to Admin';
            } else if (currentUserRole === 'admin') {
                promoteBtn.style.display = 'inline-block';
                promoteBtn.textContent = 'Promote to Owner';
                demoteBtn.style.display = 'inline-block';
                demoteBtn.textContent = 'Demote to User';
            } else { // user
                promoteBtn.style.display = 'inline-block';
                promoteBtn.textContent = 'Promote to Admin';
                demoteBtn.style.display = 'none';
            }
            
            if (isBanned) {
                banBtn.textContent = 'Unban User';
                banBtn.onclick = unbanUser;
                banBtn.style.background = '#4CAF50';
                banBtn.style.color = 'white';
            } else {
                banBtn.textContent = 'Ban User';
                banBtn.onclick = banUser;
                banBtn.style.background = '';
                banBtn.style.color = '';
            }
            
            document.getElementById('userActionsModal').style.display = 'flex';
        }

        function closeUserModal() {
            document.getElementById('userActionsModal').style.display = 'none';
            currentUserId = null;
            currentUserBanned = false;
            currentUserRole = '';
        }

        function promoteUser() {
            if (!currentUserId) return;
            const roleText = currentUserRole === 'user' ? 'admin' : 'owner';
            performAction('promote_user', currentUserId, `User will be promoted to ${roleText} role. This will give them elevated privileges.`);
            closeUserModal();
        }

        function demoteUser() {
            if (!currentUserId) return;
            const roleText = currentUserRole === 'owner' ? 'admin' : 'user';
            performAction('demote_user', currentUserId, `User will be demoted to ${roleText} role. This will reduce their privileges.`);
            closeUserModal();
        }

        function banUser() {
            if (!currentUserId) return;
            performAction('ban_user', currentUserId, 'User will be banned from the platform. This can be reversed using the unban function.');
            closeUserModal();
        }

        function unbanUser() {
            if (!currentUserId) return;
            performAction('unban_user', currentUserId, 'User will be unbanned and can post again. This can be reversed using the ban function.');
            closeUserModal();
        }

        function deleteUser() {
            if (!currentUserId) return;
            performAction('delete_user', currentUserId, 'User and all their content will be permanently deleted.', true);
            closeUserModal();
        }

        function deleteThread(threadId, threadTitle) {
            performAction('delete_thread', threadId, 'Thread "' + threadTitle + '" will be permanently deleted.', true);
        }

        function deletePost(postId) {
            performAction('delete_post', postId, 'This post will be permanently deleted.', true);
        }

        function performAction(action, id, message, isPermanent = false) {
            currentAction = action;
            currentId = id;
            
            const permanentWarning = isPermanent ? ' This action cannot be undone.' : '';
            document.getElementById('modalMessage').textContent = message + permanentWarning;
            document.getElementById('adminModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('adminModal').style.display = 'none';
            currentAction = null;
            currentId = null;
        }

        function confirmAction() {
            if (!currentAction || !currentId) return;

            const formData = new FormData();
            formData.append('action', currentAction);
            formData.append('id', currentId);

            fetch('/admin/actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                }
                return response.text();
            })
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        alert('Action completed successfully: ' + data.message);
                        location.reload(); // Refresh the page to show updated data
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    alert('Server error: Invalid response format. Check console for details.');
                }
            })
            .catch(error => {
                console.error('Network error:', error);
                alert('Network error: ' + error.message);
            });

            closeModal();
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const adminModal = document.getElementById('adminModal');
            const userModal = document.getElementById('userActionsModal');
            
            if (event.target === adminModal) {
                closeModal();
            }
            if (event.target === userModal) {
                closeUserModal();
            }
        }

        // Bulk Operations Functions
        function bulkDeleteThreads() {
            const category = document.getElementById('bulkThreadCategory').value;
            const date = document.getElementById('bulkThreadDate').value;
            
            let message = 'This will permanently delete ';
            if (category && date) {
                message += 'all threads in the selected category older than ' + date;
            } else if (category) {
                message += 'all threads in the selected category';
            } else if (date) {
                message += 'all threads older than ' + date;
            } else {
                message += 'ALL THREADS from the entire forum';
            }
            message += '. This action cannot be undone.';
            
            if (confirm(message)) {
                performBulkAction('bulk_delete_threads', { category, date });
            }
        }

        function clearChatChannel() {
            const channel = document.getElementById('bulkChatChannel').value;
            const date = document.getElementById('bulkChatDate').value;
            
            let message = 'This will permanently delete ';
            if (channel === 'all') {
                message += date ? 'all messages older than ' + date + ' from all channels' : 'all messages from all channels';
            } else {
                message += date ? 'all messages older than ' + date + ' from #' + channel : 'all messages from #' + channel;
            }
            message += '. This action cannot be undone.';
            
            if (confirm(message)) {
                performBulkAction('clear_chat_channel', { channel, date });
            }
        }

        function bulkUserAction() {
            const action = document.getElementById('bulkUserAction').value;
            const days = document.getElementById('bulkUserDays').value;
            const startDate = document.getElementById('bulkUserStartDate').value;
            const endDate = document.getElementById('bulkUserEndDate').value;
            
            if (!action) {
                alert('Please select an action.');
                return;
            }
            
            if (action === 'delete_date_range') {
                if (!startDate || !endDate) {
                    alert('Please specify both start and end dates for date range deletion.');
                    return;
                }
                
                let message = 'This will permanently delete all users registered between ' + startDate + ' and ' + endDate + '. This action cannot be undone.';
                
                if (confirm(message)) {
                    performBulkAction('bulk_user_action', { action, startDate, endDate });
                }
            } else {
                if (!days) {
                    alert('Please specify the number of days.');
                    return;
                }
                
                let message = 'This will ' + (action === 'ban_inactive' ? 'ban' : 'permanently delete') +
                             ' all users inactive for more than ' + days + ' days.';
                if (action === 'delete_inactive') {
                    message += ' This action cannot be undone.';
                }
                
                if (confirm(message)) {
                    performBulkAction('bulk_user_action', { action, days });
                }
            }
        }

        function ownerAction() {
            const action = document.getElementById('ownerAction').value;
            const targetUsername = document.getElementById('targetUsername').value;
            
            if (!action) {
                alert('Please select an owner action.');
                return;
            }
            
            if ((action === 'delete_admin' || action === 'hard_delete_user') && !targetUsername) {
                alert('Please specify the target username.');
                return;
            }
            
            let message = '';
            switch (action) {
                case 'delete_admin':
                    message = 'This will permanently delete the admin/owner user "' + targetUsername + '" and all their content. This action cannot be undone and requires owner access.';
                    break;
                case 'hard_delete_user':
                    message = 'This will permanently delete the user "' + targetUsername + '" and all their content from the database. This action cannot be undone.';
                    break;
                case 'purge_all_inactive':
                    message = 'This will permanently delete ALL inactive users and their content from the database. This action cannot be undone and may affect many users.';
                    break;
            }
            
            if (confirm(message + '\n\nAre you absolutely sure you want to proceed?')) {
                performBulkAction('owner_action', { action, targetUsername });
            }
        }

        // Show/hide date range controls based on selected action
        document.getElementById('bulkUserAction').addEventListener('change', function() {
            const dateRangeControls = document.getElementById('dateRangeControls');
            if (this.value === 'delete_date_range') {
                dateRangeControls.style.display = 'flex';
            } else {
                dateRangeControls.style.display = 'none';
            }
        });

        // Show/hide target username input based on selected owner action
        <?php if (isOwner()): ?>
        document.getElementById('ownerAction').addEventListener('change', function() {
            const targetControls = document.getElementById('ownerTarget');
            if (this.value === 'delete_admin' || this.value === 'hard_delete_user') {
                targetControls.style.display = 'flex';
            } else {
                targetControls.style.display = 'none';
            }
        });
        <?php endif; ?>

        function performBulkAction(action, params) {
            const formData = new FormData();
            formData.append('action', action);
            
            for (const [key, value] of Object.entries(params)) {
                if (value) formData.append(key, value);
            }

            fetch('/admin/actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                }
                return response.text();
            })
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        alert('Bulk operation completed successfully: ' + data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    alert('Server error: Invalid response format. Check console for details.');
                }
            })
            .catch(error => {
                console.error('Network error:', error);
                alert('Network error: ' + error.message);
            });
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

        .user-actions-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .user-actions-buttons .btn {
            flex: 1;
            min-width: 120px;
        }
        /* Bulk Operations Styles */
        .bulk-section {
            margin-bottom: 32px;
        }

        .bulk-actions {
            display: grid;
            gap: 16px;
        }

        .bulk-action-card {
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 12px;
            padding: 20px;
        }

        .bulk-action-card h5 {
            margin: 0 0 8px;
            color: var(--text);
            font-size: 16px;
        }

        .bulk-action-card p {
            margin: 0 0 16px;
            color: var(--text-dim);
            font-size: 14px;
        }

        .bulk-controls {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .bulk-select,
        .bulk-input {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(0,0,0,.3);
            color: var(--text);
            font-size: 14px;
            min-width: 150px;
        }

        .bulk-select:focus,
        .bulk-input:focus {
            outline: none;
            border-color: var(--crimson);
            box-shadow: 0 0 0 3px rgba(220,20,60,.1);
        }

        @media (max-width: 768px) {
            .bulk-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .bulk-select,
            .bulk-input {
                min-width: auto;
                width: 100%;
            }
        }
    </style>
</body>
</html>