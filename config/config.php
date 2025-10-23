<?php
/**
 * TTS PMS Configuration File
 * Main configuration settings for the application
 */

// Prevent direct access
if (!defined('TTS_PMS_ROOT')) {
    define('TTS_PMS_ROOT', dirname(__DIR__));
}

// Load environment variables from .env file FIRST
$envFile = TTS_PMS_ROOT . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

// Environment Configuration
define('APP_ENV', 'production'); // Set to production for cPanel
define('APP_DEBUG', false); // Disable debug mode for production

// Application Settings
define('APP_NAME', 'TTS PMS');
define('APP_VERSION', '1.0.0');
define('APP_DESCRIPTION', 'TTS Professional Management System');
define('APP_AUTHOR', 'TTS Development Team');

// URL Configuration
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'pms.prizmasoft.com';
define('APP_URL', $protocol . '://' . $host);
define('BASE_URL', APP_URL); // Root directory for cPanel
define('API_URL', BASE_URL . '/api');

// Directory Paths
define('ROOT_PATH', TTS_PMS_ROOT);
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('LOGS_PATH', ROOT_PATH . '/logs');
define('CACHE_PATH', ROOT_PATH . '/cache');

// Web Paths
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/uploads');
define('CSS_URL', ASSETS_URL . '/css');
define('JS_URL', ASSETS_URL . '/js');
define('IMG_URL', ASSETS_URL . '/images');

// Security Configuration
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'your-super-secret-jwt-key-change-this-in-production');
define('JWT_EXPIRY', 3600 * 24); // 24 hours
define('SESSION_TIMEOUT', 3600 * 2); // 2 hours
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Encryption
define('ENCRYPTION_KEY', $_ENV['ENCRYPTION_KEY'] ?? 'your-32-character-encryption-key-here');
define('ENCRYPTION_METHOD', 'AES-256-CBC');

// File Upload Configuration
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', [
    'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'],
    'archive' => ['zip', 'rar', '7z']
]);

// Email Configuration
define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com');
define('MAIL_PORT', $_ENV['MAIL_PORT'] ?? 587);
define('MAIL_USERNAME', $_ENV['MAIL_USERNAME'] ?? '');
define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? '');
define('MAIL_ENCRYPTION', $_ENV['MAIL_ENCRYPTION'] ?? 'tls');
define('MAIL_FROM_ADDRESS', $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@tts-pms.com');
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? 'TTS PMS');

// Payment Gateway Configuration
define('STRIPE_PUBLISHABLE_KEY', $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '');
define('STRIPE_SECRET_KEY', $_ENV['STRIPE_SECRET_KEY'] ?? '');
define('STRIPE_WEBHOOK_SECRET', $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '');

define('PAYPAL_CLIENT_ID', $_ENV['PAYPAL_CLIENT_ID'] ?? '');
define('PAYPAL_CLIENT_SECRET', $_ENV['PAYPAL_CLIENT_SECRET'] ?? '');
define('PAYPAL_ENVIRONMENT', $_ENV['PAYPAL_ENVIRONMENT'] ?? 'sandbox'); // sandbox or live

define('GOOGLE_PAY_MERCHANT_ID', $_ENV['GOOGLE_PAY_MERCHANT_ID'] ?? '');

// API Configuration
define('API_RATE_LIMIT', 100); // requests per minute
define('API_VERSION', 'v1');
define('API_TIMEOUT', 30); // seconds

// Cache Configuration
define('CACHE_ENABLED', true);
define('CACHE_DRIVER', 'file'); // file, redis, memcached
define('CACHE_TTL', 3600); // 1 hour

// Logging Configuration
define('LOG_ENABLED', true);
define('LOG_LEVEL', APP_DEBUG ? 'debug' : 'error'); // debug, info, warning, error
define('LOG_MAX_FILES', 30);
define('LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB

// Timezone and Localization
define('DEFAULT_TIMEZONE', $_ENV['DEFAULT_TIMEZONE'] ?? 'UTC');
define('DEFAULT_LANGUAGE', $_ENV['DEFAULT_LANGUAGE'] ?? 'en');
define('DEFAULT_CURRENCY', $_ENV['DEFAULT_CURRENCY'] ?? 'USD');

// Pagination
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// System Settings
define('MAINTENANCE_MODE', false); // Disabled for production
define('REGISTRATION_ENABLED', $_ENV['REGISTRATION_ENABLED'] ?? true);
define('EMAIL_VERIFICATION_REQUIRED', $_ENV['EMAIL_VERIFICATION_REQUIRED'] ?? true);

// Dashboard Configuration
define('DASHBOARD_REFRESH_INTERVAL', 30); // seconds
define('DASHBOARD_CHART_COLORS', [
    'primary' => '#1266f1',
    'success' => '#00b74a', 
    'warning' => '#fbbd08',
    'danger' => '#f93154',
    'info' => '#39c0ed',
    'secondary' => '#6c757d'
]);

// Role-based Dashboard Settings
define('VISITOR_EVALUATION_TIMEOUT', 1800); // 30 minutes
define('CANDIDATE_APPLICATION_LIMIT', 5); // max applications per day
define('EMPLOYEE_BREAK_DURATION', 3600); // 1 hour max break
define('MANAGER_TEAM_SIZE_LIMIT', 50); // max team members
define('CEO_DASHBOARD_CACHE_TTL', 300); // 5 minutes

// Time Tracking Configuration
define('WORK_DAY_START', '09:00:00');
define('WORK_DAY_END', '18:00:00');
define('BREAK_TIME_LIMIT', 3600); // 1 hour
define('OVERTIME_THRESHOLD', 8); // hours per day
define('WEEKLY_HOUR_LIMIT', 40); // hours per week

// Task Management
define('TASK_PRIORITY_LEVELS', ['low', 'medium', 'high', 'urgent']);
define('TASK_AUTO_ASSIGNMENT', true);
define('TASK_DEADLINE_REMINDER', 24); // hours before deadline

// Training & Onboarding
define('ONBOARDING_TASK_LIMIT', 20); // max tasks per user
define('TRAINING_MODULE_TIMEOUT', 7200); // 2 hours
define('MANDATORY_TRAINING_DEADLINE', 7); // days from start date

// Performance Metrics
define('ACCURACY_TARGET', 95); // percentage
define('PRODUCTIVITY_TARGET', 85); // percentage  
define('QUALITY_RATING_SCALE', 5); // 1-5 scale
define('PERFORMANCE_REVIEW_CYCLE', 30); // days

// WordPress Integration (if needed)
define('WP_INTEGRATION_ENABLED', $_ENV['WP_INTEGRATION_ENABLED'] ?? false);
define('WP_URL', $_ENV['WP_URL'] ?? '');
define('WP_API_KEY', $_ENV['WP_API_KEY'] ?? '');

// Social Media Integration
define('GOOGLE_CLIENT_ID', $_ENV['GOOGLE_CLIENT_ID'] ?? '');
define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
define('FACEBOOK_APP_ID', $_ENV['FACEBOOK_APP_ID'] ?? '');
define('FACEBOOK_APP_SECRET', $_ENV['FACEBOOK_APP_SECRET'] ?? '');

// Notification Settings
define('PUSH_NOTIFICATIONS_ENABLED', $_ENV['PUSH_NOTIFICATIONS_ENABLED'] ?? false);
define('SMS_NOTIFICATIONS_ENABLED', $_ENV['SMS_NOTIFICATIONS_ENABLED'] ?? false);
define('EMAIL_NOTIFICATIONS_ENABLED', $_ENV['EMAIL_NOTIFICATIONS_ENABLED'] ?? true);

// Backup Configuration
define('BACKUP_ENABLED', $_ENV['BACKUP_ENABLED'] ?? true);
define('BACKUP_FREQUENCY', $_ENV['BACKUP_FREQUENCY'] ?? 'daily'); // hourly, daily, weekly
define('BACKUP_RETENTION_DAYS', $_ENV['BACKUP_RETENTION_DAYS'] ?? 30);

// Performance Settings
define('ENABLE_COMPRESSION', true);
define('ENABLE_MINIFICATION', !APP_DEBUG);
define('ENABLE_CDN', $_ENV['ENABLE_CDN'] ?? false);
define('CDN_URL', $_ENV['CDN_URL'] ?? '');

// Error Reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOGS_PATH . '/php_errors.log');
}

// Set timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Security Headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    if (isset($_SERVER['HTTPS'])) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// Auto-create directories if they don't exist
$directories = [UPLOADS_PATH, LOGS_PATH, CACHE_PATH];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Environment variables already loaded at the top of this file

// Function to get configuration value
function config($key, $default = null) {
    return $_ENV[$key] ?? $default;
}

// Function to check if we're in development mode
function is_development() {
    return APP_ENV === 'development';
}

// Function to check if we're in production mode
function is_production() {
    return APP_ENV === 'production';
}

// Function to get base URL
function base_url($path = '') {
    return BASE_URL . ($path ? '/' . ltrim($path, '/') : '');
}

// Function to get asset URL
function asset_url($path) {
    return ASSETS_URL . '/' . ltrim($path, '/');
}

// Function to generate CSRF token
function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Function to verify CSRF token
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Function to sanitize input
function sanitize_input($input) {
    if (is_array($input)) {
        return array_map('sanitize_input', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Function to log messages
function log_message($level, $message, $context = []) {
    if (!LOG_ENABLED) return;
    
    $logFile = LOGS_PATH . '/app_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = $context ? ' ' . json_encode($context) : '';
    $logEntry = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Application initialization
if (!defined('TTS_PMS_INITIALIZED')) {
    define('TTS_PMS_INITIALIZED', true);
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Log application start
    if (APP_DEBUG) {
        log_message('info', 'TTS PMS Application initialized', [
            'version' => APP_VERSION,
            'environment' => APP_ENV,
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit')
        ]);
    }
}

?>
