-- TTS PMS Phase 5 - Queue Processor Optimization
-- Additional indexes and columns for efficient queue processing

-- Add indexes for queue processing performance
ALTER TABLE `tts_admin_sync` 
ADD INDEX IF NOT EXISTS `idx_status_priority` (`status`, `priority`, `created_at`),
ADD INDEX IF NOT EXISTS `idx_retry_processing` (`retry_count`, `status`),
ADD INDEX IF NOT EXISTS `idx_cleanup_expired` (`status`, `created_at`),
ADD INDEX IF NOT EXISTS `idx_stuck_jobs` (`status`, `started_at`);

-- Add index for health monitoring queries
ALTER TABLE `tts_system_health` 
ADD INDEX IF NOT EXISTS `idx_check_type_name` (`check_type`, `check_name`),
ADD INDEX IF NOT EXISTS `idx_status_checked` (`status`, `checked_at`);

-- Optimize admin edits table for sync process queries
ALTER TABLE `tts_admin_edits` 
ADD INDEX IF NOT EXISTS `idx_sync_process` (`action_type`, `object_type`, `created_at`);

-- Add queue statistics view for monitoring
CREATE OR REPLACE VIEW `v_sync_queue_stats` AS
SELECT 
    status,
    COUNT(*) as job_count,
    MIN(created_at) as oldest_job,
    MAX(created_at) as newest_job,
    AVG(retry_count) as avg_retries
FROM tts_admin_sync 
GROUP BY status;

-- Add health monitoring view
CREATE OR REPLACE VIEW `v_system_health_summary` AS
SELECT 
    check_type,
    COUNT(*) as total_checks,
    SUM(CASE WHEN status = 'healthy' THEN 1 ELSE 0 END) as healthy_count,
    SUM(CASE WHEN status = 'warning' THEN 1 ELSE 0 END) as warning_count,
    SUM(CASE WHEN status = 'critical' THEN 1 ELSE 0 END) as critical_count,
    MAX(checked_at) as last_check
FROM tts_system_health 
GROUP BY check_type;

-- Insert initial queue processor health record
INSERT IGNORE INTO `tts_system_health` (`check_type`, `check_name`, `status`, `message`) VALUES
('sync_queue', 'Queue Processor', 'healthy', 'Queue processor ready for operation');

-- Update settings for queue processor configuration
INSERT IGNORE INTO `tts_settings` (`setting_key`, `setting_value`, `category`, `description`) VALUES
('queue_batch_size', '10', 'system', 'Number of jobs to process per batch'),
('queue_max_retries', '3', 'system', 'Maximum retry attempts for failed jobs'),
('queue_lock_timeout', '300', 'system', 'Job lock timeout in seconds'),
('queue_expire_hours', '24', 'system', 'Hours after which failed jobs expire'),
('queue_failure_alert_threshold', '10', 'system', 'Failure rate % threshold for alerts'),
('admin_email', 'tts.workhub@gmail.com', 'system', 'Admin email for system alerts');

COMMIT;
