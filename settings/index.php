<?php
require_once __DIR__ . '/../config/auth.php';

// Require login
requireLogin();

$currentUser = getCurrentUser();
$pageTitle = "Account Settings — Azox — Trial by Fate";
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

    <!-- Settings Container -->
    <main class="container">
        <div class="hero-section">
            <div class="eyebrow"><span class="dot"></span>Account Management</div>
            <h1>Account Settings</h1>
            <p>Manage your account information, security settings, and preferences.</p>
        </div>

        <!-- Settings Sections -->
        <div class="settings-container">
            <!-- Account Information -->
            <div class="settings-section">
                <h2>Account Information</h2>
                <div class="settings-card">
                    <div class="setting-item">
                        <label>Username</label>
                        <div class="setting-value">
                            <strong><?= sanitizeOutput($currentUser['username']) ?></strong>
                            <small>Username cannot be changed</small>
                        </div>
                    </div>
                    <div class="setting-item">
                        <label>Email Address</label>
                        <div class="setting-value">
                            <span id="currentEmail"><?= sanitizeOutput($currentUser['email']) ?></span>
                            <button class="btn ghost" onclick="showChangeEmail()">Change Email</button>
                        </div>
                    </div>
                    <div class="setting-item">
                        <label>Account Role</label>
                        <div class="setting-value">
                            <span class="role-badge <?= $currentUser['role'] ?>">
                                <?= ucfirst($currentUser['role']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="setting-item">
                        <label>Member Since</label>
                        <div class="setting-value">
                            <?= date('F j, Y', strtotime($currentUser['created_at'])) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="settings-section">
                <h2>Security</h2>
                <div class="settings-card">
                    <div class="setting-item">
                        <label>Password</label>
                        <div class="setting-value">
                            <span>••••••••••••</span>
                            <button class="btn ghost" onclick="showChangePassword()">Change Password</button>
                        </div>
                    </div>
                    <div class="setting-item">
                        <label>Last Login</label>
                        <div class="setting-value">
                            <?php
                            $lastActive = strtotime($currentUser['last_active']);
                            $now = time();
                            $diff = $now - $lastActive;
                            
                            if ($diff < 300) echo 'Currently active';
                            elseif ($diff < 3600) echo floor($diff/60) . ' minutes ago';
                            elseif ($diff < 86400) echo floor($diff/3600) . ' hours ago';
                            else echo floor($diff/86400) . ' days ago';
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="settings-section danger-zone">
                <h2>Danger Zone</h2>
                <div class="settings-card danger-card">
                    <div class="setting-item">
                        <div class="danger-content">
                            <h3>Delete Account</h3>
                            <p>Permanently delete your account and all associated data. This action cannot be undone.</p>
                        </div>
                        <button class="btn danger" onclick="showDeleteAccount()">Delete Account</button>
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

    <!-- Change Email Modal -->
    <div id="emailModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Change Email Address</h3>
                <button class="modal-close" onclick="closeEmailModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="emailForm" class="settings-form">
                    <div class="form-group">
                        <label for="currentPassword">Current Password</label>
                        <input type="password" id="currentPasswordEmail" name="currentPassword" required>
                        <small>Enter your current password to confirm this change</small>
                    </div>
                    <div class="form-group">
                        <label for="newEmail">New Email Address</label>
                        <input type="email" id="newEmail" name="newEmail" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn ghost" onclick="closeEmailModal()">Cancel</button>
                <button class="btn primary" onclick="changeEmail()">Update Email</button>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="passwordModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Change Password</h3>
                <button class="modal-close" onclick="closePasswordModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="passwordForm" class="settings-form">
                    <div class="form-group">
                        <label for="currentPasswordChange">Current Password</label>
                        <input type="password" id="currentPasswordChange" name="currentPassword" required>
                    </div>
                    <div class="form-group">
                        <label for="newPassword">New Password</label>
                        <input type="password" id="newPassword" name="newPassword" required minlength="8">
                        <small>Password must be at least 8 characters long</small>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirm New Password</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn ghost" onclick="closePasswordModal()">Cancel</button>
                <button class="btn primary" onclick="changePassword()">Update Password</button>
            </div>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div id="deleteModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete Account</h3>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="danger-warning">
                    <h4>⚠️ This action is permanent and cannot be undone!</h4>
                    <p>Deleting your account will:</p>
                    <ul>
                        <li>Permanently delete your user profile</li>
                        <li>Remove all your forum posts and threads</li>
                        <li>Delete all your chat messages</li>
                        <li>Remove you from all notifications</li>
                        <li>Make your username available for others to use</li>
                    </ul>
                </div>
                <form id="deleteForm" class="settings-form">
                    <div class="form-group">
                        <label for="deletePassword">Enter your password to confirm</label>
                        <input type="password" id="deletePassword" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="deleteConfirm">Type "DELETE" to confirm</label>
                        <input type="text" id="deleteConfirm" name="confirm" required placeholder="DELETE">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn ghost" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn danger" onclick="deleteAccount()">Delete My Account</button>
            </div>
        </div>
    </div>

    <style>
        /* Settings Styles */
        .settings-container {
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 32px;
        }

        .settings-section {
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 12px;
            overflow: hidden;
        }

        .settings-section h2 {
            margin: 0;
            padding: 20px 24px;
            background: rgba(255,255,255,.02);
            border-bottom: 1px solid rgba(255,255,255,.08);
            font-size: 18px;
            font-weight: 700;
            color: var(--text);
        }

        .settings-card {
            padding: 24px;
        }

        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 16px 0;
            border-bottom: 1px solid rgba(255,255,255,.06);
        }

        .setting-item:last-child {
            border-bottom: none;
        }

        .setting-item label {
            font-weight: 600;
            color: var(--text);
            margin-bottom: 4px;
            min-width: 120px;
        }

        .setting-value {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 8px;
            flex: 1;
            text-align: right;
        }

        .setting-value small {
            color: var(--text-dim);
            font-size: 12px;
        }

        .role-badge {
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .role-badge.admin {
            background: rgba(220,20,60,.2);
            color: var(--crimson);
        }

        .role-badge.user {
            background: rgba(255,255,255,.1);
            color: var(--text-dim);
        }

        /* Danger Zone */
        .danger-zone {
            border-color: rgba(220,20,60,.3);
        }

        .danger-zone h2 {
            color: var(--crimson);
            background: rgba(220,20,60,.1);
            border-bottom-color: rgba(220,20,60,.2);
        }

        .danger-card {
            background: rgba(220,20,60,.05);
        }

        .danger-content h3 {
            margin: 0 0 8px;
            color: var(--crimson);
            font-size: 16px;
        }

        .danger-content p {
            margin: 0;
            color: var(--text-dim);
            font-size: 14px;
            line-height: 1.4;
        }

        .btn.danger {
            background: var(--crimson);
            color: white;
            border-color: var(--crimson-2);
        }

        .btn.danger:hover {
            background: var(--crimson-2);
        }

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

        .modal-footer {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding: 20px 24px;
            border-top: 1px solid rgba(255,255,255,.08);
        }

        .settings-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .settings-form .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .settings-form label {
            font-weight: 600;
            color: var(--text);
            font-size: 14px;
        }

        .settings-form input {
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,.12);
            background: rgba(0,0,0,.3);
            color: var(--text);
            font-size: 16px;
        }

        .settings-form input:focus {
            outline: none;
            border-color: var(--crimson);
            box-shadow: 0 0 0 3px rgba(220,20,60,.1);
        }

        .settings-form small {
            color: var(--text-dim);
            font-size: 12px;
        }

        .danger-warning {
            background: rgba(220,20,60,.1);
            border: 1px solid rgba(220,20,60,.3);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .danger-warning h4 {
            margin: 0 0 12px;
            color: var(--crimson);
            font-size: 16px;
        }

        .danger-warning p {
            margin: 0 0 12px;
            color: var(--text-dim);
        }

        .danger-warning ul {
            margin: 0;
            padding-left: 20px;
            color: var(--text-dim);
        }

        .danger-warning li {
            margin-bottom: 4px;
        }

        @media (max-width: 768px) {
            .setting-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .setting-value {
                align-items: flex-start;
                text-align: left;
                width: 100%;
            }
        }
    </style>

    <script>
        function showChangeEmail() {
            document.getElementById('emailModal').style.display = 'flex';
        }

        function closeEmailModal() {
            document.getElementById('emailModal').style.display = 'none';
            document.getElementById('emailForm').reset();
        }

        function showChangePassword() {
            document.getElementById('passwordModal').style.display = 'flex';
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
            document.getElementById('passwordForm').reset();
        }

        function showDeleteAccount() {
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            document.getElementById('deleteForm').reset();
        }

        async function changeEmail() {
            const currentPassword = document.getElementById('currentPasswordEmail').value;
            const newEmail = document.getElementById('newEmail').value;

            if (!currentPassword || !newEmail) {
                alert('Please fill in all fields');
                return;
            }

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'change_email',
                        currentPassword: currentPassword,
                        newEmail: newEmail
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    alert('Email address updated successfully');
                    document.getElementById('currentEmail').textContent = newEmail;
                    closeEmailModal();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to update email address. Please try again.');
            }
        }

        async function changePassword() {
            const currentPassword = document.getElementById('currentPasswordChange').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (!currentPassword || !newPassword || !confirmPassword) {
                alert('Please fill in all fields');
                return;
            }

            if (newPassword !== confirmPassword) {
                alert('New passwords do not match');
                return;
            }

            if (newPassword.length < 8) {
                alert('Password must be at least 8 characters long');
                return;
            }

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'change_password',
                        currentPassword: currentPassword,
                        newPassword: newPassword
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    alert('Password updated successfully');
                    closePasswordModal();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to update password. Please try again.');
            }
        }

        async function deleteAccount() {
            const password = document.getElementById('deletePassword').value;
            const confirm = document.getElementById('deleteConfirm').value;

            if (!password || !confirm) {
                alert('Please fill in all fields');
                return;
            }

            if (confirm !== 'DELETE') {
                alert('Please type "DELETE" to confirm account deletion');
                return;
            }

            if (!window.confirm('Are you absolutely sure you want to delete your account? This action cannot be undone!')) {
                return;
            }

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'delete_account',
                        password: password
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    alert('Account deleted successfully. You will be redirected to the homepage.');
                    window.location.href = '/';
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to delete account. Please try again.');
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const emailModal = document.getElementById('emailModal');
            const passwordModal = document.getElementById('passwordModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target === emailModal) {
                closeEmailModal();
            }
            if (event.target === passwordModal) {
                closePasswordModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>