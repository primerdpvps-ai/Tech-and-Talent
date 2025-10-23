<?php
/**
 * TTS PMS Phase 6 - Security Helpers
 * Rate limiting, maintenance mode, and security utilities
 */

/**
 * Rate limiter with exponential backoff
 */
class RateLimiter {
    private $db;
    private $cacheDir;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->cacheDir = dirname(__DIR__) . '/cache/rate_limits/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Check if request is rate limited
     */
    public function isRateLimited($identifier, $maxAttempts = 5, $windowMinutes = 15) {
        $key = $this->getCacheKey($identifier);
        $attempts = $this->getAttempts($key);
        
        if ($attempts >= $maxAttempts) {
            $backoffMinutes = $this->calculateBackoff($attempts);
            $this->logRateLimit($identifier, $attempts, $backoffMinutes);
            return ['limited' => true, 'backoff_minutes' => $backoffMinutes];
        }
        
        return ['limited' => false, 'attempts' => $attempts];
    }
    
    /**
     * Record failed attempt
     */
    public function recordAttempt($identifier) {
        $key = $this->getCacheKey($identifier);
        $attempts = $this->getAttempts($key) + 1;
        
        $data = [
            'attempts' => $attempts,
            'first_attempt' => time(),
            'last_attempt' => time()
        ];
        
        if (file_exists($this->cacheDir . $key)) {
            $existing = json_decode(file_get_contents($this->cacheDir . $key), true);
            $data['first_attempt'] = $existing['first_attempt'] ?? time();
        }
        
        file_put_contents($this->cacheDir . $key, json_encode($data));
        
        return $attempts;
    }
    
    /**
     * Clear rate limit for identifier
     */
    public function clearLimit($identifier) {
        $key = $this->getCacheKey($identifier);
        $file = $this->cacheDir . $key;
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    /**
     * Get current attempts for identifier
     */
    private function getAttempts($key) {
        $file = $this->cacheDir . $key;
        if (!file_exists($file)) {
            return 0;
        }
        
        $data = json_decode(file_get_contents($file), true);
        
        // Check if window has expired
        if (isset($data['first_attempt']) && (time() - $data['first_attempt']) > (15 * 60)) {
            unlink($file);
            return 0;
        }
        
        return $data['attempts'] ?? 0;
    }
    
    /**
     * Calculate exponential backoff
     */
    private function calculateBackoff($attempts) {
        return min(pow(2, $attempts - 5) * 5, 1440); // Max 24 hours
    }
    
    /**
     * Generate cache key
     */
    private function getCacheKey($identifier) {
        return 'rate_' . hash('sha256', $identifier);
    }
    
    /**
     * Log rate limit event
     */
    private function logRateLimit($identifier, $attempts, $backoffMinutes) {
        try {
            $this->db->insert('tts_admin_edits', [
                'admin_id' => $_SESSION['user_id'] ?? 0,
                'action_type' => 'rate_limit_triggered',
                'object_type' => 'security',
                'object_id' => hash('sha256', $identifier),
                'changes' => json_encode([
                    'attempts' => $attempts,
                    'backoff_minutes' => $backoffMinutes,
                    'identifier_hash' => substr(hash('sha256', $identifier), 0, 8)
                ]),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            error_log("Failed to log rate limit: " . $e->getMessage());
        }
    }
}

/**
 * Maintenance mode checker
 */
class MaintenanceMode {
    private static $instance = null;
    private $db;
    private $isMaintenanceMode = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Check if site is in maintenance mode
     */
    public function isEnabled() {
        if ($this->isMaintenanceMode === null) {
            try {
                $setting = $this->db->fetchOne(
                    "SELECT setting_value FROM tts_settings WHERE setting_key = 'maintenance_mode' AND category = 'system'"
                );
                $this->isMaintenanceMode = ($setting && $setting['setting_value'] === '1');
            } catch (Exception $e) {
                $this->isMaintenanceMode = false;
            }
        }
        
        return $this->isMaintenanceMode;
    }
    
    /**
     * Check if current user can bypass maintenance mode
     */
    public function canBypass() {
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            return false;
        }
        
        // Super admins can always bypass
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin') {
            return true;
        }
        
        // Check for bypass capability
        return $this->hasCapability('bypass_maintenance');
    }
    
    /**
     * Enable maintenance mode
     */
    public function enable($message = 'Site is temporarily under maintenance') {
        try {
            $this->db->query(
                "UPDATE tts_settings SET setting_value = '1' WHERE setting_key = 'maintenance_mode' AND category = 'system'"
            );
            
            $this->db->query(
                "UPDATE tts_settings SET setting_value = ? WHERE setting_key = 'maintenance_message' AND category = 'system'",
                [$message]
            );
            
            $this->isMaintenanceMode = true;
            
            // Log maintenance mode activation
            $this->logMaintenanceChange('enabled', $message);
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Disable maintenance mode
     */
    public function disable() {
        try {
            $this->db->query(
                "UPDATE tts_settings SET setting_value = '0' WHERE setting_key = 'maintenance_mode' AND category = 'system'"
            );
            
            $this->isMaintenanceMode = false;
            
            // Log maintenance mode deactivation
            $this->logMaintenanceChange('disabled');
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get maintenance message
     */
    public function getMessage() {
        try {
            $setting = $this->db->fetchOne(
                "SELECT setting_value FROM tts_settings WHERE setting_key = 'maintenance_message' AND category = 'system'"
            );
            return $setting ? $setting['setting_value'] : 'Site is temporarily under maintenance';
        } catch (Exception $e) {
            return 'Site is temporarily under maintenance';
        }
    }
    
    /**
     * Check if user has capability
     */
    private function hasCapability($capability) {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        try {
            $result = $this->db->fetchOne("
                SELECT COUNT(*) as has_capability
                FROM tts_users u
                JOIN tts_roles r ON u.role = r.name
                JOIN tts_role_capabilities rc ON r.id = rc.role_id
                JOIN tts_capabilities c ON rc.capability_id = c.id
                WHERE u.id = ? AND c.name = ?
            ", [$_SESSION['user_id'], $capability]);
            
            return $result && $result['has_capability'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Log maintenance mode changes
     */
    private function logMaintenanceChange($action, $message = null) {
        try {
            $this->db->insert('tts_admin_edits', [
                'admin_id' => $_SESSION['user_id'] ?? 1,
                'action_type' => 'maintenance_mode',
                'object_type' => 'system',
                'object_id' => 'maintenance',
                'changes' => json_encode([
                    'action' => $action,
                    'message' => $message,
                    'timestamp' => date('Y-m-d H:i:s')
                ]),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            error_log("Failed to log maintenance mode change: " . $e->getMessage());
        }
    }
}

/**
 * Webhook signature validator
 */
class WebhookValidator {
    /**
     * Validate HMAC signature
     */
    public static function validateSignature($payload, $signature, $secret) {
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * Generate webhook signature
     */
    public static function generateSignature($payload, $secret) {
        return 'sha256=' . hash_hmac('sha256', $payload, $secret);
    }
}

/**
 * Secret manager for secure storage and rotation
 */
class SecretManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Store encrypted secret
     */
    public function storeSecret($key, $value, $category = 'system') {
        $encrypted = $this->encrypt($value);
        
        try {
            $this->db->query("
                INSERT INTO tts_settings (setting_key, setting_value, category, description, is_encrypted) 
                VALUES (?, ?, ?, 'Encrypted secret', 1)
                ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value),
                    is_encrypted = 1,
                    updated_at = CURRENT_TIMESTAMP
            ", [$key, $encrypted, $category]);
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Retrieve and decrypt secret
     */
    public function getSecret($key, $category = 'system') {
        try {
            $result = $this->db->fetchOne(
                "SELECT setting_value, is_encrypted FROM tts_settings WHERE setting_key = ? AND category = ?",
                [$key, $category]
            );
            
            if (!$result) {
                return null;
            }
            
            if ($result['is_encrypted']) {
                return $this->decrypt($result['setting_value']);
            }
            
            return $result['setting_value'];
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Rotate secret (generate new value)
     */
    public function rotateSecret($key, $category = 'system') {
        $newSecret = $this->generateSecret();
        
        if ($this->storeSecret($key, $newSecret, $category)) {
            // Log secret rotation
            $this->logSecretRotation($key, $category);
            return $newSecret;
        }
        
        return false;
    }
    
    /**
     * Generate secure random secret
     */
    private function generateSecret($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Encrypt value
     */
    private function encrypt($value) {
        $key = $this->getEncryptionKey();
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt value
     */
    private function decrypt($encryptedValue) {
        $key = $this->getEncryptionKey();
        $data = base64_decode($encryptedValue);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Get encryption key
     */
    private function getEncryptionKey() {
        // Use a combination of server-specific values
        $serverKey = $_SERVER['SERVER_NAME'] ?? 'localhost';
        $dbKey = DB_NAME ?? 'tts_pms';
        return hash('sha256', $serverKey . $dbKey . 'tts_encryption_salt');
    }
    
    /**
     * Log secret rotation
     */
    private function logSecretRotation($key, $category) {
        try {
            $this->db->insert('tts_admin_edits', [
                'admin_id' => $_SESSION['user_id'] ?? 1,
                'action_type' => 'secret_rotation',
                'object_type' => 'security',
                'object_id' => $key,
                'changes' => json_encode([
                    'key' => $key,
                    'category' => $category,
                    'rotated_at' => date('Y-m-d H:i:s')
                ]),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'system',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Secret Manager'
            ]);
        } catch (Exception $e) {
            error_log("Failed to log secret rotation: " . $e->getMessage());
        }
    }
}

/**
 * IP throttling for API endpoints
 */
function checkIPThrottle($endpoint, $maxRequests = 100, $windowMinutes = 60) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $identifier = $endpoint . ':' . $ip;
    
    $rateLimiter = new RateLimiter();
    $result = $rateLimiter->isRateLimited($identifier, $maxRequests, $windowMinutes);
    
    if ($result['limited']) {
        http_response_code(429);
        header('Retry-After: ' . ($result['backoff_minutes'] * 60));
        echo json_encode([
            'error' => 'Too many requests',
            'retry_after_minutes' => $result['backoff_minutes']
        ]);
        exit;
    }
    
    // Record this request
    $rateLimiter->recordAttempt($identifier);
}

/**
 * OTP rate limiting with exponential backoff
 */
function checkOTPRateLimit($identifier) {
    $rateLimiter = new RateLimiter();
    $result = $rateLimiter->isRateLimited($identifier, 3, 15); // 3 attempts per 15 minutes
    
    if ($result['limited']) {
        return [
            'allowed' => false,
            'backoff_minutes' => $result['backoff_minutes'],
            'message' => "Too many OTP requests. Please wait {$result['backoff_minutes']} minutes before trying again."
        ];
    }
    
    return ['allowed' => true, 'attempts' => $result['attempts']];
}

/**
 * Maintenance mode middleware
 */
function checkMaintenanceMode() {
    $maintenance = MaintenanceMode::getInstance();
    
    if ($maintenance->isEnabled() && !$maintenance->canBypass()) {
        // Block database writes for non-super admins
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || 
            strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
            
            http_response_code(503);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Site is in maintenance mode',
                'message' => $maintenance->getMessage()
            ]);
            exit;
        }
        
        // Show maintenance page for regular requests
        showMaintenancePage($maintenance->getMessage());
        exit;
    }
}

/**
 * Show maintenance page
 */
function showMaintenancePage($message) {
    http_response_code(503);
    header('Retry-After: 3600'); // Retry after 1 hour
    
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Maintenance Mode - TTS PMS</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #333; margin-bottom: 20px; }
            p { color: #666; line-height: 1.6; }
            .icon { font-size: 64px; margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='icon'>🔧</div>
            <h1>Site Under Maintenance</h1>
            <p>" . htmlspecialchars($message) . "</p>
            <p><small>We'll be back shortly. Thank you for your patience.</small></p>
        </div>
    </body>
    </html>";
}
?>
