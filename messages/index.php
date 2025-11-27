<?php
require_once __DIR__ . '/../config/auth.php';

// Require login
requireLogin();

$currentUser = getCurrentUser();

// Get available channels (for now, just general)
$channels = [
    'general' => 'General Chat',
    'pvp' => 'PvP Discussion',
    'trading' => 'Trading',
    'help' => 'Help & Support'
];

$currentChannel = $_GET['channel'] ?? 'general';
if (!isset($channels[$currentChannel])) {
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
                           class="channel-item <?= $channelId === $currentChannel ? 'active' : '' ?>">
                            # <?= sanitizeOutput($channelName) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Online Users -->
            <div class="online-users">
                <h3>Online (<?= count($onlineUsers) ?>)</h3>
                <div class="user-list" id="userList">
                    <?php foreach ($onlineUsers as $user): ?>
                        <div class="user-item">
                            <div class="user-status <?= (strtotime($user['last_active']) > time() - 300) ? 'online' : 'away' ?>"></div>
                            <span class="user-name <?= $user['role'] === 'admin' ? 'admin' : '' ?>">
                                <?= sanitizeOutput($user['username']) ?>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span style="color: var(--crimson); font-size: 10px;">●</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>

        <!-- Chat Main Area -->
        <div class="chat-main">
            <!-- Chat Header -->
            <div class="chat-header">
                <h2># <?= sanitizeOutput($channels[$currentChannel]) ?></h2>
                <div style="font-size: 14px; color: var(--text-dim);">
                    <?= count($onlineUsers) ?> users online
                </div>
            </div>

            <!-- Messages Area -->
            <div class="chat-messages" id="chatMessages">
                <!-- Messages will be loaded here via JavaScript -->
                <div style="text-align: center; padding: 40px; color: var(--text-dim);">
                    <div style="font-size: 32px; margin-bottom: 16px;">💬</div>
                    <p>Welcome to #<?= sanitizeOutput($channels[$currentChannel]) ?>!</p>
                    <p style="font-size: 14px;">Loading recent messages...</p>
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
                this.currentUser = {
                    id: <?= $currentUser['id'] ?>,
                    username: '<?= sanitizeOutput($currentUser['username']) ?>',
                    role: '<?= $currentUser['role'] ?>'
                };
                this.messages = [];
                this.lastMessageId = 0;
                this.typingUsers = new Set();
                this.typingTimeout = null;
                
                this.initializeElements();
                this.bindEvents();
                this.loadMessages();
                this.startPolling();
            }

            initializeElements() {
                this.messagesContainer = document.getElementById('chatMessages');
                this.messageForm = document.getElementById('messageForm');
                this.messageInput = document.getElementById('messageInput');
                this.sendButton = document.getElementById('sendButton');
                this.typingIndicator = document.getElementById('typingIndicator');
                this.typingText = document.getElementById('typingText');
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

                // Update user activity
                setInterval(() => {
                    this.updateActivity();
                }, 60000); // Every minute
            }

            async loadMessages() {
                try {
                    const response = await fetch(`api.php?action=get_messages&channel=${this.currentChannel}&after=${this.lastMessageId}`);
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
                            channel: this.currentChannel,
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
                    const userEl = document.createElement('div');
                    userEl.className = 'user-item';
                    
                    const isRecent = new Date(user.last_active).getTime() > Date.now() - 300000; // 5 minutes
                    const statusClass = isRecent ? 'online' : 'away';
                    const isAdmin = user.role === 'admin';
                    
                    userEl.innerHTML = `
                        <div class="user-status ${statusClass}"></div>
                        <span class="user-name ${isAdmin ? 'admin' : ''}">
                            ${this.escapeHtml(user.username)}
                            ${isAdmin ? '<span style="color: var(--crimson); font-size: 10px;">●</span>' : ''}
                        </span>
                    `;
                    
                    userList.appendChild(userEl);
                });

                // Update header count
                const header = document.querySelector('.chat-header h2');
                if (header) {
                    const channelName = header.textContent.split(' ')[1];
                    header.nextElementSibling.textContent = `${users.length} users online`;
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
        }

        // Initialize chat when page loads
        document.addEventListener('DOMContentLoaded', () => {
            window.azoxChat = new AzoxChat();
        });
    </script>
</body>
</html>