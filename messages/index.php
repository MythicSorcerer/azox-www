<?php
require_once __DIR__ . '/../config/auth.php';

// Require login
requireLogin();

$currentUser = getCurrentUser();

// Get available channels
$channels = [
    'general' => 'General Chat',
    'pvp' => 'PvP Discussion',
    'trading' => 'Trading',
    'help' => 'Help & Support'
];

$currentChannel = $_GET['channel'] ?? 'general';
$dmUser = $_GET['dm'] ?? null;

// If it's a DM, validate the user exists
if ($dmUser) {
    $dmUserData = fetchRow("SELECT id, username FROM users WHERE username = ? AND is_active = 1", [$dmUser]);
    if (!$dmUserData) {
        $dmUser = null;
        $currentChannel = 'general';
    }
} elseif (!isset($channels[$currentChannel])) {
    $currentChannel = 'general';
}

// Get online users (active in last 15 minutes)
$onlineUsers = fetchAll("
    SELECT username, role, last_active
    FROM users 
    WHERE last_active > DATE_SUB(NOW(), INTERVAL 15 MINUTE) 
    AND is_active = 1
    ORDER BY role DESC, username ASC
");

$pageTitle = "Chat — Azox — Trial by Fate";
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

    <!-- Chat Container -->
    <main class="chat-container">
        <!-- Chat Sidebar -->
        <aside class="chat-sidebar">
            <!-- Channels -->
            <div class="chat-channels">
                <h3>Channels</h3>
                <div class="channel-list">
                    <?php foreach ($channels as $channelId => $channelName): ?>
                        <a href="?channel=<?= $channelId ?>"
                           class="channel-item <?= $channelId === $currentChannel && !$dmUser ? 'active' : '' ?>">
                            # <?= sanitizeOutput($channelName) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Direct Messages -->
            <div class="chat-channels">
                <h3>Direct Messages</h3>
                
                <!-- User Selector for Offline Messaging -->
                <div class="user-selector">
                    <div class="user-selector-header">
                        <span class="user-selector-title">Message User</span>
                    </div>
                    <button class="user-selector-toggle" id="userSelectorToggle">
                        📝 New DM
                    </button>
                    <div class="user-selector-dropdown" id="userSelectorDropdown">
                        <input type="text" class="user-search" id="userSearch" placeholder="Search users...">
                        <div class="user-dropdown-list" id="userDropdownList">
                            <!-- Users will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
                
                <div class="channel-list" id="dmList">
                    <?php if ($dmUser): ?>
                        <a href="?dm=<?= urlencode($dmUser) ?>" class="channel-item active">
                            @ <?= sanitizeOutput($dmUser) ?>
                        </a>
                    <?php endif; ?>
                    <!-- DM list will be populated by JavaScript -->
                </div>
            </div>

            <!-- Online Users -->
            <div class="online-users">
                <h3>Online (<?= count($onlineUsers) ?>)</h3>
                <div class="user-list" id="userList">
                    <?php foreach ($onlineUsers as $user): ?>
                        <?php if ($user['username'] !== $currentUser['username']): ?>
                            <div class="user-item" data-username="<?= sanitizeOutput($user['username']) ?>">
                                <div class="user-status <?= (strtotime($user['last_active']) > time() - 300) ? 'online' : 'away' ?>"></div>
                                <span class="user-name <?= $user['role'] === 'admin' || $user['role'] === 'owner' ? 'admin' : '' ?>"
                                      onclick="azoxChat.startDM('<?= sanitizeOutput($user['username']) ?>')"
                                      style="cursor: pointer;"
                                      title="Click to send direct message">
                                    <?= sanitizeOutput($user['username']) ?>
                                    <?php if ($user['role'] === 'admin' || $user['role'] === 'owner'): ?>
                                        <span style="color: var(--crimson); font-size: 10px;">●</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>

        <!-- Chat Main Area -->
        <div class="chat-main">
            <!-- Chat Header -->
            <div class="chat-header">
                <?php if ($dmUser): ?>
                    <h2>@ <?= sanitizeOutput($dmUser) ?></h2>
                    <div style="font-size: 14px; color: var(--text-dim);">
                        Direct Message
                    </div>
                <?php else: ?>
                    <h2># <?= sanitizeOutput($channels[$currentChannel]) ?></h2>
                    <div style="font-size: 14px; color: var(--text-dim);">
                        <?= count($onlineUsers) ?> users online
                    </div>
                <?php endif; ?>
            </div>

            <!-- Messages Area -->
            <div class="chat-messages" id="chatMessages">
                <!-- Messages will be loaded here via JavaScript -->
                <div data-loading style="text-align: center; padding: 40px; color: var(--text-dim);">
                    <?php if ($dmUser): ?>
                        <div style="font-size: 32px; margin-bottom: 16px;">💬</div>
                        <p>Direct message with <?= sanitizeOutput($dmUser) ?></p>
                        <p style="font-size: 14px;">Loading recent messages...</p>
                    <?php else: ?>
                        <div style="font-size: 32px; margin-bottom: 16px;">💬</div>
                        <p>Welcome to #<?= sanitizeOutput($channels[$currentChannel]) ?>!</p>
                        <p style="font-size: 14px;">Loading recent messages...</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Typing Indicator -->
            <div class="typing-indicator" id="typingIndicator" style="display: none;">
                <span id="typingText"></span>
            </div>

            <!-- Chat Input -->
            <?php if (!isBanned()): ?>
                <div class="chat-input">
                    <form class="chat-input-form" id="messageForm">
                        <textarea
                            class="chat-input-field"
                            id="messageInput"
                            placeholder="Type your message... (Press Enter to send, Shift+Enter for new line)"
                            rows="1"
                            maxlength="1000"
                        ></textarea>
                        <button type="submit" class="chat-send-btn" id="sendButton">
                            Send
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="chat-input" style="background: rgba(220,20,60,.1); border: 1px solid rgba(220,20,60,.3);">
                    <div style="padding: 16px; text-align: center; color: var(--crimson);">
                        🚫 You are banned from sending messages in chat.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-inner">
            <p>&copy; 2025 Azox Network</p>
        </div>
    </footer>

    <script>
        // Chat functionality
        class AzoxChat {
            constructor() {
                this.currentChannel = '<?= $currentChannel ?>';
                this.dmUser = <?= $dmUser ? "'" . sanitizeOutput($dmUser) . "'" : 'null' ?>;
                this.currentUser = {
                    id: <?= $currentUser['id'] ?>,
                    username: '<?= sanitizeOutput($currentUser['username']) ?>',
                    role: '<?= $currentUser['role'] ?>'
                };
                this.messages = [];
                this.lastMessageId = 0;
                this.typingUsers = new Set();
                this.typingTimeout = null;
                this.lastDMCheck = new Date().toISOString();
                this.allUsers = [];
                this.filteredUsers = [];
                this.notificationSound = null;
                
                this.initializeElements();
                this.bindEvents();
                this.loadMessages();
                this.loadAllUsers();
                this.startPolling();
                this.initializeNotificationSound();
            }

            initializeElements() {
                this.messagesContainer = document.getElementById('chatMessages');
                this.messageForm = document.getElementById('messageForm');
                this.messageInput = document.getElementById('messageInput');
                this.sendButton = document.getElementById('sendButton');
                this.typingIndicator = document.getElementById('typingIndicator');
                this.typingText = document.getElementById('typingText');
                
                // User selector elements
                this.userSelectorToggle = document.getElementById('userSelectorToggle');
                this.userSelectorDropdown = document.getElementById('userSelectorDropdown');
                this.userSearch = document.getElementById('userSearch');
                this.userDropdownList = document.getElementById('userDropdownList');
            }

            bindEvents() {
                // Form submission
                this.messageForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.sendMessage();
                });

                // Enter key handling
                this.messageInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        this.sendMessage();
                    }
                });

                // Auto-resize textarea
                this.messageInput.addEventListener('input', () => {
                    this.messageInput.style.height = 'auto';
                    this.messageInput.style.height = Math.min(this.messageInput.scrollHeight, 120) + 'px';
                    
                    // Handle typing indicator
                    this.handleTyping();
                });

                // User selector events
                this.userSelectorToggle.addEventListener('click', () => {
                    this.toggleUserSelector();
                });

                this.userSearch.addEventListener('input', (e) => {
                    this.filterUsers(e.target.value);
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', (e) => {
                    if (!e.target.closest('.user-selector')) {
                        this.closeUserSelector();
                    }
                });

                // Update user activity
                setInterval(() => {
                    this.updateActivity();
                }, 60000); // Every minute
            }

            async loadMessages() {
                try {
                    let url = `api.php?action=get_messages&after=${this.lastMessageId}`;
                    if (this.dmUser) {
                        url += `&dm_user=${encodeURIComponent(this.dmUser)}`;
                    } else {
                        url += `&channel=${this.currentChannel}`;
                    }
                    
                    const response = await fetch(url);
                    const data = await response.json();
                    
                    if (data.success) {
                        this.displayMessages(data.messages);
                        if (data.messages.length > 0) {
                            this.lastMessageId = Math.max(...data.messages.map(m => m.id));
                        }
                    }
                } catch (error) {
                    console.error('Failed to load messages:', error);
                }
            }

            async sendMessage() {
                const content = this.messageInput.value.trim();
                if (!content) return;

                this.sendButton.disabled = true;
                this.messageInput.disabled = true;

                try {
                    const response = await fetch('api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'send_message',
                            channel: this.dmUser ? null : this.currentChannel,
                            dm_user: this.dmUser,
                            content: content
                        })
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        this.messageInput.value = '';
                        this.messageInput.style.height = 'auto';
                        this.loadMessages(); // Reload to get the new message
                    } else {
                        alert('Failed to send message: ' + (data.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Failed to send message:', error);
                    alert('Failed to send message. Please try again.');
                } finally {
                    this.sendButton.disabled = false;
                    this.messageInput.disabled = false;
                    this.messageInput.focus();
                }
            }

            displayMessages(messages) {
                if (messages.length === 0) return;

                const shouldScrollToBottom = this.isScrolledToBottom();

                messages.forEach(message => {
                    if (this.messages.find(m => m.id === message.id)) return; // Skip duplicates
                    
                    this.messages.push(message);
                    this.addMessageToDOM(message);
                });

                if (shouldScrollToBottom) {
                    this.scrollToBottom();
                }
            }

            addMessageToDOM(message) {
                const messageEl = document.createElement('div');
                messageEl.className = `message ${message.message_type}`;
                messageEl.dataset.messageId = message.id;

                if (message.message_type === 'system') {
                    messageEl.innerHTML = `
                        <div class="message-content">
                            <div style="text-align: center; font-style: italic; color: var(--text-dim); font-size: 13px;">
                                ${this.escapeHtml(message.content)}
                            </div>
                        </div>
                    `;
                } else {
                    const avatar = message.author_name.substring(0, 2).toUpperCase();
                    const isAdmin = message.author_role === 'admin';
                    const time = new Date(message.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    const canDelete = (message.sender_id == this.currentUser.id) || (this.currentUser.role === 'admin');

                    messageEl.innerHTML = `
                        <div class="message-avatar">${avatar}</div>
                        <div class="message-content">
                            <div class="message-header">
                                <span class="message-author" style="${isAdmin ? 'color: var(--crimson);' : ''}">${this.escapeHtml(message.author_name)}</span>
                                ${isAdmin ? '<span style="color: var(--crimson); font-size: 10px; margin-left: 4px;">●</span>' : ''}
                                <span class="message-time">${time}</span>
                                ${canDelete ? `<button class="message-delete-btn" onclick="azoxChat.deleteMessage(${message.id})" title="Delete message" style="opacity: 0; transition: opacity 0.2s ease;">🗑️</button>` : ''}
                            </div>
                            <div class="message-text">${this.formatMessage(message.content)}</div>
                        </div>
                    `;
                }

                // Remove loading message if it exists
                const loadingMsg = this.messagesContainer.querySelector('[data-loading]');
                if (loadingMsg) {
                    loadingMsg.remove();
                }

                this.messagesContainer.appendChild(messageEl);
                
                // Add hover event listeners for delete button visibility
                this.setupDeleteButtonHover(messageEl);
            }

            formatMessage(content) {
                // Basic message formatting
                return this.escapeHtml(content)
                    .replace(/\n/g, '<br>')
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.*?)\*/g, '<em>$1</em>')
                    .replace(/`([^`]+)`/g, '<code style="background: rgba(255,255,255,.1); padding: 2px 4px; border-radius: 3px; font-family: monospace;">$1</code>');
            }

            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            isScrolledToBottom() {
                const threshold = 100;
                return this.messagesContainer.scrollTop + this.messagesContainer.clientHeight >= 
                       this.messagesContainer.scrollHeight - threshold;
            }

            scrollToBottom() {
                this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
            }

            handleTyping() {
                // Clear existing timeout
                if (this.typingTimeout) {
                    clearTimeout(this.typingTimeout);
                }

                // Set new timeout to stop typing indicator
                this.typingTimeout = setTimeout(() => {
                    // Send stop typing indicator
                }, 3000);
            }

            async updateActivity() {
                try {
                    await fetch('api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'update_activity'
                        })
                    });
                } catch (error) {
                    console.error('Failed to update activity:', error);
                }
            }

            startPolling() {
                // Poll for new messages every 2 seconds
                setInterval(() => {
                    this.loadMessages();
                }, 2000);

                // Check for new DMs every 3 seconds (only when not in DM view)
                setInterval(() => {
                    if (!this.dmUser) {
                        this.checkNewDMs();
                    }
                }, 3000);

                // Update online users every 30 seconds
                setInterval(() => {
                    this.updateOnlineUsers();
                }, 30000);
            }

            async updateOnlineUsers() {
                try {
                    const response = await fetch('api.php?action=get_online_users');
                    const data = await response.json();
                    
                    if (data.success) {
                        this.displayOnlineUsers(data.users);
                    }
                } catch (error) {
                    console.error('Failed to update online users:', error);
                }
            }

            displayOnlineUsers(users) {
                const userList = document.getElementById('userList');
                if (!userList) return;

                userList.innerHTML = '';
                users.forEach(user => {
                    // Skip current user
                    if (user.username === this.currentUser.username) return;
                    
                    const userEl = document.createElement('div');
                    userEl.className = 'user-item';
                    userEl.dataset.username = user.username;
                    
                    const isRecent = new Date(user.last_active).getTime() > Date.now() - 300000; // 5 minutes
                    const statusClass = isRecent ? 'online' : 'away';
                    const isAdmin = user.role === 'admin' || user.role === 'owner';
                    
                    userEl.innerHTML = `
                        <div class="user-status ${statusClass}"></div>
                        <span class="user-name ${isAdmin ? 'admin' : ''}"
                              onclick="azoxChat.startDM('${this.escapeHtml(user.username)}')"
                              style="cursor: pointer;"
                              title="Click to send direct message">
                            ${this.escapeHtml(user.username)}
                            ${isAdmin ? '<span style="color: var(--crimson); font-size: 10px;">●</span>' : ''}
                        </span>
                    `;
                    
                    userList.appendChild(userEl);
                });

                // Update header count (only if not in DM mode)
                if (!this.dmUser) {
                    const header = document.querySelector('.chat-header h2');
                    if (header) {
                        header.nextElementSibling.textContent = `${users.length} users online`;
                    }
                }
            }

            async deleteMessage(messageId) {
                if (!confirm('Are you sure you want to delete this message? This action cannot be undone.')) {
                    return;
                }

                try {
                    const response = await fetch('api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'delete_message',
                            message_id: messageId
                        })
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        // Remove the message from DOM
                        const messageEl = document.querySelector(`[data-message-id="${messageId}"]`);
                        if (messageEl) {
                            messageEl.style.opacity = '0.5';
                            messageEl.style.transition = 'opacity 0.3s ease';
                            setTimeout(() => {
                                messageEl.remove();
                            }, 300);
                        }
                        
                        // Remove from messages array
                        this.messages = this.messages.filter(m => m.id !== messageId);
                    } else {
                        alert('Failed to delete message: ' + (data.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Failed to delete message:', error);
                    alert('Failed to delete message. Please try again.');
                }
            }

            setupDeleteButtonHover(messageEl) {
                const deleteBtn = messageEl.querySelector('.message-delete-btn');
                if (!deleteBtn) return;

                messageEl.addEventListener('mouseenter', () => {
                    deleteBtn.style.opacity = '1';
                });

                messageEl.addEventListener('mouseleave', () => {
                    deleteBtn.style.opacity = '0';
                });
            }

            startDM(username) {
                if (username === this.currentUser.username) return;
                window.location.href = `?dm=${encodeURIComponent(username)}`;
            }

            // New methods for enhanced DM functionality

            async loadAllUsers() {
                try {
                    const response = await fetch('api.php?action=get_all_users');
                    const data = await response.json();
                    
                    if (data.success) {
                        this.allUsers = data.users;
                        this.filteredUsers = [...this.allUsers];
                        this.renderUserDropdown();
                    }
                } catch (error) {
                    console.error('Failed to load users:', error);
                }
            }

            toggleUserSelector() {
                const isActive = this.userSelectorDropdown.classList.contains('active');
                if (isActive) {
                    this.closeUserSelector();
                } else {
                    this.openUserSelector();
                }
            }

            openUserSelector() {
                this.userSelectorDropdown.classList.add('active');
                this.userSearch.focus();
                this.loadAllUsers(); // Refresh user list
            }

            closeUserSelector() {
                this.userSelectorDropdown.classList.remove('active');
                this.userSearch.value = '';
                this.filteredUsers = [...this.allUsers];
                this.renderUserDropdown();
            }

            filterUsers(searchTerm) {
                const term = searchTerm.toLowerCase().trim();
                this.filteredUsers = this.allUsers.filter(user =>
                    user.username.toLowerCase().includes(term)
                );
                this.renderUserDropdown();
            }

            renderUserDropdown() {
                if (!this.userDropdownList) return;

                if (this.filteredUsers.length === 0) {
                    this.userDropdownList.innerHTML = `
                        <div class="user-dropdown-empty">
                            ${this.allUsers.length === 0 ? 'Loading users...' : 'No users found'}
                        </div>
                    `;
                    return;
                }

                this.userDropdownList.innerHTML = this.filteredUsers.map(user => `
                    <div class="user-dropdown-item" onclick="azoxChat.selectUser('${this.escapeHtml(user.username)}')">
                        <div class="user-dropdown-status ${user.is_online ? 'online' : ''}"></div>
                        <span class="user-dropdown-name ${user.role === 'admin' ? 'admin' : ''}">
                            ${this.escapeHtml(user.username)}
                        </span>
                        <span class="user-dropdown-role">
                            ${user.is_online ? 'online' : 'offline'}
                            ${user.role === 'admin' ? ' • admin' : ''}
                        </span>
                    </div>
                `).join('');
            }

            selectUser(username) {
                this.closeUserSelector();
                this.startDM(username);
            }

            async checkNewDMs() {
                try {
                    const response = await fetch(`api.php?action=check_new_dms&last_check=${encodeURIComponent(this.lastDMCheck)}`);
                    const data = await response.json();
                    
                    if (data.success && data.new_dms.length > 0) {
                        data.new_dms.forEach(dm => {
                            this.showDMNotification(dm);
                        });
                        this.lastDMCheck = new Date().toISOString();
                    }
                } catch (error) {
                    console.error('Failed to check for new DMs:', error);
                }
            }

            showDMNotification(dm) {
                // Don't show notification if we're already in DM with this user
                if (this.dmUser === dm.sender_name) return;

                const notification = document.createElement('div');
                notification.className = 'dm-notification';
                notification.onclick = () => {
                    this.startDM(dm.sender_name);
                    this.closeDMNotification(notification);
                };

                const preview = dm.content.length > 50 ? dm.content.substring(0, 50) + '...' : dm.content;
                
                notification.innerHTML = `
                    <button class="dm-notification-close" onclick="event.stopPropagation(); azoxChat.closeDMNotification(this.parentElement)">×</button>
                    <div class="dm-notification-header">
                        <span class="dm-notification-icon">💬</span>
                        <span class="dm-notification-title">New Direct Message</span>
                    </div>
                    <div class="dm-notification-sender">
                        From: ${this.escapeHtml(dm.sender_name)}
                        ${dm.sender_role === 'admin' ? '<span style="color: var(--crimson); font-size: 10px; margin-left: 4px;">●</span>' : ''}
                    </div>
                    <div class="dm-notification-content">
                        ${this.escapeHtml(preview)}
                    </div>
                `;

                document.body.appendChild(notification);

                // Play notification sound
                this.playNotificationSound();

                // Auto-remove after 8 seconds
                setTimeout(() => {
                    if (notification.parentElement) {
                        this.closeDMNotification(notification);
                    }
                }, 8000);
            }

            closeDMNotification(notification) {
                notification.style.animation = 'slideOutRight 0.3s ease-in';
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 300);
            }

            initializeNotificationSound() {
                // Create a simple notification sound using Web Audio API
                try {
                    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    this.notificationSound = audioContext;
                } catch (error) {
                    console.log('Web Audio API not supported');
                }
            }

            playNotificationSound() {
                if (!this.notificationSound) return;

                try {
                    const oscillator = this.notificationSound.createOscillator();
                    const gainNode = this.notificationSound.createGain();
                    
                    oscillator.connect(gainNode);
                    gainNode.connect(this.notificationSound.destination);
                    
                    oscillator.frequency.setValueAtTime(800, this.notificationSound.currentTime);
                    oscillator.frequency.setValueAtTime(600, this.notificationSound.currentTime + 0.1);
                    
                    gainNode.gain.setValueAtTime(0.1, this.notificationSound.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, this.notificationSound.currentTime + 0.2);
                    
                    oscillator.start(this.notificationSound.currentTime);
                    oscillator.stop(this.notificationSound.currentTime + 0.2);
                } catch (error) {
                    console.log('Could not play notification sound');
                }
            }
        }

        // Initialize chat when page loads
        document.addEventListener('DOMContentLoaded', () => {
            window.azoxChat = new AzoxChat();
        });
    </script>
</body>
</html>