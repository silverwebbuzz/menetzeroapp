-- ============================================================================
-- MenetZero Database Enhancement Migration
-- Final SQL file for new enhancements
-- ============================================================================
-- 
-- IMPORTANT: 
-- 1. BACKUP YOUR DATABASE before running this script!
-- 2. This file only adds NEW tables and modifies EXISTING tables
-- 3. Legacy tables (carbon_emissions, facilities, etc.) are NOT modified
-- 4. Test on development first!
-- ============================================================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================================================
-- PART 1: MODIFY EXISTING TABLES
-- ============================================================================

-- Modify companies table
-- Remove these lines if columns already exist
ALTER TABLE `companies`
ADD COLUMN `company_type` enum('client', 'partner') NOT NULL DEFAULT 'client' AFTER `is_active`,
ADD COLUMN `is_direct_client` tinyint(1) DEFAULT 1 AFTER `company_type`;

-- Add index
CREATE INDEX `idx_company_type` ON `companies` (`company_type`);

-- Update existing companies to be clients by default
UPDATE `companies` SET `company_type` = 'client', `is_direct_client` = 1 WHERE `company_type` IS NULL OR `company_type` = '';

-- Modify users table
-- Remove these lines if columns already exist
ALTER TABLE `users`
ADD COLUMN `user_type` enum('client', 'partner') DEFAULT 'client' AFTER `role`,
ADD COLUMN `custom_role_id` bigint(20) UNSIGNED NULL AFTER `user_type`,
ADD COLUMN `external_company_name` varchar(255) NULL AFTER `custom_role_id`,
ADD COLUMN `notes` text NULL AFTER `external_company_name`;

-- Add indexes for users
CREATE INDEX `idx_user_type` ON `users` (`user_type`);
CREATE INDEX `idx_custom_role_id` ON `users` (`custom_role_id`);

-- ============================================================================
-- PART 2: ADMIN TABLE (Separate from users)
-- ============================================================================

-- Admins Table (Separate authentication system)
CREATE TABLE IF NOT EXISTS `admins` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_email_unique` (`email`),
  KEY `idx_admins_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin Password Reset Tokens Table
CREATE TABLE IF NOT EXISTS `admin_password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PART 3: SUBSCRIPTION MANAGEMENT TABLES
-- ============================================================================

-- Subscription Plans Table
CREATE TABLE IF NOT EXISTS `subscription_plans` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_code` varchar(50) NOT NULL UNIQUE,
  `plan_name` varchar(255) NOT NULL,
  `plan_category` enum('client', 'partner') NOT NULL,
  `price_annual` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'AED',
  `billing_cycle` enum('annual', 'monthly') DEFAULT 'annual',
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `description` text NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `limits` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plan_code` (`plan_code`),
  KEY `plan_category` (`plan_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Client Subscriptions Table
CREATE TABLE IF NOT EXISTS `client_subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_plan_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('active', 'cancelled', 'expired', 'suspended', 'trialing') DEFAULT 'active',
  `billing_cycle` enum('annual', 'monthly') DEFAULT 'annual',
  `started_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `auto_renew` tinyint(1) DEFAULT 1,
  `payment_method` varchar(50) NULL,
  `stripe_subscription_id` varchar(255) NULL,
  `stripe_customer_id` varchar(255) NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_active_subscription` (`company_id`, `status`),
  KEY `idx_company_id` (`company_id`),
  KEY `idx_subscription_plan_id` (`subscription_plan_id`),
  CONSTRAINT `fk_client_subscriptions_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_client_subscriptions_plan` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Partner Subscriptions Table
CREATE TABLE IF NOT EXISTS `partner_subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_plan_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('active', 'cancelled', 'expired', 'suspended', 'trialing') DEFAULT 'active',
  `billing_cycle` enum('annual', 'monthly') DEFAULT 'annual',
  `started_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `auto_renew` tinyint(1) DEFAULT 1,
  `payment_method` varchar(50) NULL,
  `stripe_subscription_id` varchar(255) NULL,
  `stripe_customer_id` varchar(255) NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_active_subscription` (`company_id`, `status`),
  KEY `idx_company_id` (`company_id`),
  KEY `idx_subscription_plan_id` (`subscription_plan_id`),
  CONSTRAINT `fk_partner_subscriptions_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_partner_subscriptions_plan` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PART 3: PARTNER EXTERNAL CLIENT TABLES
-- ============================================================================

-- Partner External Clients Table
CREATE TABLE IF NOT EXISTS `partner_external_clients` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_company_id` bigint(20) UNSIGNED NOT NULL,
  `client_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) NULL,
  `email` varchar(255) NULL,
  `phone` varchar(255) NULL,
  `industry` varchar(255) NULL,
  `sector` varchar(255) NULL,
  `address` text NULL,
  `city` varchar(255) NULL,
  `country` varchar(255) NULL,
  `status` enum('active', 'inactive', 'archived') DEFAULT 'active',
  `notes` text NULL,
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_partner_company_id` (`partner_company_id`),
  KEY `idx_status` (`status`),
  KEY `idx_partner_status` (`partner_company_id`, `status`),
  CONSTRAINT `fk_partner_external_clients_company` FOREIGN KEY (`partner_company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Partner External Client Locations Table
CREATE TABLE IF NOT EXISTS `partner_external_client_locations` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_external_client_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) NULL,
  `city` varchar(255) NULL,
  `country` varchar(255) NULL,
  `location_type` varchar(255) NULL,
  `staff_count` int(11) NULL,
  `staff_work_from_home` tinyint(1) DEFAULT 0,
  `work_from_home_percentage` decimal(5,2) NULL,
  `fiscal_year_start` varchar(16) DEFAULT 'January',
  `is_head_office` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `receives_utility_bills` tinyint(1) DEFAULT 0,
  `pays_electricity_proportion` tinyint(1) DEFAULT 0,
  `shared_building_services` tinyint(1) DEFAULT 0,
  `reporting_period` int(11) NULL,
  `measurement_frequency` enum('Annually','Half Yearly','Quarterly','Monthly') DEFAULT 'Annually',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_partner_external_client_id` (`partner_external_client_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_client_active` (`partner_external_client_id`, `is_active`),
  CONSTRAINT `fk_partner_external_client_locations_client` FOREIGN KEY (`partner_external_client_id`) REFERENCES `partner_external_clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Partner External Client Measurements Table
CREATE TABLE IF NOT EXISTS `partner_external_client_measurements` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_external_client_location_id` bigint(20) UNSIGNED NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `frequency` enum('monthly','quarterly','half_yearly','annually') NOT NULL,
  `status` enum('draft','submitted','under_review','not_verified','verified') DEFAULT 'draft',
  `fiscal_year` int(11) NOT NULL,
  `fiscal_year_start_month` varchar(16) NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `notes` text NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `staff_count` int(11) NULL,
  `staff_work_from_home` tinyint(1) DEFAULT 0,
  `work_from_home_percentage` decimal(5,2) NULL,
  `total_co2e` decimal(15,6) DEFAULT 0.000000,
  `scope_1_co2e` decimal(15,6) DEFAULT 0.000000,
  `scope_2_co2e` decimal(15,6) DEFAULT 0.000000,
  `scope_3_co2e` decimal(15,6) DEFAULT 0.000000,
  `co2e_calculated_at` timestamp NULL DEFAULT NULL,
  `emission_source_co2e` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_partner_external_client_location_id` (`partner_external_client_location_id`),
  KEY `idx_status` (`status`),
  KEY `idx_fiscal_year` (`fiscal_year`),
  KEY `idx_location_period` (`partner_external_client_location_id`, `period_start`, `period_end`),
  KEY `idx_status_fiscal_year` (`status`, `fiscal_year`),
  CONSTRAINT `fk_partner_external_client_measurements_location` FOREIGN KEY (`partner_external_client_location_id`) REFERENCES `partner_external_client_locations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_partner_external_client_measurements_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Partner External Client Measurement Data Table
CREATE TABLE IF NOT EXISTS `partner_external_client_measurement_data` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_external_client_measurement_id` bigint(20) UNSIGNED NOT NULL,
  `emission_source_id` int(11) NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `field_value` text NULL,
  `field_type` varchar(50) DEFAULT 'text',
  `created_by` bigint(20) UNSIGNED NULL,
  `updated_by` bigint(20) UNSIGNED NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_partner_external_client_measurement_id` (`partner_external_client_measurement_id`),
  KEY `idx_emission_source_id` (`emission_source_id`),
  CONSTRAINT `fk_partner_external_client_measurement_data_measurement` FOREIGN KEY (`partner_external_client_measurement_id`) REFERENCES `partner_external_client_measurements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_partner_external_client_measurement_data_source` FOREIGN KEY (`emission_source_id`) REFERENCES `emission_sources_master` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Partner External Client Documents Table
CREATE TABLE IF NOT EXISTS `partner_external_client_documents` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_external_client_id` bigint(20) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` enum('pdf','jpg','jpeg','png') NOT NULL,
  `file_size` int(11) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `source_type` enum('dewa','electricity','fuel','waste','water','transport','other') NOT NULL,
  `document_category` enum('bill','receipt','invoice','statement','contract','other') DEFAULT 'bill',
  `extracted_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `processed_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `ocr_confidence` decimal(5,2) NULL,
  `ocr_processed_at` timestamp NULL DEFAULT NULL,
  `ocr_attempts` int(11) DEFAULT 0,
  `ocr_error_message` text NULL,
  `status` enum('pending','processing','extracted','reviewed','approved','rejected','integrated','failed') DEFAULT 'pending',
  `approved_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `approved_by` bigint(20) UNSIGNED NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text NULL,
  `partner_external_client_measurement_id` bigint(20) UNSIGNED NULL,
  `integration_status` enum('pending','integrated','failed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_partner_external_client_id` (`partner_external_client_id`),
  KEY `idx_status` (`status`),
  KEY `idx_client_status` (`partner_external_client_id`, `status`),
  CONSTRAINT `fk_partner_external_client_documents_client` FOREIGN KEY (`partner_external_client_id`) REFERENCES `partner_external_clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_partner_external_client_documents_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Partner External Client Reports Table
CREATE TABLE IF NOT EXISTS `partner_external_client_reports` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_external_client_id` bigint(20) UNSIGNED NOT NULL,
  `report_type` varchar(100) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `file_path` varchar(500) NULL,
  `generated_at` datetime NULL,
  `generated_by` bigint(20) UNSIGNED NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_partner_external_client_id` (`partner_external_client_id`),
  KEY `idx_report_type` (`report_type`),
  CONSTRAINT `fk_partner_external_client_reports_client` FOREIGN KEY (`partner_external_client_id`) REFERENCES `partner_external_clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_partner_external_client_reports_generator` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Partner External Client Emission Boundaries Table
CREATE TABLE IF NOT EXISTS `partner_external_client_emission_boundaries` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_external_client_location_id` bigint(20) UNSIGNED NOT NULL,
  `scope` enum('Scope 1','Scope 2','Scope 3') NOT NULL,
  `selected_sources` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_partner_external_client_location_id` (`partner_external_client_location_id`),
  CONSTRAINT `fk_partner_external_client_emission_boundaries_location` FOREIGN KEY (`partner_external_client_location_id`) REFERENCES `partner_external_client_locations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PART 4: ACCESS MANAGEMENT TABLES
-- ============================================================================

-- User Company Access Table (Multi-Account Access)
CREATE TABLE IF NOT EXISTS `user_company_access` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `company_type` enum('client', 'partner') NOT NULL,
  `role_id` bigint(20) UNSIGNED NULL,
  `custom_role_id` bigint(20) UNSIGNED NULL,
  `access_level` enum('view', 'manage', 'full') DEFAULT 'view',
  `status` enum('active', 'suspended', 'revoked') DEFAULT 'active',
  `invited_by` bigint(20) UNSIGNED NULL,
  `invited_at` datetime NOT NULL,
  `last_accessed_at` datetime NULL,
  `notes` text NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_company_unique` (`user_id`, `company_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_company_id` (`company_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_user_company_access_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_company_access_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_company_access_inviter` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Active Context Table (Current Account Selection)
CREATE TABLE IF NOT EXISTS `user_active_context` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `active_company_id` bigint(20) UNSIGNED NULL,
  `active_company_type` enum('client', 'partner') NULL,
  `last_switched_at` datetime NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `fk_user_active_context_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_active_context_company` FOREIGN KEY (`active_company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Company Invitations Table
CREATE TABLE IF NOT EXISTS `company_invitations` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `company_type` enum('client', 'partner') NOT NULL,
  `email` varchar(255) NOT NULL,
  `role_id` bigint(20) UNSIGNED NULL,
  `custom_role_id` bigint(20) UNSIGNED NULL,
  `access_level` enum('view', 'manage', 'full') DEFAULT 'view',
  `token` varchar(255) NOT NULL UNIQUE,
  `status` enum('pending', 'accepted', 'rejected', 'expired') DEFAULT 'pending',
  `invited_by` bigint(20) UNSIGNED NOT NULL,
  `invited_at` datetime NOT NULL,
  `expires_at` datetime NULL,
  `accepted_at` datetime NULL,
  `accepted_by_user_id` bigint(20) UNSIGNED NULL,
  `notes` text NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_email_pending` (`company_id`, `email`, `status`),
  KEY `idx_email` (`email`),
  KEY `idx_token` (`token`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_company_invitations_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_company_invitations_inviter` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_company_invitations_accepter` FOREIGN KEY (`accepted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PART 5: ROLE MANAGEMENT TABLES
-- ============================================================================

-- Role Templates Table
CREATE TABLE IF NOT EXISTS `role_templates` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `template_code` varchar(50) NOT NULL UNIQUE,
  `template_name` varchar(255) NOT NULL,
  `description` text NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `category` enum('client', 'partner', 'both') DEFAULT 'client',
  `is_system_template` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_code` (`template_code`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Company Custom Roles Table
CREATE TABLE IF NOT EXISTS `company_custom_roles` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `role_name` varchar(255) NOT NULL,
  `description` text NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `based_on_template` varchar(50) NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_company_id` (`company_id`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_company_custom_roles_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PART 6: FEATURE FLAGS & USAGE TRACKING
-- ============================================================================

-- Feature Flags Table
CREATE TABLE IF NOT EXISTS `feature_flags` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `feature_code` varchar(100) NOT NULL,
  `is_enabled` tinyint(1) DEFAULT 0,
  `enabled_at` datetime NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_feature` (`company_id`, `feature_code`),
  KEY `idx_company_id` (`company_id`),
  CONSTRAINT `fk_feature_flags_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usage Tracking Table
CREATE TABLE IF NOT EXISTS `usage_tracking` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `resource_type` enum('location', 'user', 'document', 'report', 'api_call', 'measurement') NOT NULL,
  `resource_id` bigint(20) UNSIGNED NULL,
  `action` varchar(100) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `period` enum('daily', 'monthly', 'yearly') DEFAULT 'monthly',
  `period_start` date NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_company_id` (`company_id`),
  KEY `idx_resource_type` (`resource_type`),
  KEY `idx_period_start` (`period_start`),
  KEY `idx_company_period` (`company_id`, `period`, `period_start`),
  CONSTRAINT `fk_usage_tracking_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PART 7: SEED DEFAULT DATA
-- ============================================================================

-- Insert Default Subscription Plans for Clients
INSERT INTO `subscription_plans` (`plan_code`, `plan_name`, `plan_category`, `price_annual`, `currency`, `billing_cycle`, `is_active`, `sort_order`, `description`, `features`, `limits`) VALUES
('client_free', 'Free', 'client', 0.00, 'AED', 'annual', 1, 1, 'Free plan for clients', '["basic_measurements", "basic_reports"]', '{"locations": 1, "users": 1, "documents": 10}'),
('client_starter', 'Starter', 'client', 1199.00, 'AED', 'annual', 1, 2, 'Starter plan for MSME clients', '["basic_measurements", "basic_reports", "manual_entry"]', '{"locations": 1, "users": 2, "documents": 50}'),
('client_growth', 'Growth', 'client', 2999.00, 'AED', 'annual', 1, 3, 'Growth plan for expanding businesses', '["basic_measurements", "advanced_analytics", "basic_reports", "multi_location"]', '{"locations": 5, "users": 3, "documents": 200}'),
('client_pro', 'Pro', 'client', 5999.00, 'AED', 'annual', 1, 4, 'Professional plan with all features', '["basic_measurements", "advanced_analytics", "ocr_upload", "unlimited_reports", "data_archive"]', '{"locations": -1, "users": 5, "documents": -1}')
ON DUPLICATE KEY UPDATE `plan_name` = VALUES(`plan_name`);

-- Insert Default Subscription Plans for Partners
INSERT INTO `subscription_plans` (`plan_code`, `plan_name`, `plan_category`, `price_annual`, `currency`, `billing_cycle`, `is_active`, `sort_order`, `description`, `features`, `limits`) VALUES
('partner_free', 'Free', 'partner', 0.00, 'AED', 'annual', 1, 1, 'Free plan for partners', '["basic_analytics", "client_management"]', '{"clients": 2, "users": 1}'),
('partner_partner', 'Partner', 'partner', 9999.00, 'AED', 'annual', 1, 2, 'Partner plan for CA/Consultants', '["client_management", "analytics", "co_branded_reports", "usage_tracking"]', '{"clients": 10, "users": 5}'),
('partner_enterprise', 'Enterprise', 'partner', 29999.00, 'AED', 'annual', 1, 3, 'Enterprise plan with white-label', '["client_management", "analytics", "white_label", "api_access", "custom_integrations"]', '{"clients": -1, "users": -1}')
ON DUPLICATE KEY UPDATE `plan_name` = VALUES(`plan_name`);

-- Insert Default Role Templates for Clients
INSERT INTO `role_templates` (`template_code`, `template_name`, `description`, `permissions`, `category`, `is_system_template`, `is_active`, `sort_order`) VALUES
('client_owner', 'Owner', 'Full access to all features', '["*"]', 'client', 1, 1, 1),
('client_manager', 'Manager', 'Operational management access', '["measurements.*", "locations.*", "documents.view", "documents.upload", "reports.view"]', 'client', 1, 1, 2),
('client_data_entry', 'Data Entry', 'Data input only', '["measurements.create", "measurements.edit", "locations.view"]', 'client', 1, 1, 3),
('client_auditor', 'Auditor', 'View only access', '["measurements.view", "locations.view", "documents.view", "reports.view"]', 'client', 1, 1, 4)
ON DUPLICATE KEY UPDATE `template_name` = VALUES(`template_name`);

-- Insert Default Role Templates for Partners
INSERT INTO `role_templates` (`template_code`, `template_name`, `description`, `permissions`, `category`, `is_system_template`, `is_active`, `sort_order`) VALUES
('partner_admin', 'Partner Admin', 'Full partner access', '["*"]', 'partner', 1, 1, 1),
('partner_manager', 'Partner Manager', 'Operational management', '["clients.*", "analytics.view", "reports.*"]', 'partner', 1, 1, 2),
('partner_staff', 'Partner Staff', 'Limited access', '["clients.view", "clients.edit", "measurements.view", "locations.view"]', 'partner', 1, 1, 3)
ON DUPLICATE KEY UPDATE `template_name` = VALUES(`template_name`);

SET FOREIGN_KEY_CHECKS=1;

-- ============================================================================
-- CREATE FIRST ADMIN USER
-- ============================================================================
-- 
-- Admin User Details:
-- Name: Bhavik Koradiya
-- Email: menetzero@gmail.com
-- Password: admin@123456
-- 
-- IMPORTANT: After running this migration, create the admin user using one of these methods:
-- 
-- Method 1: Using Laravel Seeder (RECOMMENDED - Properly hashes password)
--   php artisan db:seed --class=AdminSeeder
--
-- Method 2: Using Laravel Tinker
--   php artisan tinker
--   \App\Models\Admin::create([
--       'name' => 'Bhavik Koradiya',
--       'email' => 'menetzero@gmail.com',
--       'password' => \Illuminate\Support\Facades\Hash::make('admin@123456'),
--       'is_active' => true,
--   ]);
--
-- Method 3: Direct SQL (requires generating hash first)
--   Generate hash: php artisan tinker -> echo \Illuminate\Support\Facades\Hash::make('admin@123456');
--   Then run the INSERT statement below with the generated hash
--
-- ============================================================================

-- Direct SQL Insert (Use only if you have the proper bcrypt hash)
-- INSERT INTO `admins` (`name`, `email`, `password`, `is_active`, `created_at`, `updated_at`)
-- VALUES (
--     'Bhavik Koradiya',
--     'menetzero@gmail.com',
--     'YOUR_BCRYPT_HASH_HERE', -- Replace with hash from: Hash::make('admin@123456')
--     1,
--     NOW(),
--     NOW()
-- )
-- ON DUPLICATE KEY UPDATE 
--     `name` = VALUES(`name`),
--     `is_active` = VALUES(`is_active`),
--     `updated_at` = NOW();

--
-- ============================================================================
-- MIGRATION COMPLETE
-- ============================================================================
-- 
-- Summary:
-- - Modified 2 existing tables (companies, users)
-- - Created 19 new tables for enhancements (including admins table)
-- - Seeded default subscription plans and role templates
-- 
-- Next Steps:
-- 1. Verify all tables were created successfully
-- 2. Create first admin user (see instructions above)
-- 3. Check foreign key constraints
-- 4. Update Laravel models
-- 5. Test the application
-- 6. Access admin panel at: /admin/login
-- ============================================================================

