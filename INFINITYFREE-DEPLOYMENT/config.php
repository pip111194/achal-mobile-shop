<?php
/**
 * Configuration for InfinityFree Hosting
 * Update these values with your actual database credentials
 */

// ============================================
// DATABASE CONFIGURATION
// ============================================
// Get these from InfinityFree cPanel → MySQL Databases
define('DB_HOST', 'sql123.infinityfree.com'); // Change to your DB host
define('DB_NAME', 'if0_12345678_achalshop'); // Change to your database name
define('DB_USER', 'if0_12345678'); // Change to your database username
define('DB_PASS', 'your_password_here'); // Change to your database password

// ============================================
// APP CONFIGURATION
// ============================================
define('APP_NAME', 'Achal Mobile Shop');
define('APP_VERSION', '2.0');
define('BASE_URL', 'http://yourdomain.infinityfreeapp.com'); // Change to your domain
define('UPLOAD_DIR', 'uploads/');
define('MAX_UPLOAD_SIZE', 2097152); // 2MB (InfinityFree limit)

// ============================================
// SESSION CONFIGURATION
// ============================================
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'ACHAL_SESSION');

// ============================================
// FEATURE FLAGS (Disabled for InfinityFree)
// ============================================
define('ENABLE_EMAIL', false); // SMTP is blocked on InfinityFree
define('ENABLE_FIREBASE', false); // External APIs may be blocked
define('ENABLE_GOOGLE_MAPS', false); // May be blocked
define('ENABLE_PAYMENT_GATEWAY', false); // Requires external API

// ============================================
// SECURITY SETTINGS
// ============================================
define('ADMIN_EMAIL', 'admin@achalmobileshop.com');
define('ADMIN_PASSWORD_HASH', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYzNW6J7.Ky'); // Admin@123
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_MIN_LENGTH', 8);

// ============================================
// ERROR REPORTING (Disable in production)
// ============================================
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// ============================================
// DATABASE CONNECTION
// ============================================
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                PDO::ATTR_PERSISTENT => false // Don't use persistent connections on shared hosting
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Don't expose database errors to users
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }
    
    return $pdo;
}

// ============================================
// CUSTOM SESSION HANDLER FOR INFINITYFREE
// ============================================
class DBSessionHandler implements SessionHandlerInterface {
    private $pdo;
    
    public function open($savePath, $sessionName): bool {
        $this->pdo = getDB();
        return true;
    }
    
    public function close(): bool {
        return true;
    }
    
    public function read($id): string {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT data FROM sessions WHERE id = ? AND last_activity > DATE_SUB(NOW(), INTERVAL ? SECOND)"
            );
            $stmt->execute([$id, SESSION_LIFETIME]);
            $result = $stmt->fetch();
            return $result ? $result['data'] : '';
        } catch (PDOException $e) {
            error_log("Session read failed: " . $e->getMessage());
            return '';
        }
    }
    
    public function write($id, $data): bool {
        try {
            $stmt = $this->pdo->prepare(
                "REPLACE INTO sessions (id, data, last_activity) VALUES (?, ?, NOW())"
            );
            return $stmt->execute([$id, $data]);
        } catch (PDOException $e) {
            error_log("Session write failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function destroy($id): bool {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Session destroy failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function gc($maxlifetime): int|false {
        try {
            $stmt = $this->pdo->prepare(
                "DELETE FROM sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL ? SECOND)"
            );
            $stmt->execute([$maxlifetime]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Session GC failed: " . $e->getMessage());
            return false;
        }
    }
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

/**
 * Time ago format
 */
function timeAgo($timestamp) {
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $timestamp);
    }
}

/**
 * Redirect with message
 */
function redirect($url, $message = '', $type = 'info') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'text' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $message;
    }
    return null;
}

/**
 * Upload file (with InfinityFree limits)
 */
function uploadFile($file, $directory = 'uploads/') {
    // Check if file was uploaded
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file'];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload failed'];
    }
    
    // Check file size (2MB limit for InfinityFree)
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'message' => 'File too large (max 2MB)'];
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    // Create directory if it doesn't exist
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = $directory . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => BASE_URL . '/' . $filepath
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to save file'];
}

/**
 * Log activity
 */
function logActivity($action, $data = []) {
    $logFile = __DIR__ . '/activity.log';
    $timestamp = date('Y-m-d H:i:s');
    $userId = $_SESSION['user_id'] ?? 'guest';
    
    $logEntry = json_encode([
        'timestamp' => $timestamp,
        'user_id' => $userId,
        'action' => $action,
        'data' => $data,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]) . "\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// ============================================
// INITIALIZE SESSION
// ============================================
$handler = new DBSessionHandler();
session_set_save_handler($handler, true);
session_name(SESSION_NAME);
session_start();

// Regenerate session ID periodically for security
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// ============================================
// AUTO-CLEANUP OLD SESSIONS (1% chance)
// ============================================
if (rand(1, 100) === 1) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL ? SECOND)");
        $stmt->execute([SESSION_LIFETIME]);
    } catch (PDOException $e) {
        error_log("Session cleanup failed: " . $e->getMessage());
    }
}
?>
