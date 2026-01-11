<?php
session_start();

// Handle form submission FIRST, before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // All your POST handling code here...
    // (Keep everything as-is from "header('Content-Type: application/json');" onwards)
    exit; // Make sure this exit is there!
}

// Only include nav for GET requests (viewing the form)
include __DIR__ . '/../includes/nav.php';

// config.php - Store this outside web root if possible
define('DB_HOST', 'localhost');
define('DB_NAME', 'azox_network');
define('DB_USER', 'azox_user');
define('DB_PASS', 'ribNed-9gugmo-pejtav'); // Use your actual azox_user password

define('RCON_HOST', '127.0.0.1');
define('RCON_PORT', 25575);
define('RCON_PASSWORD', 'FdC2SD1CSDPnhRSuRT3FI0feh');

define('INSTANT_ACCESS_CODE', 'azox2026'); // Change this!
define('APPROVAL_HOURS', 24);

// Database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed");
}

// Create table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS whitelist_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    minecraft_username VARCHAR(16) NOT NULL,
    note TEXT,
    ip_address VARCHAR(45),
    request_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    approved TINYINT DEFAULT 0,
    instant_access TINYINT DEFAULT 0,
    processed TINYINT DEFAULT 0,
    UNIQUE KEY unique_username (minecraft_username)
)");

// Simple RCON class
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

// Validate Minecraft username
function validateUsername($username) {
    if (!preg_match('/^[a-zA-Z0-9_]{3,16}$/', $username)) {
        return false;
    }
    
    // Optional: Check with Mojang API
    $ch = curl_init("https://api.mojang.com/users/profiles/minecraft/" . urlencode($username));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}

// Add to whitelist via RCON
// Add to whitelist via RCON
function addToWhitelist($username) {
    $rcon = new MinecraftRCON();
    if (!$rcon->connect(RCON_HOST, RCON_PORT, RCON_PASSWORD)) {
        error_log("RCON connection failed for user: $username");
        return false;
    }
    
    $result = $rcon->command("whitelist add " . $username);
    $rcon->disconnect();
    
    error_log("RCON result for $username: " . $result);
    
    // Check if the command actually succeeded
    return (strpos($result, 'Added') !== false || strpos($result, 'already') !== false);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $username = trim($_POST['username'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $accessCode = trim($_POST['access_code'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Basic validation
    if (empty($username)) {
        echo json_encode(['success' => false, 'message' => 'Username required']);
        exit;
    }
    
    if (strlen($note) > 500) {
        echo json_encode(['success' => false, 'message' => 'Note too long (max 500 chars)']);
        exit;
    }
    
    // Rate limiting - 3 requests per IP per hour
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM whitelist_requests 
                          WHERE ip_address = ? AND request_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->execute([$ip]);
    if ($stmt->fetchColumn() >= 3) {
        echo json_encode(['success' => false, 'message' => 'Too many requests. Try again later.']);
        exit;
    }
    
    // Validate username format and with Mojang
    if (!validateUsername($username)) {
        echo json_encode(['success' => false, 'message' => 'Invalid Minecraft username']);
        exit;
    }
    
    // Check for instant access
    $instantAccess = ($accessCode === INSTANT_ACCESS_CODE);
    
    try {
        // Insert request
        $stmt = $pdo->prepare("INSERT INTO whitelist_requests 
                              (minecraft_username, note, ip_address, instant_access, approved, processed) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $username, 
            $note, 
            $ip, 
            $instantAccess ? 1 : 0,
            $instantAccess ? 1 : 0,
            0
        ]);
        
        $message = $instantAccess 
            ? 'Access granted! You have been whitelisted.' 
            : 'Request submitted! You will be whitelisted within ' . APPROVAL_HOURS . ' hours.';
        
        // If instant access, add to whitelist immediately
        if ($instantAccess) {
            $whitelistSuccess = addToWhitelist($username);
            if ($whitelistSuccess) {
                $pdo->prepare("UPDATE whitelist_requests SET processed = 1 WHERE minecraft_username = ?")
                    ->execute([$username]);
                $message = 'Access granted! You have been whitelisted and can join now.';
            } else {
                $message = 'Request approved but RCON failed. Please contact an admin.';
            }
        }
        
        echo json_encode(['success' => true, 'message' => $message]);
        
    } catch(PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'message' => 'Username already requested']);
        } else {
            echo json_encode(['success' => false, 'message' => 'System error. Try again later.']);
        }
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minecraft Whitelist Request</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

    <div class="container">
        <h1>🎮 Whitelist Request</h1>
        <p class="subtitle">Request access to our Minecraft server</p>
        <br>
        <form id="whitelistForm">
            <div class="form-group">
                <label for="username">Minecraft Username *</label>
                <input type="text" id="username" name="username" required 
                       pattern="[a-zA-Z0-9_]{3,16}" 
                       placeholder="Steve">
                <div class="hint">Your exact Minecraft username (3-16 characters)</div>
            </div>
            <br><br>
            <div class="form-group">
                <label for="note">Why do you want to join?</label>
                <textarea id="note" name="note" placeholder="Tell us a bit about yourself and who you are." maxlength="500"></textarea>
                <div class="hint">Optional, but it helps us get to know you</div>
            </div>
            <br><br>
            <div class="form-group">
                <label for="access_code">Access Code (Optional)</label>
                <input type="text" id="access_code" name="access_code" placeholder="Leave empty for standard 24h approval">
                <div class="hint">Have a access code? Enter it for instant access!</div>
            </div>
            <br><br>
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
