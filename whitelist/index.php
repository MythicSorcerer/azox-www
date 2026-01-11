<?php
session_start();

// ==================== CONFIGURATION ====================
define('DB_HOST', 'localhost');
define('DB_NAME', 'azox_network');
define('DB_USER', 'azox_user');
define('DB_PASS', 'ribNed-9gugmo-pejtav');

define('RCON_HOST', '127.0.0.1');
define('RCON_PORT', 25575);
define('RCON_PASSWORD', 'FdC2SD1CSDPnhRSuRT3FI0feh');

define('ACCESS_CODE', 'azox2026'); // Change this to your secret code

// ==================== DATABASE SETUP ====================
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed");
}

// Create table
$pdo->exec("CREATE TABLE IF NOT EXISTS whitelist_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    minecraft_username VARCHAR(16) NOT NULL,
    who_you_are TEXT NOT NULL,
    invitee VARCHAR(16),
    ip_address VARCHAR(45),
    request_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_username (minecraft_username)
)");

// ==================== RCON CLASS ====================
class MinecraftRCON {
    private $socket;
    private $reqId = 0;
    
    public function connect($host, $port, $password) {
        $this->socket = @fsockopen($host, $port, $errno, $errstr, 3);
        if (!$this->socket) return false;
        
        stream_set_timeout($this->socket, 3);
        return $this->auth($password);
    }
    
    private function auth($password) {
        $this->writePacket(3, $password);
        $response = $this->readPacket();
        return $response['id'] !== -1;
    }
    
    public function command($cmd) {
        $this->writePacket(2, $cmd);
        $response = $this->readPacket();
        return $response['payload'];
    }
    
    private function writePacket($type, $payload) {
        $id = ++$this->reqId;
        $data = pack('VV', $id, $type) . $payload . "\x00\x00";
        $packet = pack('V', strlen($data)) . $data;
        fwrite($this->socket, $packet);
    }
    
    private function readPacket() {
        $size = unpack('V', fread($this->socket, 4))[1];
        $data = fread($this->socket, $size);
        $packet = unpack('Vid/Vtype', substr($data, 0, 8));
        $packet['payload'] = substr($data, 8, -2);
        return $packet;
    }
    
    public function disconnect() {
        if ($this->socket) fclose($this->socket);
    }
}

// ==================== FUNCTIONS ====================
function addToWhitelist($username) {
    $rcon = new MinecraftRCON();
    if (!$rcon->connect(RCON_HOST, RCON_PORT, RCON_PASSWORD)) {
        return ['success' => false, 'error' => 'RCON connection failed'];
    }
    
    // Add to whitelist
    $result = $rcon->command("whitelist add " . $username);
    
    // Announce in chat
    $rcon->command("say [whitelist] added " . $username . " to whitelist");
    
    $rcon->disconnect();
    
    return ['success' => true, 'result' => $result];
}

// ==================== HANDLE FORM SUBMISSION ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $username = trim($_POST['username'] ?? '');
    $whoYouAre = trim($_POST['who_you_are'] ?? '');
    $accessCode = trim($_POST['access_code'] ?? '');
    $invitee = trim($_POST['invitee'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Validation
    if (empty($username)) {
        echo json_encode(['success' => false, 'message' => 'Username is required']);
        exit;
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]{3,16}$/', $username)) {
        echo json_encode(['success' => false, 'message' => 'Invalid username format (3-16 characters, letters, numbers, underscores only)']);
        exit;
    }
    
    if (empty($whoYouAre)) {
        echo json_encode(['success' => false, 'message' => 'Please tell us who you are']);
        exit;
    }
    
    if (strlen($whoYouAre) > 500) {
        echo json_encode(['success' => false, 'message' => 'Who you are is too long (max 500 characters)']);
        exit;
    }
    
    if (empty($accessCode)) {
        echo json_encode(['success' => false, 'message' => 'Access code is required']);
        exit;
    }
    
    if ($accessCode !== ACCESS_CODE) {
        echo json_encode(['success' => false, 'message' => 'Invalid access code']);
        exit;
    }
    
    if (!empty($invitee) && !preg_match('/^[a-zA-Z0-9_]{3,16}$/', $invitee)) {
        echo json_encode(['success' => false, 'message' => 'Invalid invitee username format']);
        exit;
    }
    
    // Rate limiting - 3 requests per IP per hour
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM whitelist_requests 
                          WHERE ip_address = ? AND request_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->execute([$ip]);
    if ($stmt->fetchColumn() >= 3) {
        echo json_encode(['success' => false, 'message' => 'Too many requests. Please try again later.']);
        exit;
    }
    
    try {
        // Insert into database
        $stmt = $pdo->prepare("INSERT INTO whitelist_requests 
                              (minecraft_username, who_you_are, invitee, ip_address) 
                              VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $username,
            $whoYouAre,
            $invitee ?: null,
            $ip
        ]);
        
        // Add to whitelist via RCON
        $rconResult = addToWhitelist($username);
        
        if ($rconResult['success']) {
            echo json_encode([
                'success' => true, 
                'message' => '✅ Success! You have been whitelisted and can now join the server.'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Access code accepted but RCON failed. Please contact an admin. Error: ' . $rconResult['error']
            ]);
        }
        
    } catch(PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'message' => 'This username has already been whitelisted']);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'System error: ' . $e->getMessage() . ' (Code: ' . $e->getCode() . ')'
            ]);
        }
    }
    exit;
}

// ==================== INCLUDE NAV (only for GET requests) ====================
include __DIR__ . '/../includes/nav.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minecraft Whitelist Request</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .message {
            display: none;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            font-weight: 500;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .hint {
            font-size: 0.85em;
            color: #666;
            margin-top: 5px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn.primary {
            background: #007bff;
            color: white;
        }
        .btn.primary:hover {
            background: #0056b3;
        }
        .btn.primary:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        .full-width {
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎮 Minecraft Whitelist Request</h1>
        <p class="subtitle">Request access to the Azox Network Minecraft server</p>
        <br>
        
        <form id="whitelistForm">
            <div class="form-group">
                <label for="username">Minecraft Username *</label>
                <input type="text" id="username" name="username" required 
                       pattern="[a-zA-Z0-9_]{3,16}" 
                       placeholder="Steve"
                       maxlength="16">
                <div class="hint">Your Minecraft username (3-16 characters)</div>
            </div>
            
            <div class="form-group">
                <label for="who_you_are">Who are you? *</label>
                <textarea id="who_you_are" name="who_you_are" required 
                          placeholder="Tell us who you are, how you found us, etc."
                          maxlength="500"></textarea>
                <div class="hint">Help us get to know you (required for our records)</div>
            </div>
            
            <div class="form-group">
                <label for="access_code">Access Code *</label>
                <input type="text" id="access_code" name="access_code" required 
                       placeholder="Enter the access code">
                <div class="hint">Required to join the server</div>
            </div>
            
            <div class="form-group">
                <label for="invitee">Who invited you? (Optional)</label>
                <input type="text" id="invitee" name="invitee" 
                       pattern="[a-zA-Z0-9_]{3,16}"
                       placeholder="Username of person who invited you"
                       maxlength="16">
                <div class="hint">If someone referred you, enter their username (both of you may receive rewards later!)</div>
            </div>
            
            <button type="submit" id="submitBtn" class="btn primary full-width">Submit Request</button>
        </form>
        
        <div id="message" class="message"></div>
    </div>

    <script>
        const form = document.getElementById('whitelistForm');
        const submitBtn = document.getElementById('submitBtn');
        const message = document.getElementById('message');
        
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
            message.style.display = 'none';
            
            const formData = new FormData(form);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                message.textContent = data.message;
                message.className = 'message ' + (data.success ? 'success' : 'error');
                message.style.display = 'block';
                
                if (data.success) {
                    form.reset();
                }
            } catch (error) {
                message.textContent = 'Network error. Please try again.';
                message.className = 'message error';
                message.style.display = 'block';
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Request';
            }
        });
    </script>
</body>
</html>