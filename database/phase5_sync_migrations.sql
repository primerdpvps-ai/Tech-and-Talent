-- TTS PMS Phase 5 - Sync Queue and Deployment Tables
-- MariaDB 10.6.23 Compatible

-- Admin sync queue table for background processing
CREATE TABLE IF NOT EXISTS `tts_admin_sync` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sync_id` VARCHAR(100) NOT NULL UNIQUE,
    `action_type` VARCHAR(50) NOT NULL,
    `data_payload` LONGTEXT NOT NULL,
    `priority` TINYINT DEFAULT 1,
    `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    `admin_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `started_at` TIMESTAMP NULL,
    `completed_at` TIMESTAMP NULL,
    `retry_count` INT DEFAULT 0,
    `max_retries` INT DEFAULT 3,
    `error_message` TEXT NULL,
    `result_data` TEXT NULL,
    INDEX `idx_sync_id` (`sync_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_priority` (`priority`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_admin_id` (`admin_id`),
    FOREIGN KEY (`admin_id`) REFERENCES `tts_users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backup metadata table
CREATE TABLE IF NOT EXISTS `tts_backups` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `backup_id` VARCHAR(100) NOT NULL UNIQUE,
    `backup_type` ENUM('manual', 'scheduled', 'pre_restore') DEFAULT 'manual',
    `backup_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_size` BIGINT NOT NULL,
    `tables_included` TEXT NOT NULL,
    `files_included` TEXT NULL,
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NULL,
    `is_compressed` BOOLEAN DEFAULT TRUE,
    `checksum` VARCHAR(64) NULL,
    `status` ENUM('creating', 'completed', 'failed', 'expired') DEFAULT 'creating',
    INDEX `idx_backup_id` (`backup_id`),
    INDEX `idx_backup_type` (`backup_type`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`created_by`) REFERENCES `tts_users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System health monitoring table
CREATE TABLE IF NOT EXISTS `tts_system_health` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `check_type` VARCHAR(50) NOT NULL,
    `check_name` VARCHAR(100) NOT NULL,
    `status` ENUM('healthy', 'warning', 'critical') DEFAULT 'healthy',
    `message` TEXT NULL,
    `details` JSON NULL,
    `checked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `next_check` TIMESTAMP NULL,
    INDEX `idx_check_type` (`check_type`),
    INDEX `idx_status` (`status`),
    INDEX `idx_checked_at` (`checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Installation tracking table
CREATE TABLE IF NOT EXISTS `tts_installation` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `component` VARCHAR(100) NOT NULL,
    `version` VARCHAR(20) NOT NULL,
    `status` ENUM('pending', 'installed', 'failed', 'updated') DEFAULT 'pending',
    `installed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `installed_by` INT NULL,
    `details` JSON NULL,
    UNIQUE KEY `unique_component` (`component`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`installed_by`) REFERENCES `tts_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert initial system health checks
INSERT IGNORE INTO `tts_system_health` (`check_type`, `check_name`, `status`, `message`) VALUES
('database', 'Connection Test', 'healthy', 'Database connection successful'),
('filesystem', 'Write Permissions', 'healthy', 'All directories writable'),
('email', 'SMTP Configuration', 'warning', 'SMTP not tested yet'),
('backup', 'Backup System', 'healthy', 'Backup system ready'),
('sync', 'Sync Queue', 'healthy', 'Sync queue operational');

-- Insert installation tracking records
INSERT IGNORE INTO `tts_installation` (`component`, `version`, `status`) VALUES
('core_system', '1.0.0', 'installed'),
('admin_panel', '1.0.0', 'installed'),
('visual_builder', '1.0.0', 'installed'),
('global_settings', '1.0.0', 'installed'),
('sync_api', '1.0.0', 'pending'),
('backup_system', '1.0.0', 'pending');

-- Add missing indexes for performance
ALTER TABLE `tts_admin_edits` 
ADD INDEX IF NOT EXISTS `idx_action_type` (`action_type`),
ADD INDEX IF NOT EXISTS `idx_object_type` (`object_type`),
ADD INDEX IF NOT EXISTS `idx_created_at` (`created_at`);

ALTER TABLE `tts_settings` 
ADD INDEX IF NOT EXISTS `idx_category_key` (`category`, `setting_key`);

ALTER TABLE `tts_cms_pages` 
ADD INDEX IF NOT EXISTS `idx_status_updated` (`status`, `updated_at`);

ALTER TABLE `tts_page_layouts` 
ADD INDEX IF NOT EXISTS `idx_page_current` (`page_id`, `is_current`);

-- Update module configurations for Phase 5
INSERT IGNORE INTO `tts_module_config` (`module_name`, `is_enabled`, `config_data`, `admin_id`) VALUES
('sync_api', TRUE, '{"queue_processing": true, "retry_limit": 3, "batch_size": 10}', 1),
('backup_system', TRUE, '{"auto_backup": true, "retention_days": 30, "compress": true}', 1),
('health_monitoring', TRUE, '{"check_interval": 300, "alert_threshold": "warning"}', 1);

COMMIT;
