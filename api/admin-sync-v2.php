<?php
/**
 * TTS PMS Phase 5 - Real-Time Sync API
 * Core foundation with request validation, routing, and queue management
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/init.php';
require_once '../includes/admin_helpers.php';
require_once '../includes/security_helpers.php';
require_once 'admin-sync-helpers.php';

session_start();

// Rate limit admin sync API (100 requests per hour per IP)
checkIPThrottle('admin_sync_api', 100, 60);

// Check maintenance mode
checkMaintenanceMode();

// Validate admin session
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Admin session required']);
    exit;
}

// Validate admin capabilities
if (!function_exists('require_capability')) {
    http_response_code(500);
    echo json_encode(['error' => 'Admin helpers not loaded']);
    exit;
}

$db = Database::getInstance();

// Main request handler
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        handleSyncRequest();
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        handleStatusRequest();
    } else {
        throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        'request_id' => generateRequestId()
    ]);
    
    // Log error to audit trail
    logSyncError($e->getMessage());
}

/**
 * Handle POST sync requests with validation and routing
 */
function handleSyncRequest() {
    global $db;
    
    // Validate CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        throw new Exception('Invalid CSRF token');
    }
    
    $action = sanitize_input($_POST['action'] ?? '');
    $data = $_POST['data'] ?? [];
    $priority = (int)($_POST['priority'] ?? 1);
    
    if (empty($action)) {
        throw new Exception('Sync action required');
    }
    
    // Validate action and required capabilities
    $requiredCapability = getRequiredCapability($action);
    require_capability($requiredCapability);
    
    // Generate unique sync ID
    $syncId = generateSyncId();
    
    // Start database transaction
    $db->beginTransaction();
    
    try {
        // Route to appropriate handler
        $result = routeSyncAction($action, $data, $syncId);
        
        // Queue sync operation for processing
        queueSyncOperation($syncId, $action, $data, $priority);
        
        // Log successful sync initiation
        logSyncEvent($action, $syncId, $data, 'initiated');
        
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'sync_id' => $syncId,
            'action' => $action,
            'message' => $result['message'] ?? 'Sync operation queued successfully',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        throw $e;
    }
}

/**
 * Handle GET status requests
 */
function handleStatusRequest() {
    global $db;
    
    $syncId = sanitize_input($_GET['sync_id'] ?? '');
    
    if ($syncId) {
        // Get specific sync operation status
        $operation = $db->fetchOne(
            "SELECT * FROM tts_admin_sync WHERE sync_id = ?",
            [$syncId]
        );
        
        if (!$operation) {
            throw new Exception('Sync operation not found');
        }
        
        echo json_encode([
            'success' => true,
            'operation' => $operation,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        // Get queue status overview
        $stats = getSyncQueueStats();
        
        echo json_encode([
            'success' => true,
            'queue_stats' => $stats,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

/**
 * Route sync action to appropriate handler
 */
function routeSyncAction($action, $data, $syncId) {
    switch ($action) {
        case 'page_update':
            return handlePageUpdate($data, $syncId);
            
        case 'settings_update':
            return handleSettingsUpdate($data, $syncId);
            
        case 'module_toggle':
            return handleModuleToggle($data, $syncId);
            
        case 'payroll_update':
            return handlePayrollUpdate($data, $syncId);
            
        case 'seo_update':
            return handleSEOUpdate($data, $syncId);
            
        default:
            throw new Exception("Unsupported sync action: $action");
    }
}

/**
 * Get required capability for sync action
 */
function getRequiredCapability($action) {
    $capabilities = [
        'page_update' => 'manage_pages',
        'settings_update' => 'manage_settings',
        'module_toggle' => 'manage_modules',
        'payroll_update' => 'manage_payroll',
        'seo_update' => 'manage_settings'
    ];
    
    return $capabilities[$action] ?? 'manage_system';
}

/**
 * Queue sync operation for background processing
 */
function queueSyncOperation($syncId, $action, $data, $priority = 1) {
    global $db;
    
    $db->insert('tts_admin_sync', [
        'sync_id' => $syncId,
        'action_type' => $action,
        'data_payload' => json_encode($data),
        'priority' => $priority,
        'status' => 'pending',
        'admin_id' => $_SESSION['user_id'] ?? 1,
        'created_at' => date('Y-m-d H:i:s'),
        'retry_count' => 0,
        'max_retries' => 3
    ]);
}

/**
 * Get sync queue statistics
 */
function getSyncQueueStats() {
    global $db;
    
    $stats = [
        'pending' => 0,
        'processing' => 0,
        'completed' => 0,
        'failed' => 0,
        'total' => 0
    ];
    
    $results = $db->fetchAll(
        "SELECT status, COUNT(*) as count FROM tts_admin_sync GROUP BY status"
    );
    
    foreach ($results as $row) {
        $stats[$row['status']] = (int)$row['count'];
        $stats['total'] += (int)$row['count'];
    }
    
    // Get recent operations (last 24 hours)
    $recent = $db->fetchAll(
        "SELECT action_type, status, created_at FROM tts_admin_sync 
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
         ORDER BY created_at DESC LIMIT 10"
    );
    
    $stats['recent_operations'] = $recent;
    
    return $stats;
}

/**
 * Log sync event to audit trail
 */
function logSyncEvent($action, $syncId, $data, $status) {
    global $db;
    
    $db->insert('tts_admin_edits', [
        'admin_id' => $_SESSION['user_id'] ?? 1,
        'action_type' => 'sync_event',
        'object_type' => $action,
        'object_id' => $syncId,
        'before_json' => null,
        'after_json' => json_encode($data),
        'changes' => "Sync $status: $action",
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
}

/**
 * Log sync error to audit trail
 */
function logSyncError($errorMessage) {
    global $db;
    
    try {
        $db->insert('tts_admin_edits', [
            'admin_id' => $_SESSION['user_id'] ?? 1,
            'action_type' => 'sync_error',
            'object_type' => 'api',
            'object_id' => generateRequestId(),
            'changes' => "Sync API Error: $errorMessage",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        // Fail silently if audit logging fails
        error_log("Failed to log sync error: " . $e->getMessage());
    }
}

/**
 * Generate unique sync ID
 */
function generateSyncId() {
    return 'sync_' . uniqid() . '_' . time();
}

/**
 * Generate unique request ID for tracking
 */
function generateRequestId() {
    return 'req_' . uniqid() . '_' . substr(md5($_SERVER['REQUEST_URI'] ?? ''), 0, 8);
}

/**
 * Handle page update sync - CMS JSON + physical file generation
 */
function handlePageUpdate($data, $syncId) {
    global $db;
    
    $pageId = (int)($data['page_id'] ?? 0);
    $layoutJson = $data['layout_json'] ?? '';
    $generateFile = (bool)($data['generate_file'] ?? true);
    
    if (!$pageId || !$layoutJson) {
        throw new Exception('Page ID and layout JSON required');
    }
    
    // Get page info
    $page = $db->fetchOne("SELECT * FROM tts_cms_pages WHERE id = ?", [$pageId]);
    if (!$page) {
        throw new Exception('Page not found');
    }
    
    // Get current layout for backup
    $currentLayout = $db->fetchOne(
        "SELECT * FROM tts_page_layouts WHERE page_id = ? AND is_current = 1",
        [$pageId]
    );
    
    // Create backup in history
    if ($currentLayout) {
        $db->insert('tts_cms_history', [
            'content_type' => 'page',
            'content_id' => $pageId,
            'version_number' => getNextVersionNumber('page', $pageId),
            'content_data' => $currentLayout['layout_json'],
            'layout_backup' => $currentLayout['layout_json'],
            'admin_id' => $_SESSION['user_id'],
            'change_description' => "Auto-backup before sync update (ID: $syncId)",
            'is_auto_backup' => true
        ]);
        
        // Mark current as not current
        $db->update('tts_page_layouts', ['is_current' => false], 'page_id = ?', [$pageId]);
    }
    
    // Save new layout
    $layoutId = $db->insert('tts_page_layouts', [
        'page_id' => $pageId,
        'layout_json' => $layoutJson,
        'version' => ($currentLayout['version'] ?? 0) + 1,
        'is_current' => true,
        'layout_type' => 'page',
        'admin_id' => $_SESSION['user_id']
    ]);
    
    // Generate physical file if requested
    $filePath = null;
    if ($generateFile) {
        $layoutData = json_decode($layoutJson, true);
        if ($layoutData) {
            $filePath = generatePageFile($page, $layoutData);
        }
    }
    
    // Update page metadata
    $db->update('tts_cms_pages', [
        'updated_by' => $_SESSION['user_id'],
        'updated_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$pageId]);
    
    return [
        'message' => "Page '{$page['title']}' updated successfully",
        'layout_id' => $layoutId,
        'file_path' => $filePath,
        'version' => ($currentLayout['version'] ?? 0) + 1
    ];
}

/**
 * Handle settings update sync - Global settings propagation
 */
function handleSettingsUpdate($data, $syncId) {
    global $db;
    
    $category = sanitize_input($data['category'] ?? '');
    $settings = $data['settings'] ?? [];
    
    if (!$category || empty($settings)) {
        throw new Exception('Category and settings required');
    }
    
    // Get current settings for audit
    $currentSettings = [];
    foreach (array_keys($settings) as $key) {
        $current = $db->fetchOne(
            "SELECT setting_value FROM tts_settings WHERE setting_key = ? AND category = ?",
            [$key, $category]
        );
        if ($current) {
            $currentSettings[$key] = $current['setting_value'];
        }
    }
    
    // Update each setting
    foreach ($settings as $key => $value) {
        $db->query("
            INSERT INTO tts_settings (setting_key, setting_value, category, description) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                updated_at = CURRENT_TIMESTAMP
        ", [$key, $value, $category, getSettingDescription($key)]);
    }
    
    // Apply settings to configuration files
    applySettingsToFiles($category, $settings);
    
    // Clear settings cache
    clearSettingsCache($category);
    
    return [
        'message' => ucfirst($category) . ' settings updated successfully',
        'category' => $category,
        'updated_count' => count($settings),
        'before' => $currentSettings,
        'after' => $settings
    ];
}

/**
 * Handle module toggle sync - Enable/disable modules with dependency checking
 */
function handleModuleToggle($data, $syncId) {
    global $db;
    
    $moduleName = sanitize_input($data['module_name'] ?? '');
    $isEnabled = (bool)($data['is_enabled'] ?? false);
    $skipDependencyCheck = (bool)($data['skip_dependency_check'] ?? false);
    
    if (!$moduleName) {
        throw new Exception('Module name required');
    }
    
    // Get current module status
    $currentModule = $db->fetchOne(
        "SELECT * FROM tts_module_config WHERE module_name = ?",
        [$moduleName]
    );
    
    if (!$currentModule) {
        throw new Exception('Module not found');
    }
    
    // Check dependencies if disabling
    if (!$isEnabled && !$skipDependencyCheck) {
        $dependencies = checkModuleDependencies($moduleName);
        if (!empty($dependencies)) {
            throw new Exception('Cannot disable module. Dependencies: ' . implode(', ', $dependencies));
        }
    }
    
    // Update module status
    $db->update('tts_module_config', [
        'is_enabled' => $isEnabled,
        'admin_id' => $_SESSION['user_id'],
        'updated_at' => date('Y-m-d H:i:s')
    ], 'module_name = ?', [$moduleName]);
    
    // Rebuild navigation cache
    rebuildNavigationCache();
    
    // Clear module-specific cache
    clearModuleCache($moduleName);
    
    return [
        'message' => "Module '$moduleName' " . ($isEnabled ? 'enabled' : 'disabled') . ' successfully',
        'module_name' => $moduleName,
        'enabled' => $isEnabled,
        'previous_status' => (bool)$currentModule['is_enabled']
    ];
}

/**
 * Handle payroll update sync - Rate and bonus propagation
 */
function handlePayrollUpdate($data, $syncId) {
    global $db;
    
    $settings = $data['settings'] ?? [];
    $recalculateExisting = (bool)($data['recalculate_existing'] ?? false);
    
    if (empty($settings)) {
        throw new Exception('Payroll settings required');
    }
    
    // Get current payroll settings for audit
    $currentSettings = [];
    foreach (array_keys($settings) as $key) {
        $current = $db->fetchOne(
            "SELECT setting_value FROM tts_settings WHERE setting_key = ? AND category = 'payroll'",
            [$key]
        );
        if ($current) {
            $currentSettings[$key] = $current['setting_value'];
        }
    }
    
    // Update payroll settings
    foreach ($settings as $key => $value) {
        $db->query("
            INSERT INTO tts_settings (setting_key, setting_value, category, description) 
            VALUES (?, ?, 'payroll', ?)
            ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                updated_at = CURRENT_TIMESTAMP
        ", [$key, $value, getSettingDescription($key)]);
    }
    
    // Update payroll configuration file
    updatePayrollConfigFile($settings);
    
    // Queue payroll recalculation if requested
    if ($recalculateExisting) {
        queuePayrollRecalculation($syncId);
    }
    
    return [
        'message' => 'Payroll settings updated successfully',
        'updated_count' => count($settings),
        'recalculation_queued' => $recalculateExisting,
        'before' => $currentSettings,
        'after' => $settings
    ];
}

/**
 * Handle SEO update sync - Meta tag propagation across pages
 */
function handleSEOUpdate($data, $syncId) {
    global $db;
    
    $seoSettings = $data['settings'] ?? [];
    $updateExistingPages = (bool)($data['update_existing_pages'] ?? true);
    
    if (empty($seoSettings)) {
        throw new Exception('SEO settings required');
    }
    
    // Get current SEO settings for audit
    $currentSettings = [];
    foreach (array_keys($seoSettings) as $key) {
        $current = $db->fetchOne(
            "SELECT setting_value FROM tts_settings WHERE setting_key = ? AND category = 'seo'",
            [$key]
        );
        if ($current) {
            $currentSettings[$key] = $current['setting_value'];
        }
    }
    
    // Update SEO settings in database
    foreach ($seoSettings as $key => $value) {
        $db->query("
            INSERT INTO tts_settings (setting_key, setting_value, category, description) 
            VALUES (?, ?, 'seo', ?)
            ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                updated_at = CURRENT_TIMESTAMP
        ", [$key, $value, getSettingDescription($key)]);
    }
    
    $updatedFiles = [];
    
    // Update existing pages if requested
    if ($updateExistingPages) {
        $updatedFiles = updateSEOInExistingPages($seoSettings);
    }
    
    // Update template files
    updateSEOInTemplates($seoSettings);
    
    return [
        'message' => 'SEO settings updated successfully',
        'updated_count' => count($seoSettings),
        'files_updated' => count($updatedFiles),
        'updated_files' => $updatedFiles,
        'before' => $currentSettings,
        'after' => $seoSettings
    ];
}
?>
