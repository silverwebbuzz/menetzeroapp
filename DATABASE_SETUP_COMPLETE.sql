-- ============================================================================
-- MenetZero - Complete Database Setup
-- Consolidated SQL File - All Database Queries
-- ============================================================================
-- 
-- This SQL file contains ALL database setup queries for the MenetZero system:
-- 1. Table modifications (ALTER TABLE)
-- 2. New table creation (CREATE TABLE)
-- 3. Master data insertion (INSERT)
-- 4. Indexes and constraints
-- 5. Quick Input system setup
-- 6. Subscription and partner system setup
--
-- IMPORTANT: 
-- 1. BACKUP YOUR DATABASE before running this script!
-- 2. Run this on development first!
-- 3. Review all INSERT statements and adjust values as needed
-- 4. Ensure master_industry_categories table exists before running this script!
--
-- ============================================================================

-- ============================================================================
-- PART 1: FINAL DATABASE MIGRATION
-- ============================================================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Modify companies table
ALTER TABLE `companies`
ADD COLUMN `company_type` enum('client', 'partner') NOT NULL DEFAULT 'client' AFTER `is_active`,
ADD COLUMN `is_direct_client` tinyint(1) DEFAULT 1 AFTER `company_type`;

CREATE INDEX `idx_company_type` ON `companies` (`company_type`);

UPDATE `companies` SET `company_type` = 'client', `is_direct_client` = 1 WHERE `company_type` IS NULL OR `company_type` = '';

-- Modify users table
ALTER TABLE `users`
ADD COLUMN `user_type` enum('client', 'partner') DEFAULT 'client' AFTER `role`,
ADD COLUMN `custom_role_id` bigint(20) UNSIGNED NULL AFTER `user_type`,
ADD COLUMN `external_company_name` varchar(255) NULL AFTER `custom_role_id`,
ADD COLUMN `notes` text NULL AFTER `external_company_name`;

CREATE INDEX `idx_user_type` ON `users` (`user_type`);
CREATE INDEX `idx_custom_role_id` ON `users` (`custom_role_id`);

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

-- ============================================================================
-- PART 2: QUICK INPUT SYSTEM SETUP
-- ============================================================================

-- Enhance emission_sources_master table
ALTER TABLE `emission_sources_master`
ADD COLUMN `quick_input_slug` VARCHAR(100) NULL UNIQUE COMMENT 'URL slug (natural-gas, fuel, vehicle, etc.)',
ADD COLUMN `quick_input_icon` VARCHAR(50) NULL COMMENT 'Icon identifier',
ADD COLUMN `quick_input_order` INT(11) DEFAULT 0 COMMENT 'Menu display order',
ADD COLUMN `is_quick_input` TINYINT(1) DEFAULT 0 COMMENT 'Show in Quick Input menu',
ADD COLUMN `instructions` TEXT NULL COMMENT 'Form instructions text',
ADD COLUMN `tutorial_link` VARCHAR(255) NULL COMMENT 'Tutorial/documentation link',
ADD COLUMN `ipcc_category_code` VARCHAR(20) NULL COMMENT 'IPCC category code (e.g., 2.A.2, 2.B.1, 2.C.1)',
ADD COLUMN `ipcc_sector` VARCHAR(100) NULL COMMENT 'IPCC sector (e.g., Industrial Processes)',
ADD COLUMN `ipcc_subcategory` VARCHAR(255) NULL COMMENT 'IPCC subcategory description',
ADD COLUMN `emission_type` ENUM('combustion', 'process', 'fugitive', 'electricity', 'other') NULL COMMENT 'Type of emission',
ADD COLUMN `default_unit` VARCHAR(50) NULL COMMENT 'Default unit for this source';

CREATE INDEX `idx_quick_input` ON `emission_sources_master` (`is_quick_input`, `quick_input_order`);
CREATE INDEX `idx_ipcc_category` ON `emission_sources_master` (`ipcc_category_code`);

-- Enhance emission_factors table
ALTER TABLE `emission_factors`
ADD COLUMN `fuel_type` VARCHAR(100) NULL COMMENT 'Fuel type (Natural Gas, Diesel, Petrol, etc.)',
ADD COLUMN `fuel_category` VARCHAR(50) NULL COMMENT 'Fuel category (Gaseous, Liquid, Solid)',
ADD COLUMN `vehicle_type` VARCHAR(100) NULL COMMENT 'Vehicle type (Small car, Medium car, etc.)',
ADD COLUMN `vehicle_size` VARCHAR(50) NULL COMMENT 'Vehicle size category',
ADD COLUMN `co2_factor` DECIMAL(15,6) NULL COMMENT 'CO2 emission factor (kg CO2/unit)',
ADD COLUMN `ch4_factor` DECIMAL(15,6) NULL COMMENT 'CH4 emission factor (kg CH4/unit)',
ADD COLUMN `n2o_factor` DECIMAL(15,6) NULL COMMENT 'N2O emission factor (kg N2O/unit)',
ADD COLUMN `total_co2e_factor` DECIMAL(15,6) NULL COMMENT 'Total CO2e factor (kg CO2e/unit) - calculated',
ADD COLUMN `source_standard` ENUM('DEFRA', 'IPCC', 'UAE', 'MOCCAE', 'USEPA', 'Custom') DEFAULT 'DEFRA',
ADD COLUMN `source_reference` VARCHAR(255) NULL COMMENT 'Reference document/source',
ADD COLUMN `gwp_version` VARCHAR(20) DEFAULT 'AR6' COMMENT 'GWP version used (AR4, AR5, AR6)',
ADD COLUMN `is_default` TINYINT(1) DEFAULT 0 COMMENT 'Default factor for this source/region',
ADD COLUMN `priority` INT(11) DEFAULT 0 COMMENT 'Priority for selection (higher = preferred)';

CREATE INDEX `idx_fuel_type` ON `emission_factors` (`fuel_type`, `fuel_category`);
CREATE INDEX `idx_vehicle_type` ON `emission_factors` (`vehicle_type`, `vehicle_size`);
CREATE INDEX `idx_source_standard` ON `emission_factors` (`source_standard`, `region`);
CREATE INDEX `idx_default_priority` ON `emission_factors` (`is_default`, `priority` DESC);

-- Modify measurement_data table
CREATE INDEX `idx_measurement_source` ON `measurement_data` (`measurement_id`, `emission_source_id`);

ALTER TABLE `measurement_data`
ADD COLUMN `entry_date` DATE NULL COMMENT 'Date of this specific entry',
ADD COLUMN `entry_number` INT(11) NULL COMMENT 'Entry sequence number',
ADD COLUMN `fuel_type` VARCHAR(100) NULL COMMENT 'Fuel type used (if applicable)',
ADD COLUMN `vehicle_type` VARCHAR(100) NULL COMMENT 'Vehicle type (if applicable)',
ADD COLUMN `gas_type` VARCHAR(50) NULL COMMENT 'Refrigerant gas type (if applicable)',
ADD COLUMN `co2_emissions` DECIMAL(15,4) NULL COMMENT 'CO2 emissions (kg)',
ADD COLUMN `ch4_emissions` DECIMAL(15,4) NULL COMMENT 'CH4 emissions (kg)',
ADD COLUMN `n2o_emissions` DECIMAL(15,4) NULL COMMENT 'N2O emissions (kg)',
ADD COLUMN `gwp_version_used` VARCHAR(20) DEFAULT 'AR6' COMMENT 'GWP version used for calculation',
ADD COLUMN `emission_factor_id` BIGINT(20) UNSIGNED NULL COMMENT 'Reference to emission_factors table',
ADD COLUMN `calculation_method` VARCHAR(100) NULL COMMENT 'Method used (Tier 1, Tier 2, Tier 3)',
ADD COLUMN `supplier_emission_factor` DECIMAL(15,6) NULL COMMENT 'Supplier-specific factor (if used)',
ADD COLUMN `calculated_co2e` DECIMAL(15, 4) NULL DEFAULT NULL COMMENT 'Total CO2e calculated value',
ADD COLUMN `scope` ENUM('Scope 1', 'Scope 2', 'Scope 3') NULL DEFAULT NULL COMMENT 'Emission scope',
ADD COLUMN `quantity` DECIMAL(15, 4) NULL DEFAULT NULL COMMENT 'Quantity value for Quick Input entries',
ADD COLUMN `unit` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Unit of measurement (kWh, liters, kg, etc.)',
ADD COLUMN `notes` TEXT NULL DEFAULT NULL COMMENT 'Additional notes',
ADD COLUMN `additional_data` JSON NULL DEFAULT NULL COMMENT 'Additional form field data as JSON',
ADD COLUMN `is_offset` TINYINT(1) NULL DEFAULT 0 COMMENT 'Whether this emission is offset';

CREATE INDEX `idx_entry_date` ON `measurement_data` (`entry_date`);
CREATE INDEX `idx_fuel_type_md` ON `measurement_data` (`fuel_type`);
CREATE INDEX `idx_emission_factor` ON `measurement_data` (`emission_factor_id`);
CREATE INDEX `idx_measurement_data_scope` ON `measurement_data` (`measurement_id`, `scope`);
CREATE INDEX `idx_measurement_data_scope_only` ON `measurement_data` (`scope`);

-- Create emission_gwp_values table
CREATE TABLE IF NOT EXISTS `emission_gwp_values` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `gas_name` VARCHAR(100) NOT NULL COMMENT 'Gas name (CO2, CH4, N2O, HFC-134a, etc.)',
  `gas_code` VARCHAR(50) NULL COMMENT 'Gas code/identifier',
  `gwp_version` ENUM('AR4', 'AR5', 'AR6') DEFAULT 'AR6' COMMENT 'IPCC Assessment Report version',
  `gwp_100_year` DECIMAL(10,2) NOT NULL COMMENT '100-year GWP value',
  `gwp_20_year` DECIMAL(10,2) NULL COMMENT '20-year GWP value',
  `gwp_500_year` DECIMAL(10,2) NULL COMMENT '500-year GWP value',
  `notes` TEXT NULL COMMENT 'Additional notes about the gas',
  `is_kyoto_protocol` TINYINT(1) DEFAULT 0 COMMENT 'Is this a Kyoto Protocol gas?',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_gas_version` (`gas_name`, `gwp_version`),
  KEY `idx_gas_code` (`gas_code`),
  KEY `idx_gwp_version` (`gwp_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create emission_source_form_fields table
CREATE TABLE IF NOT EXISTS `emission_source_form_fields` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `emission_source_id` BIGINT(20) UNSIGNED NOT NULL,
  `field_name` VARCHAR(100) NOT NULL,
  `field_type` ENUM('text', 'number', 'select', 'textarea', 'date', 'checkbox', 'radio') NOT NULL,
  `field_label` VARCHAR(255) NOT NULL,
  `field_placeholder` VARCHAR(255) NULL,
  `field_options` JSON NULL COMMENT 'Options for select/radio (e.g., [{"value": "tonnes", "label": "Tonnes"}]',
  `is_required` TINYINT(1) DEFAULT 0,
  `field_order` INT(11) DEFAULT 0,
  `validation_rules` JSON NULL COMMENT 'Validation rules (min, max, pattern, etc.)',
  `help_text` TEXT NULL,
  `default_value` VARCHAR(255) NULL,
  `conditional_logic` JSON NULL COMMENT 'Show/hide rules: {"depends_on": "field_name", "show_if": "value"}',
  `depends_on_field` VARCHAR(100) NULL COMMENT 'Field name this depends on',
  `depends_on_value` VARCHAR(255) NULL COMMENT 'Value that triggers this field',
  `calculation_formula` TEXT NULL COMMENT 'Formula for calculated fields',
  `unit_conversion` JSON NULL COMMENT 'Unit conversion factors',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_emission_source_id` (`emission_source_id`),
  KEY `idx_field_order` (`emission_source_id`, `field_order`),
  KEY `idx_depends_on` (`depends_on_field`),
  CONSTRAINT `fk_form_fields_emission_source` FOREIGN KEY (`emission_source_id`) 
    REFERENCES `emission_sources_master` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create emission_unit_conversions table
CREATE TABLE IF NOT EXISTS `emission_unit_conversions` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `from_unit` VARCHAR(50) NOT NULL,
  `to_unit` VARCHAR(50) NOT NULL,
  `conversion_factor` DECIMAL(15,6) NOT NULL COMMENT 'Multiply from_unit by this to get to_unit',
  `fuel_type` VARCHAR(100) NULL COMMENT 'Fuel-specific conversion (if applicable)',
  `region` VARCHAR(50) NULL COMMENT 'Region-specific conversion (if applicable)',
  `is_active` TINYINT(1) DEFAULT 1,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_conversion` (`from_unit`, `to_unit`, `fuel_type`, `region`),
  KEY `idx_from_unit` (`from_unit`),
  KEY `idx_fuel_type` (`fuel_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create emission_factor_selection_rules table
CREATE TABLE IF NOT EXISTS `emission_factor_selection_rules` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `emission_source_id` BIGINT(20) UNSIGNED NOT NULL,
  `rule_name` VARCHAR(255) NOT NULL,
  `priority` INT(11) DEFAULT 0 COMMENT 'Higher priority = selected first',
  `conditions` JSON NOT NULL COMMENT 'Conditions: {"region": "UAE", "fuel_type": "Natural Gas", "unit": "kWh"}',
  `emission_factor_id` BIGINT(20) UNSIGNED NULL COMMENT 'Specific factor to use',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_emission_source` (`emission_source_id`),
  KEY `idx_priority` (`emission_source_id`, `priority` DESC),
  CONSTRAINT `fk_selection_rules_source` FOREIGN KEY (`emission_source_id`) 
    REFERENCES `emission_sources_master` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_selection_rules_factor` FOREIGN KEY (`emission_factor_id`) 
    REFERENCES `emission_factors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create emission_industry_labels table
CREATE TABLE IF NOT EXISTS `emission_industry_labels` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `emission_source_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Links to emission_sources_master',
  `industry_category_id` BIGINT(20) UNSIGNED NULL COMMENT 'Links to master_industry_categories.id (can be Level 1, 2, or 3)',
  `match_level` TINYINT(1) NULL COMMENT '1=Sector (Level 1), 2=Industry (Level 2), 3=Subcategory (Level 3) - which level this label applies to',
  `also_match_children` TINYINT(1) DEFAULT 0 COMMENT 'If 1, also applies to all child categories (cascading match)',
  `unit_type` VARCHAR(100) NULL COMMENT 'Unit type context (e.g., Main Factory, Office Building, Restaurant/Kitchen, Data Center, Warehouse)',
  `user_friendly_name` VARCHAR(255) NOT NULL COMMENT 'User-friendly name for this emission source in this industry context',
  `user_friendly_description` TEXT NULL COMMENT 'Industry-specific description',
  `common_equipment` TEXT NULL COMMENT 'Common equipment/use cases for this industry',
  `typical_units` VARCHAR(255) NULL COMMENT 'Typical units used in this industry context',
  `display_order` INT(11) DEFAULT 0 COMMENT 'Display order within industry',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_emission_source` (`emission_source_id`),
  KEY `idx_industry_category` (`industry_category_id`, `match_level`),
  KEY `idx_unit_type` (`unit_type`),
  KEY `idx_industry_source` (`industry_category_id`, `emission_source_id`),
  KEY `idx_match_level` (`match_level`, `also_match_children`),
  CONSTRAINT `fk_industry_labels_source` FOREIGN KEY (`emission_source_id`) 
    REFERENCES `emission_sources_master` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_industry_labels_category` FOREIGN KEY (`industry_category_id`) 
    REFERENCES `master_industry_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert GWP Values (AR6 values)
INSERT INTO `emission_gwp_values` (`gas_name`, `gas_code`, `gwp_version`, `gwp_100_year`, `notes`, `is_kyoto_protocol`) VALUES
('CO₂', 'CO2', 'AR6', 1.00, 'Reference gas', 1),
('CH₄ (fossil)', 'CH4_FOSSIL', 'AR6', 27.20, 'Without climate–carbon feedbacks', 1),
('CH₄ (biogenic)', 'CH4_BIOGENIC', 'AR6', 29.80, 'With climate–carbon feedbacks', 1),
('N₂O', 'N2O', 'AR6', 273.00, 'Very high due to long lifetime', 1),
('HFC-134a', 'HFC134A', 'AR6', 1530.00, 'Common refrigerant', 1),
('HFC-152a', 'HFC152A', 'AR6', 148.00, 'Lower GWP HFC', 1),
('HFC-23', 'HFC23', 'AR6', 14600.00, 'Extremely high', 1),
('HFC-32', 'HFC32', 'AR6', 771.00, 'Used in AC/refrigeration', 1),
('HFC-125', 'HFC125', 'AR6', 3170.00, 'Blend component', 1),
('HFC-143a', 'HFC143A', 'AR6', 5160.00, 'Blend component', 1),
('HFC-245fa', 'HFC245FA', 'AR6', 1030.00, 'Foam blowing agent', 1),
('HFC-365mfc', 'HFC365MFC', 'AR6', 794.00, 'Foam blowing agent', 1),
('HFC-227ea', 'HFC227EA', 'AR6', 3220.00, 'Fire suppressant', 1),
('HFC-236fa', 'HFC236FA', 'AR6', 8180.00, 'Fire suppressant', 1),
('HFC-4310mee', 'HFC4310MEE', 'AR6', 1640.00, 'Specialty solvent', 1),
('SF₆', 'SF6', 'AR6', 25200.00, 'Highest single GWP gas', 1),
('NF₃', 'NF3', 'AR6', 19700.00, 'Semiconductor industry', 1),
('CF₄ (PFC-14)', 'PFC14', 'AR6', 7370.00, 'Very long-lived', 1),
('C₂F₆ (PFC-116)', 'PFC116', 'AR6', 12200.00, 'Very long-lived', 1),
('C₃F₈ (PFC-218)', 'PFC218', 'AR6', 7890.00, 'Very long-lived', 1),
('C₄F₁₀ (PFC-318)', 'PFC318', 'AR6', 6910.00, 'Very long-lived', 1),
('C₅F₁₂', 'PFC512', 'AR6', 8930.00, 'Very long-lived', 1),
('C₆F₁₄', 'PFC614', 'AR6', 7910.00, 'Very long-lived', 1),
('C₇F₁₆', 'PFC716', 'AR6', 7310.00, 'Very long-lived', 1),
('C₈F₁₈', 'PFC818', 'AR6', 7630.00, 'Very long-lived', 1)
ON DUPLICATE KEY UPDATE 
  `gwp_100_year` = VALUES(`gwp_100_year`),
  `notes` = VALUES(`notes`);

-- Insert Quick Input Emission Sources
INSERT INTO `emission_sources_master` 
(`name`, `description`, `scope`, `category`, `subcategory`, `is_active`, 
 `quick_input_slug`, `quick_input_icon`, `quick_input_order`, `is_quick_input`,
 `instructions`, `emission_type`, `default_unit`) 
VALUES 
('Natural Gas (Stationary combustion)', 
 'Natural gas used for heating, boilers, and cooking in stationary equipment',
 'Scope 1', 'Direct Emissions', 'Stationary Combustion', 1,
 'natural-gas', 'flame', 1, 1,
 'Enter the amount of energy used at each site of operation. Copies of utility bills can be linked in the Additional Data section.',
 'combustion', 'cubic metres'),
('Fuel (Stationary combustion)', 
 'Diesel, petrol, LPG, and other fuels used in stationary equipment and generators',
 'Scope 1', 'Direct Emissions', 'Stationary Combustion', 1,
 'fuel', 'fuel-pump', 2, 1,
 'Enter the amount of fuel used at each site of operation. Select fuel category and type.',
 'combustion', 'litres'),
('Vehicle (Mobile combustion)', 
 'Company cars, vans, trucks, and other vehicles',
 'Scope 1', 'Direct Emissions', 'Mobile Combustion', 1,
 'vehicle', 'car', 3, 1,
 'We recommend entering details for each individual vehicle, unless multiple vehicles are the same size and fuel type.',
 'combustion', 'km'),
('Refrigerants (Fugitive emissions)', 
 'AC & cooling gases used in air conditioning and refrigeration systems',
 'Scope 1', 'Direct Emissions', 'Fugitive Emissions', 1,
 'refrigerants', 'snowflake', 4, 1,
 'Refrigerant gases are most commonly used in air conditioning systems. Be sure to only enter data on gas top-ups/refills, rather than e.g., the capacity of the unit.',
 'fugitive', 'kg'),
('Process (Process Emissions)', 
 'Manufacturing or chemical processes that produce emissions',
 'Scope 1', 'Direct Emissions', 'Process Emissions', 1,
 'process', 'gear', 5, 1,
 'Enter process-specific emission data based on your industry type.',
 'process', 'tonnes', '2.A.2'),
('Electricity (Purchased Electricity)', 
 'Grid power, offices, factories - purchased electricity',
 'Scope 2', 'Indirect Energy', 'Purchased Electricity', 1,
 'electricity', 'lightning', 6, 1,
 'Enter the amount of electricity used at each site of operation, electric vehicle kWhs can also be entered here.',
 'electricity', 'kWh'),
('Purchased Heat, Steam & Cooling', 
 'District cooling, steam, chilled water',
 'Scope 2', 'Indirect Energy', 'Purchased Heat/Steam/Cooling', 1,
 'heat-steam-cooling', 'thermometer', 7, 1,
 'Enter purchased heat, steam, or cooling energy consumption.',
 'electricity', 'kWh'),
('Flights (Business travel)', 
 'Business air travel emissions',
 'Scope 3', 'Indirect', 'Business Travel', 1,
 'flights', 'airplane', 8, 1,
 'Enter business flight travel data including distance and flight class.',
 'other', 'km'),
('Public Transport (Employee commuting)', 
 'Employee commuting via public transport',
 'Scope 3', 'Indirect', 'Employee Commuting', 1,
 'public-transport', 'bus', 9, 1,
 'Enter employee commuting data via public transport.',
 'other', 'km'),
('Home Workers (Remote work)', 
 'Remote / work-from-home emissions',
 'Scope 3', 'Indirect', 'Remote Work', 1,
 'home-workers', 'home', 10, 1,
 'Enter remote work energy consumption data.',
 'other', 'kWh')
ON DUPLICATE KEY UPDATE 
  `quick_input_slug` = VALUES(`quick_input_slug`),
  `is_quick_input` = VALUES(`is_quick_input`);

-- Note: Additional emission factors, form fields, and unit conversions
-- are included in separate sections below. See the complete file for all data.

SET FOREIGN_KEY_CHECKS=1;

-- ============================================================================
-- END OF DATABASE SETUP
-- ============================================================================
-- 
-- This file contains the complete database structure for MenetZero system.
-- For detailed form fields, emission factors, and unit conversions,
-- refer to the documentation files.
-- 
-- ============================================================================

-- ============================================================================
-- PROCESS EMISSIONS FORM FIELDS AND EMISSION FACTORS
-- ============================================================================
-- This file contains form fields and emission factors for Process (Process Emissions)
-- Based on IPCC process emission categories from Process Emission/ folder
-- ============================================================================

-- ============================================================================
-- PART 1: FORM FIELDS FOR PROCESS EMISSIONS
-- ============================================================================

-- 1.1 Process Type (Main field - selects the type of industrial process)
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `field_options`, `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'process_type',
  'select',
  'Process Type',
  'Select an option',
  JSON_ARRAY(
    JSON_OBJECT('value', 'Adipic Acid Production', 'label', 'Adipic Acid Production'),
    JSON_OBJECT('value', 'Aluminium Production', 'label', 'Aluminium Production'),
    JSON_OBJECT('value', 'Ammonia Production', 'label', 'Ammonia Production'),
    JSON_OBJECT('value', 'Carbide Production', 'label', 'Carbide Production'),
    JSON_OBJECT('value', 'Caprolactam, Glyoxal and Glyoxalic Acid Production', 'label', 'Caprolactam, Glyoxal and Glyoxalic Acid Production'),
    JSON_OBJECT('value', 'Cement Production', 'label', 'Cement Production'),
    JSON_OBJECT('value', 'Ceramics Production', 'label', 'Ceramics Production'),
    JSON_OBJECT('value', 'Electronics Production', 'label', 'Electronics Production'),
    JSON_OBJECT('value', 'Food and Beverage Production', 'label', 'Food and Beverage Production'),
    JSON_OBJECT('value', 'Glass Production', 'label', 'Glass Production'),
    JSON_OBJECT('value', 'Hydrogen Production', 'label', 'Hydrogen Production'),
    JSON_OBJECT('value', 'Lead Production', 'label', 'Lead Production'),
    JSON_OBJECT('value', 'Lime Production', 'label', 'Lime Production'),
    JSON_OBJECT('value', 'Magnesium Production', 'label', 'Magnesium Production'),
    JSON_OBJECT('value', 'Nitric Acid Production', 'label', 'Nitric Acid Production'),
    JSON_OBJECT('value', 'Non-Metallurgical Magnesia Production', 'label', 'Non-Metallurgical Magnesia Production'),
    JSON_OBJECT('value', 'Non-Energy Products from Fuels and Solvent Use', 'label', 'Non-Energy Products from Fuels and Solvent Use'),
    JSON_OBJECT('value', 'Other use of Soda Ash', 'label', 'Other use of Soda Ash'),
    JSON_OBJECT('value', 'Petrochemical and Carbon Black Production', 'label', 'Petrochemical and Carbon Black Production'),
    JSON_OBJECT('value', 'Soda Ash Production', 'label', 'Soda Ash Production'),
    JSON_OBJECT('value', 'Steel and Iron Production', 'label', 'Steel and Iron Production'),
    JSON_OBJECT('value', 'Titanium Dioxide Production', 'label', 'Titanium Dioxide Production'),
    JSON_OBJECT('value', 'Zinc Production', 'label', 'Zinc Production'),
    JSON_OBJECT('value', 'Other Process Emissions', 'label', 'Other Process Emissions')
  ),
  1,
  1,
  'Select the type of industrial process that generates emissions.'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'process'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `field_options` = VALUES(`field_options`),
  `help_text` = VALUES(`help_text`);

-- 1.2 Unit of Measure (Depends on process type)
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `field_options`, `is_required`, `field_order`, `help_text`, `depends_on_field`)
SELECT 
  es.id,
  'unit_of_measure',
  'select',
  'Unit of Measure',
  'Select an option',
  JSON_ARRAY(
    JSON_OBJECT('value', 'tonnes', 'label', 'tonnes'),
    JSON_OBJECT('value', 'kg', 'label', 'kg'),
    JSON_OBJECT('value', 'cubic metres', 'label', 'cubic metres'),
    JSON_OBJECT('value', 'litres', 'label', 'litres')
  ),
  1,
  2,
  'Select the unit of measure for the process output or input quantity.',
  'process_type'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'process'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `field_options` = VALUES(`field_options`),
  `help_text` = VALUES(`help_text`),
  `depends_on_field` = VALUES(`depends_on_field`);

-- 1.3 Quantity/Amount
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`, `depends_on_field`, `validation_rules`)
SELECT 
  es.id,
  'amount',
  'number',
  'Quantity',
  'Enter quantity',
  1,
  3,
  'Enter the quantity of process output or input material in the selected unit of measure.',
  'unit_of_measure',
  JSON_OBJECT('min', 0, 'step', 'any')
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'process'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `help_text` = VALUES(`help_text`),
  `depends_on_field` = VALUES(`depends_on_field`),
  `validation_rules` = VALUES(`validation_rules`);

-- 1.4 Link field (Additional Data)
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'link',
  'text',
  'Link',
  'e.g. Sharepoint or Google Dr...',
  0,
  4,
  'Link to supporting documents'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'process'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `help_text` = VALUES(`help_text`);

-- 1.5 Comments field (Additional Data)
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'comments',
  'textarea',
  'Comments',
  'Add any additional notes...',
  0,
  5,
  'Additional comments or notes'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'process'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `help_text` = VALUES(`help_text`);

-- ============================================================================
-- PART 2: EMISSION FACTORS FOR PROCESS EMISSIONS
-- ============================================================================
-- Note: These are placeholder emission factors based on typical process emissions
-- You will need to update these with actual values from your Excel files
-- Each process type should have its own emission factor(s) with appropriate units

-- 2.1 Cement Production (example - update with actual values from Excel)
INSERT INTO `emission_factors` 
(`emission_source_id`, `factor_value`, `total_co2e_factor`, `unit`, `calculation_method`, `region`, 
 `valid_from`, `valid_to`, `is_active`, `description`, `fuel_type`, `source_standard`, `source_reference`, 
 `gwp_version`, `is_default`, `priority`) 
SELECT 
  es.id,
  850.000000, -- Placeholder - update with actual value from Excel
  850.000000,
  'tonnes',
  'IPCC 2006 Guidelines',
  'UAE',
  2024,
  NULL,
  1,
  'CO2e emission factor for Cement Production (kg CO2e per tonne of cement produced).',
  'Cement Production',
  'IPCC',
  'IPCC 2006 Guidelines for National Greenhouse Gas Inventories',
  'AR6',
  1,
  100
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'process'
ON DUPLICATE KEY UPDATE 
  `factor_value` = VALUES(`factor_value`),
  `total_co2e_factor` = VALUES(`total_co2e_factor`),
  `description` = VALUES(`description`);

-- 2.2 Steel and Iron Production (example - update with actual values from Excel)
INSERT INTO `emission_factors` 
(`emission_source_id`, `factor_value`, `total_co2e_factor`, `unit`, `calculation_method`, `region`, 
 `valid_from`, `valid_to`, `is_active`, `description`, `fuel_type`, `source_standard`, `source_reference`, 
 `gwp_version`, `is_default`, `priority`) 
SELECT 
  es.id,
  1800.000000, -- Placeholder - update with actual value from Excel
  1800.000000,
  'tonnes',
  'IPCC 2006 Guidelines',
  'UAE',
  2024,
  NULL,
  1,
  'CO2e emission factor for Steel and Iron Production (kg CO2e per tonne of steel produced).',
  'Steel and Iron Production',
  'IPCC',
  'IPCC 2006 Guidelines for National Greenhouse Gas Inventories',
  'AR6',
  1,
  100
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'process'
ON DUPLICATE KEY UPDATE 
  `factor_value` = VALUES(`factor_value`),
  `total_co2e_factor` = VALUES(`total_co2e_factor`),
  `description` = VALUES(`description`);

-- 2.3 Aluminium Production (example - update with actual values from Excel)
INSERT INTO `emission_factors` 
(`emission_source_id`, `factor_value`, `total_co2e_factor`, `unit`, `calculation_method`, `region`, 
 `valid_from`, `valid_to`, `is_active`, `description`, `fuel_type`, `source_standard`, `source_reference`, 
 `gwp_version`, `is_default`, `priority`) 
SELECT 
  es.id,
  12000.000000, -- Placeholder - update with actual value from Excel
  12000.000000,
  'tonnes',
  'IPCC 2006 Guidelines',
  'UAE',
  2024,
  NULL,
  1,
  'CO2e emission factor for Aluminium Production (kg CO2e per tonne of aluminium produced).',
  'Aluminium Production',
  'IPCC',
  'IPCC 2006 Guidelines for National Greenhouse Gas Inventories',
  'AR6',
  1,
  100
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'process'
ON DUPLICATE KEY UPDATE 
  `factor_value` = VALUES(`factor_value`),
  `total_co2e_factor` = VALUES(`total_co2e_factor`),
  `description` = VALUES(`description`);

-- Note: Add more process emission factors here for each process type
-- You will need to extract the actual emission factors from your Excel files
-- and insert them with the correct process type as fuel_type

-- ============================================================================
-- PART 3: UNIT CONVERSIONS FOR PROCESS EMISSIONS (if needed)
-- ============================================================================
-- Add unit conversions if process emissions use different units

INSERT INTO `emission_unit_conversions` (`from_unit`, `to_unit`, `conversion_factor`, `fuel_type`, `region`, `notes`, `is_active`) VALUES
-- Process emissions conversions (if applicable)
('kg', 'tonnes', 0.001, NULL, NULL, 'Standard conversion: 1 kg = 0.001 tonnes', 1),
('tonnes', 'kg', 1000.0, NULL, NULL, 'Standard conversion: 1 tonne = 1000 kg', 1)
ON DUPLICATE KEY UPDATE 
  `conversion_factor` = VALUES(`conversion_factor`),
  `notes` = VALUES(`notes`);

-- ============================================================================
-- VEHICLE (MOBILE COMBUSTION) FORM FIELDS AND EMISSION FACTORS
-- ============================================================================
-- This file contains form fields and emission factors for Vehicle (Mobile combustion)
-- Simplified form: Only uses distance-based calculation with vehicle size selection
-- Based on DEFRA 2024 Mobile Combustion factors
-- ============================================================================

-- ============================================================================
-- PART 1: FORM FIELDS FOR VEHICLE (MOBILE COMBUSTION)
-- ============================================================================

-- 1.1 Type of Vehicle (Company Owned, Leased, etc.)
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `field_options`, `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'vehicle_ownership_type',
  'select',
  'Type of Vehicle',
  'Select an option',
  JSON_ARRAY(
    JSON_OBJECT('value', 'Company Owned', 'label', 'Company Owned'),
    JSON_OBJECT('value', 'Leased', 'label', 'Leased'),
    JSON_OBJECT('value', 'Rented', 'label', 'Rented'),
    JSON_OBJECT('value', 'Employee Owned', 'label', 'Employee Owned'),
    JSON_OBJECT('value', 'Other', 'label', 'Other')
  ),
  1,
  1,
  'Select the ownership type of the vehicle'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'vehicle'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `field_options` = VALUES(`field_options`),
  `help_text` = VALUES(`help_text`);

-- 1.2 Vehicle Size (Based on DEFRA categories)
-- This is the main field for selecting vehicle size
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `field_options`, `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'vehicle_size',
  'select',
  'Type of Fuel / Vehicle (By Size)',
  'Select an option',
  JSON_ARRAY(
    -- Cars
    JSON_OBJECT('value', 'Small car', 'label', 'Small car'),
    JSON_OBJECT('value', 'Medium car', 'label', 'Medium car'),
    JSON_OBJECT('value', 'Large car', 'label', 'Large car'),
    JSON_OBJECT('value', 'Average car', 'label', 'Average car'),
    -- Motorbikes
    JSON_OBJECT('value', 'Small Motorbike', 'label', 'Small Motorbike'),
    JSON_OBJECT('value', 'Medium Motorbike', 'label', 'Medium Motorbike'),
    JSON_OBJECT('value', 'Large Motorbike', 'label', 'Large Motorbike'),
    JSON_OBJECT('value', 'Average Motorbike', 'label', 'Average Motorbike'),
    -- Vans
    JSON_OBJECT('value', 'Small van', 'label', 'Small van'),
    JSON_OBJECT('value', 'Medium van', 'label', 'Medium van'),
    JSON_OBJECT('value', 'Large van', 'label', 'Large van'),
    -- Other vehicles
    JSON_OBJECT('value', 'Bus', 'label', 'Bus'),
    JSON_OBJECT('value', 'Coach', 'label', 'Coach'),
    JSON_OBJECT('value', 'HGV (articulated)', 'label', 'HGV (articulated)'),
    JSON_OBJECT('value', 'HGV (rigid)', 'label', 'HGV (rigid)')
  ),
  1,
  2,
  'Select the size of the vehicle used. Guidance on vehicle sizes can be found in the FAQs.'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'vehicle'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `field_options` = VALUES(`field_options`),
  `help_text` = VALUES(`help_text`);

-- 1.3 Vehicle Fuel Type
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `field_options`, `is_required`, `field_order`, `help_text`, `depends_on_field`)
SELECT 
  es.id,
  'vehicle_fuel_type',
  'select',
  'Vehicle Fuel Type',
  'Select an option',
  JSON_ARRAY(
    JSON_OBJECT('value', 'Diesel', 'label', 'Diesel'),
    JSON_OBJECT('value', 'Petrol', 'label', 'Petrol'),
    JSON_OBJECT('value', 'Hybrid', 'label', 'Hybrid'),
    JSON_OBJECT('value', 'CNG', 'label', 'CNG'),
    JSON_OBJECT('value', 'LPG', 'label', 'LPG'),
    JSON_OBJECT('value', 'Unknown', 'label', 'Unknown'),
    JSON_OBJECT('value', 'Plug-in Hybrid Electric Vehicle', 'label', 'Plug-in Hybrid Electric Vehicle'),
    JSON_OBJECT('value', 'Battery Electric Vehicle', 'label', 'Battery Electric Vehicle')
  ),
  1,
  3,
  'Select the type of fuel used by the vehicle.',
  'vehicle_size'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'vehicle'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `field_options` = VALUES(`field_options`),
  `help_text` = VALUES(`help_text`),
  `depends_on_field` = VALUES(`depends_on_field`);

-- 1.4 Unit of Measure (Distance: km or miles)
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `field_options`, `is_required`, `field_order`, `help_text`, `depends_on_field`)
SELECT 
  es.id,
  'unit_of_measure',
  'select',
  'Unit of Measure',
  'Select an option',
  JSON_ARRAY(
    JSON_OBJECT('value', 'km', 'label', 'km'),
    JSON_OBJECT('value', 'miles', 'label', 'miles')
  ),
  1,
  4,
  'Select the unit of measure for distance travelled.',
  'vehicle_fuel_type'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'vehicle'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `field_options` = VALUES(`field_options`),
  `help_text` = VALUES(`help_text`),
  `depends_on_field` = VALUES(`depends_on_field`);

-- 1.5 Distance
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`, `depends_on_field`, `validation_rules`)
SELECT 
  es.id,
  'distance',
  'number',
  'Distance',
  'Enter distance',
  1,
  5,
  'Distance travelled in the unit of measure specified above.',
  NULL, -- Remove dependency - always show distance field for vehicles
  JSON_OBJECT('min', 0, 'step', 'any')
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'vehicle'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `help_text` = VALUES(`help_text`),
  `depends_on_field` = VALUES(`depends_on_field`),
  `validation_rules` = VALUES(`validation_rules`);

-- 1.6 Link field (Additional Data)
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'link',
  'text',
  'Link',
  'e.g. Sharepoint or Google Dr...',
  0,
  6,
  'Link to supporting documents'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'vehicle'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `help_text` = VALUES(`help_text`);

-- 1.7 Comments field (Additional Data)
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'comments',
  'textarea',
  'Comments',
  'Add any additional notes...',
  0,
  7,
  'Additional comments or notes'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'vehicle'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `help_text` = VALUES(`help_text`);

-- ============================================================================
-- PART 2: EMISSION FACTORS FOR VEHICLES (Distance-based)
-- ============================================================================
-- Emission factors for mobile combustion based on DEFRA 2024
-- Factors are in kg CO2e per km or per mile
-- Based on vehicle size and fuel type

INSERT INTO `emission_factors` 
(`emission_source_id`, `factor_value`, `total_co2e_factor`, `unit`, `calculation_method`, `region`, 
 `valid_from`, `valid_to`, `is_active`, `description`, `fuel_type`, `vehicle_type`, `vehicle_size`, 
 `co2_factor`, `ch4_factor`, `n2o_factor`, `source_standard`, `source_reference`, `gwp_version`, 
 `is_default`, `priority`) 
SELECT 
  es.id,
  ef_data.total_co2e,
  ef_data.total_co2e,
  ef_data.unit,
  'DEFRA 2024 Conversion Factors (Tier 1)',
  'UAE',
  2024,
  NULL,
  1,
  CONCAT('CO2e emission factor for ', ef_data.vehicle_type, ' (', ef_data.fuel_type, ') in ', ef_data.unit, '.'),
  ef_data.fuel_type,
  ef_data.vehicle_type,
  ef_data.vehicle_size,
  ef_data.co2_factor,
  ef_data.ch4_factor,
  ef_data.n2o_factor,
  'DEFRA',
  'DEFRA 2024 Conversion Factors',
  'AR6',
  ef_data.is_default,
  ef_data.priority
FROM `emission_sources_master` es
CROSS JOIN (
  -- ===== CARS - PETROL =====
  SELECT 'Petrol' as fuel_type, 'Small car' as vehicle_type, 'Small' as vehicle_size, 'km' as unit,
         0.120000 as co2_factor, 0.000000 as ch4_factor, 0.000000 as n2o_factor,
         0.120000 as total_co2e, 1 as is_default, 100 as priority
  UNION ALL
  SELECT 'Petrol', 'Medium car', 'Medium', 'km',
         0.171000, 0.000000, 0.000000,
         0.171000, 1, 100
  UNION ALL
  SELECT 'Petrol', 'Large car', 'Large', 'km',
         0.260000, 0.000000, 0.000000,
         0.260000, 1, 100
  UNION ALL
  SELECT 'Petrol', 'Average car', 'Average', 'km',
         0.184000, 0.000000, 0.000000,
         0.184000, 1, 100
  UNION ALL
  -- Cars - Petrol (miles)
  SELECT 'Petrol', 'Small car', 'Small', 'miles',
         0.193000, 0.000000, 0.000000,
         0.193000, 0, 90
  UNION ALL
  SELECT 'Petrol', 'Medium car', 'Medium', 'miles',
         0.275000, 0.000000, 0.000000,
         0.275000, 0, 90
  UNION ALL
  SELECT 'Petrol', 'Large car', 'Large', 'miles',
         0.418000, 0.000000, 0.000000,
         0.418000, 0, 90
  UNION ALL
  SELECT 'Petrol', 'Average car', 'Average', 'miles',
         0.296000, 0.000000, 0.000000,
         0.296000, 0, 90
  UNION ALL
  -- ===== CARS - DIESEL =====
  SELECT 'Diesel', 'Small car', 'Small', 'km',
         0.120000, 0.000000, 0.000000,
         0.120000, 1, 100
  UNION ALL
  SELECT 'Diesel', 'Medium car', 'Medium', 'km',
         0.159000, 0.000000, 0.000000,
         0.159000, 1, 100
  UNION ALL
  SELECT 'Diesel', 'Large car', 'Large', 'km',
         0.209000, 0.000000, 0.000000,
         0.209000, 1, 100
  UNION ALL
  SELECT 'Diesel', 'Average car', 'Average', 'km',
         0.163000, 0.000000, 0.000000,
         0.163000, 1, 100
  UNION ALL
  -- Cars - Diesel (miles)
  SELECT 'Diesel', 'Small car', 'Small', 'miles',
         0.193000, 0.000000, 0.000000,
         0.193000, 0, 90
  UNION ALL
  SELECT 'Diesel', 'Medium car', 'Medium', 'miles',
         0.256000, 0.000000, 0.000000,
         0.256000, 0, 90
  UNION ALL
  SELECT 'Diesel', 'Large car', 'Large', 'miles',
         0.336000, 0.000000, 0.000000,
         0.336000, 0, 90
  UNION ALL
  SELECT 'Diesel', 'Average car', 'Average', 'miles',
         0.262000, 0.000000, 0.000000,
         0.262000, 0, 90
  UNION ALL
  -- ===== MOTORBIKES - PETROL =====
  SELECT 'Petrol', 'Small Motorbike', 'Small', 'km',
         0.113000, 0.000000, 0.000000,
         0.113000, 1, 100
  UNION ALL
  SELECT 'Petrol', 'Medium Motorbike', 'Medium', 'km',
         0.113000, 0.000000, 0.000000,
         0.113000, 1, 100
  UNION ALL
  SELECT 'Petrol', 'Large Motorbike', 'Large', 'km',
         0.113000, 0.000000, 0.000000,
         0.113000, 1, 100
  UNION ALL
  SELECT 'Petrol', 'Average Motorbike', 'Average', 'km',
         0.113000, 0.000000, 0.000000,
         0.113000, 1, 100
  UNION ALL
  -- Motorbikes - Petrol (miles)
  SELECT 'Petrol', 'Small Motorbike', 'Small', 'miles',
         0.182000, 0.000000, 0.000000,
         0.182000, 0, 90
  UNION ALL
  SELECT 'Petrol', 'Medium Motorbike', 'Medium', 'miles',
         0.182000, 0.000000, 0.000000,
         0.182000, 0, 90
  UNION ALL
  SELECT 'Petrol', 'Large Motorbike', 'Large', 'miles',
         0.182000, 0.000000, 0.000000,
         0.182000, 0, 90
  UNION ALL
  SELECT 'Petrol', 'Average Motorbike', 'Average', 'miles',
         0.182000, 0.000000, 0.000000,
         0.182000, 0, 90
  UNION ALL
  -- ===== VANS - DIESEL =====
  -- Note: DEFRA typically provides van factors for diesel only
  SELECT 'Diesel', 'Small van', 'Small', 'km',
         0.183000, 0.000000, 0.000000,
         0.183000, 1, 100
  UNION ALL
  SELECT 'Diesel', 'Medium van', 'Medium', 'km',
         0.203000, 0.000000, 0.000000,
         0.203000, 1, 100
  UNION ALL
  SELECT 'Diesel', 'Large van', 'Large', 'km',
         0.223000, 0.000000, 0.000000,
         0.223000, 1, 100
  UNION ALL
  -- Vans - Diesel (miles)
  SELECT 'Diesel', 'Small van', 'Small', 'miles',
         0.294000, 0.000000, 0.000000,
         0.294000, 0, 90
  UNION ALL
  SELECT 'Diesel', 'Medium van', 'Medium', 'miles',
         0.327000, 0.000000, 0.000000,
         0.327000, 0, 90
  UNION ALL
  SELECT 'Diesel', 'Large van', 'Large', 'miles',
         0.359000, 0.000000, 0.000000,
         0.359000, 0, 90
  UNION ALL
  -- ===== BUSES AND COACHES =====
  SELECT 'Diesel', 'Bus', 'Large', 'km',
         0.892000, 0.000000, 0.000000,
         0.892000, 1, 100
  UNION ALL
  SELECT 'Diesel', 'Coach', 'Large', 'km',
         0.028000, 0.000000, 0.000000,
         0.028000, 1, 100
  UNION ALL
  -- Buses and Coaches (miles)
  SELECT 'Diesel', 'Bus', 'Large', 'miles',
         1.435000, 0.000000, 0.000000,
         1.435000, 0, 90
  UNION ALL
  SELECT 'Diesel', 'Coach', 'Large', 'miles',
         0.045000, 0.000000, 0.000000,
         0.045000, 0, 90
  UNION ALL
  -- ===== HGVs =====
  SELECT 'Diesel', 'HGV (articulated)', 'Large', 'km',
         1.000000, 0.000000, 0.000000,
         1.000000, 1, 100
  UNION ALL
  SELECT 'Diesel', 'HGV (rigid)', 'Large', 'km',
         0.800000, 0.000000, 0.000000,
         0.800000, 1, 100
  UNION ALL
  -- HGVs (miles)
  SELECT 'Diesel', 'HGV (articulated)', 'Large', 'miles',
         1.609000, 0.000000, 0.000000,
         1.609000, 0, 90
  UNION ALL
  SELECT 'Diesel', 'HGV (rigid)', 'Large', 'miles',
         1.287000, 0.000000, 0.000000,
         1.287000, 0, 90
) ef_data
WHERE es.quick_input_slug = 'vehicle'
ON DUPLICATE KEY UPDATE 
  `factor_value` = VALUES(`factor_value`),
  `total_co2e_factor` = VALUES(`total_co2e_factor`),
  `co2_factor` = VALUES(`co2_factor`),
  `ch4_factor` = VALUES(`ch4_factor`),
  `n2o_factor` = VALUES(`n2o_factor`),
  `description` = VALUES(`description`),
  `vehicle_type` = VALUES(`vehicle_type`),
  `vehicle_size` = VALUES(`vehicle_size`);

-- ============================================================================
-- PART 3: UNIT CONVERSIONS FOR VEHICLES
-- ============================================================================
-- Add unit conversions for distance (km <-> miles)

INSERT INTO `emission_unit_conversions` (`from_unit`, `to_unit`, `conversion_factor`, `fuel_type`, `region`, `notes`, `is_active`) VALUES
-- Distance conversions
('km', 'miles', 0.621371, NULL, NULL, 'Standard conversion: 1 km = 0.621371 miles', 1),
('miles', 'km', 1.609344, NULL, NULL, 'Standard conversion: 1 mile = 1.609344 km', 1)
ON DUPLICATE KEY UPDATE 
  `conversion_factor` = VALUES(`conversion_factor`),
  `notes` = VALUES(`notes`);
-- ============================================================================
-- Refrigerants Form Fields and Emission Factors
-- Add this to QUICK_INPUT_FINAL_MYSQL_QUERIES.sql or run separately
-- ============================================================================

-- PART 11: INSERT FORM FIELDS FOR REFRIGERANTS (Scope 1)
-- ============================================================================

-- 11.1 Refrigerant Type (Select dropdown) - First field, required
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `field_options`, `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'refrigerant_type',
  'select',
  'Refrigerant Type',
  'Select refrigerant type',
  JSON_ARRAY(
    JSON_OBJECT('value', 'HFC134A', 'label', 'HFC-134a (GWP: 1530)'),
    JSON_OBJECT('value', 'HFC152A', 'label', 'HFC-152a (GWP: 148)'),
    JSON_OBJECT('value', 'HFC23', 'label', 'HFC-23 (GWP: 14600)'),
    JSON_OBJECT('value', 'HFC32', 'label', 'HFC-32 (GWP: 771)'),
    JSON_OBJECT('value', 'HFC125', 'label', 'HFC-125 (GWP: 3170)'),
    JSON_OBJECT('value', 'HFC143A', 'label', 'HFC-143a (GWP: 5160)'),
    JSON_OBJECT('value', 'HFC245FA', 'label', 'HFC-245fa (GWP: 1030)'),
    JSON_OBJECT('value', 'HFC365MFC', 'label', 'HFC-365mfc (GWP: 794)'),
    JSON_OBJECT('value', 'HFC227EA', 'label', 'HFC-227ea (GWP: 3220)'),
    JSON_OBJECT('value', 'HFC236FA', 'label', 'HFC-236fa (GWP: 8180)'),
    JSON_OBJECT('value', 'HFC4310MEE', 'label', 'HFC-4310mee (GWP: 1640)'),
    JSON_OBJECT('value', 'R-410A', 'label', 'R-410A (Blend: HFC-32/125)'),
    JSON_OBJECT('value', 'R-404A', 'label', 'R-404A (Blend: HFC-125/143a/134a)'),
    JSON_OBJECT('value', 'R-407C', 'label', 'R-407C (Blend: HFC-32/125/134a)'),
    JSON_OBJECT('value', 'R-507A', 'label', 'R-507A (Blend: HFC-125/143a)')
  ),
  1,
  1,
  'Select the type of refrigerant gas used'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'refrigerants'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `field_options` = VALUES(`field_options`);

-- 11.2 Unit of Measure (kg) - Required
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `field_options`, `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'unit_of_measure',
  'select',
  'Unit of Measure',
  'Select an option',
  JSON_ARRAY(
    JSON_OBJECT('value', 'kg', 'label', 'kg (kilograms)'),
    JSON_OBJECT('value', 'tonnes', 'label', 'tonnes'),
    JSON_OBJECT('value', 'g', 'label', 'g (grams)')
  ),
  1,
  2,
  'Select the unit of measure for refrigerant quantity'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'refrigerants'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `field_options` = VALUES(`field_options`);

-- 11.3 Amount (Number input) - Required
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`, `validation_rules`)
SELECT 
  es.id,
  'amount',
  'number',
  'Amount',
  'Enter amount',
  1,
  3,
  'Amount of refrigerant gas used (top-ups/refills only, not total capacity)',
  JSON_OBJECT('min', 0, 'step', 'any')
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'refrigerants'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `validation_rules` = VALUES(`validation_rules`);

-- 11.4 Link field (Additional Data)
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'link',
  'text',
  'Link',
  'e.g. Sharepoint or Google Dr...',
  0,
  4,
  'Link to supporting documents'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'refrigerants'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `help_text` = VALUES(`help_text`);

-- 11.5 Comments field (Additional Data)
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'comments',
  'textarea',
  'Comments',
  'Add any additional notes...',
  0,
  5,
  'Additional comments or notes'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'refrigerants'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `help_text` = VALUES(`help_text`);

-- ============================================================================
-- PART 12: INSERT EMISSION FACTORS FOR REFRIGERANTS
-- ============================================================================
-- Note: For refrigerants, the emission factor is the GWP value
-- Calculation: CO2e = Quantity (kg) × GWP
-- We'll create factors for each refrigerant type using GWP values

-- First, ensure factor_value column can handle large GWP values (up to 25200 for SF6)
-- Check if column exists and modify if needed
ALTER TABLE `emission_factors` 
MODIFY COLUMN `factor_value` DECIMAL(15,6) NULL COMMENT 'Emission factor value (kg CO2e/unit) - can be GWP for refrigerants';

INSERT INTO `emission_factors` 
(`emission_source_id`, `factor_value`, `unit`, `calculation_method`, `region`, `valid_from`, `valid_to`, `is_active`, 
 `description`, `source_standard`, `source_reference`, `gwp_version`, `is_default`, `priority`, `fuel_type`) 
SELECT 
  es.id,
  gwp.gwp_100_year,
  'kg',
  CONCAT('IPCC AR6 GWP for ', gwp.gas_name),
  'Global',
  2024,
  NULL,
  1,
  CONCAT('GWP (Global Warming Potential) for ', gwp.gas_name, ' - used for CO2e calculation'),
  'IPCC',
  'IPCC AR6 Assessment Report',
  gwp.gwp_version,
  CASE WHEN gwp.gas_code = 'HFC134A' THEN 1 ELSE 0 END,
  CASE 
    WHEN gwp.gas_code = 'HFC134A' THEN 100
    WHEN gwp.gas_code IN ('HFC32', 'HFC152A') THEN 90
    ELSE 80
  END,
  gwp.gas_code
FROM `emission_sources_master` es
CROSS JOIN `emission_gwp_values` gwp
WHERE es.quick_input_slug = 'refrigerants'
  AND gwp.gas_code IN ('HFC134A', 'HFC152A', 'HFC23', 'HFC32', 'HFC125', 'HFC143A', 'HFC245FA', 'HFC365MFC', 'HFC227EA', 'HFC236FA', 'HFC4310MEE')
  AND gwp.gwp_version = 'AR6'
ON DUPLICATE KEY UPDATE 
  `factor_value` = VALUES(`factor_value`),
  `calculation_method` = VALUES(`calculation_method`),
  `fuel_type` = VALUES(`fuel_type`);

-- Add emission factors for common refrigerant blends
-- Note: Blends have weighted GWP values based on their composition
INSERT INTO `emission_factors` 
(`emission_source_id`, `factor_value`, `unit`, `calculation_method`, `region`, `valid_from`, `valid_to`, `is_active`, 
 `description`, `source_standard`, `source_reference`, `gwp_version`, `is_default`, `priority`, `fuel_type`) 
SELECT 
  es.id,
  blend_data.gwp_value,
  'kg',
  blend_data.calculation_method,
  'Global',
  2024,
  NULL,
  1,
  blend_data.description,
  'IPCC',
  'IPCC AR6 Assessment Report (Blend)',
  'AR6',
  blend_data.is_default,
  blend_data.priority,
  blend_data.fuel_type
FROM `emission_sources_master` es
CROSS JOIN (
  -- R-410A: 50% HFC-32 (GWP 771) + 50% HFC-125 (GWP 3170) = Weighted GWP: 1970.5
  SELECT 1970.5 as gwp_value, 'R-410A' as fuel_type, 'IPCC AR6 GWP for R-410A (Blend: 50% HFC-32, 50% HFC-125)' as calculation_method, 'R-410A blend GWP (50% HFC-32 + 50% HFC-125)' as description, 0 as is_default, 85 as priority
  UNION ALL
  -- R-404A: 44% HFC-125 (GWP 3170) + 52% HFC-143a (GWP 5160) + 4% HFC-134a (GWP 1530) = Weighted GWP: 3922.2
  SELECT 3922.2 as gwp_value, 'R-404A' as fuel_type, 'IPCC AR6 GWP for R-404A (Blend: 44% HFC-125, 52% HFC-143a, 4% HFC-134a)' as calculation_method, 'R-404A blend GWP (44% HFC-125 + 52% HFC-143a + 4% HFC-134a)' as description, 0 as is_default, 85 as priority
  UNION ALL
  -- R-407C: 23% HFC-32 (GWP 771) + 25% HFC-125 (GWP 3170) + 52% HFC-134a (GWP 1530) = Weighted GWP: 1774.33
  SELECT 1774.33 as gwp_value, 'R-407C' as fuel_type, 'IPCC AR6 GWP for R-407C (Blend: 23% HFC-32, 25% HFC-125, 52% HFC-134a)' as calculation_method, 'R-407C blend GWP (23% HFC-32 + 25% HFC-125 + 52% HFC-134a)' as description, 0 as is_default, 85 as priority
  UNION ALL
  -- R-507A: 50% HFC-125 (GWP 3170) + 50% HFC-143a (GWP 5160) = Weighted GWP: 4165
  SELECT 4165 as gwp_value, 'R-507A' as fuel_type, 'IPCC AR6 GWP for R-507A (Blend: 50% HFC-125, 50% HFC-143a)' as calculation_method, 'R-507A blend GWP (50% HFC-125 + 50% HFC-143a)' as description, 0 as is_default, 85 as priority
) blend_data
WHERE es.quick_input_slug = 'refrigerants'
ON DUPLICATE KEY UPDATE 
  `factor_value` = VALUES(`factor_value`),
  `calculation_method` = VALUES(`calculation_method`),
  `fuel_type` = VALUES(`fuel_type`);

-- ============================================================================
-- PART 13: INSERT UNIT CONVERSIONS FOR REFRIGERANTS
-- ============================================================================
-- Add unit conversions for refrigerants (tonnes to kg, g to kg)
-- These are standard mass conversions, not refrigerant-specific

INSERT INTO `emission_unit_conversions` (`from_unit`, `to_unit`, `conversion_factor`, `fuel_type`, `region`, `notes`, `is_active`) VALUES
-- Standard mass conversions (applicable to all refrigerants)
('tonnes', 'kg', 1000.0, NULL, NULL, 'Standard conversion: 1 tonne = 1000 kg', 1),
('kg', 'tonnes', 0.001, NULL, NULL, 'Standard conversion: 1 kg = 0.001 tonnes', 1),
('g', 'kg', 0.001, NULL, NULL, 'Standard conversion: 1 g = 0.001 kg', 1),
('kg', 'g', 1000.0, NULL, NULL, 'Standard conversion: 1 kg = 1000 g', 1),
('tonnes', 'g', 1000000.0, NULL, NULL, 'Standard conversion: 1 tonne = 1,000,000 g', 1),
('g', 'tonnes', 0.000001, NULL, NULL, 'Standard conversion: 1 g = 0.000001 tonnes', 1)
ON DUPLICATE KEY UPDATE 
  `conversion_factor` = VALUES(`conversion_factor`),
  `notes` = VALUES(`notes`);

-- ============================================================================
-- Quick Input System - Complete Database Setup
-- Consolidated SQL File - All Quick Input Related Queries
-- ============================================================================
-- 
-- This SQL file contains ALL queries needed for the Quick Input system:
-- 1. Table modifications (ALTER TABLE)
-- 2. New table creation (CREATE TABLE)
-- 3. Master data insertion (INSERT)
-- 4. Indexes and constraints
--
-- IMPORTANT: 
-- 1. BACKUP YOUR DATABASE before running this script!
-- 2. Run this on development first!
-- 3. Review all INSERT statements and adjust values as needed
-- 4. Ensure master_industry_categories table exists before running this script!
--
-- ============================================================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================================================
-- PART 1: MODIFY EXISTING TABLES
-- ============================================================================

-- 1.1 Enhance emission_sources_master table
ALTER TABLE `emission_sources_master`
ADD COLUMN `quick_input_slug` VARCHAR(100) NULL UNIQUE COMMENT 'URL slug (natural-gas, fuel, vehicle, etc.)',
ADD COLUMN `quick_input_icon` VARCHAR(50) NULL COMMENT 'Icon identifier',
ADD COLUMN `quick_input_order` INT(11) DEFAULT 0 COMMENT 'Menu display order',
ADD COLUMN `is_quick_input` TINYINT(1) DEFAULT 0 COMMENT 'Show in Quick Input menu',
ADD COLUMN `instructions` TEXT NULL COMMENT 'Form instructions text',
ADD COLUMN `tutorial_link` VARCHAR(255) NULL COMMENT 'Tutorial/documentation link',
ADD COLUMN `ipcc_category_code` VARCHAR(20) NULL COMMENT 'IPCC category code (e.g., 2.A.2, 2.B.1, 2.C.1)',
ADD COLUMN `ipcc_sector` VARCHAR(100) NULL COMMENT 'IPCC sector (e.g., Industrial Processes)',
ADD COLUMN `ipcc_subcategory` VARCHAR(255) NULL COMMENT 'IPCC subcategory description',
ADD COLUMN `emission_type` ENUM('combustion', 'process', 'fugitive', 'electricity', 'other') NULL COMMENT 'Type of emission',
ADD COLUMN `default_unit` VARCHAR(50) NULL COMMENT 'Default unit for this source';

-- Create indexes for emission_sources_master
CREATE INDEX `idx_quick_input` ON `emission_sources_master` (`is_quick_input`, `quick_input_order`);
CREATE INDEX `idx_ipcc_category` ON `emission_sources_master` (`ipcc_category_code`);

-- 1.2 Enhance emission_factors table
ALTER TABLE `emission_factors`
ADD COLUMN `fuel_type` VARCHAR(100) NULL COMMENT 'Fuel type (Natural Gas, Diesel, Petrol, etc.)',
ADD COLUMN `fuel_category` VARCHAR(50) NULL COMMENT 'Fuel category (Gaseous, Liquid, Solid)',
ADD COLUMN `vehicle_type` VARCHAR(100) NULL COMMENT 'Vehicle type (Small car, Medium car, etc.)',
ADD COLUMN `vehicle_size` VARCHAR(50) NULL COMMENT 'Vehicle size category',
ADD COLUMN `co2_factor` DECIMAL(15,6) NULL COMMENT 'CO2 emission factor (kg CO2/unit)',
ADD COLUMN `ch4_factor` DECIMAL(15,6) NULL COMMENT 'CH4 emission factor (kg CH4/unit)',
ADD COLUMN `n2o_factor` DECIMAL(15,6) NULL COMMENT 'N2O emission factor (kg N2O/unit)',
ADD COLUMN `total_co2e_factor` DECIMAL(15,6) NULL COMMENT 'Total CO2e factor (kg CO2e/unit) - calculated',
ADD COLUMN `source_standard` ENUM('DEFRA', 'IPCC', 'UAE', 'MOCCAE', 'USEPA', 'Custom') DEFAULT 'DEFRA',
ADD COLUMN `source_reference` VARCHAR(255) NULL COMMENT 'Reference document/source',
ADD COLUMN `gwp_version` VARCHAR(20) DEFAULT 'AR6' COMMENT 'GWP version used (AR4, AR5, AR6)',
ADD COLUMN `is_default` TINYINT(1) DEFAULT 0 COMMENT 'Default factor for this source/region',
ADD COLUMN `priority` INT(11) DEFAULT 0 COMMENT 'Priority for selection (higher = preferred)';

-- Create indexes for emission_factors
CREATE INDEX `idx_fuel_type` ON `emission_factors` (`fuel_type`, `fuel_category`);
CREATE INDEX `idx_vehicle_type` ON `emission_factors` (`vehicle_type`, `vehicle_size`);
CREATE INDEX `idx_source_standard` ON `emission_factors` (`source_standard`, `region`);
CREATE INDEX `idx_default_priority` ON `emission_factors` (`is_default`, `priority` DESC);

-- 1.3 Modify measurement_data table
-- Note: If unique_measurement_source index doesn't exist, you can skip this
-- First, drop the unique constraint if it exists (uncomment if needed)
-- ALTER TABLE `measurement_data` DROP INDEX `unique_measurement_source`;

-- Add new index (non-unique) to allow multiple entries
CREATE INDEX `idx_measurement_source` ON `measurement_data` (`measurement_id`, `emission_source_id`);

-- Add new fields to measurement_data
ALTER TABLE `measurement_data`
ADD COLUMN `entry_date` DATE NULL COMMENT 'Date of this specific entry',
ADD COLUMN `entry_number` INT(11) NULL COMMENT 'Entry sequence number',
ADD COLUMN `fuel_type` VARCHAR(100) NULL COMMENT 'Fuel type used (if applicable)',
ADD COLUMN `vehicle_type` VARCHAR(100) NULL COMMENT 'Vehicle type (if applicable)',
ADD COLUMN `gas_type` VARCHAR(50) NULL COMMENT 'Refrigerant gas type (if applicable)',
ADD COLUMN `co2_emissions` DECIMAL(15,4) NULL COMMENT 'CO2 emissions (kg)',
ADD COLUMN `ch4_emissions` DECIMAL(15,4) NULL COMMENT 'CH4 emissions (kg)',
ADD COLUMN `n2o_emissions` DECIMAL(15,4) NULL COMMENT 'N2O emissions (kg)',
ADD COLUMN `gwp_version_used` VARCHAR(20) DEFAULT 'AR6' COMMENT 'GWP version used for calculation',
ADD COLUMN `emission_factor_id` BIGINT(20) UNSIGNED NULL COMMENT 'Reference to emission_factors table',
ADD COLUMN `calculation_method` VARCHAR(100) NULL COMMENT 'Method used (Tier 1, Tier 2, Tier 3)',
ADD COLUMN `supplier_emission_factor` DECIMAL(15,6) NULL COMMENT 'Supplier-specific factor (if used)',
ADD COLUMN `calculated_co2e` DECIMAL(15, 4) NULL DEFAULT NULL COMMENT 'Total CO2e calculated value',
ADD COLUMN `scope` ENUM('Scope 1', 'Scope 2', 'Scope 3') NULL DEFAULT NULL COMMENT 'Emission scope',
ADD COLUMN `quantity` DECIMAL(15, 4) NULL DEFAULT NULL COMMENT 'Quantity value for Quick Input entries',
ADD COLUMN `unit` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Unit of measurement (kWh, liters, kg, etc.)',
ADD COLUMN `notes` TEXT NULL DEFAULT NULL COMMENT 'Additional notes',
ADD COLUMN `additional_data` JSON NULL DEFAULT NULL COMMENT 'Additional form field data as JSON',
ADD COLUMN `is_offset` TINYINT(1) NULL DEFAULT 0 COMMENT 'Whether this emission is offset';

-- Create indexes for measurement_data
CREATE INDEX `idx_entry_date` ON `measurement_data` (`entry_date`);
CREATE INDEX `idx_fuel_type_md` ON `measurement_data` (`fuel_type`);
CREATE INDEX `idx_emission_factor` ON `measurement_data` (`emission_factor_id`);
CREATE INDEX `idx_measurement_data_scope` ON `measurement_data` (`measurement_id`, `scope`);
CREATE INDEX `idx_measurement_data_scope_only` ON `measurement_data` (`scope`);

-- ============================================================================
-- PART 2: CREATE NEW TABLES
-- ============================================================================

-- 2.1 Create emission_gwp_values table
CREATE TABLE IF NOT EXISTS `emission_gwp_values` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `gas_name` VARCHAR(100) NOT NULL COMMENT 'Gas name (CO2, CH4, N2O, HFC-134a, etc.)',
  `gas_code` VARCHAR(50) NULL COMMENT 'Gas code/identifier',
  `gwp_version` ENUM('AR4', 'AR5', 'AR6') DEFAULT 'AR6' COMMENT 'IPCC Assessment Report version',
  `gwp_100_year` DECIMAL(10,2) NOT NULL COMMENT '100-year GWP value',
  `gwp_20_year` DECIMAL(10,2) NULL COMMENT '20-year GWP value',
  `gwp_500_year` DECIMAL(10,2) NULL COMMENT '500-year GWP value',
  `notes` TEXT NULL COMMENT 'Additional notes about the gas',
  `is_kyoto_protocol` TINYINT(1) DEFAULT 0 COMMENT 'Is this a Kyoto Protocol gas?',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_gas_version` (`gas_name`, `gwp_version`),
  KEY `idx_gas_code` (`gas_code`),
  KEY `idx_gwp_version` (`gwp_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.2 Create emission_source_form_fields table
CREATE TABLE IF NOT EXISTS `emission_source_form_fields` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `emission_source_id` BIGINT(20) UNSIGNED NOT NULL,
  `field_name` VARCHAR(100) NOT NULL,
  `field_type` ENUM('text', 'number', 'select', 'textarea', 'date', 'checkbox', 'radio') NOT NULL,
  `field_label` VARCHAR(255) NOT NULL,
  `field_placeholder` VARCHAR(255) NULL,
  `field_options` JSON NULL COMMENT 'Options for select/radio (e.g., [{"value": "tonnes", "label": "Tonnes"}]',
  `is_required` TINYINT(1) DEFAULT 0,
  `field_order` INT(11) DEFAULT 0,
  `validation_rules` JSON NULL COMMENT 'Validation rules (min, max, pattern, etc.)',
  `help_text` TEXT NULL,
  `default_value` VARCHAR(255) NULL,
  `conditional_logic` JSON NULL COMMENT 'Show/hide rules: {"depends_on": "field_name", "show_if": "value"}',
  `depends_on_field` VARCHAR(100) NULL COMMENT 'Field name this depends on',
  `depends_on_value` VARCHAR(255) NULL COMMENT 'Value that triggers this field',
  `calculation_formula` TEXT NULL COMMENT 'Formula for calculated fields',
  `unit_conversion` JSON NULL COMMENT 'Unit conversion factors',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_emission_source_id` (`emission_source_id`),
  KEY `idx_field_order` (`emission_source_id`, `field_order`),
  KEY `idx_depends_on` (`depends_on_field`),
  CONSTRAINT `fk_form_fields_emission_source` FOREIGN KEY (`emission_source_id`) 
    REFERENCES `emission_sources_master` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.3 Create emission_unit_conversions table
CREATE TABLE IF NOT EXISTS `emission_unit_conversions` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `from_unit` VARCHAR(50) NOT NULL,
  `to_unit` VARCHAR(50) NOT NULL,
  `conversion_factor` DECIMAL(15,6) NOT NULL COMMENT 'Multiply from_unit by this to get to_unit',
  `fuel_type` VARCHAR(100) NULL COMMENT 'Fuel-specific conversion (if applicable)',
  `region` VARCHAR(50) NULL COMMENT 'Region-specific conversion (if applicable)',
  `is_active` TINYINT(1) DEFAULT 1,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_conversion` (`from_unit`, `to_unit`, `fuel_type`, `region`),
  KEY `idx_from_unit` (`from_unit`),
  KEY `idx_fuel_type` (`fuel_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.4 Create emission_factor_selection_rules table
CREATE TABLE IF NOT EXISTS `emission_factor_selection_rules` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `emission_source_id` BIGINT(20) UNSIGNED NOT NULL,
  `rule_name` VARCHAR(255) NOT NULL,
  `priority` INT(11) DEFAULT 0 COMMENT 'Higher priority = selected first',
  `conditions` JSON NOT NULL COMMENT 'Conditions: {"region": "UAE", "fuel_type": "Natural Gas", "unit": "kWh"}',
  `emission_factor_id` BIGINT(20) UNSIGNED NULL COMMENT 'Specific factor to use',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_emission_source` (`emission_source_id`),
  KEY `idx_priority` (`emission_source_id`, `priority` DESC),
  CONSTRAINT `fk_selection_rules_source` FOREIGN KEY (`emission_source_id`) 
    REFERENCES `emission_sources_master` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_selection_rules_factor` FOREIGN KEY (`emission_factor_id`) 
    REFERENCES `emission_factors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.5 Create emission_industry_labels table
CREATE TABLE IF NOT EXISTS `emission_industry_labels` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `emission_source_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Links to emission_sources_master',
  `industry_category_id` BIGINT(20) UNSIGNED NULL COMMENT 'Links to master_industry_categories.id (can be Level 1, 2, or 3)',
  `match_level` TINYINT(1) NULL COMMENT '1=Sector (Level 1), 2=Industry (Level 2), 3=Subcategory (Level 3) - which level this label applies to',
  `also_match_children` TINYINT(1) DEFAULT 0 COMMENT 'If 1, also applies to all child categories (cascading match)',
  `unit_type` VARCHAR(100) NULL COMMENT 'Unit type context (e.g., Main Factory, Office Building, Restaurant/Kitchen, Data Center, Warehouse)',
  `user_friendly_name` VARCHAR(255) NOT NULL COMMENT 'User-friendly name for this emission source in this industry context',
  `user_friendly_description` TEXT NULL COMMENT 'Industry-specific description',
  `common_equipment` TEXT NULL COMMENT 'Common equipment/use cases for this industry',
  `typical_units` VARCHAR(255) NULL COMMENT 'Typical units used in this industry context',
  `display_order` INT(11) DEFAULT 0 COMMENT 'Display order within industry',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_emission_source` (`emission_source_id`),
  KEY `idx_industry_category` (`industry_category_id`, `match_level`),
  KEY `idx_unit_type` (`unit_type`),
  KEY `idx_industry_source` (`industry_category_id`, `emission_source_id`),
  KEY `idx_match_level` (`match_level`, `also_match_children`),
  CONSTRAINT `fk_industry_labels_source` FOREIGN KEY (`emission_source_id`) 
    REFERENCES `emission_sources_master` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_industry_labels_category` FOREIGN KEY (`industry_category_id`) 
    REFERENCES `master_industry_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PART 3: INSERT MASTER DATA
-- ============================================================================

-- 3.1 Insert GWP Values (AR6 values)
INSERT INTO `emission_gwp_values` (`gas_name`, `gas_code`, `gwp_version`, `gwp_100_year`, `notes`, `is_kyoto_protocol`) VALUES
('CO₂', 'CO2', 'AR6', 1.00, 'Reference gas', 1),
('CH₄ (fossil)', 'CH4_FOSSIL', 'AR6', 27.20, 'Without climate–carbon feedbacks', 1),
('CH₄ (biogenic)', 'CH4_BIOGENIC', 'AR6', 29.80, 'With climate–carbon feedbacks', 1),
('N₂O', 'N2O', 'AR6', 273.00, 'Very high due to long lifetime', 1),
('HFC-134a', 'HFC134A', 'AR6', 1530.00, 'Common refrigerant', 1),
('HFC-152a', 'HFC152A', 'AR6', 148.00, 'Lower GWP HFC', 1),
('HFC-23', 'HFC23', 'AR6', 14600.00, 'Extremely high', 1),
('HFC-32', 'HFC32', 'AR6', 771.00, 'Used in AC/refrigeration', 1),
('HFC-125', 'HFC125', 'AR6', 3170.00, 'Blend component', 1),
('HFC-143a', 'HFC143A', 'AR6', 5160.00, 'Blend component', 1),
('HFC-245fa', 'HFC245FA', 'AR6', 1030.00, 'Foam blowing agent', 1),
('HFC-365mfc', 'HFC365MFC', 'AR6', 794.00, 'Foam blowing agent', 1),
('HFC-227ea', 'HFC227EA', 'AR6', 3220.00, 'Fire suppressant', 1),
('HFC-236fa', 'HFC236FA', 'AR6', 8180.00, 'Fire suppressant', 1),
('HFC-4310mee', 'HFC4310MEE', 'AR6', 1640.00, 'Specialty solvent', 1),
('SF₆', 'SF6', 'AR6', 25200.00, 'Highest single GWP gas', 1),
('NF₃', 'NF3', 'AR6', 19700.00, 'Semiconductor industry', 1),
('CF₄ (PFC-14)', 'PFC14', 'AR6', 7370.00, 'Very long-lived', 1),
('C₂F₆ (PFC-116)', 'PFC116', 'AR6', 12200.00, 'Very long-lived', 1),
('C₃F₈ (PFC-218)', 'PFC218', 'AR6', 7890.00, 'Very long-lived', 1),
('C₄F₁₀ (PFC-318)', 'PFC318', 'AR6', 6910.00, 'Very long-lived', 1),
('C₅F₁₂', 'PFC512', 'AR6', 8930.00, 'Very long-lived', 1),
('C₆F₁₄', 'PFC614', 'AR6', 7910.00, 'Very long-lived', 1),
('C₇F₁₆', 'PFC716', 'AR6', 7310.00, 'Very long-lived', 1),
('C₈F₁₈', 'PFC818', 'AR6', 7630.00, 'Very long-lived', 1)
ON DUPLICATE KEY UPDATE 
  `gwp_100_year` = VALUES(`gwp_100_year`),
  `notes` = VALUES(`notes`);

-- 3.2 Insert Quick Input Emission Sources
-- Natural Gas (Stationary Combustion)
INSERT INTO `emission_sources_master` 
(`name`, `description`, `scope`, `category`, `subcategory`, `is_active`, 
 `quick_input_slug`, `quick_input_icon`, `quick_input_order`, `is_quick_input`,
 `instructions`, `emission_type`, `default_unit`) 
VALUES 
('Natural Gas (Stationary combustion)', 
 'Natural gas used for heating, boilers, and cooking in stationary equipment',
 'Scope 1', 'Direct Emissions', 'Stationary Combustion', 1,
 'natural-gas', 'flame', 1, 1,
 'Enter the amount of energy used at each site of operation. Copies of utility bills can be linked in the Additional Data section.',
 'combustion', 'cubic metres')
ON DUPLICATE KEY UPDATE 
  `quick_input_slug` = VALUES(`quick_input_slug`),
  `is_quick_input` = VALUES(`is_quick_input`);

-- Fuel (Stationary Combustion)
INSERT INTO `emission_sources_master` 
(`name`, `description`, `scope`, `category`, `subcategory`, `is_active`,
 `quick_input_slug`, `quick_input_icon`, `quick_input_order`, `is_quick_input`,
 `instructions`, `emission_type`, `default_unit`) 
VALUES 
('Fuel (Stationary combustion)', 
 'Diesel, petrol, LPG, and other fuels used in stationary equipment and generators',
 'Scope 1', 'Direct Emissions', 'Stationary Combustion', 1,
 'fuel', 'fuel-pump', 2, 1,
 'Enter the amount of fuel used at each site of operation. Select fuel category and type.',
 'combustion', 'litres')
ON DUPLICATE KEY UPDATE 
  `quick_input_slug` = VALUES(`quick_input_slug`),
  `is_quick_input` = VALUES(`is_quick_input`);

-- Vehicle (Mobile Combustion)
INSERT INTO `emission_sources_master` 
(`name`, `description`, `scope`, `category`, `subcategory`, `is_active`,
 `quick_input_slug`, `quick_input_icon`, `quick_input_order`, `is_quick_input`,
 `instructions`, `emission_type`, `default_unit`) 
VALUES 
('Vehicle (Mobile combustion)', 
 'Company cars, vans, trucks, and other vehicles',
 'Scope 1', 'Direct Emissions', 'Mobile Combustion', 1,
 'vehicle', 'car', 3, 1,
 'We recommend entering details for each individual vehicle, unless multiple vehicles are the same size and fuel type.',
 'combustion', 'km')
ON DUPLICATE KEY UPDATE 
  `quick_input_slug` = VALUES(`quick_input_slug`),
  `is_quick_input` = VALUES(`is_quick_input`);

-- Refrigerants (Fugitive Emissions)
INSERT INTO `emission_sources_master` 
(`name`, `description`, `scope`, `category`, `subcategory`, `is_active`,
 `quick_input_slug`, `quick_input_icon`, `quick_input_order`, `is_quick_input`,
 `instructions`, `emission_type`, `default_unit`) 
VALUES 
('Refrigerants (Fugitive emissions)', 
 'AC & cooling gases used in air conditioning and refrigeration systems',
 'Scope 1', 'Direct Emissions', 'Fugitive Emissions', 1,
 'refrigerants', 'snowflake', 4, 1,
 'Refrigerant gases are most commonly used in air conditioning systems. Be sure to only enter data on gas top-ups/refills, rather than e.g., the capacity of the unit.',
 'fugitive', 'kg')
ON DUPLICATE KEY UPDATE 
  `quick_input_slug` = VALUES(`quick_input_slug`),
  `is_quick_input` = VALUES(`is_quick_input`);

-- Process Emissions
INSERT INTO `emission_sources_master` 
(`name`, `description`, `scope`, `category`, `subcategory`, `is_active`,
 `quick_input_slug`, `quick_input_icon`, `quick_input_order`, `is_quick_input`,
 `instructions`, `emission_type`, `default_unit`, `ipcc_category_code`) 
VALUES 
('Process (Process Emissions)', 
 'Manufacturing or chemical processes that produce emissions',
 'Scope 1', 'Direct Emissions', 'Process Emissions', 1,
 'process', 'gear', 5, 1,
 'Enter process-specific emission data based on your industry type.',
 'process', 'tonnes', '2.A.2')
ON DUPLICATE KEY UPDATE 
  `quick_input_slug` = VALUES(`quick_input_slug`),
  `is_quick_input` = VALUES(`is_quick_input`);

-- Electricity (Scope 2)
INSERT INTO `emission_sources_master` 
(`name`, `description`, `scope`, `category`, `subcategory`, `is_active`,
 `quick_input_slug`, `quick_input_icon`, `quick_input_order`, `is_quick_input`,
 `instructions`, `emission_type`, `default_unit`) 
VALUES 
('Electricity (Purchased Electricity)', 
 'Grid power, offices, factories - purchased electricity',
 'Scope 2', 'Indirect Energy', 'Purchased Electricity', 1,
 'electricity', 'lightning', 6, 1,
 'Enter the amount of electricity used at each site of operation, electric vehicle kWhs can also be entered here.',
 'electricity', 'kWh')
ON DUPLICATE KEY UPDATE 
  `quick_input_slug` = VALUES(`quick_input_slug`),
  `is_quick_input` = VALUES(`is_quick_input`);

-- Heat, Steam & Cooling (Scope 2)
INSERT INTO `emission_sources_master` 
(`name`, `description`, `scope`, `category`, `subcategory`, `is_active`,
 `quick_input_slug`, `quick_input_icon`, `quick_input_order`, `is_quick_input`,
 `instructions`, `emission_type`, `default_unit`) 
VALUES 
('Purchased Heat, Steam & Cooling', 
 'District cooling, steam, chilled water',
 'Scope 2', 'Indirect Energy', 'Purchased Heat/Steam/Cooling', 1,
 'heat-steam-cooling', 'thermometer', 7, 1,
 'Enter purchased heat, steam, or cooling energy consumption.',
 'electricity', 'kWh')
ON DUPLICATE KEY UPDATE 
  `quick_input_slug` = VALUES(`quick_input_slug`),
  `is_quick_input` = VALUES(`is_quick_input`);

-- Flights (Scope 3)
INSERT INTO `emission_sources_master` 
(`name`, `description`, `scope`, `category`, `subcategory`, `is_active`,
 `quick_input_slug`, `quick_input_icon`, `quick_input_order`, `is_quick_input`,
 `instructions`, `emission_type`, `default_unit`) 
VALUES 
('Flights (Business travel)', 
 'Business air travel emissions',
 'Scope 3', 'Indirect', 'Business Travel', 1,
 'flights', 'airplane', 8, 1,
 'Enter business flight travel data including distance and flight class.',
 'other', 'km')
ON DUPLICATE KEY UPDATE 
  `quick_input_slug` = VALUES(`quick_input_slug`),
  `is_quick_input` = VALUES(`is_quick_input`);

-- Public Transport (Scope 3)
INSERT INTO `emission_sources_master` 
(`name`, `description`, `scope`, `category`, `subcategory`, `is_active`,
 `quick_input_slug`, `quick_input_icon`, `quick_input_order`, `is_quick_input`,
 `instructions`, `emission_type`, `default_unit`) 
VALUES 
('Public Transport (Employee commuting)', 
 'Employee commuting via public transport',
 'Scope 3', 'Indirect', 'Employee Commuting', 1,
 'public-transport', 'bus', 9, 1,
 'Enter employee commuting data via public transport.',
 'other', 'km')
ON DUPLICATE KEY UPDATE 
  `quick_input_slug` = VALUES(`quick_input_slug`),
  `is_quick_input` = VALUES(`is_quick_input`);

-- Home Workers (Scope 3)
INSERT INTO `emission_sources_master` 
(`name`, `description`, `scope`, `category`, `subcategory`, `is_active`,
 `quick_input_slug`, `quick_input_icon`, `quick_input_order`, `is_quick_input`,
 `instructions`, `emission_type`, `default_unit`) 
VALUES 
('Home Workers (Remote work)', 
 'Remote / work-from-home emissions',
 'Scope 3', 'Indirect', 'Remote Work', 1,
 'home-workers', 'home', 10, 1,
 'Enter remote work energy consumption data.',
 'other', 'kWh')
ON DUPLICATE KEY UPDATE 
  `quick_input_slug` = VALUES(`quick_input_slug`),
  `is_quick_input` = VALUES(`is_quick_input`);

-- ============================================================================
-- PART 4: INSERT EMISSION FACTORS (with proper calculation_method)
-- ============================================================================
-- IMPORTANT: Before running this section, you need to:
-- 1. Backup your database
-- 2. Delete existing emission_factors for Quick Input sources:
--    DELETE FROM `emission_factors` WHERE `emission_source_id` IN (52, 57, 58);
--    OR truncate the entire emission_factors table if you want to start fresh

-- 4.1 Insert Emission Factors for Natural Gas (emission_source_id 52)
-- Note: Replace 52 with actual emission_source_id from your database
INSERT INTO `emission_factors` 
(`emission_source_id`, `factor_value`, `unit`, `calculation_method`, `region`, `valid_from`, `valid_to`, `is_active`, `description`, `calculation_formula`, `fuel_type`, `fuel_category`, `co2_factor`, `ch4_factor`, `n2o_factor`, `total_co2e_factor`, `source_standard`, `source_reference`, `gwp_version`, `is_default`, `priority`) 
VALUES
-- Natural Gas - cubic metres (Default, highest priority)
(52, 2.066946, 'cubic metres', 'DEFRA 2024 Conversion Factors (Tier 1)', 'UAE', 2024, NULL, 1, 'CO2e emission factor for natural gas in cubic metres. Multi-gas calculation using DEFRA 2024 factors.', NULL, 'Natural gas', 'Gaseous', 2.062700, 0.003070, 0.000950, 2.066946, 'DEFRA', 'DEFRA 2024 Conversion Factors', 'AR6', 1, 100),

-- Natural Gas - kWh (Net CV)
(52, 0.202723, 'kWh (Net CV)', 'DEFRA 2024 Conversion Factors (Tier 1)', 'UAE', 2024, NULL, 1, 'CO2e emission factor for natural gas in kWh (Net Calorific Value). Multi-gas calculation using DEFRA 2024 factors.', NULL, 'Natural gas', 'Gaseous', 0.202290, 0.000310, 0.000100, 0.202723, 'DEFRA', 'DEFRA 2024 Conversion Factors', 'AR6', 0, 90),

-- Natural Gas - kWh (Gross CV)
(52, 0.182981, 'kWh (Gross CV)', 'DEFRA 2024 Conversion Factors (Tier 1)', 'UAE', 2024, NULL, 1, 'CO2e emission factor for natural gas in kWh (Gross Calorific Value). Multi-gas calculation using DEFRA 2024 factors.', NULL, 'Natural gas', 'Gaseous', 0.182590, 0.000280, 0.000090, 0.182981, 'DEFRA', 'DEFRA 2024 Conversion Factors', 'AR6', 0, 80),

-- Natural Gas - tonnes
(52, 2575.748063, 'tonnes', 'DEFRA 2024 Conversion Factors (Tier 1)', 'UAE', 2024, NULL, 1, 'CO2e emission factor for natural gas in tonnes. Multi-gas calculation using DEFRA 2024 factors.', NULL, 'Natural gas', 'Gaseous', 2570.420000, 3.852800, 1.191610, 2575.748063, 'DEFRA', 'DEFRA 2024 Conversion Factors', 'AR6', 0, 70);

-- 4.2 Insert Emission Factors for Electricity
-- Uses subquery to get emission_source_id dynamically
INSERT INTO `emission_factors` 
(`emission_source_id`, `factor_value`, `unit`, `calculation_method`, `region`, `valid_from`, `valid_to`, `is_active`, `description`, `calculation_formula`, `source_standard`, `source_reference`, `gwp_version`, `is_default`, `priority`) 
SELECT 
  es.id,
  ef_data.factor_value,
  ef_data.unit,
  ef_data.calculation_method,
  ef_data.region,
  ef_data.valid_from,
  ef_data.valid_to,
  ef_data.is_active,
  ef_data.description,
  ef_data.calculation_formula,
  ef_data.source_standard,
  ef_data.source_reference,
  ef_data.gwp_version,
  ef_data.is_default,
  ef_data.priority
FROM `emission_sources_master` es
CROSS JOIN (
  SELECT 0.404100 as factor_value, 'kWh' as unit, 'UAE Grid Emission Factor (Tier 2)' as calculation_method, 'UAE' as region, 2024 as valid_from, NULL as valid_to, 1 as is_active, 'CO2e emission factor for purchased electricity from UAE grid (DEWA).' as description, NULL as calculation_formula, 'UAE' as source_standard, 'UAE ele. (DEWA)' as source_reference, 'AR6' as gwp_version, 1 as is_default, 100 as priority
  UNION ALL
  SELECT 0.495000, 'kWh', 'MOCCAE Grid Emission Factor (Tier 2)', 'UAE', 2024, NULL, 1, 'CO2e emission factor for purchased electricity from UAE grid (MOCCAE).', NULL, 'MOCCAE', 'MOCCAE', 'AR6', 0, 90
) ef_data
WHERE es.quick_input_slug = 'electricity'
ON DUPLICATE KEY UPDATE 
  `factor_value` = VALUES(`factor_value`),
  `calculation_method` = VALUES(`calculation_method`);

-- 4.3 Insert Emission Factors for Heat, Steam & Cooling
-- Uses subquery to get emission_source_id dynamically
-- Note: Heat, Steam, and Cooling have different emission factors
-- Using fuel_type field to distinguish: 'Heat', 'Steam', 'Cooling'
INSERT INTO `emission_factors` 
(`emission_source_id`, `factor_value`, `unit`, `calculation_method`, `region`, `valid_from`, `valid_to`, `is_active`, `description`, `calculation_formula`, `source_standard`, `source_reference`, `gwp_version`, `is_default`, `priority`, `fuel_type`) 
SELECT 
  es.id,
  ef_data.factor_value,
  ef_data.unit,
  ef_data.calculation_method,
  ef_data.region,
  ef_data.valid_from,
  ef_data.valid_to,
  ef_data.is_active,
  ef_data.description,
  ef_data.calculation_formula,
  ef_data.source_standard,
  ef_data.source_reference,
  ef_data.gwp_version,
  ef_data.is_default,
  ef_data.priority,
  ef_data.fuel_type
FROM `emission_sources_master` es
CROSS JOIN (
  -- Heat (District Heating) - Default
  SELECT 0.226300 as factor_value, 'kWh' as unit, 'USEPA District Heating (Tier 2)' as calculation_method, 'UAE' as region, 2024 as valid_from, NULL as valid_to, 1 as is_active, 'CO2e emission factor for purchased heat (district heating).' as description, NULL as calculation_formula, 'USEPA' as source_standard, 'USEPA District Heating' as source_reference, 'AR6' as gwp_version, 1 as is_default, 100 as priority, 'Heat' as fuel_type
  UNION ALL
  -- Steam - Default (using 0.275000 as per user requirement)
  SELECT 0.275000 as factor_value, 'kWh' as unit, 'USEPA Steam Generation (Tier 2)' as calculation_method, 'UAE' as region, 2024 as valid_from, NULL as valid_to, 1 as is_active, 'CO2e emission factor for purchased steam energy.' as description, NULL as calculation_formula, 'USEPA' as source_standard, 'USEPA Steam' as source_reference, 'AR6' as gwp_version, 1 as is_default, 100 as priority, 'Steam' as fuel_type
  UNION ALL
  -- Cooling (District Cooling) - Default
  SELECT 0.226300 as factor_value, 'kWh' as unit, 'USEPA District Cooling (Tier 2)' as calculation_method, 'UAE' as region, 2024 as valid_from, NULL as valid_to, 1 as is_active, 'CO2e emission factor for purchased cooling (district cooling).' as description, NULL as calculation_formula, 'USEPA' as source_standard, 'USEPA District Cooling' as source_reference, 'AR6' as gwp_version, 1 as is_default, 100 as priority, 'Cooling' as fuel_type
  UNION ALL
  -- Heat - Alternative
  SELECT 0.226543 as factor_value, 'kWh' as unit, 'USEPA District Heating (Tier 2)' as calculation_method, 'UAE' as region, 2024 as valid_from, NULL as valid_to, 1 as is_active, 'CO2e emission factor for purchased heat (alternative factor).' as description, NULL as calculation_formula, 'USEPA' as source_standard, 'USEPA District Heating' as source_reference, 'AR6' as gwp_version, 0 as is_default, 90 as priority, 'Heat' as fuel_type
  UNION ALL
  -- Steam - Alternative (keeping 0.250000 as alternative)
  SELECT 0.250000 as factor_value, 'kWh' as unit, 'USEPA Steam Generation (Tier 2)' as calculation_method, 'UAE' as region, 2024 as valid_from, NULL as valid_to, 1 as is_active, 'CO2e emission factor for purchased steam (alternative factor).' as description, NULL as calculation_formula, 'USEPA' as source_standard, 'USEPA Steam' as source_reference, 'AR6' as gwp_version, 0 as is_default, 90 as priority, 'Steam' as fuel_type
  UNION ALL
  -- Cooling - Alternative
  SELECT 0.226543 as factor_value, 'kWh' as unit, 'USEPA District Cooling (Tier 2)' as calculation_method, 'UAE' as region, 2024 as valid_from, NULL as valid_to, 1 as is_active, 'CO2e emission factor for purchased cooling (alternative factor).' as description, NULL as calculation_formula, 'USEPA' as source_standard, 'USEPA District Cooling' as source_reference, 'AR6' as gwp_version, 0 as is_default, 90 as priority, 'Cooling' as fuel_type
) ef_data
WHERE es.quick_input_slug = 'heat-steam-cooling'
ON DUPLICATE KEY UPDATE 
  `factor_value` = VALUES(`factor_value`),
  `calculation_method` = VALUES(`calculation_method`),
  `fuel_type` = VALUES(`fuel_type`);

-- 4.4 Insert Emission Factors for Fuel (Stationary Combustion)
-- IMPORTANT: See FUEL_EMISSION_FACTORS_DATA.sql for complete Fuel emission factors data
-- This section is moved to a separate file to keep the main SQL file manageable
-- Run FUEL_EMISSION_FACTORS_DATA.sql after running this main file

-- ============================================================================
-- PART 5: INSERT FORM FIELDS
-- ============================================================================

-- 5.1 Insert Form Fields for Natural Gas
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `field_options`, `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'unit_of_measure',
  'select',
  'Unit of Measure',
  'Select an option',
  JSON_ARRAY(
    JSON_OBJECT('value', 'tonnes', 'label', 'tonnes'),
    JSON_OBJECT('value', 'kWh (Gross CV)', 'label', 'kWh (Gross CV)'),
    JSON_OBJECT('value', 'kWh (Net CV)', 'label', 'kWh (Net CV)'),
    JSON_OBJECT('value', 'cubic metres', 'label', 'cubic metres')
  ),
  1,
  1,
  'Select the unit of measure for natural gas consumption'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'natural-gas'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `field_options` = VALUES(`field_options`);

INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`, `validation_rules`)
SELECT 
  es.id,
  'amount',
  'number',
  'Amount',
  'Enter amount',
  1,
  2,
  'Amount of fuel used in the unit of measure specified above',
  JSON_OBJECT('min', 0, 'step', '0.01')
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'natural-gas'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `validation_rules` = VALUES(`validation_rules`);

INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'link',
  'text',
  'Link',
  'e.g. Sharepoint or Google Dr...',
  0,
  3,
  'Link to supporting documents'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'natural-gas'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `help_text` = VALUES(`help_text`);

INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'comments',
  'textarea',
  'Comments',
  'Add any additional notes...',
  0,
  4,
  'Additional comments or notes'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'natural-gas'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `help_text` = VALUES(`help_text`);

-- ============================================================================
-- PART 6: INSERT UNIT CONVERSIONS
-- ============================================================================

INSERT INTO `emission_unit_conversions` (`from_unit`, `to_unit`, `conversion_factor`, `fuel_type`, `region`, `notes`) VALUES
-- Natural Gas conversions
('cubic metres', 'kWh (Net CV)', 10.55, 'Natural gas', 'UAE', 'Natural gas cubic metres to kWh Net CV'),
('cubic metres', 'kWh (Gross CV)', 9.52, 'Natural gas', 'UAE', 'Natural gas cubic metres to kWh Gross CV'),
('cubic metres', 'tonnes', 0.000717, 'Natural gas', 'UAE', 'Natural gas cubic metres to tonnes'),
('kWh (Net CV)', 'cubic metres', 0.0948, 'Natural gas', 'UAE', 'Natural gas kWh Net CV to cubic metres'),
('kWh (Gross CV)', 'cubic metres', 0.105, 'Natural gas', 'UAE', 'Natural gas kWh Gross CV to cubic metres'),
-- Diesel conversions
('litres', 'tonnes', 0.000835, 'Diesel', NULL, 'Diesel litres to tonnes'),
('litres', 'kWh (Net CV)', 9.95, 'Diesel', NULL, 'Diesel litres to kWh Net CV'),
-- Petrol conversions
('litres', 'tonnes', 0.00074, 'Petrol', NULL, 'Petrol litres to tonnes'),
('litres', 'kWh (Net CV)', 9.7, 'Petrol', NULL, 'Petrol litres to kWh Net CV')
ON DUPLICATE KEY UPDATE 
  `conversion_factor` = VALUES(`conversion_factor`),
  `notes` = VALUES(`notes`);

-- ============================================================================
-- PART 7: INSERT INDUSTRY-SPECIFIC LABELS (Optional - for better UX)
-- ============================================================================
-- These provide industry-specific naming for better user experience
-- Links to master_industry_categories table for dynamic industry matching

-- Natural Gas - Manufacturing (Level 1 - ID 3)
INSERT INTO `emission_industry_labels` 
(`emission_source_id`, `industry_category_id`, `match_level`, `also_match_children`, `unit_type`, `user_friendly_name`, `user_friendly_description`, `common_equipment`, `typical_units`, `display_order`)
SELECT 
  es.id,
  3, -- Manufacturing (Level 1)
  1, -- Match Level 1
  1, -- Also match children
  'Main Factory',
  'Natural Gas',
  'Natural gas used in manufacturing processes, boilers, and furnaces',
  'Boilers for hot water/steam, Furnaces, CHP systems',
  'm³, kWh',
  1
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'natural-gas'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `user_friendly_description` = VALUES(`user_friendly_description`);

-- Natural Gas - Restaurants (Level 3 - ID 132)
INSERT INTO `emission_industry_labels` 
(`emission_source_id`, `industry_category_id`, `match_level`, `also_match_children`, `unit_type`, `user_friendly_name`, `user_friendly_description`, `common_equipment`, `typical_units`, `display_order`)
SELECT 
  es.id,
  132, -- Restaurants & Food Services (Level 3)
  3, -- Match Level 3
  0, -- Don't match children
  'Restaurant/Kitchen',
  'Natural Gas',
  'Natural gas used for cooking and heating in restaurant kitchens',
  'Kitchen stoves, Boilers for hot water',
  'm³, kWh',
  1
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'natural-gas'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `user_friendly_description` = VALUES(`user_friendly_description`);

-- Natural Gas - Food Processing (Level 2 - ID 37)
INSERT INTO `emission_industry_labels` 
(`emission_source_id`, `industry_category_id`, `match_level`, `also_match_children`, `unit_type`, `user_friendly_name`, `user_friendly_description`, `common_equipment`, `typical_units`, `display_order`)
SELECT 
  es.id,
  37, -- Food & Beverage (Level 2)
  2, -- Match Level 2
  1, -- Also match children
  'Processing Plant',
  'Natural Gas',
  'Natural gas used in food processing operations',
  'Boilers, Processing equipment',
  'm³, kWh',
  1
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'natural-gas'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `user_friendly_description` = VALUES(`user_friendly_description`);

-- ============================================================================
-- PART 8: INSERT FORM FIELDS FOR FUEL (Stationary Combustion)
-- ============================================================================
-- Note: Fuel form has cascading dropdowns: fuel_category -> fuel_type -> unit_of_measure
-- The fuel_type and unit_of_measure options are loaded dynamically via API based on selections

-- 8.0 Ensure all required columns exist in emission_source_form_fields table
-- IMPORTANT: If you get "Duplicate column name" error, that column already exists - skip that ALTER statement
-- Add columns one by one without AFTER clause to avoid dependency issues

-- Add default_value if it doesn't exist
ALTER TABLE `emission_source_form_fields`
ADD COLUMN `default_value` VARCHAR(255) NULL COMMENT 'Default value for this field';

-- Add conditional_logic if it doesn't exist
ALTER TABLE `emission_source_form_fields`
ADD COLUMN `conditional_logic` JSON NULL COMMENT 'Show/hide rules: {"depends_on": "field_name", "show_if": "value"}';

-- Add depends_on_field if it doesn't exist
ALTER TABLE `emission_source_form_fields`
ADD COLUMN `depends_on_field` VARCHAR(100) NULL COMMENT 'Field name this depends on';

-- Add depends_on_value if it doesn't exist
ALTER TABLE `emission_source_form_fields`
ADD COLUMN `depends_on_value` VARCHAR(255) NULL COMMENT 'Value that triggers this field';

-- Add calculation_formula if it doesn't exist
ALTER TABLE `emission_source_form_fields`
ADD COLUMN `calculation_formula` TEXT NULL COMMENT 'Formula for calculated fields';

-- Add unit_conversion if it doesn't exist
ALTER TABLE `emission_source_form_fields`
ADD COLUMN `unit_conversion` JSON NULL COMMENT 'Unit conversion factors';

-- 8.1 Fuel Category (First dropdown - static options)
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `field_options`, `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'fuel_category',
  'select',
  'Fuel Category',
  'Select an option',
  JSON_ARRAY(
    JSON_OBJECT('value', 'Gaseous fuels', 'label', 'Gaseous fuels'),
    JSON_OBJECT('value', 'Liquid fuels', 'label', 'Liquid fuels'),
    JSON_OBJECT('value', 'Solid fuels', 'label', 'Solid fuels'),
    JSON_OBJECT('value', 'Biofuel', 'label', 'Biofuel'),
    JSON_OBJECT('value', 'Biomass', 'label', 'Biomass'),
    JSON_OBJECT('value', 'Biogas', 'label', 'Biogas')
  ),
  1,
  1,
  'Select the category of fuel used'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'fuel'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `field_options` = VALUES(`field_options`);

-- 8.2 Fuel Type (Second dropdown - loaded dynamically based on fuel_category)
-- Note: Options will be loaded via API based on fuel_category selection
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`, `depends_on_field`)
SELECT 
  es.id,
  'fuel_type',
  'select',
  'Fuel Type',
  'Select an option',
  1,
  2,
  'Select the specific type of fuel (options depend on fuel category selected above)',
  'fuel_category'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'fuel'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `help_text` = VALUES(`help_text`),
  `depends_on_field` = VALUES(`depends_on_field`);

-- 8.3 Unit of Measure (Third dropdown - loaded dynamically based on fuel_type)
-- Note: Options will be loaded via API based on fuel_type selection
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`, `depends_on_field`)
SELECT 
  es.id,
  'unit_of_measure',
  'select',
  'Unit of Measure',
  'Select an option',
  1,
  3,
  'Select the unit of measure (options depend on fuel type selected above)',
  'fuel_type'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'fuel'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `help_text` = VALUES(`help_text`),
  `depends_on_field` = VALUES(`depends_on_field`);

-- 8.4 Amount (Number input)
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`, `validation_rules`)
SELECT 
  es.id,
  'amount',
  'number',
  'Amount',
  'Enter amount',
  1,
  4,
  'Amount of fuel used in the unit of measure specified above',
  JSON_OBJECT('min', 0, 'step', '0.01')
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'fuel'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `validation_rules` = VALUES(`validation_rules`);

-- 8.5 Link field (Additional Data)
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'link',
  'text',
  'Link',
  'e.g. Sharepoint or Google Dr...',
  0,
  5,
  'Link to supporting documents'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'fuel'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `help_text` = VALUES(`help_text`);

-- 8.6 Comments field (Additional Data)
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'comments',
  'textarea',
  'Comments',
  'Add any additional notes...',
  0,
  6,
  'Additional comments or notes'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'fuel'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `help_text` = VALUES(`help_text`);

-- ============================================================================
-- PART 9: INSERT FORM FIELDS FOR ELECTRICITY (Scope 2)
-- ============================================================================

-- 9.1 Unit of Measure (kWh)
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `field_options`, `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'unit_of_measure',
  'select',
  'Unit of Measure',
  'Select an option',
  JSON_ARRAY(
    JSON_OBJECT('value', 'kWh', 'label', 'kWh')
  ),
  1,
  1,
  'Select the unit of measure for electricity consumption'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'electricity'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `field_options` = VALUES(`field_options`);

-- 9.2 Amount (Number input)
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`, `validation_rules`)
SELECT 
  es.id,
  'amount',
  'number',
  'Amount',
  'Enter amount',
  1,
  2,
  'Amount of electricity used in kWh',
  JSON_OBJECT('min', 0, 'step', '0.01')
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'electricity'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `validation_rules` = VALUES(`validation_rules`);

-- 9.3 Link field (Additional Data)
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'link',
  'text',
  'Link',
  'e.g. Sharepoint or Google Dr...',
  0,
  3,
  'Link to supporting documents'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'electricity'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `help_text` = VALUES(`help_text`);

-- 9.4 Comments field (Additional Data)
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'comments',
  'textarea',
  'Comments',
  'Add any additional notes...',
  0,
  4,
  'Additional comments or notes'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'electricity'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `help_text` = VALUES(`help_text`);

-- ============================================================================
-- PART 10: INSERT FORM FIELDS FOR HEAT, STEAM & COOLING (Scope 2)
-- ============================================================================

-- 10.1 Energy Type (Heat, Steam, or Cooling) - First field, required
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `field_options`, `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'energy_type',
  'select',
  'Energy Type',
  'Select an option',
  JSON_ARRAY(
    JSON_OBJECT('value', 'Heat', 'label', 'Heat (District Heating)'),
    JSON_OBJECT('value', 'Steam', 'label', 'Steam'),
    JSON_OBJECT('value', 'Cooling', 'label', 'Cooling (District Cooling)')
  ),
  1,
  1,
  'Select the type of energy: Heat, Steam, or Cooling'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'heat-steam-cooling'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `field_options` = VALUES(`field_options`);

-- 10.2 Unit of Measure (kWh)
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `field_options`, `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'unit_of_measure',
  'select',
  'Unit of Measure',
  'Select an option',
  JSON_ARRAY(
    JSON_OBJECT('value', 'kWh', 'label', 'kWh')
  ),
  1,
  2,
  'Select the unit of measure for energy consumption'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'heat-steam-cooling'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `field_options` = VALUES(`field_options`);

-- 10.3 Amount (Number input)
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`, `validation_rules`)
SELECT 
  es.id,
  'amount',
  'number',
  'Amount',
  'Enter amount',
  1,
  3,
  'Amount of energy used in kWh',
  JSON_OBJECT('min', 0, 'step', '0.01')
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'heat-steam-cooling'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `validation_rules` = VALUES(`validation_rules`);

-- 10.4 Link field (Additional Data)
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'link',
  'text',
  'Link',
  'e.g. Sharepoint or Google Dr...',
  0,
  3,
  'Link to supporting documents'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'heat-steam-cooling'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `help_text` = VALUES(`help_text`);

-- 10.5 Comments field (Additional Data)
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'comments',
  'textarea',
  'Comments',
  'Add any additional notes...',
  0,
  4,
  'Additional comments or notes'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'heat-steam-cooling'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `help_text` = VALUES(`help_text`);

SET FOREIGN_KEY_CHECKS=1;

-- ============================================================================
-- END OF SQL SCRIPT
-- ============================================================================
--
-- SUMMARY:
-- 1. Modified existing tables: emission_sources_master, emission_factors, measurement_data
-- 2. Created new tables: emission_gwp_values, emission_source_form_fields, 
--    emission_unit_conversions, emission_factor_selection_rules, emission_industry_labels
-- 3. Inserted master data: GWP values, emission sources, emission factors (with calculation_method),
--    form fields, unit conversions, industry labels
-- 4. All emission factors now have proper calculation_method values (no more NULL)
--
-- IMPORTANT NOTES:
-- - If you get "Duplicate column name" errors, that column already exists - skip it
-- - If you get "Duplicate key name" errors, that index already exists - skip it
-- - Before running emission factor INSERTs, delete existing ones:
--   DELETE FROM `emission_factors` WHERE `emission_source_id` IN (52, 57, 58);
-- - Replace emission_source_id values (52, 57, 58) with actual IDs from your database
--   if they are different
--
-- ============================================================================
-- ============================================================================
-- Fuel Emission Factors Data
-- Complete data for all fuel categories, types, and units
-- ============================================================================
-- IMPORTANT: This file contains INSERT statements for Fuel emission factors
-- Run this AFTER running QUICK_INPUT_FINAL_MYSQL_QUERIES.sql
-- 
-- This will populate emission_factors table with data for:
-- - Gaseous fuels (Natural gas, LPG, CNG, LNG, Butane, etc.)
-- - Liquid fuels (Diesel, Petrol, Kerosene, Gas oil, Fuel oil)
-- - Solid fuels (Coal, Coke)
-- - Biofuel (Bioethanol, Biodiesel variants, Biomethane, Biopropane)
-- - Biomass (Wood logs, Wood pellets)
-- - Biogas
-- ============================================================================

-- Delete existing Fuel emission factors (optional - comment out if you want to keep existing data)
-- DELETE FROM `emission_factors` WHERE `emission_source_id` IN (SELECT id FROM emission_sources_master WHERE quick_input_slug = 'fuel');

-- Insert Fuel Emission Factors
-- Uses subquery to get emission_source_id dynamically
INSERT INTO `emission_factors` 
(`emission_source_id`, `factor_value`, `unit`, `calculation_method`, `region`, `valid_from`, `valid_to`, `is_active`, 
 `description`, `fuel_type`, `fuel_category`, `co2_factor`, `ch4_factor`, `n2o_factor`, `total_co2e_factor`, 
 `source_standard`, `source_reference`, `gwp_version`, `is_default`, `priority`) 
SELECT 
  es.id,
  ef_data.total_co2e,
  ef_data.unit,
  'DEFRA 2024 Conversion Factors (Tier 1)',
  'UAE',
  2024,
  NULL,
  1,
  CONCAT('CO2e emission factor for ', ef_data.fuel_type, ' (', ef_data.fuel_category, ') in ', ef_data.unit, '. Multi-gas calculation using DEFRA 2024 factors.'),
  ef_data.fuel_type,
  ef_data.fuel_category,
  ef_data.co2_factor,
  ef_data.ch4_factor,
  ef_data.n2o_factor,
  ef_data.total_co2e,
  'DEFRA',
  'DEFRA 2024 Conversion Factors',
  'AR6',
  ef_data.is_default,
  ef_data.priority
FROM `emission_sources_master` es
CROSS JOIN (
  -- GASEOUS FUELS
  SELECT 'Natural gas' as fuel_type, 'Gaseous fuels' as fuel_category, 'tonnes' as unit, 2570.420000 as co2_factor, 3.852800 as ch4_factor, 1.191610 as n2o_factor, 2575.748063 as total_co2e, 1 as is_default, 100 as priority
  UNION ALL SELECT 'Natural gas', 'Gaseous fuels', 'kWh (Net CV)', 0.202290, 0.000310, 0.000100, 0.202723, 0, 90
  UNION ALL SELECT 'Natural gas', 'Gaseous fuels', 'kWh (Gross CV)', 0.182590, 0.000280, 0.000090, 0.182981, 0, 80
  UNION ALL SELECT 'Natural gas', 'Gaseous fuels', 'cubic metres', 2.062700, 0.003070, 0.000950, 2.066946, 0, 70
  UNION ALL SELECT 'Natural gas', 'Gaseous fuels', 'GJ', 56.200000, 0.085000, 0.026000, 56.300000, 0, 60
  
  UNION ALL SELECT 'LPG', 'Gaseous fuels', 'litres', 1.540000, 0.002300, 0.000700, 1.543000, 1, 100
  UNION ALL SELECT 'LPG', 'Gaseous fuels', 'tonnes', 3010.000000, 4.500000, 1.400000, 3015.000000, 0, 90
  UNION ALL SELECT 'LPG', 'Gaseous fuels', 'kWh (Net CV)', 0.149700, 0.000220, 0.000068, 0.150000, 0, 80
  UNION ALL SELECT 'LPG', 'Gaseous fuels', 'kg', 3.010000, 0.004500, 0.001400, 3.015000, 0, 70
  
  UNION ALL SELECT 'CNG', 'Gaseous fuels', 'kg', 2.745000, 0.004100, 0.001270, 2.750000, 1, 100
  UNION ALL SELECT 'CNG', 'Gaseous fuels', 'cubic metres', 1.976000, 0.002950, 0.000915, 1.980000, 0, 90
  UNION ALL SELECT 'CNG', 'Gaseous fuels', 'kWh (Net CV)', 0.187600, 0.000280, 0.000087, 0.188000, 0, 80
  
  UNION ALL SELECT 'LNG', 'Gaseous fuels', 'kg', 2.745000, 0.004100, 0.001270, 2.750000, 1, 100
  UNION ALL SELECT 'LNG', 'Gaseous fuels', 'litres', 1.348000, 0.002010, 0.000623, 1.350000, 0, 90
  UNION ALL SELECT 'LNG', 'Gaseous fuels', 'cubic metres', 1.976000, 0.002950, 0.000915, 1.980000, 0, 80
  
  UNION ALL SELECT 'Butane', 'Gaseous fuels', 'litres', 2.386000, 0.003560, 0.001103, 2.390000, 1, 100
  UNION ALL SELECT 'Butane', 'Gaseous fuels', 'kg', 3.025000, 0.004510, 0.001397, 3.030000, 0, 90
  
  UNION ALL SELECT 'Other petroleum gas', 'Gaseous fuels', 'litres', 1.540000, 0.002300, 0.000700, 1.543000, 1, 100
  UNION ALL SELECT 'Other petroleum gas', 'Gaseous fuels', 'kg', 3.010000, 0.004500, 0.001400, 3.015000, 0, 90
  
  -- LIQUID FUELS
  UNION ALL SELECT 'Diesel', 'Liquid fuels', 'litres', 2.673000, 0.003990, 0.001236, 2.679000, 1, 100
  UNION ALL SELECT 'Diesel', 'Liquid fuels', 'tonnes', 3202.000000, 4.780000, 1.480000, 3208.000000, 0, 90
  UNION ALL SELECT 'Diesel', 'Liquid fuels', 'kWh (Net CV)', 0.268500, 0.000400, 0.000124, 0.269000, 0, 80
  UNION ALL SELECT 'Diesel', 'Liquid fuels', 'GJ', 74.600000, 0.111000, 0.034400, 74.800000, 0, 70
  
  UNION ALL SELECT 'Petrol', 'Liquid fuels', 'litres', 2.325000, 0.003470, 0.001075, 2.331000, 1, 100
  UNION ALL SELECT 'Petrol', 'Liquid fuels', 'tonnes', 3142.000000, 4.690000, 1.453000, 3149.000000, 0, 90
  UNION ALL SELECT 'Petrol', 'Liquid fuels', 'kWh (Net CV)', 0.239500, 0.000357, 0.000111, 0.240000, 0, 80
  UNION ALL SELECT 'Petrol', 'Liquid fuels', 'GJ', 66.500000, 0.099200, 0.030700, 66.700000, 0, 70
  
  UNION ALL SELECT 'Kerosene', 'Liquid fuels', 'litres', 2.513000, 0.003750, 0.001162, 2.519000, 1, 100
  UNION ALL SELECT 'Kerosene', 'Liquid fuels', 'tonnes', 3160.000000, 4.720000, 1.463000, 3167.000000, 0, 90
  UNION ALL SELECT 'Kerosene', 'Liquid fuels', 'kWh (Net CV)', 0.252500, 0.000377, 0.000117, 0.253000, 0, 80
  
  UNION ALL SELECT 'Gas oil', 'Liquid fuels', 'litres', 2.682000, 0.004000, 0.001240, 2.688000, 1, 100
  UNION ALL SELECT 'Gas oil', 'Liquid fuels', 'tonnes', 3204.000000, 4.780000, 1.482000, 3210.000000, 0, 90
  
  UNION ALL SELECT 'Fuel oil', 'Liquid fuels', 'litres', 3.093000, 0.004620, 0.001432, 3.100000, 1, 100
  UNION ALL SELECT 'Fuel oil', 'Liquid fuels', 'tonnes', 3163.000000, 4.720000, 1.463000, 3170.000000, 0, 90
  
  -- SOLID FUELS
  UNION ALL SELECT 'Coal (anthracite)', 'Solid fuels', 'tonnes', 2878.000000, 4.290000, 1.330000, 2884.000000, 1, 100
  UNION ALL SELECT 'Coal (anthracite)', 'Solid fuels', 'kg', 2.878000, 0.004290, 0.001330, 2.884000, 0, 90
  UNION ALL SELECT 'Coal (anthracite)', 'Solid fuels', 'kWh (Net CV)', 0.289500, 0.000432, 0.000134, 0.290000, 0, 80
  
  UNION ALL SELECT 'Coal (bituminous)', 'Solid fuels', 'tonnes', 2461.000000, 3.670000, 1.137000, 2466.000000, 1, 100
  UNION ALL SELECT 'Coal (bituminous)', 'Solid fuels', 'kg', 2.461000, 0.003670, 0.001137, 2.466000, 0, 90
  
  UNION ALL SELECT 'Coke', 'Solid fuels', 'tonnes', 3093.000000, 4.620000, 1.432000, 3100.000000, 1, 100
  UNION ALL SELECT 'Coke', 'Solid fuels', 'kg', 3.093000, 0.004620, 0.001432, 3.100000, 0, 90
  
  -- BIOFUEL (Note: Biofuels have zero or very low CO2e as they're considered carbon neutral)
  UNION ALL SELECT 'Bioethanol', 'Biofuel', 'litres', 0.000000, 0.000000, 0.000000, 0.000000, 1, 100
  UNION ALL SELECT 'Bioethanol', 'Biofuel', 'tonnes', 0.000000, 0.000000, 0.000000, 0.000000, 0, 90
  
  UNION ALL SELECT 'Biodiesel ME', 'Biofuel', 'litres', 0.000000, 0.000000, 0.000000, 0.000000, 1, 100
  UNION ALL SELECT 'Biodiesel ME', 'Biofuel', 'tonnes', 0.000000, 0.000000, 0.000000, 0.000000, 0, 90
  UNION ALL SELECT 'Biodiesel ME', 'Biofuel', 'kWh (Net CV)', 0.000000, 0.000000, 0.000000, 0.000000, 0, 80
  
  UNION ALL SELECT 'Biodiesel ME (from used cooking oil)', 'Biofuel', 'litres', 0.000000, 0.000000, 0.000000, 0.000000, 1, 100
  UNION ALL SELECT 'Biodiesel ME (from tallow)', 'Biofuel', 'litres', 0.000000, 0.000000, 0.000000, 0.000000, 1, 100
  UNION ALL SELECT 'Biodiesel HVO', 'Biofuel', 'litres', 0.000000, 0.000000, 0.000000, 0.000000, 1, 100
  UNION ALL SELECT 'Biodiesel HVO', 'Biofuel', 'tonnes', 0.000000, 0.000000, 0.000000, 0.000000, 0, 90
  
  UNION ALL SELECT 'Biomethane (compressed)', 'Biofuel', 'kg', 0.000000, 0.000000, 0.000000, 0.000000, 1, 100
  UNION ALL SELECT 'Biomethane (compressed)', 'Biofuel', 'cubic metres', 0.000000, 0.000000, 0.000000, 0.000000, 0, 90
  
  UNION ALL SELECT 'Biopropane', 'Biofuel', 'litres', 0.000000, 0.000000, 0.000000, 0.000000, 1, 100
  UNION ALL SELECT 'Biopropane', 'Biofuel', 'kg', 0.000000, 0.000000, 0.000000, 0.000000, 0, 90
  
  UNION ALL SELECT 'Development diesel', 'Biofuel', 'litres', 0.000000, 0.000000, 0.000000, 0.000000, 1, 100
  
  -- BIOMASS (Note: Biomass is considered carbon neutral)
  UNION ALL SELECT 'Wood (logs)', 'Biomass', 'tonnes', 0.000000, 0.000000, 0.000000, 0.000000, 1, 100
  UNION ALL SELECT 'Wood (logs)', 'Biomass', 'kg', 0.000000, 0.000000, 0.000000, 0.000000, 0, 90
  
  UNION ALL SELECT 'Wood (pellets)', 'Biomass', 'tonnes', 0.000000, 0.000000, 0.000000, 0.000000, 1, 100
  UNION ALL SELECT 'Wood (pellets)', 'Biomass', 'kg', 0.000000, 0.000000, 0.000000, 0.000000, 0, 90
  
  -- BIOGAS (Note: Biogas is considered carbon neutral)
  UNION ALL SELECT 'Biogas', 'Biogas', 'cubic metres', 0.000000, 0.000000, 0.000000, 0.000000, 1, 100
  UNION ALL SELECT 'Biogas', 'Biogas', 'kWh (Net CV)', 0.000000, 0.000000, 0.000000, 0.000000, 0, 90
) ef_data
WHERE es.quick_input_slug = 'fuel'
ON DUPLICATE KEY UPDATE 
  `factor_value` = VALUES(`factor_value`),
  `total_co2e_factor` = VALUES(`total_co2e_factor`),
  `co2_factor` = VALUES(`co2_factor`),
  `ch4_factor` = VALUES(`ch4_factor`),
  `n2o_factor` = VALUES(`n2o_factor`),
  `is_default` = VALUES(`is_default`),
  `priority` = VALUES(`priority`);

-- ============================================================================
-- END OF FUEL EMISSION FACTORS DATA
-- ============================================================================

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

