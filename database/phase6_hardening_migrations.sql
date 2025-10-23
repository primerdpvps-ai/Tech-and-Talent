-- TTS PMS Phase 6 - Security Hardening & Performance Database Migrations
-- MariaDB 10.6.23 Compatible - Security and performance enhancements

-- Add maintenance mode settings
INSERT IGNORE INTO `tts_settings` (`setting_key`, `setting_value`, `category`, `description`) VALUES
('maintenance_mode', '0', 'system', 'Enable maintenance mode (0=disabled, 1=enabled)'),
('maintenance_message', 'Site is temporarily under maintenance. Please check back shortly.', 'system', 'Message displayed during maintenance mode'),
('page_cache_enabled', '1', 'system', 'Enable page caching for public pages'),
('rate_limit_enabled', '1', 'system', 'Enable rate limiting for API endpoints'),
('slow_query_threshold', '1.0', 'system', 'Slow query threshold in seconds'),
('error_spike_threshold', '10', 'system', 'Error spike alert threshold per hour'),
('webhook_secret', '', 'system', 'Secret key for webhook signature validation');

-- Add bypass maintenance capability
INSERT IGNORE INTO `tts_capabilities` (`name`, `description`) VALUES
('bypass_maintenance', 'Access site during maintenance mode');

-- Assign bypass capability to super admin role
INSERT IGNORE INTO `tts_role_capabilities` (`role_id`, `capability_id`)
SELECT r.id, c.id 
FROM `tts_roles` r, `tts_capabilities` c 
WHERE r.name = 'super_admin' AND c.name = 'bypass_maintenance';

-- Add encrypted flag to settings table
ALTER TABLE `tts_settings` 
ADD COLUMN IF NOT EXISTS `is_encrypted` TINYINT(1) DEFAULT 0 AFTER `description`,
ADD INDEX IF NOT EXISTS `idx_encrypted_settings` (`is_encrypted`, `category`);

-- Create rate limiting table
CREATE TABLE IF NOT EXISTS `tts_rate_limits` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `identifier` VARCHAR(255) NOT NULL,
    `attempts` INT(11) DEFAULT 1,
    `first_attempt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_attempt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_identifier` (`identifier`),
    KEY `idx_expires` (`expires_at`),
    KEY `idx_last_attempt` (`last_attempt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create performance metrics table
CREATE TABLE IF NOT EXISTS `tts_performance_metrics` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `metric_type` VARCHAR(50) NOT NULL,
    `metric_name` VARCHAR(100) NOT NULL,
    `metric_value` DECIMAL(10,3) NOT NULL,
    `metadata` JSON NULL,
    `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_type_name_recorded` (`metric_type`, `metric_name`, `recorded_at`),
    KEY `idx_recorded_at` (`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create error aggregation table
CREATE TABLE IF NOT EXISTS `tts_error_aggregation` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `error_type` VARCHAR(100) NOT NULL,
    `error_hash` VARCHAR(64) NOT NULL,
    `error_message` TEXT NOT NULL,
    `occurrence_count` INT(11) DEFAULT 1,
    `first_occurred` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_occurred` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `metadata` JSON NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_error_hash` (`error_hash`),
    KEY `idx_error_type_count` (`error_type`, `occurrence_count`),
    KEY `idx_last_occurred` (`last_occurred`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add performance indexes for existing tables
ALTER TABLE `tts_admin_edits` 
ADD INDEX IF NOT EXISTS `idx_action_created_ip` (`action_type`, `created_at`, `ip_address`),
ADD INDEX IF NOT EXISTS `idx_admin_action_created` (`admin_id`, `action_type`, `created_at`);

ALTER TABLE `tts_admin_sync` 
ADD INDEX IF NOT EXISTS `idx_status_created_priority` (`status`, `created_at`, `priority`),
ADD INDEX IF NOT EXISTS `idx_admin_status` (`admin_id`, `status`);

ALTER TABLE `tts_system_health` 
ADD INDEX IF NOT EXISTS `idx_type_status_checked` (`check_type`, `status`, `checked_at`);

-- Create slow query analysis view
CREATE OR REPLACE VIEW `v_slow_operations` AS
SELECT 
    action_type,
    object_type,
    COUNT(*) as occurrence_count,
    AVG(JSON_EXTRACT(changes, '$.duration_seconds')) as avg_duration,
    MAX(JSON_EXTRACT(changes, '$.duration_seconds')) as max_duration,
    MAX(created_at) as last_occurrence
FROM tts_admin_edits 
WHERE action_type = 'slow_operation'
AND JSON_VALID(changes)
AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY action_type, object_type
ORDER BY avg_duration DESC;

-- Create security events view
CREATE OR REPLACE VIEW `v_security_events` AS
SELECT 
    action_type,
    ip_address,
    COUNT(*) as event_count,
    COUNT(DISTINCT admin_id) as unique_users,
    MIN(created_at) as first_event,
    MAX(created_at) as last_event
FROM tts_admin_edits 
WHERE action_type IN ('login_failed', 'rate_limit_triggered', 'unauthorized_access')
AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY action_type, ip_address
HAVING event_count > 3
ORDER BY event_count DESC;

-- Create performance summary view
CREATE OR REPLACE VIEW `v_performance_summary` AS
SELECT 
    DATE(recorded_at) as date,
    metric_type,
    metric_name,
    AVG(metric_value) as avg_value,
    MIN(metric_value) as min_value,
    MAX(metric_value) as max_value,
    COUNT(*) as measurement_count
FROM tts_performance_metrics 
WHERE recorded_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(recorded_at), metric_type, metric_name
ORDER BY date DESC, metric_type, metric_name;

-- Create cache management procedures
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS `ClearPageCache`()
BEGIN
    DECLARE cache_dir VARCHAR(500) DEFAULT '/cache/pages/';
    DECLARE files_cleared INT DEFAULT 0;
    
    -- This would clear page cache files
    -- Implementation depends on file system access from MySQL
    
    INSERT INTO tts_admin_edits (admin_id, action_type, object_type, changes, ip_address, user_agent)
    VALUES (1, 'cache_cleared', 'system', JSON_OBJECT('type', 'page_cache', 'files_cleared', files_cleared), 'system', 'Cache Management');
    
END$$

CREATE PROCEDURE IF NOT EXISTS `AnalyzeSlowQueries`()
BEGIN
    DECLARE slow_query_count INT DEFAULT 0;
    
    -- Get slow query count from status
    SELECT VARIABLE_VALUE INTO slow_query_count
    FROM information_schema.GLOBAL_STATUS 
    WHERE VARIABLE_NAME = 'Slow_queries';
    
    -- Insert performance metric
    INSERT INTO tts_performance_metrics (metric_type, metric_name, metric_value, metadata)
    VALUES ('database', 'slow_queries', slow_query_count, JSON_OBJECT('timestamp', NOW()));
    
END$$

CREATE PROCEDURE IF NOT EXISTS `AggregateErrors`()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE error_type VARCHAR(100);
    DECLARE error_msg TEXT;
    DECLARE error_count INT;
    
    DECLARE error_cursor CURSOR FOR 
        SELECT action_type, changes, COUNT(*) as cnt
        FROM tts_admin_edits 
        WHERE action_type LIKE '%_failed' 
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        GROUP BY action_type, changes;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN error_cursor;
    
    error_loop: LOOP
        FETCH error_cursor INTO error_type, error_msg, error_count;
        IF done THEN
            LEAVE error_loop;
        END IF;
        
        -- Create error hash
        SET @error_hash = SHA2(CONCAT(error_type, error_msg), 256);
        
        -- Insert or update error aggregation
        INSERT INTO tts_error_aggregation (error_type, error_hash, error_message, occurrence_count, metadata)
        VALUES (error_type, @error_hash, error_msg, error_count, JSON_OBJECT('last_aggregated', NOW()))
        ON DUPLICATE KEY UPDATE 
            occurrence_count = occurrence_count + error_count,
            last_occurred = NOW(),
            metadata = JSON_SET(metadata, '$.last_aggregated', NOW());
        
    END LOOP;
    
    CLOSE error_cursor;
    
END$$

DELIMITER ;

-- Create maintenance mode trigger
DELIMITER $$

CREATE TRIGGER IF NOT EXISTS `tr_maintenance_mode_log` 
AFTER UPDATE ON `tts_settings`
FOR EACH ROW
BEGIN
    IF NEW.setting_key = 'maintenance_mode' AND OLD.setting_value != NEW.setting_value THEN
        INSERT INTO tts_admin_edits (admin_id, action_type, object_type, object_id, changes, ip_address, user_agent)
        VALUES (
            COALESCE(@current_admin_id, 1),
            'maintenance_mode_toggle',
            'system',
            'maintenance',
            JSON_OBJECT('old_value', OLD.setting_value, 'new_value', NEW.setting_value, 'timestamp', NOW()),
            COALESCE(@current_ip, 'system'),
            COALESCE(@current_user_agent, 'System Trigger')
        );
    END IF;
END$$

DELIMITER ;

-- Insert initial performance baselines
INSERT IGNORE INTO `tts_performance_metrics` (`metric_type`, `metric_name`, `metric_value`, `metadata`) VALUES
('database', 'connection_time', 0.050, '{"baseline": true, "unit": "seconds"}'),
('application', 'page_load_time', 0.200, '{"baseline": true, "unit": "seconds"}'),
('cache', 'hit_rate', 85.0, '{"baseline": true, "unit": "percentage"}');

-- Create event for automated maintenance tasks
CREATE EVENT IF NOT EXISTS `ev_performance_maintenance`
ON SCHEDULE EVERY 1 HOUR
DO
BEGIN
    -- Clean up old rate limit entries
    DELETE FROM tts_rate_limits WHERE expires_at < NOW();
    
    -- Clean up old performance metrics (keep 30 days)
    DELETE FROM tts_performance_metrics WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- Aggregate errors
    CALL AggregateErrors();
    
    -- Analyze slow queries
    CALL AnalyzeSlowQueries();
END;

-- Enable event scheduler if not already enabled
SET GLOBAL event_scheduler = ON;

COMMIT;
