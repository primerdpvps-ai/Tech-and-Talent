-- TTS PMS Complete Database Schema
-- Production Ready - MariaDB 10.6.23 & cPanel Compatible
-- Character Set: utf8mb4 | PHP 8.4.11 | Single Comprehensive File

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 1;

-- ========================================
-- CORE USER MANAGEMENT
-- ========================================

CREATE TABLE IF NOT EXISTS `tts_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NULL,
    `role` ENUM('visitor', 'candidate', 'new_employee', 'employee', 'manager', 'ceo', 'admin') DEFAULT 'visitor',
    `status` ENUM('ACTIVE', 'INACTIVE', 'PENDING_VERIFICATION', 'SUSPENDED') DEFAULT 'PENDING_VERIFICATION',
    `email_verified` BOOLEAN DEFAULT FALSE,
    `phone_verified` BOOLEAN DEFAULT FALSE,
    `verification_token` VARCHAR(64) NULL,
    `token_expiry` TIMESTAMP NULL,
    `last_login` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role`),
    INDEX `idx_status` (`status`),
    INDEX `idx_verification_token` (`verification_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tts_legal_acknowledgments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `document_type` ENUM('terms', 'privacy', 'nda') NOT NULL,
    `acknowledged_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `ip_address` VARCHAR(45) NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_document_type` (`document_type`),
    FOREIGN KEY (`user_id`) REFERENCES `tts_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- VISITOR EVALUATION SYSTEM
-- ========================================

CREATE TABLE IF NOT EXISTS `tts_evaluations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `age` INT NOT NULL,
    `guardian_agree` VARCHAR(10) NULL,
    `senior_reason` TEXT NULL,
    `physical_fit` VARCHAR(10) NULL,
    `caretaking` VARCHAR(10) NULL,
    `device_type` VARCHAR(50) NOT NULL,
    `ram` VARCHAR(20) NOT NULL,
    `processor` VARCHAR(100) NOT NULL,
    `stable_internet` VARCHAR(10) NOT NULL,
    `provider` VARCHAR(50) NULL,
    `link_speed` VARCHAR(20) NULL,
    `num_users` INT NULL,
    `speedtest_url` VARCHAR(255) NULL,
    `profession` VARCHAR(50) NOT NULL,
    `daily_time` VARCHAR(10) NOT NULL,
    `time_windows` TEXT NULL,
    `qualification` VARCHAR(255) NOT NULL,
    `confidentiality` VARCHAR(10) NOT NULL,
    `typing_speed` VARCHAR(10) NOT NULL,
    `status` ENUM('Eligible', 'Pending', 'Rejected') NOT NULL,
    `reasons` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`user_id`) REFERENCES `tts_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TIME TRACKING SYSTEM
-- ========================================

CREATE TABLE IF NOT EXISTS `tts_time_entries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `date` DATE NOT NULL,
    `clock_in` TIME NULL,
    `clock_out` TIME NULL,
    `break_start` TIME NULL,
    `break_end` TIME NULL,
    `total_hours` DECIMAL(4,2) DEFAULT 0.00,
    `status` ENUM('active', 'break', 'completed') DEFAULT 'active',
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_date` (`user_id`, `date`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_date` (`date`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`user_id`) REFERENCES `tts_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- PAYROLL SYSTEM
-- ========================================

CREATE TABLE IF NOT EXISTS `tts_payslips` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `pay_period` VARCHAR(7) NOT NULL, -- YYYY-MM format
    `basic_salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `allowances` DECIMAL(10,2) DEFAULT 0.00,
    `overtime_hours` DECIMAL(4,2) DEFAULT 0.00,
    `overtime_rate` DECIMAL(8,2) DEFAULT 0.00,
    `overtime_amount` DECIMAL(10,2) DEFAULT 0.00,
    `training_allowance` DECIMAL(10,2) DEFAULT 0.00,
    `management_allowance` DECIMAL(10,2) DEFAULT 0.00,
    `executive_allowance` DECIMAL(10,2) DEFAULT 0.00,
    `performance_bonus` DECIMAL(10,2) DEFAULT 0.00,
    `gross_salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `tax_deduction` DECIMAL(10,2) DEFAULT 0.00,
    `insurance_deduction` DECIMAL(10,2) DEFAULT 0.00,
    `other_deductions` DECIMAL(10,2) DEFAULT 0.00,
    `total_deductions` DECIMAL(10,2) DEFAULT 0.00,
    `net_salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `working_days` INT DEFAULT 0,
    `present_days` INT DEFAULT 0,
    `absent_days` INT DEFAULT 0,
    `leave_days` INT DEFAULT 0,
    `training_days` INT DEFAULT 0,
    `status` ENUM('draft', 'approved', 'paid') DEFAULT 'draft',
    `payment_date` DATE NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_period` (`user_id`, `pay_period`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_pay_period` (`pay_period`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`user_id`) REFERENCES `tts_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- LEAVE MANAGEMENT SYSTEM
-- ========================================

CREATE TABLE IF NOT EXISTS `tts_leave_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `leave_type` ENUM('annual', 'sick', 'casual', 'emergency', 'maternity', 'paternity', 'management', 'executive', 'business_travel', 'conference', 'personal') NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `total_days` INT NOT NULL,
    `reason` TEXT NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    `applied_date` DATE NOT NULL,
    `approved_by` INT NULL,
    `rejection_reason` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_leave_type` (`leave_type`),
    INDEX `idx_dates` (`start_date`, `end_date`),
    INDEX `idx_approved_by` (`approved_by`),
    FOREIGN KEY (`user_id`) REFERENCES `tts_users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approved_by`) REFERENCES `tts_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tts_leave_balances` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `year` YEAR NOT NULL,
    `annual_leave` INT DEFAULT 0,
    `sick_leave` INT DEFAULT 0,
    `casual_leave` INT DEFAULT 0,
    `emergency_leave` INT DEFAULT 0,
    `executive_leave` INT DEFAULT 0,
    `annual_used` INT DEFAULT 0,
    `sick_used` INT DEFAULT 0,
    `casual_used` INT DEFAULT 0,
    `emergency_used` INT DEFAULT 0,
    `executive_used` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_year` (`user_id`, `year`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_year` (`year`),
    FOREIGN KEY (`user_id`) REFERENCES `tts_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TASK MANAGEMENT SYSTEM
-- ========================================

CREATE TABLE IF NOT EXISTS `tts_daily_tasks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `assigned_by` INT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    `status` ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    `due_date` DATE NULL,
    `completed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_assigned_by` (`assigned_by`),
    INDEX `idx_status` (`status`),
    INDEX `idx_priority` (`priority`),
    INDEX `idx_due_date` (`due_date`),
    FOREIGN KEY (`user_id`) REFERENCES `tts_users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_by`) REFERENCES `tts_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- ONBOARDING SYSTEM
-- ========================================

CREATE TABLE IF NOT EXISTS `tts_onboarding_tasks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `priority` ENUM('low', 'medium', 'high') DEFAULT 'medium',
    `status` ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    `due_date` DATE NULL,
    `completed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_priority` (`priority`),
    FOREIGN KEY (`user_id`) REFERENCES `tts_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tts_training_modules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `duration` VARCHAR(50) NULL,
    `is_mandatory` BOOLEAN DEFAULT FALSE,
    `order_sequence` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_mandatory` (`is_mandatory`),
    INDEX `idx_sequence` (`order_sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tts_user_training_progress` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `training_module_id` INT NOT NULL,
    `status` ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
    `progress_percentage` INT DEFAULT 0,
    `started_at` TIMESTAMP NULL,
    `completed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_module` (`user_id`, `training_module_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_module_id` (`training_module_id`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`user_id`) REFERENCES `tts_users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`training_module_id`) REFERENCES `tts_training_modules`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- EXISTING TABLES (KEEPING ORIGINAL)
-- ========================================

CREATE TABLE IF NOT EXISTS `tts_page_layouts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `layout_data` LONGTEXT NOT NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_created_by` (`created_by`),
    FOREIGN KEY (`created_by`) REFERENCES `tts_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- JOB & APPLICATION SYSTEM
-- ========================================

CREATE TABLE IF NOT EXISTS `tts_job_positions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `department` VARCHAR(100) NOT NULL,
    `type` ENUM('full-time', 'part-time', 'contract', 'internship') DEFAULT 'full-time',
    `description` TEXT NOT NULL,
    `requirements` JSON NULL,
    `salary_range` VARCHAR(100) NULL,
    `status` ENUM('open', 'closed', 'draft') DEFAULT 'open',
    `posted_date` DATE NOT NULL,
    `deadline` DATE NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_department` (`department`),
    INDEX `idx_posted_date` (`posted_date`),
    FOREIGN KEY (`created_by`) REFERENCES `tts_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tts_job_applications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `job_position_id` INT NOT NULL,
    `cover_letter` TEXT NOT NULL,
    `status` ENUM('submitted', 'under_review', 'interview_scheduled', 'accepted', 'rejected') DEFAULT 'submitted',
    `notes` TEXT NULL,
    `reviewed_by` INT NULL,
    `reviewed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_job_position_id` (`job_position_id`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`user_id`) REFERENCES `tts_users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`job_position_id`) REFERENCES `tts_job_positions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reviewed_by`) REFERENCES `tts_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- EMPLOYEE ONBOARDING & TRAINING
-- ========================================

CREATE TABLE IF NOT EXISTS `tts_onboarding_tasks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `status` ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    `priority` ENUM('low', 'medium', 'high') DEFAULT 'medium',
    `due_date` DATE NOT NULL,
    `completed_date` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_due_date` (`due_date`),
    FOREIGN KEY (`user_id`) REFERENCES `tts_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tts_training_modules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `duration` VARCHAR(50) NOT NULL,
    `content_url` VARCHAR(500) NULL,
    `is_mandatory` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tts_user_training_progress` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `training_module_id` INT NOT NULL,
    `status` ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
    `progress_percentage` INT DEFAULT 0,
    `started_at` TIMESTAMP NULL,
    `completed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_training_module_id` (`training_module_id`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`user_id`) REFERENCES `tts_users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`training_module_id`) REFERENCES `tts_training_modules`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_module` (`user_id`, `training_module_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TASK & TIME MANAGEMENT
-- ========================================

CREATE TABLE IF NOT EXISTS `tts_daily_tasks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `assigned_by` INT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `priority` ENUM('low', 'medium', 'high') DEFAULT 'medium',
    `status` ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    `assigned_time` TIME NULL,
    `estimated_hours` DECIMAL(3,1) NULL,
    `completed_percentage` INT DEFAULT 0,
    `due_date` DATE NOT NULL,
    `completed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_assigned_by` (`assigned_by`),
    INDEX `idx_status` (`status`),
    INDEX `idx_due_date` (`due_date`),
    FOREIGN KEY (`user_id`) REFERENCES `tts_users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_by`) REFERENCES `tts_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tts_time_entries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `date` DATE NOT NULL,
    `clock_in` TIME NULL,
    `clock_out` TIME NULL,
    `break_start` TIME NULL,
    `break_end` TIME NULL,
    `total_hours` DECIMAL(4,2) DEFAULT 0,
    `status` ENUM('active', 'completed', 'break') DEFAULT 'active',
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_date` (`date`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`user_id`) REFERENCES `tts_users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_date` (`user_id`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tts_timesheets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `date` DATE NOT NULL,
    `hours_worked` DECIMAL(4,2) NOT NULL,
    `description` TEXT NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `approved_by` INT NULL,
    `approved_at` TIMESTAMP NULL,
    `rejection_reason` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_date` (`date`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`user_id`) REFERENCES `tts_users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approved_by`) REFERENCES `tts_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- LEAVE MANAGEMENT
-- ========================================

CREATE TABLE IF NOT EXISTS `tts_leave_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `leave_type` ENUM('annual', 'sick', 'emergency', 'maternity', 'paternity') NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `reason` TEXT NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `approved_by` INT NULL,
    `approved_at` TIMESTAMP NULL,
    `rejection_reason` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_start_date` (`start_date`),
    FOREIGN KEY (`user_id`) REFERENCES `tts_users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approved_by`) REFERENCES `tts_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- GIGS & PAYMENT SYSTEM
-- ========================================

CREATE TABLE IF NOT EXISTS `tts_gigs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `budget` DECIMAL(10,2) NOT NULL,
    `deadline` DATE NOT NULL,
    `skills_required` TEXT NULL,
    `status` ENUM('open', 'in_progress', 'completed', 'cancelled') DEFAULT 'open',
    `created_by` INT NULL,
    `assigned_to` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_deadline` (`deadline`),
    INDEX `idx_created_by` (`created_by`),
    INDEX `idx_assigned_to` (`assigned_to`),
    FOREIGN KEY (`created_by`) REFERENCES `tts_users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`assigned_to`) REFERENCES `tts_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tts_payment_intents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `gig_id` INT NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `currency` VARCHAR(3) DEFAULT 'PKR',
    `client_secret` VARCHAR(255) NOT NULL,
    `status` ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_gig_id` (`gig_id`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`gig_id`) REFERENCES `tts_gigs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tts_payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `gig_id` INT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `currency` VARCHAR(3) DEFAULT 'PKR',
    `payment_method` VARCHAR(50) NULL,
    `stripe_payment_id` VARCHAR(255) NULL,
    `status` ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    `refund_amount` DECIMAL(10,2) NULL,
    `refund_reason` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_gig_id` (`gig_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_stripe_payment_id` (`stripe_payment_id`),
    FOREIGN KEY (`user_id`) REFERENCES `tts_users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`gig_id`) REFERENCES `tts_gigs`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- CLIENT & PROJECT MANAGEMENT
-- ========================================

CREATE TABLE IF NOT EXISTS `tts_clients` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `business_name` VARCHAR(255) NOT NULL,
    `contact_person` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) NULL,
    `address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `country` VARCHAR(100) DEFAULT 'Pakistan',
    `industry` VARCHAR(100) NULL,
    `website` VARCHAR(255) NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_business_name` (`business_name`),
    INDEX `idx_email` (`email`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tts_projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `client_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `status` ENUM('planning', 'active', 'on_hold', 'completed', 'cancelled') DEFAULT 'planning',
    `start_date` DATE NULL,
    `end_date` DATE NULL,
    `total_amount` DECIMAL(12,2) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_client_id` (`client_id`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`client_id`) REFERENCES `tts_clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- DIVI BUILDER INTEGRATION
-- ========================================

CREATE TABLE IF NOT EXISTS `tts_page_layouts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `page_type` VARCHAR(50) NOT NULL,
    `page_role` VARCHAR(50) NOT NULL,
    `layout_data` LONGTEXT NULL,
    `custom_css` LONGTEXT NULL,
    `custom_js` LONGTEXT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_page_type` (`page_type`),
    INDEX `idx_page_role` (`page_role`),
    INDEX `idx_active` (`is_active`),
    UNIQUE KEY `unique_page_layout` (`page_type`, `page_role`),
    FOREIGN KEY (`created_by`) REFERENCES `tts_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- SYSTEM CONFIGURATION
-- ========================================

CREATE TABLE IF NOT EXISTS `tts_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT NULL,
    `setting_type` ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    `description` TEXT NULL,
    `category` VARCHAR(50) DEFAULT 'general',
    `is_public` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_setting_key` (`setting_key`),
    INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tts_audit_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(100) NOT NULL,
    `table_name` VARCHAR(100) NULL,
    `record_id` INT NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_table_name` (`table_name`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- DEMO DATA INSERTION
-- ========================================

-- Demo users (password: demo123)
INSERT IGNORE INTO `tts_users` (`email`, `password_hash`, `first_name`, `last_name`, `role`, `status`, `email_verified`) VALUES
('visitor@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo', 'Visitor', 'visitor', 'ACTIVE', TRUE),
('candidate@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo', 'Candidate', 'candidate', 'ACTIVE', TRUE),
('newemployee@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo', 'New Employee', 'new_employee', 'ACTIVE', TRUE),
('employee@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo', 'Employee', 'employee', 'ACTIVE', TRUE),
('manager@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo', 'Manager', 'manager', 'ACTIVE', TRUE),
('ceo@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo', 'CEO', 'ceo', 'ACTIVE', TRUE);

-- Demo job positions
INSERT IGNORE INTO `tts_job_positions` (`title`, `department`, `type`, `description`, `requirements`, `salary_range`, `status`, `posted_date`, `created_by`) VALUES
('Data Entry Specialist', 'Operations', 'part-time', 'Accurate data entry and validation for client projects', '["Typing speed 40+ WPM", "Attention to detail", "Basic Excel skills"]', 'PKR 25,000 - 35,000', 'open', CURDATE(), 5),
('Junior Data Analyst', 'Analytics', 'full-time', 'Support data analysis projects and report generation', '["Excel proficiency", "Basic SQL knowledge", "Analytical thinking"]', 'PKR 40,000 - 55,000', 'open', CURDATE(), 5),
('Database Administrator Trainee', 'IT', 'full-time', 'Learn database management and maintenance', '["Basic database knowledge", "Problem-solving skills", "Willingness to learn"]', 'PKR 35,000 - 45,000', 'open', CURDATE(), 5);

-- Demo training modules
INSERT IGNORE INTO `tts_training_modules` (`title`, `description`, `duration`, `is_mandatory`) VALUES
('Data Entry Best Practices', 'Learn efficient and accurate data entry techniques', '45 minutes', TRUE),
('Quality Assurance Standards', 'Understanding our quality control processes', '30 minutes', TRUE),
('Client Communication Guidelines', 'Professional communication standards and protocols', '25 minutes', FALSE),
('Time Management & Productivity', 'Tools and techniques for effective time management', '35 minutes', FALSE);

-- Demo gigs
INSERT IGNORE INTO `tts_gigs` (`title`, `description`, `budget`, `deadline`, `skills_required`, `status`) VALUES
('Website Development', 'Build a responsive e-commerce website with payment integration', 75000.00, '2025-03-01', 'PHP, MySQL, JavaScript, HTML/CSS', 'open'),
('Mobile App Design', 'Design UI/UX for a mobile application', 45000.00, '2025-02-15', 'Figma, Adobe XD, UI/UX Design', 'open'),
('Database Optimization', 'Optimize existing database queries and structure', 25000.00, '2025-02-28', 'MySQL, Database Design, Performance Tuning', 'open');

-- System settings
INSERT IGNORE INTO `tts_settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `category`) VALUES
('hourly_rate', '125', 'number', 'Base hourly rate in PKR', 'payroll'),
('streak_bonus', '500', 'number', 'Weekly bonus for 28-day streak in PKR', 'payroll'),
('security_fund', '1000', 'number', 'Security fund deduction amount in PKR', 'payroll'),
('operational_start', '11:00', 'string', 'Operational window start time (PKT)', 'schedule'),
('operational_end', '02:00', 'string', 'Operational window end time (PKT)', 'schedule'),
('min_daily_hours_pt', '2', 'number', 'Minimum daily hours for part-time', 'schedule'),
('min_daily_hours_ft', '6', 'number', 'Minimum daily hours for full-time', 'schedule'),
('max_daily_hours_ft', '8', 'number', 'Maximum daily hours for full-time', 'schedule'),
('company_name', 'Tech & Talent Solutions Ltd.', 'string', 'Company name', 'general'),
('company_email', 'info@tts.com.pk', 'string', 'Company contact email', 'general'),
('company_phone', '+92 300 1234567', 'string', 'Company contact phone', 'general'),
('dashboard_refresh_interval', '30', 'number', 'Dashboard auto-refresh interval in seconds', 'dashboard'),
('visitor_evaluation_timeout', '1800', 'number', 'Visitor evaluation form timeout in seconds', 'dashboard'),
('employee_break_duration', '3600', 'number', 'Maximum break duration in seconds', 'dashboard'),
('task_deadline_reminder', '24', 'number', 'Hours before deadline to send reminder', 'dashboard'),
('performance_review_cycle', '30', 'number', 'Performance review cycle in days', 'dashboard');

-- Performance optimization
ANALYZE TABLE `tts_users`, `tts_evaluations`, `tts_job_positions`, `tts_job_applications`, 
             `tts_onboarding_tasks`, `tts_training_modules`, `tts_user_training_progress`, 
             `tts_daily_tasks`, `tts_time_entries`, `tts_leave_requests`, `tts_gigs`, 
             `tts_payment_intents`, `tts_payments`, `tts_clients`, `tts_projects`, 
             `tts_page_layouts`, `tts_settings`, `tts_audit_log`;

-- ========================================
-- SAMPLE DATA FOR TESTING
-- ========================================

-- Insert demo users for testing
INSERT IGNORE INTO `tts_users` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `phone`, `role`, `status`, `email_verified`, `phone_verified`, `created_at`) VALUES
(1, 'admin@tts-pms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', '+92300123456', 'admin', 'ACTIVE', 1, 1, NOW()),
(2, 'ceo@tts-pms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Smith', '+92300123457', 'ceo', 'ACTIVE', 1, 1, NOW()),
(3, 'manager@tts-pms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah', 'Johnson', '+92300123458', 'manager', 'ACTIVE', 1, 1, NOW()),
(4, 'employee@tts-pms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike', 'Davis', '+92300123459', 'employee', 'ACTIVE', 1, 1, NOW()),
(5, 'newemployee@tts-pms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lisa', 'Wilson', '+92300123460', 'new_employee', 'ACTIVE', 1, 1, NOW()),
(6, 'candidate@tts-pms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David', 'Brown', '+92300123461', 'candidate', 'ACTIVE', 1, 1, NOW()),
(7, 'visitor@tts-pms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anna', 'Taylor', '+92300123462', 'visitor', 'ACTIVE', 1, 1, NOW());

-- Insert leave balances for users
INSERT IGNORE INTO `tts_leave_balances` (`user_id`, `year`, `annual_leave`, `sick_leave`, `casual_leave`, `emergency_leave`, `executive_leave`, `annual_used`, `sick_used`, `casual_used`, `emergency_used`, `executive_used`) VALUES
(2, YEAR(NOW()), 30, 15, 10, 5, 10, 3, 0, 1, 0, 1), -- CEO
(3, YEAR(NOW()), 25, 12, 8, 3, 0, 2, 1, 0, 0, 0), -- Manager
(4, YEAR(NOW()), 21, 10, 5, 2, 0, 5, 2, 1, 0, 0), -- Employee
(5, YEAR(NOW()), 0, 5, 0, 2, 0, 0, 0, 0, 0, 0); -- New Employee

-- Insert sample training modules
INSERT IGNORE INTO `tts_training_modules` (`id`, `title`, `description`, `duration`, `is_mandatory`, `order_sequence`) VALUES
(1, 'Company Orientation', 'Introduction to TTS company culture, values, and policies', '2 hours', 1, 1),
(2, 'Data Entry Fundamentals', 'Basic data entry skills and accuracy requirements', '4 hours', 1, 2),
(3, 'Quality Control Procedures', 'Understanding quality standards and review processes', '3 hours', 1, 3),
(4, 'Confidentiality and Security', 'Data protection, confidentiality agreements, and security protocols', '2 hours', 1, 4),
(5, 'Software Tools Training', 'Training on company software and productivity tools', '6 hours', 1, 5),
(6, 'Advanced Excel Skills', 'Advanced Excel functions for data analysis', '4 hours', 0, 6);

-- Insert onboarding tasks for new employee
INSERT IGNORE INTO `tts_onboarding_tasks` (`user_id`, `title`, `description`, `priority`, `status`, `due_date`) VALUES
(5, 'Complete Profile Setup', 'Fill out all personal and contact information', 'high', 'completed', DATE_ADD(NOW(), INTERVAL 1 DAY)),
(5, 'Review Company Handbook', 'Read and acknowledge company policies and procedures', 'high', 'in_progress', DATE_ADD(NOW(), INTERVAL 3 DAY)),
(5, 'Complete Mandatory Training', 'Finish all required training modules', 'high', 'pending', DATE_ADD(NOW(), INTERVAL 7 DAY)),
(5, 'Setup Workstation', 'Configure computer and software access', 'medium', 'pending', DATE_ADD(NOW(), INTERVAL 5 DAY)),
(5, 'Meet with Supervisor', 'Initial meeting with direct supervisor', 'medium', 'pending', DATE_ADD(NOW(), INTERVAL 10 DAY));

-- Insert training progress for new employee
INSERT IGNORE INTO `tts_user_training_progress` (`user_id`, `training_module_id`, `status`, `progress_percentage`, `started_at`) VALUES
(5, 1, 'completed', 100, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(5, 2, 'in_progress', 60, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(5, 3, 'not_started', 0, NULL),
(5, 4, 'not_started', 0, NULL),
(5, 5, 'not_started', 0, NULL);

-- Insert sample daily tasks
INSERT IGNORE INTO `tts_daily_tasks` (`user_id`, `assigned_by`, `title`, `description`, `priority`, `status`, `due_date`) VALUES
(4, 3, 'Process Customer Database', 'Enter customer information from forms batch #234', 'high', 'in_progress', CURDATE()),
(4, 3, 'Quality Review - Project ABC', 'Review and verify data accuracy for Project ABC', 'medium', 'pending', DATE_ADD(CURDATE(), INTERVAL 1 DAY)),
(5, 3, 'Training Module Completion', 'Complete Data Entry Fundamentals training', 'high', 'in_progress', DATE_ADD(CURDATE(), INTERVAL 2 DAY));

-- Insert sample time entries
INSERT IGNORE INTO `tts_time_entries` (`user_id`, `date`, `clock_in`, `clock_out`, `total_hours`, `status`) VALUES
(4, CURDATE(), '09:00:00', '17:00:00', 8.00, 'completed'),
(4, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '09:15:00', '17:30:00', 8.25, 'completed'),
(5, CURDATE(), '09:30:00', NULL, 0.00, 'active'),
(5, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '10:00:00', '16:00:00', 6.00, 'completed');

-- Insert sample job positions
INSERT IGNORE INTO `tts_job_positions` (`title`, `department`, `type`, `description`, `salary_range`, `status`, `posted_date`, `deadline`, `created_by`) VALUES
('Data Entry Specialist', 'Operations', 'full-time', 'Responsible for accurate data entry and quality control', 'PKR 45,000 - 55,000', 'open', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 3),
('Senior Data Analyst', 'Analytics', 'full-time', 'Lead data analysis projects and mentor junior staff', 'PKR 70,000 - 90,000', 'open', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 45 DAY), 2);

COMMIT;
