-- TTS PMS Super Admin Integration Tables
-- Additional tables for Super Admin master control system

-- Settings table for global configuration
CREATE TABLE IF NOT EXISTS `tts_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT NULL,
    `category` VARCHAR(50) NOT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_setting` (`setting_key`, `category`),
    INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin edits audit log
CREATE TABLE IF NOT EXISTS `tts_admin_edits` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT NOT NULL,
    `action_type` ENUM('page_edit', 'settings_update', 'user_modify', 'module_toggle', 'database_edit') NOT NULL,
    `target_table` VARCHAR(100) NULL,
    `target_id` INT NULL,
    `changes` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_admin_id` (`admin_id`),
    INDEX `idx_action_type` (`action_type`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Page layouts and builder templates
CREATE TABLE IF NOT EXISTS `tts_page_layouts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `file_path` VARCHAR(500) NOT NULL,
    `layout_name` VARCHAR(100) NULL,
    `original_content` LONGTEXT NULL,
    `new_content` LONGTEXT NULL,
    `admin_id` INT NOT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `backup_created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_file_path` (`file_path`),
    INDEX `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CMS history for rollback functionality
CREATE TABLE IF NOT EXISTS `tts_cms_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `content_type` ENUM('page', 'setting', 'template', 'module') NOT NULL,
    `content_id` VARCHAR(255) NOT NULL,
    `version_number` INT NOT NULL DEFAULT 1,
    `content_data` LONGTEXT NULL,
    `admin_id` INT NOT NULL,
    `change_description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_content` (`content_type`, `content_id`),
    INDEX `idx_version` (`version_number`),
    INDEX `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Builder templates library
CREATE TABLE IF NOT EXISTS `tts_builder_templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `template_name` VARCHAR(100) NOT NULL,
    `template_type` ENUM('page', 'component', 'layout') NOT NULL,
    `template_content` LONGTEXT NOT NULL,
    `preview_image` VARCHAR(500) NULL,
    `category` VARCHAR(50) NULL,
    `is_system` BOOLEAN DEFAULT FALSE,
    `admin_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_template_type` (`template_type`),
    INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin sync events for real-time updates
CREATE TABLE IF NOT EXISTS `tts_admin_sync` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_type` VARCHAR(50) NOT NULL,
    `target_path` VARCHAR(500) NULL,
    `sync_data` JSON NULL,
    `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    `admin_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `processed_at` TIMESTAMP NULL,
    INDEX `idx_status` (`status`),
    INDEX `idx_event_type` (`event_type`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Module configurations
CREATE TABLE IF NOT EXISTS `tts_module_config` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `module_name` VARCHAR(100) NOT NULL UNIQUE,
    `is_enabled` BOOLEAN DEFAULT TRUE,
    `config_data` JSON NULL,
    `dependencies` JSON NULL,
    `version` VARCHAR(20) NULL,
    `admin_id` INT NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_module_name` (`module_name`),
    INDEX `idx_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT IGNORE INTO `tts_settings` (`setting_key`, `setting_value`, `category`, `description`) VALUES
-- Payroll settings
('base_hourly_rate', '125', 'payroll', 'Base hourly rate in PKR'),
('streak_bonus', '500', 'payroll', '28-day streak bonus in PKR'),
('daily_working_hours', '8', 'payroll', 'Standard daily working hours'),
('overtime_multiplier', '1.5', 'payroll', 'Overtime rate multiplier'),

-- Email settings
('smtp_host', 'smtp.gmail.com', 'email', 'SMTP server host'),
('smtp_port', '465', 'email', 'SMTP server port'),
('smtp_username', 'tts.workhub@gmail.com', 'email', 'SMTP username'),
('smtp_password', 'wcjr uqat kqlz npvd', 'email', 'SMTP password'),
('smtp_encryption', 'ssl', 'email', 'SMTP encryption type'),
('from_email', 'tts.workhub@gmail.com', 'email', 'Default from email address'),
('from_name', 'TTS WorkHub', 'email', 'Default from name'),

-- Branding settings
('site_name', 'Tech & Talent Solutions', 'branding', 'Site name'),
('tagline', 'Precision Data, Global Talent', 'branding', 'Site tagline'),
('logo_url', '', 'branding', 'Logo URL'),
('favicon_url', '', 'branding', 'Favicon URL'),
('footer_text', '© 2024 Tech & Talent Solutions. All rights reserved.', 'branding', 'Footer text'),

-- SEO settings
('meta_title', 'Tech & Talent Solutions - Professional Services', 'seo', 'Default meta title'),
('meta_description', 'Leading provider of professional tech services and workforce management solutions in Pakistan.', 'seo', 'Default meta description'),
('canonical_url', 'https://pms.prizmasoft.com', 'seo', 'Canonical URL'),
('meta_keywords', 'data entry, professional services, workforce management', 'seo', 'Meta keywords'),

-- Auth settings
('gmail_only', 'true', 'auth', 'Require Gmail addresses only'),
('otp_cooldown', '60', 'auth', 'OTP cooldown in seconds'),
('session_timeout', '3600', 'auth', 'Session timeout in seconds'),
('max_login_attempts', '5', 'auth', 'Maximum login attempts');

-- Insert default module configurations
INSERT IGNORE INTO `tts_module_config` (`module_name`, `is_enabled`, `config_data`, `admin_id`) VALUES
('payroll', TRUE, '{"auto_calculate": true, "approval_required": true}', 1),
('training', TRUE, '{"auto_assign": true, "completion_tracking": true}', 1),
('leave_management', TRUE, '{"auto_approval_days": 2, "manager_approval": true}', 1),
('evaluations', TRUE, '{"passing_score": 70, "retake_allowed": true}', 1),
('time_tracking', TRUE, '{"auto_break": true, "overtime_alert": true}', 1),
('onboarding', TRUE, '{"auto_tasks": true, "mentor_assignment": true}', 1),
('gigs', TRUE, '{"auto_matching": false, "skill_verification": true}', 1),
('page_builder', TRUE, '{"auto_backup": true, "version_control": true}', 1);

-- Insert default builder templates
INSERT IGNORE INTO `tts_builder_templates` (`template_name`, `template_type`, `template_content`, `category`, `is_system`, `admin_id`) VALUES
('Basic Page', 'page', '<!DOCTYPE html>\n<html>\n<head>\n<title>{{title}}</title>\n</head>\n<body>\n<h1>{{heading}}</h1>\n<p>{{content}}</p>\n</body>\n</html>', 'basic', TRUE, 1),
('Dashboard Layout', 'layout', '<div class="dashboard-container">\n<aside class="sidebar">{{sidebar}}</aside>\n<main class="content">{{content}}</main>\n</div>', 'dashboard', TRUE, 1),
('Card Component', 'component', '<div class="card">\n<div class="card-header">{{title}}</div>\n<div class="card-body">{{content}}</div>\n</div>', 'components', TRUE, 1);
