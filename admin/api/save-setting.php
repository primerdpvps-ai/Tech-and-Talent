<?php
/**
 * TTS PMS - Save Setting API
 * Handles global settings updates with validation and audit
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/init.php';
require_once '../../includes/admin_helpers.php';

session_start();
require_admin();
require_capability('manage_settings');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$db = Database::getInstance();

try {
    $category = sanitize_input($_POST['category'] ?? '');
    $settings = $_POST['settings'] ?? [];
    
    if (!$category || empty($settings)) {
        throw new Exception('Category and settings required');
    }
    
    // Validate category
    $validCategories = ['branding', 'email', 'seo', 'payroll', 'auth', 'system'];
    if (!in_array($category, $validCategories)) {
        throw new Exception('Invalid settings category');
    }
    
    // Get current settings for audit
    $currentSettings = [];
    foreach (array_keys($settings) as $key) {
        $current = $db->fetchOne("SELECT setting_value FROM tts_settings WHERE setting_key = ? AND category = ?", [$key, $category]);
        if ($current) {
            $currentSettings[$key] = $current['setting_value'];
        }
    }
    
    // Validate and save each setting
    foreach ($settings as $key => $value) {
        // Validate setting based on category and key
        $validatedValue = validateSetting($category, $key, $value);
        
        // Update or insert setting
        $db->query("
            INSERT INTO tts_settings (setting_key, setting_value, category, description) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                updated_at = CURRENT_TIMESTAMP
        ", [$key, $validatedValue, $category, getSettingDescription($key)]);
    }
    
    // Update configuration files if needed
    updateConfigFiles($category, $settings);
    
    // Log the change
    log_admin_action(
        'settings_update',
        'settings',
        $category,
        $currentSettings,
        $settings,
        "Updated {$category} settings"
    );
    
    echo json_encode([
        'success' => true,
        'message' => ucfirst($category) . ' settings updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function validateSetting($category, $key, $value) {
    switch ($category) {
        case 'email':
            switch ($key) {
                case 'smtp_port':
                    $port = (int)$value;
                    if ($port < 1 || $port > 65535) {
                        throw new Exception('Invalid SMTP port');
                    }
                    return $port;
                    
                case 'smtp_host':
                case 'smtp_username':
                case 'from_email':
                    if (empty($value)) {
                        throw new Exception("$key cannot be empty");
                    }
                    return sanitize_input($value);
                    
                case 'smtp_encryption':
                    if (!in_array($value, ['ssl', 'tls', 'none'])) {
                        throw new Exception('Invalid encryption type');
                    }
                    return $value;
                    
                default:
                    return sanitize_input($value);
            }
            
        case 'payroll':
            switch ($key) {
                case 'base_hourly_rate':
                case 'streak_bonus':
                case 'overtime_multiplier':
                    $num = (float)$value;
                    if ($num < 0) {
                        throw new Exception("$key must be positive");
                    }
                    return $num;
                    
                case 'daily_working_hours':
                    $hours = (int)$value;
                    if ($hours < 1 || $hours > 24) {
                        throw new Exception('Daily working hours must be between 1-24');
                    }
                    return $hours;
                    
                default:
                    return sanitize_input($value);
            }
            
        case 'auth':
            switch ($key) {
                case 'gmail_only':
                case 'require_email_verification':
                    return (bool)$value;
                    
                case 'otp_cooldown':
                case 'session_timeout':
                case 'max_login_attempts':
                    $num = (int)$value;
                    if ($num < 1) {
                        throw new Exception("$key must be positive");
                    }
                    return $num;
                    
                default:
                    return sanitize_input($value);
            }
            
        case 'seo':
            switch ($key) {
                case 'canonical_url':
                    if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                        throw new Exception('Invalid canonical URL');
                    }
                    return $value;
                    
                default:
                    return sanitize_input($value);
            }
            
        default:
            return sanitize_input($value);
    }
}

function updateConfigFiles($category, $settings) {
    switch ($category) {
        case 'email':
            updateEmailConfig($settings);
            break;
            
        case 'payroll':
            updatePayrollConfig($settings);
            break;
            
        case 'branding':
            updateBrandingConfig($settings);
            break;
    }
}

function updateEmailConfig($settings) {
    $configPath = dirname(dirname(__DIR__)) . '/config/email_config.php';
    
    if (!file_exists($configPath)) {
        return;
    }
    
    $content = file_get_contents($configPath);
    
    $replacements = [
        'smtp_host' => 'SMTP_HOST',
        'smtp_port' => 'SMTP_PORT',
        'smtp_username' => 'SMTP_USERNAME',
        'smtp_password' => 'SMTP_PASSWORD',
        'smtp_encryption' => 'SMTP_ENCRYPTION',
        'from_email' => 'FROM_EMAIL',
        'from_name' => 'FROM_NAME'
    ];
    
    foreach ($replacements as $key => $constant) {
        if (isset($settings[$key])) {
            $pattern = "/define\('$constant', '[^']*'\);/";
            $replacement = "define('$constant', '{$settings[$key]}');";
            $content = preg_replace($pattern, $replacement, $content);
        }
    }
    
    file_put_contents($configPath, $content);
}

function updatePayrollConfig($settings) {
    $configPath = dirname(dirname(__DIR__)) . '/config/app_config.php';
    
    if (!file_exists($configPath)) {
        return;
    }
    
    $content = file_get_contents($configPath);
    
    foreach ($settings as $key => $value) {
        $constantName = 'PAYROLL_' . strtoupper($key);
        $pattern = "/define\('$constantName', '[^']*'\);/";
        $replacement = "define('$constantName', '$value');";
        
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $replacement, $content);
        } else {
            // Add new constant if it doesn't exist
            $content .= "\ndefine('$constantName', '$value');";
        }
    }
    
    file_put_contents($configPath, $content);
}

function updateBrandingConfig($settings) {
    // Update branding in template files
    $files = [
        dirname(dirname(__DIR__)) . '/index.php',
        dirname(dirname(__DIR__)) . '/packages/web/auth/sign-in.php'
    ];
    
    foreach ($files as $file) {
        if (!file_exists($file)) continue;
        
        $content = file_get_contents($file);
        
        if (isset($settings['site_name'])) {
            $content = preg_replace(
                '/<title>[^<]*<\/title>/',
                "<title>{$settings['site_name']}</title>",
                $content
            );
        }
        
        if (isset($settings['meta_description'])) {
            $pattern = '/<meta name="description" content="[^"]*">/';
            $replacement = '<meta name="description" content="' . htmlspecialchars($settings['meta_description']) . '">';
            
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $replacement, $content);
            } else {
                // Add meta description if it doesn't exist
                $content = str_replace(
                    '</head>',
                    "    $replacement\n</head>",
                    $content
                );
            }
        }
        
        file_put_contents($file, $content);
    }
}

function getSettingDescription($key) {
    $descriptions = [
        // Branding
        'site_name' => 'Website name displayed in title and headers',
        'tagline' => 'Site tagline or slogan',
        'logo_url' => 'URL to site logo image',
        'favicon_url' => 'URL to favicon image',
        'footer_text' => 'Copyright text in footer',
        
        // Email
        'smtp_host' => 'SMTP server hostname',
        'smtp_port' => 'SMTP server port number',
        'smtp_username' => 'SMTP authentication username',
        'smtp_password' => 'SMTP authentication password',
        'smtp_encryption' => 'SMTP encryption method (SSL/TLS)',
        'from_email' => 'Default sender email address',
        'from_name' => 'Default sender name',
        
        // SEO
        'meta_title' => 'Default page title for SEO',
        'meta_description' => 'Default meta description',
        'canonical_url' => 'Canonical URL for the site',
        'robots_index' => 'Allow search engines to index site',
        'sitemap_enabled' => 'Enable XML sitemap generation',
        
        // Payroll
        'base_hourly_rate' => 'Base hourly rate in PKR',
        'streak_bonus' => 'Bonus for consecutive work streaks',
        'daily_working_hours' => 'Standard daily working hours',
        'overtime_multiplier' => 'Overtime rate multiplier',
        
        // Auth
        'gmail_only' => 'Require Gmail addresses for registration',
        'otp_cooldown' => 'OTP request cooldown in seconds',
        'session_timeout' => 'Session timeout in seconds',
        'max_login_attempts' => 'Maximum failed login attempts'
    ];
    
    return $descriptions[$key] ?? '';
}
?>
