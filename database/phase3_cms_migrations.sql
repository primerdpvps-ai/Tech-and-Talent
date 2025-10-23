-- TTS PMS Phase 3 - Visual Builder & CMS Database Migrations
-- MariaDB 10.6.23 Compatible - Content Management System

-- CMS Pages table for page metadata
CREATE TABLE IF NOT EXISTS `tts_cms_pages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `title` VARCHAR(255) NOT NULL,
    `meta_title` VARCHAR(255) NULL,
    `meta_description` TEXT NULL,
    `canonical_url` VARCHAR(500) NULL,
    `robots_index` BOOLEAN DEFAULT TRUE,
    `robots_follow` BOOLEAN DEFAULT TRUE,
    `status` ENUM('draft', 'published', 'scheduled', 'archived') DEFAULT 'draft',
    `template` VARCHAR(100) DEFAULT 'default',
    `featured_image` VARCHAR(500) NULL,
    `excerpt` TEXT NULL,
    `publish_date` TIMESTAMP NULL,
    `unpublish_date` TIMESTAMP NULL,
    `created_by` INT NOT NULL,
    `updated_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `tts_users`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`updated_by`) REFERENCES `tts_users`(`id`) ON DELETE RESTRICT,
    INDEX `idx_slug` (`slug`),
    INDEX `idx_status` (`status`),
    INDEX `idx_publish_date` (`publish_date`),
    INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enhanced page layouts table for visual builder
ALTER TABLE `tts_page_layouts` 
ADD COLUMN IF NOT EXISTS `page_id` INT NULL AFTER `id`,
ADD COLUMN IF NOT EXISTS `layout_json` LONGTEXT NULL AFTER `new_content`,
ADD COLUMN IF NOT EXISTS `version` INT DEFAULT 1 AFTER `layout_json`,
ADD COLUMN IF NOT EXISTS `is_current` BOOLEAN DEFAULT TRUE AFTER `version`,
ADD COLUMN IF NOT EXISTS `layout_type` ENUM('page', 'template', 'component') DEFAULT 'page' AFTER `is_current`,
ADD INDEX IF NOT EXISTS `idx_page_id` (`page_id`),
ADD INDEX IF NOT EXISTS `idx_version` (`version`),
ADD INDEX IF NOT EXISTS `idx_current` (`is_current`);

-- Media files table for image uploads
CREATE TABLE IF NOT EXISTS `tts_media_files` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `filename` VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_url` VARCHAR(500) NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `file_size` BIGINT NOT NULL,
    `width` INT NULL,
    `height` INT NULL,
    `alt_text` VARCHAR(255) NULL,
    `caption` TEXT NULL,
    `storage_type` ENUM('local', 's3', 'cdn') DEFAULT 'local',
    `uploaded_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`uploaded_by`) REFERENCES `tts_users`(`id`) ON DELETE RESTRICT,
    INDEX `idx_filename` (`filename`),
    INDEX `idx_mime_type` (`mime_type`),
    INDEX `idx_uploaded_by` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enhanced CMS history for version control
ALTER TABLE `tts_cms_history`
ADD COLUMN IF NOT EXISTS `file_backup_path` VARCHAR(500) NULL AFTER `content_data`,
ADD COLUMN IF NOT EXISTS `layout_backup` LONGTEXT NULL AFTER `file_backup_path`,
ADD COLUMN IF NOT EXISTS `change_summary` VARCHAR(255) NULL AFTER `change_description`,
ADD COLUMN IF NOT EXISTS `is_auto_backup` BOOLEAN DEFAULT FALSE AFTER `change_summary`,
ADD INDEX IF NOT EXISTS `idx_auto_backup` (`is_auto_backup`);

-- Page templates table for pre-built layouts
CREATE TABLE IF NOT EXISTS `tts_page_templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `template_json` LONGTEXT NOT NULL,
    `preview_image` VARCHAR(500) NULL,
    `category` VARCHAR(50) DEFAULT 'general',
    `is_system` BOOLEAN DEFAULT FALSE,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `tts_users`(`id`) ON DELETE RESTRICT,
    INDEX `idx_category` (`category`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Page components library
CREATE TABLE IF NOT EXISTS `tts_page_components` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `component_type` VARCHAR(50) NOT NULL,
    `component_json` LONGTEXT NOT NULL,
    `preview_html` TEXT NULL,
    `category` VARCHAR(50) DEFAULT 'general',
    `is_system` BOOLEAN DEFAULT FALSE,
    `usage_count` INT DEFAULT 0,
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `tts_users`(`id`) ON DELETE RESTRICT,
    INDEX `idx_type` (`component_type`),
    INDEX `idx_category` (`category`),
    INDEX `idx_usage` (`usage_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default page templates
INSERT IGNORE INTO `tts_page_templates` (`name`, `description`, `template_json`, `category`, `is_system`, `created_by`) VALUES
('Hero Landing', 'Full-width hero section with call-to-action', '{"sections":[{"type":"hero","settings":{"background":"gradient","height":"100vh"},"rows":[{"columns":[{"width":"12","blocks":[{"type":"heading","content":"Welcome to TTS PMS","settings":{"size":"h1","align":"center"}},{"type":"text","content":"Professional workforce management solutions","settings":{"align":"center","size":"lg"}},{"type":"button","content":"Get Started","settings":{"style":"primary","size":"lg","align":"center"}}]}]}]}]}', 'landing', TRUE, 1),

('Services Page', 'Professional services showcase layout', '{"sections":[{"type":"header","rows":[{"columns":[{"width":"12","blocks":[{"type":"heading","content":"Our Services","settings":{"size":"h1","align":"center"}},{"type":"text","content":"Comprehensive solutions for your business needs","settings":{"align":"center"}}]}]}]},{"type":"features","rows":[{"columns":[{"width":"4","blocks":[{"type":"feature","content":{"title":"Data Entry","description":"Accurate and efficient data processing","icon":"fas fa-keyboard"}}]},{"width":"4","blocks":[{"type":"feature","content":{"title":"Training Programs","description":"Skill development and certification","icon":"fas fa-graduation-cap"}}]},{"width":"4","blocks":[{"type":"feature","content":{"title":"Quality Assurance","description":"Rigorous testing and validation","icon":"fas fa-check-circle"}}]}]}]}]}', 'business', TRUE, 1),

('Contact Page', 'Contact form with company information', '{"sections":[{"type":"contact","rows":[{"columns":[{"width":"6","blocks":[{"type":"heading","content":"Get In Touch","settings":{"size":"h2"}},{"type":"text","content":"Ready to start your project? Contact us today."},{"type":"contact_form","settings":{"fields":["name","email","message"],"submit_text":"Send Message"}}]},{"width":"6","blocks":[{"type":"heading","content":"Contact Information","settings":{"size":"h3"}},{"type":"contact_info","content":{"address":"Lahore, Pakistan","phone":"+92 300 1234567","email":"info@tts.com","hours":"Mon-Fri 9AM-6PM"}}]}]}]}]}', 'contact', TRUE, 1),

('About Us', 'Company story and team showcase', '{"sections":[{"type":"about","rows":[{"columns":[{"width":"12","blocks":[{"type":"heading","content":"About Tech & Talent Solutions","settings":{"size":"h1","align":"center"}},{"type":"text","content":"We are a leading provider of professional services, specializing in data entry, workforce management, and business solutions.","settings":{"align":"center","size":"lg"}}]}]},{"columns":[{"width":"6","blocks":[{"type":"image","settings":{"src":"","alt":"Team photo","rounded":true}}]},{"width":"6","blocks":[{"type":"heading","content":"Our Mission","settings":{"size":"h3"}},{"type":"text","content":"To deliver exceptional results through precision, innovation, and dedication to our clients success."}]}]}]}]}', 'company', TRUE, 1);

-- Insert default page components
INSERT IGNORE INTO `tts_page_components` (`name`, `component_type`, `component_json`, `category`, `is_system`, `created_by`) VALUES
('Call to Action Button', 'button', '{"type":"button","settings":{"text":"Get Started","style":"primary","size":"lg","link":"#","target":"_self","icon":"","animation":"fadeIn"}}', 'buttons', TRUE, 1),

('Feature Card', 'feature', '{"type":"feature_card","settings":{"title":"Feature Title","description":"Feature description goes here","icon":"fas fa-star","background":"white","border":true,"shadow":true}}', 'cards', TRUE, 1),

('Testimonial', 'testimonial', '{"type":"testimonial","settings":{"quote":"This service exceeded our expectations","author":"John Doe","position":"CEO","company":"Example Corp","avatar":"","rating":5}}', 'social', TRUE, 1),

('Contact Form', 'form', '{"type":"contact_form","settings":{"fields":[{"type":"text","name":"name","label":"Full Name","required":true},{"type":"email","name":"email","label":"Email Address","required":true},{"type":"textarea","name":"message","label":"Message","required":true}],"submit_text":"Send Message","success_message":"Thank you for your message!"}}', 'forms', TRUE, 1),

('Image Gallery', 'gallery', '{"type":"image_gallery","settings":{"images":[],"columns":3,"spacing":"md","lightbox":true,"captions":true}}', 'media', TRUE, 1),

('Stats Counter', 'stats', '{"type":"stats_counter","settings":{"stats":[{"number":"100+","label":"Projects Completed"},{"number":"50+","label":"Happy Clients"},{"number":"24/7","label":"Support Available"}],"animation":"countUp"}}', 'data', TRUE, 1);

-- Update module config to include page builder
INSERT IGNORE INTO `tts_module_config` (`module_name`, `is_enabled`, `config_data`, `admin_id`) VALUES
('visual_builder', TRUE, '{"auto_backup": true, "version_limit": 10, "image_optimization": true}', 1);

COMMIT;
