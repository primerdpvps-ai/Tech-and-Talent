<?php
/**
 * TTS PMS Initialization File
 * Bootstrap the application with all necessary configurations
 */

// Prevent direct access
if (!defined('TTS_PMS_INIT')) {
    define('TTS_PMS_INIT', true);
}

// Define root path
if (!defined('TTS_PMS_ROOT')) {
    define('TTS_PMS_ROOT', dirname(__DIR__));
}

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Karachi');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load main configuration
require_once __DIR__ . '/config.php';

// Load database configuration
require_once __DIR__ . '/database.php';

// Load application configuration
require_once __DIR__ . '/app_config.php';

// Load email configuration
require_once __DIR__ . '/email_config.php';

// Load admin helpers if in admin context
if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
    if (file_exists(__DIR__ . '/../includes/admin_helpers.php')) {
        require_once __DIR__ . '/../includes/admin_helpers.php';
    }
}

// Auto-load classes (simple autoloader)
spl_autoload_register(function ($className) {
    $directories = [
        TTS_PMS_ROOT . '/classes/',
        TTS_PMS_ROOT . '/includes/',
        TTS_PMS_ROOT . '/models/',
        TTS_PMS_ROOT . '/controllers/',
        TTS_PMS_ROOT . '/services/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Load utility functions
if (file_exists(TTS_PMS_ROOT . '/includes/functions.php')) {
    require_once TTS_PMS_ROOT . '/includes/functions.php';
}

// Initialize error handling
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    $errorTypes = [
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parse Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict Notice',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED => 'Deprecated',
        E_USER_DEPRECATED => 'User Deprecated'
    ];
    
    $errorType = $errorTypes[$severity] ?? 'Unknown Error';
    $errorMessage = "[$errorType] $message in $file on line $line";
    
    log_message('error', $errorMessage);
    
    if (APP_DEBUG) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
        echo "<strong>$errorType:</strong> $message<br>";
        echo "<strong>File:</strong> $file<br>";
        echo "<strong>Line:</strong> $line";
        echo "</div>";
    }
    
    return true;
});

// Initialize exception handling
set_exception_handler(function($exception) {
    $message = "Uncaught exception: " . $exception->getMessage();
    $file = $exception->getFile();
    $line = $exception->getLine();
    $trace = $exception->getTraceAsString();
    
    log_message('error', "$message in $file on line $line", [
        'trace' => $trace
    ]);
    
    if (APP_DEBUG) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
        echo "<h3>Uncaught Exception</h3>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($message) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($file) . "</p>";
        echo "<p><strong>Line:</strong> $line</p>";
        echo "<details><summary>Stack Trace</summary><pre>" . htmlspecialchars($trace) . "</pre></details>";
        echo "</div>";
    } else {
        echo "<h1>Something went wrong</h1>";
        echo "<p>We're sorry, but something went wrong. Please try again later.</p>";
    }
});

// Check if maintenance mode is enabled
if (MAINTENANCE_MODE && !APP_DEBUG) {
    http_response_code(503);
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Maintenance Mode - " . APP_NAME . "</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #333; }
            p { color: #666; line-height: 1.6; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>🔧 Maintenance Mode</h1>
            <p>We're currently performing scheduled maintenance to improve your experience.</p>
            <p>Please check back in a few minutes. Thank you for your patience!</p>
        </div>
    </body>
    </html>";
    exit;
}

// Initialize session security
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID periodically for security
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// CSRF protection initialization
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Log successful initialization
if (APP_DEBUG) {
    log_message('info', 'TTS PMS successfully initialized', [
        'session_id' => session_id(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown'
    ]);
}

// Function to check if system is properly initialized
function is_system_initialized() {
    return defined('TTS_PMS_INITIALIZED') && 
           class_exists('Database') && 
           Database::getInstance()->testConnection();
}

// Function to get system status
function get_system_status() {
    return [
        'initialized' => is_system_initialized(),
        'database' => db_health_check(),
        'php_version' => PHP_VERSION,
        'memory_usage' => memory_get_usage(true),
        'memory_peak' => memory_get_peak_usage(true),
        'uptime' => time() - $_SERVER['REQUEST_TIME'],
        'environment' => APP_ENV,
        'debug_mode' => APP_DEBUG,
        'maintenance_mode' => MAINTENANCE_MODE
    ];
}

?>
