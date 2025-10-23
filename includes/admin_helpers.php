<?php
/**
 * TTS PMS - Super Admin Helper Functions
 * Runtime helpers for capability checking and module control
 */

if (!defined('TTS_PMS_INIT')) {
    die('Direct access not allowed');
}

/**
 * Check if current user has a specific capability
 */
function user_can($capability, $userId = null) {
    global $db;
    
    if (!$userId) {
        $userId = $_SESSION['user_id'] ?? null;
    }
    
    if (!$userId) {
        return false;
    }
    
    try {
        $result = $db->fetchOne("
            SELECT COUNT(*) as has_capability
            FROM tts_user_role ur
            JOIN tts_role_capability rc ON ur.role_id = rc.role_id
            JOIN tts_capabilities c ON rc.capability_id = c.id
            WHERE ur.user_id = ? 
            AND c.capability_key = ?
            AND (ur.expires_at IS NULL OR ur.expires_at > NOW())
        ", [$userId, $capability]);
        
        return $result['has_capability'] > 0;
    } catch (Exception $e) {
        error_log("Capability check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if a module is enabled
 */
function module_enabled($moduleName) {
    global $db;
    
    try {
        $result = $db->fetchOne("
            SELECT is_enabled 
            FROM tts_module_config 
            WHERE module_name = ?
        ", [$moduleName]);
        
        return $result ? (bool)$result['is_enabled'] : false;
    } catch (Exception $e) {
        error_log("Module check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user capabilities
 */
function get_user_capabilities($userId = null) {
    global $db;
    
    if (!$userId) {
        $userId = $_SESSION['user_id'] ?? null;
    }
    
    if (!$userId) {
        return [];
    }
    
    try {
        $result = $db->fetchAll("
            SELECT DISTINCT c.capability_key, c.display_name, c.category
            FROM tts_user_role ur
            JOIN tts_role_capability rc ON ur.role_id = rc.role_id
            JOIN tts_capabilities c ON rc.capability_id = c.id
            WHERE ur.user_id = ?
            AND (ur.expires_at IS NULL OR ur.expires_at > NOW())
            ORDER BY c.category, c.display_name
        ", [$userId]);
        
        return $result ?: [];
    } catch (Exception $e) {
        error_log("Get capabilities error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get user roles
 */
function get_user_roles($userId = null) {
    global $db;
    
    if (!$userId) {
        $userId = $_SESSION['user_id'] ?? null;
    }
    
    if (!$userId) {
        return [];
    }
    
    try {
        $result = $db->fetchAll("
            SELECT r.id, r.role_name, r.display_name, r.description
            FROM tts_user_role ur
            JOIN tts_roles r ON ur.role_id = r.id
            WHERE ur.user_id = ?
            AND (ur.expires_at IS NULL OR ur.expires_at > NOW())
            ORDER BY r.display_name
        ", [$userId]);
        
        return $result ?: [];
    } catch (Exception $e) {
        error_log("Get roles error: " . $e->getMessage());
        return [];
    }
}

/**
 * Require capability or redirect
 */
function require_capability($capability, $redirectUrl = 'index.php') {
    if (!user_can($capability)) {
        header("Location: $redirectUrl");
        exit;
    }
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Log admin action to audit trail
 */
function log_admin_action($actionType, $objectType = null, $objectId = null, $beforeData = null, $afterData = null, $description = null) {
    global $db;
    
    try {
        $adminId = $_SESSION['user_id'] ?? 1;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $data = [
            'admin_id' => $adminId,
            'action_type' => $actionType,
            'object_type' => $objectType,
            'object_id' => $objectId,
            'changes' => $description,
            'before_json' => $beforeData ? json_encode($beforeData) : null,
            'after_json' => $afterData ? json_encode($afterData) : null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $db->insert('tts_admin_edits', $data);
    } catch (Exception $e) {
        error_log("Audit log error: " . $e->getMessage());
    }
}

/**
 * Check if user is admin (has admin session)
 */
function is_admin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Require admin access
 */
function require_admin($redirectUrl = 'login.php') {
    if (!is_admin()) {
        header("Location: $redirectUrl");
        exit;
    }
}

/**
 * Get module configuration
 */
function get_module_config($moduleName) {
    global $db;
    
    try {
        $result = $db->fetchOne("
            SELECT config_data, is_enabled 
            FROM tts_module_config 
            WHERE module_name = ?
        ", [$moduleName]);
        
        if ($result) {
            return [
                'enabled' => (bool)$result['is_enabled'],
                'config' => $result['config_data'] ? json_decode($result['config_data'], true) : []
            ];
        }
        
        return ['enabled' => false, 'config' => []];
    } catch (Exception $e) {
        error_log("Module config error: " . $e->getMessage());
        return ['enabled' => false, 'config' => []];
    }
}

/**
 * Update module configuration
 */
function update_module_config($moduleName, $isEnabled, $configData = []) {
    global $db;
    
    try {
        $adminId = $_SESSION['user_id'] ?? 1;
        
        // Get current config for audit
        $currentConfig = get_module_config($moduleName);
        
        $db->query("
            INSERT INTO tts_module_config (module_name, is_enabled, config_data, admin_id) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                is_enabled = VALUES(is_enabled),
                config_data = VALUES(config_data),
                admin_id = VALUES(admin_id),
                updated_at = CURRENT_TIMESTAMP
        ", [$moduleName, $isEnabled, json_encode($configData), $adminId]);
        
        // Log the change
        log_admin_action(
            'module_toggle',
            'module',
            $moduleName,
            $currentConfig,
            ['enabled' => $isEnabled, 'config' => $configData],
            "Module $moduleName " . ($isEnabled ? 'enabled' : 'disabled')
        );
        
        return true;
    } catch (Exception $e) {
        error_log("Update module config error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all available modules
 */
function get_all_modules() {
    return [
        'gigs' => [
            'name' => 'Gigs & Services',
            'description' => 'Freelance gig management and service marketplace',
            'icon' => 'fas fa-briefcase',
            'dependencies' => []
        ],
        'payroll' => [
            'name' => 'Payroll Management',
            'description' => 'Employee payroll processing and salary management',
            'icon' => 'fas fa-money-bill-wave',
            'dependencies' => []
        ],
        'training' => [
            'name' => 'Training Modules',
            'description' => 'Employee training and skill development programs',
            'icon' => 'fas fa-graduation-cap',
            'dependencies' => []
        ],
        'leave_management' => [
            'name' => 'Leave Management',
            'description' => 'Employee leave requests and approval workflow',
            'icon' => 'fas fa-calendar-times',
            'dependencies' => []
        ],
        'evaluations' => [
            'name' => 'Employee Evaluations',
            'description' => 'Performance evaluations and skill assessments',
            'icon' => 'fas fa-clipboard-check',
            'dependencies' => []
        ],
        'time_tracking' => [
            'name' => 'Time Tracking',
            'description' => 'Work hours tracking and timesheet management',
            'icon' => 'fas fa-clock',
            'dependencies' => []
        ],
        'onboarding' => [
            'name' => 'Employee Onboarding',
            'description' => 'New employee onboarding process and tasks',
            'icon' => 'fas fa-user-plus',
            'dependencies' => ['training']
        ],
        'page_builder' => [
            'name' => 'Visual Page Builder',
            'description' => 'Drag-and-drop page builder and template system',
            'icon' => 'fas fa-edit',
            'dependencies' => []
        ]
    ];
}

/**
 * Check module dependencies
 */
function check_module_dependencies($moduleName, $action = 'disable') {
    $modules = get_all_modules();
    $issues = [];
    
    if (!isset($modules[$moduleName])) {
        return ['Module not found'];
    }
    
    if ($action === 'disable') {
        // Check if other modules depend on this one
        foreach ($modules as $name => $config) {
            if (in_array($moduleName, $config['dependencies']) && module_enabled($name)) {
                $issues[] = "Module '{$config['name']}' depends on this module";
            }
        }
    } elseif ($action === 'enable') {
        // Check if dependencies are enabled
        foreach ($modules[$moduleName]['dependencies'] as $dependency) {
            if (!module_enabled($dependency)) {
                $depName = $modules[$dependency]['name'] ?? $dependency;
                $issues[] = "Required dependency '$depName' is not enabled";
            }
        }
    }
    
    return $issues;
}

/**
 * Sanitize input for database
 */
function sanitize_input($input, $type = 'string') {
    switch ($type) {
        case 'int':
            return (int)$input;
        case 'float':
            return (float)$input;
        case 'bool':
            return (bool)$input;
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        case 'url':
            return filter_var($input, FILTER_SANITIZE_URL);
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Format bytes for display
 */
function format_bytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Get pagination info
 */
function get_pagination($total, $page, $perPage = 20) {
    $totalPages = ceil($total / $perPage);
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    
    return [
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_prev' => $page > 1,
        'has_next' => $page < $totalPages,
        'prev_page' => $page - 1,
        'next_page' => $page + 1
    ];
}
?>
