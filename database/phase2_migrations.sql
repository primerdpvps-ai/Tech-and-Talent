-- TTS PMS Super Admin Phase 2 - Database Migrations
-- MariaDB 10.6.23 Compatible - Role & Permission System

-- Roles table
CREATE TABLE IF NOT EXISTS `tts_roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_name` VARCHAR(100) NOT NULL UNIQUE,
    `display_name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `is_system` BOOLEAN DEFAULT FALSE,
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_role_name` (`role_name`),
    INDEX `idx_system` (`is_system`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Capabilities table
CREATE TABLE IF NOT EXISTS `tts_capabilities` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `capability_key` VARCHAR(100) NOT NULL UNIQUE,
    `display_name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `category` VARCHAR(50) NOT NULL,
    `is_system` BOOLEAN DEFAULT TRUE,
    INDEX `idx_capability_key` (`capability_key`),
    INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role-Capability mapping
CREATE TABLE IF NOT EXISTS `tts_role_capability` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT NOT NULL,
    `capability_id` INT NOT NULL,
    `granted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `granted_by` INT NOT NULL,
    UNIQUE KEY `unique_role_capability` (`role_id`, `capability_id`),
    FOREIGN KEY (`role_id`) REFERENCES `tts_roles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`capability_id`) REFERENCES `tts_capabilities`(`id`) ON DELETE CASCADE,
    INDEX `idx_role_id` (`role_id`),
    INDEX `idx_capability_id` (`capability_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User-Role mapping (extends existing tts_users)
CREATE TABLE IF NOT EXISTS `tts_user_role` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `role_id` INT NOT NULL,
    `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `assigned_by` INT NOT NULL,
    `expires_at` TIMESTAMP NULL,
    UNIQUE KEY `unique_user_role` (`user_id`, `role_id`),
    FOREIGN KEY (`user_id`) REFERENCES `tts_users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`role_id`) REFERENCES `tts_roles`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_role_id` (`role_id`),
    INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enhanced module config (already exists, adding indexes)
ALTER TABLE `tts_module_config` 
ADD INDEX IF NOT EXISTS `idx_enabled` (`is_enabled`),
ADD INDEX IF NOT EXISTS `idx_updated` (`updated_at`);

-- Enhanced admin edits for better querying
ALTER TABLE `tts_admin_edits`
ADD COLUMN IF NOT EXISTS `object_type` VARCHAR(50) NULL AFTER `target_table`,
ADD COLUMN IF NOT EXISTS `object_id` VARCHAR(100) NULL AFTER `object_type`,
ADD COLUMN IF NOT EXISTS `before_json` JSON NULL AFTER `changes`,
ADD COLUMN IF NOT EXISTS `after_json` JSON NULL AFTER `before_json`,
ADD INDEX IF NOT EXISTS `idx_object` (`object_type`, `object_id`),
ADD INDEX IF NOT EXISTS `idx_admin_action` (`admin_id`, `action_type`);

-- Insert default capabilities
INSERT IGNORE INTO `tts_capabilities` (`capability_key`, `display_name`, `description`, `category`) VALUES
-- Page Management
('manage_pages', 'Manage Pages', 'Create, edit, and delete website pages', 'content'),
('manage_posts', 'Manage Posts', 'Create, edit, and delete blog posts and content', 'content'),
('manage_builder', 'Page Builder Access', 'Use visual page builder and templates', 'content'),
('manage_media', 'Manage Media', 'Upload and manage images, files, and media', 'content'),

-- System Settings
('manage_settings', 'System Settings', 'Configure global system settings', 'system'),
('manage_branding', 'Branding Control', 'Update logos, colors, and site branding', 'system'),
('manage_modules', 'Module Control', 'Enable/disable system modules', 'system'),
('manage_database', 'Database Access', 'View and manage database tables', 'system'),

-- Financial
('manage_payroll', 'Payroll Management', 'Configure payroll settings and process payments', 'financial'),
('view_finance', 'Financial Reports', 'View financial reports and analytics', 'financial'),
('manage_billing', 'Billing Management', 'Manage client billing and invoices', 'financial'),

-- HR & Operations
('approve_applications', 'Approve Applications', 'Review and approve job applications', 'hr'),
('approve_leaves', 'Approve Leave Requests', 'Approve or deny employee leave requests', 'hr'),
('manage_training', 'Training Management', 'Manage training modules and assignments', 'hr'),
('manage_evaluations', 'Employee Evaluations', 'Conduct and manage employee evaluations', 'hr'),

-- User Management
('manage_users', 'User Management', 'Create, edit, and delete user accounts', 'users'),
('manage_roles', 'Role Management', 'Create and assign user roles and permissions', 'users'),
('view_audit_log', 'Audit Log Access', 'View system audit logs and changes', 'users'),

-- Advanced
('system_admin', 'System Administrator', 'Full system access (super admin)', 'advanced'),
('developer_access', 'Developer Tools', 'Access to developer tools and debugging', 'advanced');

-- Insert default roles
INSERT IGNORE INTO `tts_roles` (`role_name`, `display_name`, `description`, `is_system`, `created_by`) VALUES
('super_admin', 'Super Administrator', 'Full system access with all capabilities', TRUE, 1),
('admin', 'Administrator', 'Standard admin with most capabilities', TRUE, 1),
('manager', 'Manager', 'Management role with HR and operational access', TRUE, 1),
('hr_specialist', 'HR Specialist', 'Human resources focused role', TRUE, 1),
('finance_manager', 'Finance Manager', 'Financial management and reporting', TRUE, 1),
('content_editor', 'Content Editor', 'Content creation and page management', TRUE, 1),
('employee', 'Employee', 'Standard employee access', TRUE, 1),
('viewer', 'Viewer', 'Read-only access to most areas', TRUE, 1);

-- Assign capabilities to super_admin role (all capabilities)
INSERT IGNORE INTO `tts_role_capability` (`role_id`, `capability_id`, `granted_by`)
SELECT r.id, c.id, 1
FROM `tts_roles` r
CROSS JOIN `tts_capabilities` c
WHERE r.role_name = 'super_admin';

-- Assign capabilities to admin role (most capabilities except system_admin)
INSERT IGNORE INTO `tts_role_capability` (`role_id`, `capability_id`, `granted_by`)
SELECT r.id, c.id, 1
FROM `tts_roles` r
CROSS JOIN `tts_capabilities` c
WHERE r.role_name = 'admin' 
AND c.capability_key != 'system_admin' 
AND c.capability_key != 'developer_access';

-- Assign capabilities to manager role
INSERT IGNORE INTO `tts_role_capability` (`role_id`, `capability_id`, `granted_by`)
SELECT r.id, c.id, 1
FROM `tts_roles` r
CROSS JOIN `tts_capabilities` c
WHERE r.role_name = 'manager' 
AND c.capability_key IN (
    'approve_applications', 'approve_leaves', 'manage_training', 
    'manage_evaluations', 'view_finance', 'manage_users'
);

-- Assign capabilities to hr_specialist role
INSERT IGNORE INTO `tts_role_capability` (`role_id`, `capability_id`, `granted_by`)
SELECT r.id, c.id, 1
FROM `tts_roles` r
CROSS JOIN `tts_capabilities` c
WHERE r.role_name = 'hr_specialist' 
AND c.capability_key IN (
    'approve_applications', 'approve_leaves', 'manage_training', 
    'manage_evaluations', 'manage_users'
);

-- Assign capabilities to finance_manager role
INSERT IGNORE INTO `tts_role_capability` (`role_id`, `capability_id`, `granted_by`)
SELECT r.id, c.id, 1
FROM `tts_roles` r
CROSS JOIN `tts_capabilities` c
WHERE r.role_name = 'finance_manager' 
AND c.capability_key IN (
    'manage_payroll', 'view_finance', 'manage_billing'
);

-- Assign capabilities to content_editor role
INSERT IGNORE INTO `tts_role_capability` (`role_id`, `capability_id`, `granted_by`)
SELECT r.id, c.id, 1
FROM `tts_roles` r
CROSS JOIN `tts_capabilities` c
WHERE r.role_name = 'content_editor' 
AND c.capability_key IN (
    'manage_pages', 'manage_posts', 'manage_builder', 'manage_media', 'manage_branding'
);

-- Assign super_admin role to admin user (ID 1)
INSERT IGNORE INTO `tts_user_role` (`user_id`, `role_id`, `assigned_by`)
SELECT 1, r.id, 1
FROM `tts_roles` r
WHERE r.role_name = 'super_admin';

-- Update existing users with appropriate roles based on their current role column
INSERT IGNORE INTO `tts_user_role` (`user_id`, `role_id`, `assigned_by`)
SELECT u.id, r.id, 1
FROM `tts_users` u
JOIN `tts_roles` r ON (
    (u.role = 'admin' AND r.role_name = 'admin') OR
    (u.role = 'manager' AND r.role_name = 'manager') OR
    (u.role = 'employee' AND r.role_name = 'employee') OR
    (u.role = 'ceo' AND r.role_name = 'super_admin') OR
    (u.role IN ('visitor', 'candidate', 'new_employee') AND r.role_name = 'viewer')
)
WHERE u.id != 1; -- Skip admin user already assigned above

COMMIT;
