-- TTS PMS Phase 4 - Global Settings Database Migrations
-- MariaDB 10.6.23 Compatible - Settings and Configuration

-- Insert default settings for all categories
INSERT IGNORE INTO `tts_settings` (`setting_key`, `setting_value`, `category`, `description`) VALUES

-- Branding & Appearance Settings
('site_name', 'Tech & Talent Solutions', 'branding', 'Website name displayed in title and headers'),
('tagline', 'Precision Data, Global Talent', 'branding', 'Site tagline or slogan'),
('logo_url', '', 'branding', 'URL to site logo image'),
('favicon_url', '', 'branding', 'URL to favicon image'),
('footer_text', '© 2024 Tech & Talent Solutions. All rights reserved.', 'branding', 'Copyright text in footer'),
('social_facebook', '', 'branding', 'Facebook page URL'),
('social_twitter', '', 'branding', 'Twitter profile URL'),
('social_linkedin', '', 'branding', 'LinkedIn company page URL'),

-- Email & SMTP Settings
('smtp_host', 'smtp.gmail.com', 'email', 'SMTP server hostname'),
('smtp_port', '465', 'email', 'SMTP server port number'),
('smtp_username', 'tts.workhub@gmail.com', 'email', 'SMTP authentication username'),
('smtp_password', 'wcjr uqat kqlz npvd', 'email', 'SMTP authentication password'),
('smtp_encryption', 'ssl', 'email', 'SMTP encryption method (SSL/TLS)'),
('from_email', 'tts.workhub@gmail.com', 'email', 'Default sender email address'),
('from_name', 'TTS WorkHub', 'email', 'Default sender name'),

-- SEO & Meta Settings
('meta_title', 'Tech & Talent Solutions - Professional Services', 'seo', 'Default page title for SEO'),
('meta_description', 'Leading provider of professional tech services and workforce management solutions in Pakistan.', 'seo', 'Default meta description'),
('canonical_url', 'https://pms.prizmasoft.com', 'seo', 'Canonical URL for the site'),
('meta_keywords', 'data entry, professional services, workforce management', 'seo', 'Default meta keywords'),
('robots_index', '1', 'seo', 'Allow search engines to index site'),
('robots_follow', '1', 'seo', 'Allow search engines to follow links'),
('sitemap_enabled', '1', 'seo', 'Enable XML sitemap generation'),

-- Payroll & HR Settings
('base_hourly_rate', '125', 'payroll', 'Base hourly rate in PKR'),
('streak_bonus', '500', 'payroll', 'Bonus for 28-day consecutive work streaks'),
('daily_working_hours', '8', 'payroll', 'Standard daily working hours'),
('overtime_multiplier', '1.5', 'payroll', 'Overtime rate multiplier'),
('security_fund_rate', '0.02', 'payroll', 'Security fund deduction rate'),
('holiday_rate_multiplier', '2.0', 'payroll', 'Holiday work rate multiplier'),

-- Authentication & Security Settings
('gmail_only', '1', 'auth', 'Require Gmail addresses for registration'),
('otp_cooldown', '60', 'auth', 'OTP request cooldown in seconds'),
('session_timeout', '3600', 'auth', 'Session timeout in seconds'),
('max_login_attempts', '5', 'auth', 'Maximum failed login attempts'),
('require_email_verification', '1', 'auth', 'Require email verification for new accounts'),
('password_min_length', '8', 'auth', 'Minimum password length requirement'),

-- System Settings
('maintenance_mode', '0', 'system', 'Enable maintenance mode'),
('debug_mode', '0', 'system', 'Enable debug logging'),
('backup_frequency', 'daily', 'system', 'Automatic backup frequency'),
('max_file_upload_size', '10485760', 'system', 'Maximum file upload size in bytes'),
('timezone', 'Asia/Karachi', 'system', 'Default system timezone'),
('date_format', 'Y-m-d', 'system', 'Default date format'),
('time_format', 'H:i:s', 'system', 'Default time format'),

-- Module-specific settings
('payroll_auto_calculate', '1', 'modules', 'Auto-calculate payroll on attendance'),
('training_tracking_enabled', '1', 'modules', 'Enable training progress tracking'),
('performance_reviews_enabled', '1', 'modules', 'Enable performance review system'),
('document_management_enabled', '1', 'modules', 'Enable document management'),
('communication_hub_enabled', '1', 'modules', 'Enable internal communication hub'),
('project_management_enabled', '1', 'modules', 'Enable project management tools'),
('reporting_dashboard_enabled', '1', 'modules', 'Enable reporting dashboard'),
('user_management_enabled', '1', 'modules', 'Enable user management system');

-- Update existing module configurations
UPDATE `tts_module_config` SET `config_data` = JSON_SET(
    COALESCE(`config_data`, '{}'),
    '$.auto_backup', true,
    '$.version_limit', 10,
    '$.image_optimization', true
) WHERE `module_name` = 'visual_builder';

-- Add settings for existing modules
INSERT IGNORE INTO `tts_module_config` (`module_name`, `is_enabled`, `config_data`, `admin_id`) VALUES
('global_settings', TRUE, '{"categories": ["branding", "email", "seo", "payroll", "auth"], "auto_apply": true}', 1),
('audit_system', TRUE, '{"retention_days": 365, "auto_cleanup": true, "detailed_logging": true}', 1),
('backup_system', TRUE, '{"frequency": "daily", "retention_count": 30, "compress": true}', 1);

COMMIT;
