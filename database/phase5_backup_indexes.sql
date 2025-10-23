-- TTS PMS Phase 5 - Backup System Database Optimizations
-- Indexes and constraints for backup management

-- Add indexes for backup queries
ALTER TABLE `tts_backups` 
ADD INDEX IF NOT EXISTS `idx_backup_type_created` (`backup_type`, `created_at`),
ADD INDEX IF NOT EXISTS `idx_status_expires` (`status`, `expires_at`),
ADD INDEX IF NOT EXISTS `idx_created_by` (`created_by`);

-- Add backup capability if not exists
INSERT IGNORE INTO `tts_capabilities` (`name`, `description`) VALUES
('manage_backups', 'Create, restore, and manage system backups');

-- Assign backup capability to admin role
INSERT IGNORE INTO `tts_role_capabilities` (`role_id`, `capability_id`)
SELECT r.id, c.id 
FROM `tts_roles` r, `tts_capabilities` c 
WHERE r.name = 'admin' AND c.name = 'manage_backups';

-- Add backup-specific settings
INSERT IGNORE INTO `tts_settings` (`setting_key`, `setting_value`, `category`, `description`) VALUES
('backup_retention_days', '30', 'system', 'Number of days to retain backup files'),
('backup_max_size_mb', '500', 'system', 'Maximum backup file size in MB'),
('backup_include_media', '1', 'system', 'Include media files in backups by default'),
('backup_compress_level', '6', 'system', 'ZIP compression level (0-9)'),
('backup_notification_email', '', 'system', 'Email address for backup notifications');

-- Create backup cleanup procedure
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS `CleanupExpiredBackups`()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE backup_path VARCHAR(500);
    DECLARE backup_cursor CURSOR FOR 
        SELECT file_path FROM tts_backups 
        WHERE status = 'completed' 
        AND (expires_at IS NOT NULL AND expires_at < NOW())
        OR (expires_at IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY));
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN backup_cursor;
    
    cleanup_loop: LOOP
        FETCH backup_cursor INTO backup_path;
        IF done THEN
            LEAVE cleanup_loop;
        END IF;
        
        -- Mark as expired in database
        UPDATE tts_backups 
        SET status = 'expired' 
        WHERE file_path = backup_path;
        
    END LOOP;
    
    CLOSE backup_cursor;
    
    -- Insert cleanup log
    INSERT INTO tts_admin_edits (admin_id, action_type, object_type, changes, ip_address, user_agent)
    VALUES (1, 'backup_cleanup', 'system', 'Automated backup cleanup completed', 'system', 'Cleanup Procedure');
    
END$$

DELIMITER ;

-- Create backup statistics view
CREATE OR REPLACE VIEW `v_backup_statistics` AS
SELECT 
    backup_type,
    status,
    COUNT(*) as backup_count,
    SUM(file_size) as total_size,
    AVG(file_size) as avg_size,
    MIN(created_at) as oldest_backup,
    MAX(created_at) as newest_backup
FROM tts_backups 
GROUP BY backup_type, status;

-- Insert initial backup health check
INSERT IGNORE INTO `tts_system_health` (`check_type`, `check_name`, `status`, `message`) VALUES
('backup', 'Backup System', 'healthy', 'Backup system initialized and ready');

COMMIT;
