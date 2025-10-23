<?php
/**
 * TTS PMS - Application Configuration
 * Central configuration for all system features
 */

// Prevent direct access
if (!defined('TTS_PMS_INIT')) {
    die('Direct access not allowed');
}

// ========================================
// APPLICATION SETTINGS
// ========================================

define('APP_NAME', 'Tech & Talent Solutions PMS');
define('APP_VERSION', '2.0.0');
define('APP_DESCRIPTION', 'Precision Data, Global Talent');
define('APP_URL', 'https://pms.prizmasoft.com');
define('COMPANY_NAME', 'Tech & Talent Solutions');

// ========================================
// PAYROLL SYSTEM CONFIGURATION
// ========================================

// Salary structures by role (in PKR)
define('SALARY_STRUCTURES', [
    'visitor' => [
        'basic_salary' => 0,
        'allowances' => 0,
        'can_overtime' => false
    ],
    'candidate' => [
        'basic_salary' => 0,
        'allowances' => 0,
        'can_overtime' => false
    ],
    'new_employee' => [
        'basic_salary' => 25000,
        'allowances' => 5000,
        'training_allowance' => 3000,
        'can_overtime' => false,
        'tax_exempt' => true
    ],
    'employee' => [
        'basic_salary' => 50000,
        'allowances' => 10000,
        'overtime_rate' => 500,
        'can_overtime' => true,
        'tax_rate' => 0.05
    ],
    'manager' => [
        'basic_salary' => 80000,
        'allowances' => 20000,
        'management_allowance' => 15000,
        'can_overtime' => false,
        'tax_rate' => 0.075
    ],
    'ceo' => [
        'basic_salary' => 150000,
        'allowances' => 50000,
        'executive_allowance' => 30000,
        'performance_bonus' => 25000,
        'can_overtime' => false,
        'tax_rate' => 0.10
    ]
]);

// Payroll settings
define('PAYROLL_SETTINGS', [
    'pay_frequency' => 'monthly',
    'pay_day' => 1, // 1st of each month
    'currency' => 'PKR',
    'working_days_per_month' => 22,
    'working_hours_per_day' => 8,
    'insurance_rate' => 0.02, // 2% of basic salary
    'provident_fund_rate' => 0.05 // 5% of basic salary
]);

// ========================================
// LEAVE MANAGEMENT CONFIGURATION
// ========================================

// Leave entitlements by role
define('LEAVE_ENTITLEMENTS', [
    'visitor' => [
        'annual_leave' => 0,
        'sick_leave' => 0,
        'casual_leave' => 0,
        'emergency_leave' => 0
    ],
    'candidate' => [
        'annual_leave' => 0,
        'sick_leave' => 0,
        'casual_leave' => 0,
        'emergency_leave' => 0
    ],
    'new_employee' => [
        'annual_leave' => 0, // Not eligible during training
        'sick_leave' => 5,
        'casual_leave' => 0,
        'emergency_leave' => 2
    ],
    'employee' => [
        'annual_leave' => 21,
        'sick_leave' => 10,
        'casual_leave' => 5,
        'emergency_leave' => 3
    ],
    'manager' => [
        'annual_leave' => 25,
        'sick_leave' => 12,
        'casual_leave' => 8,
        'emergency_leave' => 5
    ],
    'ceo' => [
        'annual_leave' => 30,
        'sick_leave' => 15,
        'casual_leave' => 10,
        'emergency_leave' => 5,
        'executive_leave' => 10
    ]
]);

// Leave approval hierarchy
define('LEAVE_APPROVAL_HIERARCHY', [
    'new_employee' => ['manager', 'ceo'], // Requires manager or CEO approval
    'employee' => ['manager', 'ceo'],
    'manager' => ['ceo'], // Only CEO can approve manager leave
    'ceo' => ['auto'] // CEO leave is auto-approved
]);

// ========================================
// TIME TRACKING CONFIGURATION
// ========================================

define('TIME_TRACKING_SETTINGS', [
    'work_start_time' => '09:00',
    'work_end_time' => '17:00',
    'break_duration_minutes' => 60,
    'overtime_threshold_hours' => 8,
    'late_threshold_minutes' => 15,
    'early_departure_threshold_minutes' => 15,
    'minimum_work_hours' => 4,
    'maximum_work_hours' => 12
]);

// Training period settings for new employees
define('TRAINING_SETTINGS', [
    'training_period_days' => 90,
    'target_hours_per_month' => 80, // Reduced for training
    'mandatory_modules' => 5,
    'probation_period_days' => 180
]);

// ========================================
// EVALUATION SYSTEM CONFIGURATION
// ========================================

define('EVALUATION_CRITERIA', [
    'minimum_age' => 16,
    'maximum_age' => 65,
    'minimum_ram_gb' => 4,
    'minimum_internet_speed_mbps' => 5,
    'minimum_typing_speed_wpm' => 20,
    'allowed_devices' => ['PC', 'Laptop', 'Chromebook', 'Tablet'],
    'rejected_devices' => ['Android', 'iPhone']
]);

// Internet speed sharing rules
define('INTERNET_SHARING_RULES', [
    5 => 1,   // 5-7 Mbps: max 1 user
    8 => 2,   // 8-19 Mbps: max 2 users
    20 => 3   // 20+ Mbps: max 3 users
]);

// ========================================
// NOTIFICATION SETTINGS
// ========================================

define('NOTIFICATION_SETTINGS', [
    'email_notifications' => true,
    'sms_notifications' => false,
    'push_notifications' => true,
    'notification_types' => [
        'leave_request_submitted',
        'leave_request_approved',
        'leave_request_rejected',
        'payslip_generated',
        'task_assigned',
        'task_deadline_reminder',
        'training_module_assigned',
        'evaluation_completed'
    ]
]);

// ========================================
// SECURITY SETTINGS
// ========================================

define('SECURITY_SETTINGS', [
    'session_timeout_minutes' => 120,
    'password_min_length' => 8,
    'password_require_special_chars' => true,
    'max_login_attempts' => 5,
    'lockout_duration_minutes' => 30,
    'two_factor_auth_enabled' => false,
    'audit_log_enabled' => true
]);

// ========================================
// FILE UPLOAD SETTINGS
// ========================================

define('UPLOAD_SETTINGS', [
    'max_file_size_mb' => 10,
    'allowed_file_types' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
    'upload_path' => dirname(__DIR__) . '/uploads/',
    'profile_pictures_path' => dirname(__DIR__) . '/uploads/profiles/',
    'documents_path' => dirname(__DIR__) . '/uploads/documents/'
]);

// ========================================
// EMAIL SETTINGS
// ========================================

define('EMAIL_SETTINGS', [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'noreply@tts-pms.com',
    'smtp_password' => '', // Set in environment
    'from_email' => 'noreply@tts-pms.com',
    'from_name' => 'TTS PMS System',
    'reply_to' => 'hr@tts-pms.com'
]);

// ========================================
// DASHBOARD SETTINGS
// ========================================

define('DASHBOARD_SETTINGS', [
    'refresh_interval_seconds' => 300, // 5 minutes
    'chart_colors' => [
        'primary' => '#007bff',
        'success' => '#28a745',
        'warning' => '#ffc107',
        'danger' => '#dc3545',
        'info' => '#17a2b8'
    ],
    'items_per_page' => 20,
    'recent_activities_limit' => 10
]);

// ========================================
// API SETTINGS
// ========================================

define('API_SETTINGS', [
    'rate_limit_requests_per_minute' => 60,
    'api_key_length' => 32,
    'jwt_secret' => 'your-jwt-secret-key', // Change in production
    'jwt_expiry_hours' => 24
]);

// ========================================
// BACKUP SETTINGS
// ========================================

define('BACKUP_SETTINGS', [
    'auto_backup_enabled' => true,
    'backup_frequency_hours' => 24,
    'backup_retention_days' => 30,
    'backup_path' => dirname(__DIR__) . '/backups/',
    'backup_database' => true,
    'backup_files' => true
]);

// ========================================
// HELPER FUNCTIONS
// ========================================

/**
 * Get salary structure for a specific role
 */
function getSalaryStructure($role) {
    $structures = SALARY_STRUCTURES;
    return $structures[$role] ?? $structures['employee'];
}

/**
 * Get leave entitlements for a specific role
 */
function getLeaveEntitlements($role) {
    $entitlements = LEAVE_ENTITLEMENTS;
    return $entitlements[$role] ?? $entitlements['employee'];
}

/**
 * Check if user can approve leave for another user
 */
function canApproveLeave($approverRole, $requesterRole) {
    $hierarchy = LEAVE_APPROVAL_HIERARCHY;
    $requiredApprovers = $hierarchy[$requesterRole] ?? ['manager', 'ceo'];
    
    return in_array($approverRole, $requiredApprovers) || in_array('auto', $requiredApprovers);
}

/**
 * Calculate tax based on role and salary
 */
function calculateTax($role, $grossSalary) {
    $structure = getSalaryStructure($role);
    
    if (isset($structure['tax_exempt']) && $structure['tax_exempt']) {
        return 0;
    }
    
    $taxRate = $structure['tax_rate'] ?? 0.05;
    return $grossSalary * $taxRate;
}

/**
 * Get working hours target based on role
 */
function getWorkingHoursTarget($role) {
    if ($role === 'new_employee') {
        return TRAINING_SETTINGS['target_hours_per_month'];
    }
    
    $payrollSettings = PAYROLL_SETTINGS;
    return $payrollSettings['working_days_per_month'] * $payrollSettings['working_hours_per_day'];
}

/**
 * Check if device is allowed for evaluation
 */
function isDeviceAllowed($deviceType) {
    $criteria = EVALUATION_CRITERIA;
    return in_array($deviceType, $criteria['allowed_devices']);
}

/**
 * Get maximum users allowed for internet speed
 */
function getMaxUsersForSpeed($speedMbps) {
    $rules = INTERNET_SHARING_RULES;
    
    foreach ($rules as $minSpeed => $maxUsers) {
        if ($speedMbps >= $minSpeed) {
            continue;
        }
        return $maxUsers;
    }
    
    return max($rules); // Return highest if speed is very high
}

// ========================================
// ENVIRONMENT SPECIFIC OVERRIDES
// ========================================

// Load environment-specific configuration if exists
$envConfigFile = __DIR__ . '/env_config.php';
if (file_exists($envConfigFile)) {
    require_once $envConfigFile;
}

?>
