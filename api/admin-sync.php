<?php
/**
 * TTS PMS - Admin Sync API Endpoint
 * Handles real-time synchronization between Super Admin and live site
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../config/init.php';

// Security check - ensure admin authentication
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$db = Database::getInstance();
$adminId = $_SESSION['user_id'] ?? 1;

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            handleSyncEvent();
            break;
        case 'GET':
            handleSyncStatus();
            break;
        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleSyncEvent() {
    global $db, $adminId;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $eventType = $input['event_type'] ?? '';
    $targetPath = $input['target_path'] ?? '';
    $syncData = $input['sync_data'] ?? [];
    
    switch ($eventType) {
        case 'page_updated':
            syncPageUpdate($targetPath, $syncData);
            break;
        case 'settings_changed':
            syncSettingsChange($syncData);
            break;
        case 'module_toggled':
            syncModuleToggle($syncData);
            break;
        case 'user_modified':
            syncUserModification($syncData);
            break;
        default:
            throw new Exception('Unknown event type');
    }
    
    // Log sync event
    $db->insert('tts_admin_sync', [
        'event_type' => $eventType,
        'target_path' => $targetPath,
        'sync_data' => json_encode($syncData),
        'status' => 'completed',
        'admin_id' => $adminId,
        'processed_at' => date('Y-m-d H:i:s')
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Sync completed']);
}

function handleSyncStatus() {
    global $db;
    
    $events = $db->fetchAll(
        "SELECT * FROM tts_admin_sync WHERE status = 'pending' ORDER BY created_at ASC LIMIT 10"
    );
    
    echo json_encode(['success' => true, 'pending_events' => $events]);
}

function syncPageUpdate($targetPath, $syncData) {
    global $db, $adminId;
    
    $fullPath = dirname(__DIR__) . $targetPath;
    
    // Security check
    if (strpos($targetPath, '..') !== false || !file_exists($fullPath)) {
        throw new Exception('Invalid file path');
    }
    
    // Create backup
    $originalContent = file_get_contents($fullPath);
    $db->insert('tts_page_layouts', [
        'file_path' => $targetPath,
        'original_content' => $originalContent,
        'new_content' => $syncData['content'],
        'admin_id' => $adminId
    ]);
    
    // Write new content
    if (file_put_contents($fullPath, $syncData['content']) === false) {
        throw new Exception('Failed to write file');
    }
    
    // Update CMS history
    $db->insert('tts_cms_history', [
        'content_type' => 'page',
        'content_id' => $targetPath,
        'version_number' => getNextVersionNumber('page', $targetPath),
        'content_data' => $syncData['content'],
        'admin_id' => $adminId,
        'change_description' => $syncData['description'] ?? 'Page updated via Super Admin'
    ]);
}

function syncSettingsChange($syncData) {
    global $db, $adminId;
    
    foreach ($syncData['settings'] as $key => $value) {
        $db->query(
            "INSERT INTO tts_settings (setting_key, setting_value, category) VALUES (?, ?, ?) 
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
            [$key, $value, $syncData['category']]
        );
    }
    
    // Update configuration files if needed
    updateConfigFiles($syncData);
    
    // Log change
    $db->insert('tts_cms_history', [
        'content_type' => 'setting',
        'content_id' => $syncData['category'],
        'content_data' => json_encode($syncData['settings']),
        'admin_id' => $adminId,
        'change_description' => 'Settings updated: ' . $syncData['category']
    ]);
}

function syncModuleToggle($syncData) {
    global $db, $adminId;
    
    $moduleName = $syncData['module_name'];
    $isEnabled = $syncData['is_enabled'];
    
    $db->query(
        "INSERT INTO tts_module_config (module_name, is_enabled, admin_id) VALUES (?, ?, ?) 
         ON DUPLICATE KEY UPDATE is_enabled = VALUES(is_enabled), admin_id = VALUES(admin_id)",
        [$moduleName, $isEnabled, $adminId]
    );
    
    // Update module routes and menus
    updateModuleRoutes($moduleName, $isEnabled);
    
    // Log change
    $db->insert('tts_cms_history', [
        'content_type' => 'module',
        'content_id' => $moduleName,
        'content_data' => json_encode(['enabled' => $isEnabled]),
        'admin_id' => $adminId,
        'change_description' => "Module {$moduleName} " . ($isEnabled ? 'enabled' : 'disabled')
    ]);
}

function syncUserModification($syncData) {
    global $db, $adminId;
    
    $userId = $syncData['user_id'];
    $changes = $syncData['changes'];
    
    // Build update query
    $setParts = [];
    $params = [];
    
    foreach ($changes as $field => $value) {
        $setParts[] = "$field = ?";
        $params[] = $value;
    }
    
    $params[] = $userId;
    
    $db->query(
        "UPDATE tts_users SET " . implode(', ', $setParts) . " WHERE id = ?",
        $params
    );
    
    // Log change
    $db->insert('tts_cms_history', [
        'content_type' => 'user',
        'content_id' => $userId,
        'content_data' => json_encode($changes),
        'admin_id' => $adminId,
        'change_description' => "User {$userId} modified"
    ]);
}

function updateConfigFiles($syncData) {
    $category = $syncData['category'];
    $settings = $syncData['settings'];
    
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
    $configPath = dirname(__DIR__) . '/config/email_config.php';
    
    if (file_exists($configPath)) {
        $content = file_get_contents($configPath);
        
        // Update SMTP constants
        if (isset($settings['smtp_host'])) {
            $content = preg_replace(
                "/define\('SMTP_HOST', '[^']*'\);/",
                "define('SMTP_HOST', '{$settings['smtp_host']}');",
                $content
            );
        }
        
        if (isset($settings['smtp_port'])) {
            $content = preg_replace(
                "/define\('SMTP_PORT', '[^']*'\);/",
                "define('SMTP_PORT', '{$settings['smtp_port']}');",
                $content
            );
        }
        
        if (isset($settings['smtp_username'])) {
            $content = preg_replace(
                "/define\('SMTP_USERNAME', '[^']*'\);/",
                "define('SMTP_USERNAME', '{$settings['smtp_username']}');",
                $content
            );
        }
        
        if (isset($settings['smtp_password'])) {
            $content = preg_replace(
                "/define\('SMTP_PASSWORD', '[^']*'\);/",
                "define('SMTP_PASSWORD', '{$settings['smtp_password']}');",
                $content
            );
        }
        
        file_put_contents($configPath, $content);
    }
}

function updatePayrollConfig($settings) {
    $configPath = dirname(__DIR__) . '/config/app_config.php';
    
    if (file_exists($configPath)) {
        $content = file_get_contents($configPath);
        
        // Update payroll constants
        foreach ($settings as $key => $value) {
            $constantName = strtoupper($key);
            $pattern = "/define\('PAYROLL_{$constantName}', '[^']*'\);/";
            $replacement = "define('PAYROLL_{$constantName}', '{$value}');";
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        file_put_contents($configPath, $content);
    }
}

function updateBrandingConfig($settings) {
    // Update branding in main index.php and other template files
    $files = [
        dirname(__DIR__) . '/index.php',
        dirname(__DIR__) . '/packages/web/auth/sign-in.php'
    ];
    
    foreach ($files as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            
            if (isset($settings['site_name'])) {
                $content = preg_replace(
                    '/<title>[^<]*<\/title>/',
                    "<title>{$settings['site_name']}</title>",
                    $content
                );
            }
            
            file_put_contents($file, $content);
        }
    }
}

function updateModuleRoutes($moduleName, $isEnabled) {
    // Update dashboard navigation based on module status
    $dashboardFiles = glob(dirname(__DIR__) . '/packages/web/dashboard/*/index.php');
    
    foreach ($dashboardFiles as $file) {
        $content = file_get_contents($file);
        
        // Add or remove navigation items based on module status
        if ($isEnabled) {
            // Add module navigation if not present
            addModuleNavigation($content, $moduleName, $file);
        } else {
            // Remove module navigation
            removeModuleNavigation($content, $moduleName, $file);
        }
    }
}

function addModuleNavigation($content, $moduleName, $file) {
    $navItems = [
        'payroll' => '<a class="nav-link" href="payroll.php"><i class="fas fa-money-bill-wave me-2"></i>Payroll</a>',
        'training' => '<a class="nav-link" href="training.php"><i class="fas fa-graduation-cap me-2"></i>Training</a>',
        'leave_management' => '<a class="nav-link" href="leaves.php"><i class="fas fa-calendar-times me-2"></i>Leave Requests</a>',
        'evaluations' => '<a class="nav-link" href="evaluation.php"><i class="fas fa-clipboard-check me-2"></i>Evaluation</a>'
    ];
    
    if (isset($navItems[$moduleName]) && strpos($content, $navItems[$moduleName]) === false) {
        // Find navigation section and add item
        $pattern = '/(<nav[^>]*>.*?)<\/nav>/s';
        if (preg_match($pattern, $content, $matches)) {
            $newNav = $matches[1] . $navItems[$moduleName] . '</nav>';
            $content = str_replace($matches[0], $newNav, $content);
            file_put_contents($file, $content);
        }
    }
}

function removeModuleNavigation($content, $moduleName, $file) {
    $patterns = [
        'payroll' => '/<a[^>]*href="payroll\.php"[^>]*>.*?<\/a>/',
        'training' => '/<a[^>]*href="training\.php"[^>]*>.*?<\/a>/',
        'leave_management' => '/<a[^>]*href="leaves\.php"[^>]*>.*?<\/a>/',
        'evaluations' => '/<a[^>]*href="evaluation\.php"[^>]*>.*?<\/a>/'
    ];
    
    if (isset($patterns[$moduleName])) {
        $content = preg_replace($patterns[$moduleName], '', $content);
        file_put_contents($file, $content);
    }
}

function getNextVersionNumber($contentType, $contentId) {
    global $db;
    
    $result = $db->fetchOne(
        "SELECT MAX(version_number) as max_version FROM tts_cms_history WHERE content_type = ? AND content_id = ?",
        [$contentType, $contentId]
    );
    
    return ($result['max_version'] ?? 0) + 1;
}
?>
