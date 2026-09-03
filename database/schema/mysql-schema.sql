-- MENetZero baseline schema.
-- Generated from the live database (silverwebbuzz_in_menetzero(12).sql), 2026-09-03.
--
-- This file IS the schema. Laravel loads it on `migrate` against an empty
-- database, then runs only the migrations dated after the baseline.
-- Do not edit by hand: regenerate with `php artisan schema:dump`.

SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE `admins` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `login_count` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `admin_package_assignments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Admin who approved the assignment',
  `company_id` bigint(20) UNSIGNED NOT NULL COMMENT 'Target org: client company or consultant agency org',
  `consultant_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Set when assigned from the consultant record',
  `subscription_plan_id` bigint(20) UNSIGNED NOT NULL,
  `target_type` varchar(20) NOT NULL COMMENT 'client | consultant',
  `contract_year` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'Consultant packs (calendar contract year)',
  `duration_months` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'Client complimentary grants',
  `note` text DEFAULT NULL COMMENT 'Reason / approval note',
  `status` varchar(20) NOT NULL DEFAULT 'approved',
  `client_subscription_id` bigint(20) UNSIGNED DEFAULT NULL,
  `consultant_subscription_id` bigint(20) UNSIGNED DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `admin_password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `base_year_restatements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `base_year` smallint(5) UNSIGNED NOT NULL,
  `previous_baseline_tco2e` decimal(14,4) DEFAULT NULL,
  `restated_baseline_tco2e` decimal(14,4) NOT NULL,
  `reason` text NOT NULL,
  `restated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `carbon_calculations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `scope` enum('scope_1','scope_2','scope_3','total') NOT NULL,
  `year` int(11) NOT NULL,
  `quarter` int(11) DEFAULT NULL,
  `month` int(11) DEFAULT NULL,
  `total_emissions` decimal(15,4) NOT NULL,
  `emissions_per_employee` decimal(15,4) DEFAULT NULL,
  `emissions_per_revenue` decimal(15,4) DEFAULT NULL,
  `breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`breakdown`)),
  `trends` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`trends`)),
  `reduction_target` decimal(15,4) DEFAULT NULL,
  `reduction_achieved` decimal(15,4) DEFAULT NULL,
  `reduction_percentage` decimal(5,2) DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `calculated_by` bigint(20) UNSIGNED NOT NULL,
  `calculated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `carbon_emissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `scope` enum('scope_1','scope_2','scope_3') NOT NULL,
  `category` varchar(255) NOT NULL,
  `subcategory` varchar(255) DEFAULT NULL,
  `activity_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` decimal(15,4) NOT NULL,
  `unit` varchar(255) NOT NULL,
  `emission_factor` decimal(10,6) DEFAULT NULL,
  `total_emissions` decimal(15,4) NOT NULL,
  `activity_date` date NOT NULL,
  `data_source` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_by` bigint(20) UNSIGNED DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `client_billing_methods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `payment_method_type` enum('card','bank_account','paypal','other') DEFAULT 'card',
  `card_brand` varchar(50) DEFAULT NULL COMMENT 'e.g., Visa, Mastercard, Amex',
  `card_last4` varchar(4) DEFAULT NULL COMMENT 'Last 4 digits of card',
  `card_exp_month` varchar(2) DEFAULT NULL COMMENT 'Expiration month (01-12)',
  `card_exp_year` varchar(4) DEFAULT NULL COMMENT 'Expiration year (YYYY)',
  `cardholder_name` varchar(255) DEFAULT NULL COMMENT 'Name on card',
  `is_default` tinyint(1) DEFAULT 0 COMMENT 'Default payment method for company',
  `is_active` tinyint(1) DEFAULT 1,
  `stripe_payment_method_id` varchar(255) DEFAULT NULL COMMENT 'Stripe PaymentMethod ID if using Stripe',
  `stripe_card_id` varchar(255) DEFAULT NULL COMMENT 'Stripe Card ID if using Stripe',
  `billing_address_line1` varchar(255) DEFAULT NULL,
  `billing_address_line2` varchar(255) DEFAULT NULL,
  `billing_city` varchar(100) DEFAULT NULL,
  `billing_state` varchar(100) DEFAULT NULL,
  `billing_postal_code` varchar(20) DEFAULT NULL,
  `billing_country` varchar(100) DEFAULT NULL,
  `added_by` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'User who added this payment method',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional data in JSON format',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `client_payment_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Reference to client_subscriptions',
  `billing_method_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Reference to client_billing_methods',
  `transaction_type` varchar(50) NOT NULL DEFAULT 'subscription',
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'AED',
  `status` enum('pending','completed','failed','refunded','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL COMMENT 'e.g., card, bank_transfer, paypal',
  `description` text DEFAULT NULL COMMENT 'Transaction description',
  `stripe_payment_intent_id` varchar(255) DEFAULT NULL COMMENT 'Stripe PaymentIntent ID',
  `stripe_charge_id` varchar(255) DEFAULT NULL COMMENT 'Stripe Charge ID',
  `invoice_url` varchar(500) DEFAULT NULL COMMENT 'URL to download invoice PDF',
  `invoice_number` varchar(100) DEFAULT NULL COMMENT 'Invoice number',
  `paid_at` datetime DEFAULT NULL COMMENT 'When payment was completed',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional data in JSON format',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `client_subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_plan_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('active','cancelled','expired','suspended','trialing') DEFAULT 'active',
  `billing_cycle` enum('annual','monthly') DEFAULT 'annual',
  `started_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `auto_renew` tinyint(1) DEFAULT 1,
  `payment_method` varchar(50) DEFAULT NULL,
  `stripe_subscription_id` varchar(255) DEFAULT NULL,
  `stripe_customer_id` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `active_company_key` bigint(20) UNSIGNED GENERATED ALWAYS AS (if(`status` = 'active',`company_id`,NULL)) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `climate_opportunities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `fiscal_year` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `potential_impact` text DEFAULT NULL,
  `actions` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `climate_risks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `fiscal_year` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `risk_type` varchar(20) NOT NULL,
  `time_horizon` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `financial_impact` text DEFAULT NULL,
  `likelihood` varchar(20) DEFAULT NULL,
  `mitigation` text DEFAULT NULL,
  `owner` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `companies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `emirate` enum('Dubai','Abu Dhabi','Sharjah','Ajman','Umm Al Quwain','Fujairah','Ras Al Khaimah') DEFAULT NULL,
  `sector` varchar(255) DEFAULT NULL,
  `license_no` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `industry` varchar(255) DEFAULT NULL,
  `business_subcategory` varchar(255) DEFAULT NULL,
  `employee_count` int(11) DEFAULT NULL,
  `annual_revenue` decimal(15,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `company_type` varchar(20) NOT NULL DEFAULT 'client',
  `is_direct_client` tinyint(1) DEFAULT 1,
  `consultant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `company_custom_roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `role_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `based_on_template` varchar(50) DEFAULT NULL COMMENT 'Reference to role_templates.template_code',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `company_custom_role_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_custom_role_id` bigint(20) UNSIGNED NOT NULL,
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `company_disclosures` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `framework` varchar(20) NOT NULL DEFAULT 'ifrs_s2',
  `section` varchar(50) NOT NULL,
  `fiscal_year` smallint(5) UNSIGNED NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`content`)),
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `last_edited_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `company_invitations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `role_id` bigint(20) UNSIGNED DEFAULT NULL,
  `custom_role_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company_custom_role_id` bigint(20) UNSIGNED DEFAULT NULL,
  `access_level` enum('view','manage','full') DEFAULT 'view',
  `token` varchar(255) NOT NULL,
  `status` enum('pending','accepted','rejected','expired') DEFAULT 'pending',
  `invited_by` bigint(20) UNSIGNED NOT NULL,
  `invited_at` datetime NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `accepted_at` datetime DEFAULT NULL,
  `accepted_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `company_reporting_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `fiscal_year` smallint(5) UNSIGNED DEFAULT NULL,
  `organisational_boundary` varchar(20) NOT NULL DEFAULT 'operational_control',
  `consolidation_approach` varchar(20) NOT NULL DEFAULT 'operational_control',
  `base_year` smallint(5) UNSIGNED DEFAULT NULL,
  `base_year_rationale` text DEFAULT NULL,
  `recalculation_policy` text DEFAULT NULL,
  `recalculation_threshold_percent` decimal(5,2) DEFAULT 5.00,
  `gwp_version` varchar(10) NOT NULL DEFAULT 'AR6',
  `scope3_category_policy` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`scope3_category_policy`)),
  `intensity_denominator_type` varchar(30) DEFAULT NULL,
  `intensity_denominator_value` decimal(18,4) DEFAULT NULL,
  `intensity_denominator_unit` varchar(40) DEFAULT NULL,
  `sasb_sector` varchar(32) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `consultants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `provider` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `company_name` varchar(255) NOT NULL,
  `trade_license_number` varchar(80) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `emirates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`emirates`)),
  `languages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`languages`)),
  `specialties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specialties`)),
  `experience_years` tinyint(3) UNSIGNED DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `has_moccae_experience` tinyint(1) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('draft','pending_review','approved','rejected','suspended') NOT NULL DEFAULT 'draft',
  `admin_notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by_admin_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `agency_company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `login_count` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `consultant_client_engagements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `consultant_company_id` bigint(20) UNSIGNED NOT NULL,
  `managed_company_id` bigint(20) UNSIGNED NOT NULL,
  `consultant_subscription_id` bigint(20) UNSIGNED NOT NULL,
  `primary_reporting_year` smallint(5) UNSIGNED NOT NULL,
  `status` enum('active','archived','transferred') NOT NULL DEFAULT 'active',
  `archived_at` timestamp NULL DEFAULT NULL,
  `previous_engagement_id` bigint(20) UNSIGNED DEFAULT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `consultant_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `consultant_id` bigint(20) UNSIGNED NOT NULL,
  `document_type` varchar(40) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `consultant_intro_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `consultant_id` bigint(20) UNSIGNED NOT NULL,
  `pack_type` varchar(40) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('new','contacted','converted','closed') NOT NULL DEFAULT 'new',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `consultant_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `consultant_id` bigint(20) UNSIGNED NOT NULL,
  `intro_request_id` bigint(20) UNSIGNED DEFAULT NULL,
  `payment_transaction_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pack_type` varchar(40) DEFAULT NULL,
  `amount_aed` decimal(10,2) NOT NULL DEFAULT 0.00,
  `commission_rate` decimal(5,4) NOT NULL DEFAULT 0.1500,
  `commission_aed` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payout_aed` decimal(10,2) NOT NULL DEFAULT 0.00,
  `escrow_status` enum('pending_payment','held','released','refunded') NOT NULL DEFAULT 'pending_payment',
  `order_status` enum('draft','active','delivered','completed','disputed','cancelled') NOT NULL DEFAULT 'draft',
  `payment_reference` varchar(255) DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `consultant_password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `consultant_public_inquiries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `consultant_id` bigint(20) UNSIGNED NOT NULL,
  `requester_name` varchar(255) NOT NULL,
  `requester_email` varchar(255) NOT NULL,
  `requester_phone` varchar(30) NOT NULL,
  `requester_company` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('new','contacted','closed') NOT NULL DEFAULT 'new',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `consultant_subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `consultant_company_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_plan_id` bigint(20) UNSIGNED NOT NULL,
  `contract_year` smallint(5) UNSIGNED NOT NULL,
  `slot_limit` int(10) UNSIGNED NOT NULL,
  `starts_at` date NOT NULL,
  `expires_at` date NOT NULL,
  `status` enum('active','expired','cancelled') NOT NULL DEFAULT 'active',
  `payment_transaction_id` bigint(20) UNSIGNED DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `consultant_subscription_addons` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `consultant_subscription_id` bigint(20) UNSIGNED NOT NULL,
  `addon_type` enum('extra_slot','reporting_year_unlock') NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `managed_company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reporting_year` smallint(5) UNSIGNED DEFAULT NULL,
  `amount_aed` decimal(10,2) NOT NULL,
  `payment_transaction_id` bigint(20) UNSIGNED DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `email_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `slug` varchar(80) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `mailer` varchar(20) NOT NULL DEFAULT 'noreply',
  `reply_to` varchar(20) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `body_html` longtext NOT NULL,
  `body_text` longtext DEFAULT NULL,
  `placeholders` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`placeholders`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `emission_factors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `emission_source_id` bigint(20) UNSIGNED NOT NULL,
  `factor_value` decimal(15,6) DEFAULT NULL COMMENT 'Emission factor value (kg CO2e/unit) - can be GWP for refrigerants',
  `unit` varchar(50) NOT NULL,
  `calculation_method` varchar(100) DEFAULT NULL,
  `region` varchar(50) NOT NULL DEFAULT 'UAE',
  `valid_from` year(4) DEFAULT NULL,
  `valid_to` year(4) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `description` text DEFAULT NULL,
  `calculation_formula` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `fuel_type` varchar(100) DEFAULT NULL COMMENT 'Fuel type (Natural Gas, Diesel, Petrol, etc.)',
  `fuel_category` varchar(50) DEFAULT NULL COMMENT 'Fuel category (Gaseous, Liquid, Solid)',
  `vehicle_category` varchar(100) DEFAULT NULL COMMENT 'Vehicle category (Cars, Motorbike, LDV etc.)',
  `vehicle_type` varchar(100) DEFAULT NULL COMMENT 'Vehicle type (Small car, Medium car, etc.)',
  `vehicle_size` varchar(50) DEFAULT NULL COMMENT 'Vehicle size category',
  `co2_factor` decimal(15,6) DEFAULT NULL COMMENT 'CO2 emission factor (kg CO2/unit)',
  `ch4_factor` decimal(15,6) DEFAULT NULL COMMENT 'CH4 emission factor (kg CH4/unit)',
  `n2o_factor` decimal(15,6) DEFAULT NULL COMMENT 'N2O emission factor (kg N2O/unit)',
  `total_co2e_factor` decimal(15,6) DEFAULT NULL COMMENT 'Total CO2e factor (kg CO2e/unit) - calculated',
  `source_standard` enum('DEFRA','IPCC','UAE','MOCCAE','USEPA','Custom') DEFAULT 'DEFRA',
  `source_reference` varchar(255) DEFAULT NULL COMMENT 'Reference document/source',
  `gwp_version` varchar(20) DEFAULT 'AR6' COMMENT 'GWP version used (AR4, AR5, AR6)',
  `is_default` tinyint(1) DEFAULT 0 COMMENT 'Default factor for this source/region',
  `priority` int(11) DEFAULT 0 COMMENT 'Priority for selection (higher = preferred)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `emission_factor_selection_rules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `emission_source_id` bigint(20) UNSIGNED NOT NULL,
  `rule_name` varchar(255) NOT NULL,
  `priority` int(11) DEFAULT 0 COMMENT 'Higher priority = selected first',
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Conditions: {"region": "UAE", "fuel_type": "Natural Gas", "unit": "kWh"}' CHECK (json_valid(`conditions`)),
  `emission_factor_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Specific factor to use',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `emission_gwp_values` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `gas_name` varchar(100) NOT NULL COMMENT 'Gas name (CO2, CH4, N2O, HFC-134a, etc.)',
  `gas_code` varchar(50) DEFAULT NULL COMMENT 'Gas code/identifier',
  `gwp_version` enum('AR4','AR5','AR6') DEFAULT 'AR6' COMMENT 'IPCC Assessment Report version',
  `gwp_100_year` decimal(10,2) NOT NULL COMMENT '100-year GWP value',
  `gwp_20_year` decimal(10,2) DEFAULT NULL COMMENT '20-year GWP value',
  `gwp_500_year` decimal(10,2) DEFAULT NULL COMMENT '500-year GWP value',
  `notes` text DEFAULT NULL COMMENT 'Additional notes about the gas',
  `is_kyoto_protocol` tinyint(1) DEFAULT 0 COMMENT 'Is this a Kyoto Protocol gas?',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `emission_industry_labels` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `emission_source_id` bigint(20) UNSIGNED NOT NULL COMMENT 'Links to emission_sources_master',
  `industry_category_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Links to master_industry_categories.id (can be Level 1, 2, or 3)',
  `match_level` tinyint(1) DEFAULT NULL COMMENT '1=Sector (Level 1), 2=Industry (Level 2), 3=Subcategory (Level 3) - which level this label applies to',
  `also_match_children` tinyint(1) DEFAULT 0 COMMENT 'If 1, also applies to all child categories (cascading match)',
  `unit_type` varchar(100) DEFAULT NULL COMMENT 'Unit type context (e.g., Main Factory, Office Building, Restaurant/Kitchen, Data Center, Warehouse)',
  `user_friendly_name` varchar(255) NOT NULL COMMENT 'User-friendly name for this emission source in this industry context',
  `user_friendly_description` text DEFAULT NULL COMMENT 'Industry-specific description',
  `common_equipment` text DEFAULT NULL COMMENT 'Common equipment/use cases for this industry',
  `typical_units` varchar(255) DEFAULT NULL COMMENT 'Typical units used in this industry context',
  `display_order` int(11) DEFAULT 0 COMMENT 'Display order within industry',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `emission_sources_master` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `scope` enum('Scope 1','Scope 2','Scope 3') NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `subcategory` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `quick_input_slug` varchar(100) DEFAULT NULL COMMENT 'URL slug (natural-gas, fuel, vehicle, etc.)',
  `quick_input_icon` varchar(50) DEFAULT NULL COMMENT 'Icon identifier',
  `quick_input_order` int(11) DEFAULT 0 COMMENT 'Menu display order',
  `is_quick_input` tinyint(1) DEFAULT 0 COMMENT 'Show in Quick Input menu',
  `instructions` text DEFAULT NULL COMMENT 'Form instructions text',
  `tutorial_link` varchar(255) DEFAULT NULL COMMENT 'Tutorial/documentation link',
  `ipcc_category_code` varchar(20) DEFAULT NULL COMMENT 'IPCC category code (e.g., 2.A.2, 2.B.1, 2.C.1)',
  `ipcc_sector` varchar(100) DEFAULT NULL COMMENT 'IPCC sector (e.g., Industrial Processes)',
  `ipcc_subcategory` varchar(255) DEFAULT NULL COMMENT 'IPCC subcategory description',
  `emission_type` enum('combustion','process','fugitive','electricity','other') DEFAULT NULL COMMENT 'Type of emission',
  `default_unit` varchar(50) DEFAULT NULL COMMENT 'Default unit for this source'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `emission_source_form_fields` (
  `id` int(11) NOT NULL,
  `emission_source_id` int(11) NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `field_type` enum('number','text','select','date','checkbox','textarea') NOT NULL,
  `field_label` varchar(200) NOT NULL,
  `field_placeholder` varchar(200) DEFAULT NULL,
  `field_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`field_options`)),
  `is_required` tinyint(1) DEFAULT 0,
  `field_order` int(11) DEFAULT 0,
  `validation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_rules`)),
  `help_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `default_value` varchar(255) DEFAULT NULL COMMENT 'Default value for this field',
  `conditional_logic` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Show/hide rules: {"depends_on": "field_name", "show_if": "value"}' CHECK (json_valid(`conditional_logic`)),
  `depends_on_field` varchar(100) DEFAULT NULL COMMENT 'Field name this depends on',
  `depends_on_value` varchar(255) DEFAULT NULL COMMENT 'Value that triggers this field',
  `calculation_formula` text DEFAULT NULL COMMENT 'Formula for calculated fields',
  `unit_conversion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Unit conversion factors' CHECK (json_valid(`unit_conversion`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `emission_unit_conversions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `from_unit` varchar(50) NOT NULL,
  `to_unit` varchar(50) NOT NULL,
  `conversion_factor` decimal(15,6) NOT NULL COMMENT 'Multiply from_unit by this to get to_unit',
  `fuel_type` varchar(100) DEFAULT NULL COMMENT 'Fuel-specific conversion (if applicable)',
  `region` varchar(50) DEFAULT NULL COMMENT 'Region-specific conversion (if applicable)',
  `is_active` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `esg_kpi_snapshots` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `fiscal_year` smallint(5) UNSIGNED NOT NULL,
  `category` varchar(32) NOT NULL,
  `metric_key` varchar(64) NOT NULL,
  `value` decimal(20,4) DEFAULT NULL,
  `unit` varchar(32) DEFAULT NULL,
  `source` varchar(16) NOT NULL DEFAULT 'manual',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `esg_sustainability_targets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `target_category` varchar(50) NOT NULL,
  `metric_label` varchar(255) DEFAULT NULL,
  `baseline_value` decimal(20,4) DEFAULT NULL,
  `target_value` decimal(20,4) DEFAULT NULL,
  `unit` varchar(32) DEFAULT NULL,
  `base_year` smallint(5) UNSIGNED DEFAULT NULL,
  `target_year` smallint(5) UNSIGNED NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `feature_flags` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `feature_code` varchar(100) NOT NULL,
  `is_enabled` tinyint(1) DEFAULT 0,
  `enabled_at` datetime DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `hris_kpi_import_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `fiscal_year` smallint(5) UNSIGNED DEFAULT NULL,
  `imported_by` bigint(20) UNSIGNED DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `source_system` varchar(64) DEFAULT NULL,
  `rows_imported` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `rows_skipped` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `errors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`errors`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_number` varchar(40) NOT NULL,
  `transaction_id` bigint(20) UNSIGNED DEFAULT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `buyer_name` varchar(255) DEFAULT NULL,
  `buyer_email` varchar(255) DEFAULT NULL,
  `buyer_address` text DEFAULT NULL,
  `buyer_trn` varchar(40) DEFAULT NULL,
  `seller_name` varchar(255) DEFAULT NULL,
  `seller_address` text DEFAULT NULL,
  `seller_trn` varchar(40) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'AED',
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `charged_amount` decimal(12,2) DEFAULT NULL,
  `charged_currency` varchar(3) DEFAULT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `issued_at` timestamp NULL DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `line_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`line_items`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `locations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `location_type` varchar(255) DEFAULT NULL,
  `staff_count` int(11) DEFAULT NULL,
  `staff_work_from_home` tinyint(1) NOT NULL DEFAULT 0,
  `work_from_home_percentage` decimal(5,2) DEFAULT NULL,
  `fiscal_year_start` varchar(16) NOT NULL DEFAULT 'January',
  `is_head_office` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `receives_utility_bills` tinyint(1) NOT NULL DEFAULT 0,
  `pays_electricity_proportion` tinyint(1) NOT NULL DEFAULT 0,
  `shared_building_services` tinyint(1) NOT NULL DEFAULT 0,
  `reporting_period` int(11) DEFAULT NULL,
  `measurement_frequency` enum('Annually','Half Yearly','Quarterly','Monthly') NOT NULL DEFAULT 'Annually',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `location_emission_boundaries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `location_id` bigint(20) UNSIGNED NOT NULL,
  `scope` enum('Scope 1','Scope 2','Scope 3') NOT NULL,
  `selected_sources` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`selected_sources`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `master_industry_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Category name (Sector, Sub-Sector Category, or Sub-Sector)',
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Parent category ID (NULL for sectors)',
  `level` tinyint(1) NOT NULL COMMENT '1=Sector, 2=Sub-Sector Category, 3=Sub-Sector',
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `material_sustainability_topics` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `fiscal_year` smallint(5) UNSIGNED NOT NULL,
  `topic_key` varchar(50) NOT NULL,
  `is_material` tinyint(1) NOT NULL DEFAULT 0,
  `rationale` text DEFAULT NULL,
  `impact_materiality` varchar(20) DEFAULT NULL,
  `financial_materiality` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `measurements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `location_id` bigint(20) UNSIGNED NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `frequency` enum('monthly','quarterly','half_yearly','annually') NOT NULL,
  `status` enum('draft','submitted','under_review','not_verified','verified') NOT NULL DEFAULT 'draft',
  `fiscal_year` int(11) NOT NULL,
  `fiscal_year_start_month` varchar(16) NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `notes` text DEFAULT NULL,
  `staff_count` int(11) DEFAULT NULL,
  `staff_work_from_home` tinyint(1) DEFAULT 0,
  `work_from_home_percentage` decimal(5,2) DEFAULT NULL,
  `total_co2e` decimal(15,6) DEFAULT 0.000000,
  `scope_1_co2e` decimal(15,6) DEFAULT 0.000000,
  `scope_2_co2e` decimal(15,6) DEFAULT 0.000000,
  `scope_3_co2e` decimal(15,6) DEFAULT 0.000000,
  `co2e_calculated_at` timestamp NULL DEFAULT NULL,
  `emission_source_co2e` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`emission_source_co2e`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `measurement_audit_trail` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `measurement_id` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(50) NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `changed_by` bigint(20) UNSIGNED NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reason` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `measurement_data` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `measurement_id` bigint(20) UNSIGNED NOT NULL,
  `emission_source_id` int(11) NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `field_value` text DEFAULT NULL,
  `field_type` varchar(50) DEFAULT 'text',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `entry_date` date DEFAULT NULL COMMENT 'Date of this specific entry',
  `entry_number` int(11) DEFAULT NULL COMMENT 'Entry sequence number',
  `fuel_type` varchar(100) DEFAULT NULL COMMENT 'Fuel type used (if applicable)',
  `vehicle_type` varchar(100) DEFAULT NULL COMMENT 'Vehicle type (if applicable)',
  `gas_type` varchar(50) DEFAULT NULL COMMENT 'Refrigerant gas type (if applicable)',
  `co2_emissions` decimal(15,6) DEFAULT NULL COMMENT 'CO2 emissions (kg)',
  `ch4_emissions` decimal(15,6) DEFAULT NULL COMMENT 'CH4 emissions (kg)',
  `n2o_emissions` decimal(15,6) DEFAULT NULL COMMENT 'N2O emissions (kg)',
  `calculated_co2e` decimal(15,6) DEFAULT NULL COMMENT 'Total CO2e calculated value',
  `scope` enum('Scope 1','Scope 2','Scope 3') DEFAULT NULL COMMENT 'Emission scope',
  `quantity` decimal(15,4) DEFAULT NULL COMMENT 'Quantity value for Quick Input entries',
  `unit` varchar(50) DEFAULT NULL COMMENT 'Unit of measurement (kWh, liters, kg, etc.)',
  `notes` text DEFAULT NULL COMMENT 'Additional notes',
  `supporting_docs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`supporting_docs`)),
  `additional_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional form field data as JSON' CHECK (json_valid(`additional_data`)),
  `is_offset` tinyint(1) DEFAULT 0 COMMENT 'Whether this emission is offset',
  `gwp_version_used` varchar(20) DEFAULT 'AR6' COMMENT 'GWP version used for calculation',
  `emission_factor_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Reference to emission_factors table',
  `calculation_method` varchar(100) DEFAULT NULL COMMENT 'Method used (Tier 1, Tier 2, Tier 3)',
  `supplier_emission_factor` decimal(15,6) DEFAULT NULL COMMENT 'Supplier-specific factor (if used)',
  `scope2_method` varchar(20) DEFAULT NULL,
  `is_biogenic` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payment_gateways` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `gateway` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `mode` varchar(255) NOT NULL DEFAULT 'test',
  `key_id` text DEFAULT NULL,
  `key_secret` text DEFAULT NULL,
  `webhook_secret` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `module` varchar(100) NOT NULL COMMENT 'e.g., dashboard, locations, measurements, reports, staff, roles, settings',
  `action` varchar(50) NOT NULL COMMENT 'view, add, edit, delete',
  `name` varchar(255) NOT NULL COMMENT 'e.g., view_dashboard, add_locations',
  `description` text DEFAULT NULL,
  `group_name` varchar(100) DEFAULT NULL COMMENT 'For grouping in UI',
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `plan_feature_rows` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `label` varchar(255) NOT NULL,
  `coming_soon` tinyint(1) NOT NULL DEFAULT 0,
  `value_starter` varchar(255) DEFAULT NULL,
  `value_growth` varchar(255) DEFAULT NULL,
  `value_enterprise` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reduction_targets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `target_type` varchar(20) NOT NULL DEFAULT 'absolute',
  `scope_coverage` varchar(30) NOT NULL DEFAULT 'scope12',
  `base_year` smallint(5) UNSIGNED DEFAULT NULL,
  `target_year` smallint(5) UNSIGNED NOT NULL,
  `baseline_tco2e` decimal(14,4) DEFAULT NULL,
  `target_tco2e` decimal(14,4) DEFAULT NULL,
  `reduction_percent` decimal(8,2) DEFAULT NULL,
  `sbti_aligned` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('MOCCAE','GRI','Internal') NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_templates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `template_code` varchar(50) NOT NULL COMMENT 'e.g., admin, manager, data_handler, auditor',
  `template_name` varchar(255) NOT NULL COMMENT 'e.g., Admin, Manager, Data Handler, Auditor',
  `description` text DEFAULT NULL,
  `is_system_template` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_template_permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_template_id` bigint(20) UNSIGNED NOT NULL,
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `scope3_addons` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `price_display` varchar(255) DEFAULT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`items`)),
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_pages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `slug` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` longtext DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stakeholder_engagements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `fiscal_year` smallint(5) UNSIGNED NOT NULL,
  `stakeholder_group` varchar(255) NOT NULL,
  `engagement_method` varchar(100) DEFAULT NULL,
  `frequency` varchar(50) DEFAULT NULL,
  `topics_discussed` text DEFAULT NULL,
  `outcomes` text DEFAULT NULL,
  `last_engaged_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `structural_changes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fiscal_year` smallint(5) UNSIGNED NOT NULL,
  `change_type` varchar(30) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `triggers_recalculation` tinyint(1) NOT NULL DEFAULT 0,
  `emissions_impact_tco2e` decimal(14,4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `subscription_coupons` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(40) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('percent','fixed','free') NOT NULL DEFAULT 'percent',
  `discount_percent` decimal(5,2) DEFAULT NULL,
  `discount_amount_aed` decimal(12,2) DEFAULT NULL,
  `discount_amount_inr` decimal(12,2) DEFAULT NULL,
  `subscription_plan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `max_uses` int(10) UNSIGNED DEFAULT NULL,
  `used_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `starts_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `subscription_coupon_redemptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `coupon_id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_id` bigint(20) UNSIGNED DEFAULT NULL,
  `transaction_id` bigint(20) UNSIGNED DEFAULT NULL,
  `discount_applied` decimal(12,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL DEFAULT 'INR',
  `redeemed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `subscription_plans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `plan_code` varchar(50) NOT NULL,
  `plan_name` varchar(255) NOT NULL,
  `plan_category` varchar(20) NOT NULL DEFAULT 'client',
  `price_annual` decimal(10,2) NOT NULL,
  `price_inr` decimal(12,2) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'AED',
  `billing_cycle` enum('annual','monthly') DEFAULT 'annual',
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `limits` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `entitlements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`entitlements`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `supply_chain_suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `fiscal_year` smallint(5) UNSIGNED NOT NULL,
  `supplier_name` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'goods',
  `spend_aed` decimal(16,2) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `scope3_category` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `screening_status` varchar(30) NOT NULL DEFAULT 'not_screened',
  `human_rights_assessed` tinyint(1) NOT NULL DEFAULT 0,
  `environmental_assessed` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sustainability_risks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `fiscal_year` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `topic` varchar(50) NOT NULL,
  `time_horizon` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `financial_impact` text DEFAULT NULL,
  `likelihood` varchar(20) DEFAULT NULL,
  `mitigation` text DEFAULT NULL,
  `owner` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `transition_actions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `reduction_target_id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `action_type` varchar(30) DEFAULT NULL,
  `planned_year` smallint(5) UNSIGNED DEFAULT NULL,
  `capex_aed` decimal(14,2) DEFAULT NULL,
  `opex_aed` decimal(14,2) DEFAULT NULL,
  `expected_reduction_tco2e` decimal(14,4) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'planned',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usage_tracking` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `resource_type` enum('location','user','document','report','api_call','measurement') NOT NULL,
  `resource_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `period` enum('daily','monthly','yearly') DEFAULT 'monthly',
  `period_start` date NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `provider` varchar(50) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `role` enum('admin','company_admin','company_user') NOT NULL DEFAULT 'company_user',
  `custom_role_id` bigint(20) UNSIGNED DEFAULT NULL,
  `external_company_name` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `login_count` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_active_context` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `active_company_id` bigint(20) UNSIGNED DEFAULT NULL,
  `last_switched_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_company_roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT 'Reference to users table',
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `company_custom_role_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'NULL or 0 = Owner/Client, > 0 = Staff with custom role',
  `assigned_by` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'User who assigned this role',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admins_email_unique` (`email`),
  ADD KEY `idx_admins_is_active` (`is_active`),
  ADD KEY `admins_last_login_at_index` (`last_login_at`);

ALTER TABLE `admin_package_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_package_assignments_company_id_target_type_index` (`company_id`,`target_type`),
  ADD KEY `admin_package_assignments_admin_id_index` (`admin_id`),
  ADD KEY `admin_package_assignments_consultant_id_index` (`consultant_id`),
  ADD KEY `admin_package_assignments_subscription_plan_id_foreign` (`subscription_plan_id`),
  ADD KEY `admin_package_assignments_client_subscription_id_foreign` (`client_subscription_id`),
  ADD KEY `admin_package_assignments_consultant_subscription_id_foreign` (`consultant_subscription_id`);

ALTER TABLE `admin_password_reset_tokens`
  ADD PRIMARY KEY (`email`);

ALTER TABLE `base_year_restatements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `base_year_restatements_restated_by_foreign` (`restated_by`),
  ADD KEY `base_year_restatements_company_id_base_year_index` (`company_id`,`base_year`);

ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

ALTER TABLE `carbon_calculations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `carbon_calculations_calculated_by_foreign` (`calculated_by`),
  ADD KEY `carbon_calculations_company_id_scope_year_index` (`company_id`,`scope`,`year`),
  ADD KEY `carbon_calculations_company_id_year_quarter_index` (`company_id`,`year`,`quarter`),
  ADD KEY `carbon_calculations_company_id_year_month_index` (`company_id`,`year`,`month`);

ALTER TABLE `carbon_emissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `carbon_emissions_user_id_foreign` (`user_id`),
  ADD KEY `carbon_emissions_verified_by_foreign` (`verified_by`),
  ADD KEY `carbon_emissions_company_id_scope_activity_date_index` (`company_id`,`scope`,`activity_date`),
  ADD KEY `carbon_emissions_company_id_category_index` (`company_id`,`category`);

ALTER TABLE `client_billing_methods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company_id` (`company_id`),
  ADD KEY `idx_is_default` (`is_default`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_stripe_payment_method_id` (`stripe_payment_method_id`),
  ADD KEY `fk_billing_methods_added_by` (`added_by`);

ALTER TABLE `client_payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company_id` (`company_id`),
  ADD KEY `idx_subscription_id` (`subscription_id`),
  ADD KEY `idx_billing_method_id` (`billing_method_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_transaction_type` (`transaction_type`),
  ADD KEY `idx_paid_at` (`paid_at`),
  ADD KEY `idx_stripe_payment_intent_id` (`stripe_payment_intent_id`);

ALTER TABLE `client_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_one_active_subscription` (`active_company_key`),
  ADD KEY `idx_company_id` (`company_id`),
  ADD KEY `idx_subscription_plan_id` (`subscription_plan_id`);

ALTER TABLE `climate_opportunities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `climate_opportunities_company_id_foreign` (`company_id`);

ALTER TABLE `climate_risks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `climate_risks_company_id_foreign` (`company_id`);

ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `companies_slug_unique` (`slug`),
  ADD UNIQUE KEY `companies_email_unique` (`email`),
  ADD KEY `idx_company_type` (`company_type`),
  ADD KEY `companies_consultant_id_index` (`consultant_id`);

ALTER TABLE `company_custom_roles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company_id` (`company_id`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_based_on_template` (`based_on_template`);

ALTER TABLE `company_custom_role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_company_role_permission` (`company_custom_role_id`,`permission_id`),
  ADD KEY `idx_permission_id` (`permission_id`);

ALTER TABLE `company_disclosures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `co_disc_co_fw_sec_fy_uq` (`company_id`,`framework`,`section`,`fiscal_year`),
  ADD KEY `company_disclosures_last_edited_by_foreign` (`last_edited_by`);

ALTER TABLE `company_invitations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD UNIQUE KEY `company_email_pending` (`company_id`,`email`,`status`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_company_invitations_inviter` (`invited_by`),
  ADD KEY `fk_company_invitations_accepter` (`accepted_by_user_id`);

ALTER TABLE `company_reporting_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_reporting_settings_company_id_fiscal_year_unique` (`company_id`,`fiscal_year`);

ALTER TABLE `consultants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `consultants_email_unique` (`email`),
  ADD UNIQUE KEY `consultants_google_id_unique` (`google_id`),
  ADD KEY `consultants_reviewed_by_admin_id_foreign` (`reviewed_by_admin_id`),
  ADD KEY `consultants_agency_company_id_foreign` (`agency_company_id`),
  ADD KEY `consultants_last_login_at_index` (`last_login_at`);

ALTER TABLE `consultant_client_engagements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cce_consultant_status_idx` (`consultant_company_id`,`status`),
  ADD KEY `cce_consultant_subscription_id_foreign` (`consultant_subscription_id`),
  ADD KEY `cce_previous_engagement_id_foreign` (`previous_engagement_id`),
  ADD KEY `cce_managed_consultant_idx` (`managed_company_id`,`consultant_company_id`),
  ADD KEY `cce_managed_status_idx` (`managed_company_id`,`status`);

ALTER TABLE `consultant_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consultant_documents_consultant_id_foreign` (`consultant_id`);

ALTER TABLE `consultant_intro_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consultant_intro_requests_company_id_foreign` (`company_id`),
  ADD KEY `consultant_intro_requests_user_id_foreign` (`user_id`),
  ADD KEY `consultant_intro_requests_consultant_id_foreign` (`consultant_id`);

ALTER TABLE `consultant_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consultant_orders_company_id_foreign` (`company_id`),
  ADD KEY `consultant_orders_consultant_id_foreign` (`consultant_id`),
  ADD KEY `consultant_orders_intro_request_id_foreign` (`intro_request_id`),
  ADD KEY `consultant_orders_payment_transaction_id_foreign` (`payment_transaction_id`);

ALTER TABLE `consultant_password_reset_tokens`
  ADD PRIMARY KEY (`email`);

ALTER TABLE `consultant_public_inquiries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consultant_public_inquiries_consultant_id_foreign` (`consultant_id`);

ALTER TABLE `consultant_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cs_consultant_status_idx` (`consultant_company_id`,`status`),
  ADD KEY `cs_consultant_year_idx` (`consultant_company_id`,`contract_year`),
  ADD KEY `cs_subscription_plan_id_foreign` (`subscription_plan_id`),
  ADD KEY `cs_payment_transaction_id_foreign` (`payment_transaction_id`);

ALTER TABLE `consultant_subscription_addons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `psa_subscription_idx` (`consultant_subscription_id`),
  ADD KEY `csa_managed_company_id_foreign` (`managed_company_id`),
  ADD KEY `csa_payment_transaction_id_foreign` (`payment_transaction_id`),
  ADD KEY `csa_unlock_lookup_idx` (`consultant_subscription_id`,`addon_type`,`managed_company_id`,`reporting_year`);

ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_templates_slug_unique` (`slug`);

ALTER TABLE `emission_factors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `emission_factors_emission_source_id_foreign` (`emission_source_id`),
  ADD KEY `emission_factors_region_index` (`region`),
  ADD KEY `idx_fuel_type` (`fuel_type`,`fuel_category`),
  ADD KEY `idx_vehicle_type` (`vehicle_type`,`vehicle_size`),
  ADD KEY `idx_source_standard` (`source_standard`,`region`),
  ADD KEY `idx_default_priority` (`is_default`,`priority`);

ALTER TABLE `emission_factor_selection_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_emission_source` (`emission_source_id`),
  ADD KEY `idx_priority` (`emission_source_id`,`priority` DESC),
  ADD KEY `fk_selection_rules_factor` (`emission_factor_id`);

ALTER TABLE `emission_gwp_values`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_gas_version` (`gas_name`,`gwp_version`),
  ADD KEY `idx_gas_code` (`gas_code`),
  ADD KEY `idx_gwp_version` (`gwp_version`);

ALTER TABLE `emission_industry_labels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_emission_source` (`emission_source_id`),
  ADD KEY `idx_industry_category` (`industry_category_id`,`match_level`),
  ADD KEY `idx_unit_type` (`unit_type`),
  ADD KEY `idx_industry_source` (`industry_category_id`,`emission_source_id`),
  ADD KEY `idx_match_level` (`match_level`,`also_match_children`);

ALTER TABLE `emission_sources_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `quick_input_slug` (`quick_input_slug`),
  ADD KEY `idx_quick_input` (`is_quick_input`,`quick_input_order`),
  ADD KEY `idx_ipcc_category` (`ipcc_category_code`);

ALTER TABLE `emission_source_form_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_emission_source_order` (`emission_source_id`,`field_order`);

ALTER TABLE `emission_unit_conversions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_conversion` (`from_unit`,`to_unit`,`fuel_type`,`region`),
  ADD KEY `idx_from_unit` (`from_unit`),
  ADD KEY `idx_fuel_type` (`fuel_type`);

ALTER TABLE `esg_kpi_snapshots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `esg_kpi_company_year_metric_unique` (`company_id`,`fiscal_year`,`metric_key`),
  ADD KEY `esg_kpi_snapshots_company_id_fiscal_year_category_index` (`company_id`,`fiscal_year`,`category`);

ALTER TABLE `esg_sustainability_targets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `esg_sustainability_targets_company_id_target_category_index` (`company_id`,`target_category`);

ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

ALTER TABLE `feature_flags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_feature` (`company_id`,`feature_code`),
  ADD KEY `idx_company_id` (`company_id`);

ALTER TABLE `hris_kpi_import_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hris_kpi_import_logs_imported_by_foreign` (`imported_by`),
  ADD KEY `hris_kpi_import_logs_company_id_created_at_index` (`company_id`,`created_at`);

ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
  ADD KEY `invoices_transaction_id_foreign` (`transaction_id`),
  ADD KEY `invoices_company_id_index` (`company_id`);

ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `locations_company_id_is_active_index` (`company_id`,`is_active`),
  ADD KEY `locations_company_id_is_head_office_index` (`company_id`,`is_head_office`);

ALTER TABLE `location_emission_boundaries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `loc_scope_unique` (`location_id`,`scope`);

ALTER TABLE `master_industry_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_level` (`level`),
  ADD KEY `idx_active` (`is_active`);

ALTER TABLE `material_sustainability_topics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mat_sust_co_yr_topic_uq` (`company_id`,`fiscal_year`,`topic_key`);

ALTER TABLE `measurements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_measurement_period` (`location_id`,`period_start`,`period_end`),
  ADD KEY `measurements_created_by_foreign` (`created_by`);

ALTER TABLE `measurement_audit_trail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `measurement_audit_trail_measurement_id_action_index` (`measurement_id`,`action`),
  ADD KEY `measurement_audit_trail_changed_by_changed_at_index` (`changed_by`,`changed_at`);

ALTER TABLE `measurement_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_measurement_source` (`measurement_id`,`emission_source_id`),
  ADD KEY `idx_measurement_source_field` (`measurement_id`,`emission_source_id`,`field_name`),
  ADD KEY `idx_emission_source` (`emission_source_id`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_updated_by` (`updated_by`),
  ADD KEY `idx_entry_date` (`entry_date`),
  ADD KEY `idx_fuel_type_md` (`fuel_type`),
  ADD KEY `idx_emission_factor` (`emission_factor_id`),
  ADD KEY `idx_measurement_data_scope` (`measurement_id`,`scope`),
  ADD KEY `idx_measurement_data_scope_only` (`scope`);

ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

ALTER TABLE `payment_gateways`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_gateways_gateway_unique` (`gateway`);

ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_module_action` (`module`,`action`),
  ADD KEY `idx_module` (`module`),
  ADD KEY `idx_group_name` (`group_name`),
  ADD KEY `idx_is_active` (`is_active`);

ALTER TABLE `plan_feature_rows`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `reduction_targets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reduction_targets_company_id_foreign` (`company_id`);

ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reports_company_id_foreign` (`company_id`);

ALTER TABLE `role_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `template_code` (`template_code`),
  ADD KEY `idx_is_active` (`is_active`);

ALTER TABLE `role_template_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_template_permission` (`role_template_id`,`permission_id`),
  ADD KEY `idx_permission_id` (`permission_id`);

ALTER TABLE `scope3_addons`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

ALTER TABLE `site_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `site_pages_slug_unique` (`slug`);

ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `site_settings_key_unique` (`key`);

ALTER TABLE `stakeholder_engagements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stakeholder_engagements_company_id_fiscal_year_index` (`company_id`,`fiscal_year`);

ALTER TABLE `structural_changes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `structural_changes_location_id_foreign` (`location_id`),
  ADD KEY `structural_changes_company_id_fiscal_year_index` (`company_id`,`fiscal_year`);

ALTER TABLE `subscription_coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subscription_coupons_code_unique` (`code`),
  ADD KEY `subscription_coupons_subscription_plan_id_foreign` (`subscription_plan_id`);

ALTER TABLE `subscription_coupon_redemptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscription_coupon_redemptions_coupon_id_foreign` (`coupon_id`),
  ADD KEY `subscription_coupon_redemptions_company_id_foreign` (`company_id`);

ALTER TABLE `subscription_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plan_code` (`plan_code`),
  ADD KEY `plan_category` (`plan_category`);

ALTER TABLE `supply_chain_suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supply_chain_suppliers_company_id_fiscal_year_index` (`company_id`,`fiscal_year`);

ALTER TABLE `sustainability_risks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sustainability_risks_company_id_foreign` (`company_id`);

ALTER TABLE `transition_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transition_actions_reduction_target_id_foreign` (`reduction_target_id`),
  ADD KEY `transition_actions_company_id_foreign` (`company_id`);

ALTER TABLE `usage_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company_id` (`company_id`),
  ADD KEY `idx_resource_type` (`resource_type`),
  ADD KEY `idx_period_start` (`period_start`),
  ADD KEY `idx_company_period` (`company_id`,`period`,`period_start`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD KEY `users_company_id_foreign` (`company_id`),
  ADD KEY `idx_custom_role_id` (`custom_role_id`),
  ADD KEY `users_last_login_at_index` (`last_login_at`);

ALTER TABLE `user_active_context`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `fk_user_active_context_company` (`active_company_id`);

ALTER TABLE `user_company_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_company_role` (`user_id`,`company_id`,`company_custom_role_id`),
  ADD KEY `idx_company_id` (`company_id`),
  ADD KEY `idx_company_custom_role_id` (`company_custom_role_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_user_company_roles_user_active` (`user_id`,`is_active`),
  ADD KEY `idx_user_company_roles_company_active` (`company_id`,`is_active`);

ALTER TABLE `admins`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `admin_package_assignments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

ALTER TABLE `base_year_restatements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `carbon_calculations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `carbon_emissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `client_billing_methods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `client_payment_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `client_subscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

ALTER TABLE `climate_opportunities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=154;

ALTER TABLE `climate_risks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=154;

ALTER TABLE `companies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

ALTER TABLE `company_custom_roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

ALTER TABLE `company_custom_role_permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1808;

ALTER TABLE `company_disclosures`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1229;

ALTER TABLE `company_invitations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `company_reporting_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

ALTER TABLE `consultants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

ALTER TABLE `consultant_client_engagements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

ALTER TABLE `consultant_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `consultant_intro_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `consultant_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `consultant_public_inquiries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `consultant_subscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

ALTER TABLE `consultant_subscription_addons`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `email_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

ALTER TABLE `emission_factor_selection_rules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `emission_gwp_values`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

ALTER TABLE `emission_industry_labels`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

ALTER TABLE `emission_sources_master`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

ALTER TABLE `emission_unit_conversions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

ALTER TABLE `esg_kpi_snapshots`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3985;

ALTER TABLE `esg_sustainability_targets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=205;

ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `feature_flags`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `hris_kpi_import_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

ALTER TABLE `invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `locations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=172;

ALTER TABLE `location_emission_boundaries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=469;

ALTER TABLE `master_industry_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=180;

ALTER TABLE `material_sustainability_topics`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=511;

ALTER TABLE `measurements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=202;

ALTER TABLE `measurement_audit_trail`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=316;

ALTER TABLE `measurement_data`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1911;

ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `payment_gateways`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

ALTER TABLE `plan_feature_rows`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

ALTER TABLE `reduction_targets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

ALTER TABLE `reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `role_templates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `role_template_permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

ALTER TABLE `scope3_addons`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `site_pages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `site_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

ALTER TABLE `stakeholder_engagements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=205;

ALTER TABLE `structural_changes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `subscription_coupons`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `subscription_coupon_redemptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `subscription_plans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

ALTER TABLE `supply_chain_suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=205;

ALTER TABLE `sustainability_risks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

ALTER TABLE `transition_actions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=154;

ALTER TABLE `usage_tracking`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

ALTER TABLE `user_active_context`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

ALTER TABLE `user_company_roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

ALTER TABLE `admin_package_assignments`
  ADD CONSTRAINT `admin_package_assignments_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `admin_package_assignments_client_subscription_id_foreign` FOREIGN KEY (`client_subscription_id`) REFERENCES `client_subscriptions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `admin_package_assignments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `admin_package_assignments_consultant_id_foreign` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `admin_package_assignments_consultant_subscription_id_foreign` FOREIGN KEY (`consultant_subscription_id`) REFERENCES `consultant_subscriptions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `admin_package_assignments_subscription_plan_id_foreign` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE CASCADE;

ALTER TABLE `base_year_restatements`
  ADD CONSTRAINT `base_year_restatements_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `base_year_restatements_restated_by_foreign` FOREIGN KEY (`restated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `carbon_calculations`
  ADD CONSTRAINT `carbon_calculations_calculated_by_foreign` FOREIGN KEY (`calculated_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `carbon_calculations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `carbon_emissions`
  ADD CONSTRAINT `carbon_emissions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `carbon_emissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `carbon_emissions_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`);

ALTER TABLE `client_billing_methods`
  ADD CONSTRAINT `fk_billing_methods_added_by` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_billing_methods_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `client_payment_transactions`
  ADD CONSTRAINT `fk_client_payment_transactions_billing_method` FOREIGN KEY (`billing_method_id`) REFERENCES `client_billing_methods` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_client_payment_transactions_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_client_payment_transactions_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `client_subscriptions` (`id`) ON DELETE SET NULL;

ALTER TABLE `client_subscriptions`
  ADD CONSTRAINT `fk_client_subscriptions_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_client_subscriptions_plan` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`);

ALTER TABLE `climate_opportunities`
  ADD CONSTRAINT `climate_opportunities_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `climate_risks`
  ADD CONSTRAINT `climate_risks_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `companies`
  ADD CONSTRAINT `companies_consultant_id_foreign` FOREIGN KEY (`consultant_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL;

ALTER TABLE `company_custom_roles`
  ADD CONSTRAINT `fk_company_custom_roles_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `company_custom_role_permissions`
  ADD CONSTRAINT `fk_company_custom_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_company_custom_role_permissions_role` FOREIGN KEY (`company_custom_role_id`) REFERENCES `company_custom_roles` (`id`) ON DELETE CASCADE;

ALTER TABLE `company_disclosures`
  ADD CONSTRAINT `company_disclosures_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `company_disclosures_last_edited_by_foreign` FOREIGN KEY (`last_edited_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `company_invitations`
  ADD CONSTRAINT `fk_company_invitations_accepter` FOREIGN KEY (`accepted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_company_invitations_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_company_invitations_inviter` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `company_reporting_settings`
  ADD CONSTRAINT `company_reporting_settings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `consultants`
  ADD CONSTRAINT `consultants_agency_company_id_foreign` FOREIGN KEY (`agency_company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `consultants_reviewed_by_admin_id_foreign` FOREIGN KEY (`reviewed_by_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

ALTER TABLE `consultant_client_engagements`
  ADD CONSTRAINT `cce_consultant_company_id_foreign` FOREIGN KEY (`consultant_company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cce_consultant_subscription_id_foreign` FOREIGN KEY (`consultant_subscription_id`) REFERENCES `consultant_subscriptions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cce_managed_company_id_foreign` FOREIGN KEY (`managed_company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cce_previous_engagement_id_foreign` FOREIGN KEY (`previous_engagement_id`) REFERENCES `consultant_client_engagements` (`id`) ON DELETE SET NULL;

ALTER TABLE `consultant_documents`
  ADD CONSTRAINT `consultant_documents_consultant_id_foreign` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`id`) ON DELETE CASCADE;

ALTER TABLE `consultant_intro_requests`
  ADD CONSTRAINT `consultant_intro_requests_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `consultant_intro_requests_consultant_id_foreign` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `consultant_intro_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `consultant_orders`
  ADD CONSTRAINT `consultant_orders_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `consultant_orders_consultant_id_foreign` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `consultant_orders_intro_request_id_foreign` FOREIGN KEY (`intro_request_id`) REFERENCES `consultant_intro_requests` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `consultant_orders_payment_transaction_id_foreign` FOREIGN KEY (`payment_transaction_id`) REFERENCES `client_payment_transactions` (`id`) ON DELETE SET NULL;

ALTER TABLE `consultant_public_inquiries`
  ADD CONSTRAINT `consultant_public_inquiries_consultant_id_foreign` FOREIGN KEY (`consultant_id`) REFERENCES `consultants` (`id`) ON DELETE CASCADE;

ALTER TABLE `consultant_subscriptions`
  ADD CONSTRAINT `cs_consultant_company_id_foreign` FOREIGN KEY (`consultant_company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cs_payment_transaction_id_foreign` FOREIGN KEY (`payment_transaction_id`) REFERENCES `client_payment_transactions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cs_subscription_plan_id_foreign` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`);

ALTER TABLE `consultant_subscription_addons`
  ADD CONSTRAINT `csa_consultant_subscription_id_foreign` FOREIGN KEY (`consultant_subscription_id`) REFERENCES `consultant_subscriptions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `csa_managed_company_id_foreign` FOREIGN KEY (`managed_company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `csa_payment_transaction_id_foreign` FOREIGN KEY (`payment_transaction_id`) REFERENCES `client_payment_transactions` (`id`) ON DELETE SET NULL;

ALTER TABLE `emission_factor_selection_rules`
  ADD CONSTRAINT `fk_selection_rules_factor` FOREIGN KEY (`emission_factor_id`) REFERENCES `emission_factors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_selection_rules_source` FOREIGN KEY (`emission_source_id`) REFERENCES `emission_sources_master` (`id`) ON DELETE CASCADE;

ALTER TABLE `emission_industry_labels`
  ADD CONSTRAINT `fk_industry_labels_category` FOREIGN KEY (`industry_category_id`) REFERENCES `master_industry_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_industry_labels_source` FOREIGN KEY (`emission_source_id`) REFERENCES `emission_sources_master` (`id`) ON DELETE CASCADE;

ALTER TABLE `esg_kpi_snapshots`
  ADD CONSTRAINT `esg_kpi_snapshots_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `esg_sustainability_targets`
  ADD CONSTRAINT `esg_sustainability_targets_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `feature_flags`
  ADD CONSTRAINT `fk_feature_flags_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `hris_kpi_import_logs`
  ADD CONSTRAINT `hris_kpi_import_logs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hris_kpi_import_logs_imported_by_foreign` FOREIGN KEY (`imported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `client_payment_transactions` (`id`) ON DELETE SET NULL;

ALTER TABLE `locations`
  ADD CONSTRAINT `locations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `location_emission_boundaries`
  ADD CONSTRAINT `location_emission_boundaries_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE;

ALTER TABLE `master_industry_categories`
  ADD CONSTRAINT `fk_industry_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `master_industry_categories` (`id`) ON DELETE CASCADE;

ALTER TABLE `material_sustainability_topics`
  ADD CONSTRAINT `material_sustainability_topics_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `measurements`
  ADD CONSTRAINT `measurements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `measurements_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE;

ALTER TABLE `measurement_audit_trail`
  ADD CONSTRAINT `measurement_audit_trail_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `measurement_audit_trail_measurement_id_foreign` FOREIGN KEY (`measurement_id`) REFERENCES `measurements` (`id`) ON DELETE CASCADE;

ALTER TABLE `reduction_targets`
  ADD CONSTRAINT `reduction_targets_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `reports`
  ADD CONSTRAINT `reports_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `role_template_permissions`
  ADD CONSTRAINT `fk_role_template_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_role_template_permissions_template` FOREIGN KEY (`role_template_id`) REFERENCES `role_templates` (`id`) ON DELETE CASCADE;

ALTER TABLE `stakeholder_engagements`
  ADD CONSTRAINT `stakeholder_engagements_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `structural_changes`
  ADD CONSTRAINT `structural_changes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `structural_changes_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL;

ALTER TABLE `subscription_coupons`
  ADD CONSTRAINT `subscription_coupons_subscription_plan_id_foreign` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE SET NULL;

ALTER TABLE `subscription_coupon_redemptions`
  ADD CONSTRAINT `subscription_coupon_redemptions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subscription_coupon_redemptions_coupon_id_foreign` FOREIGN KEY (`coupon_id`) REFERENCES `subscription_coupons` (`id`) ON DELETE CASCADE;

ALTER TABLE `supply_chain_suppliers`
  ADD CONSTRAINT `supply_chain_suppliers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `sustainability_risks`
  ADD CONSTRAINT `sustainability_risks_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `transition_actions`
  ADD CONSTRAINT `transition_actions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transition_actions_reduction_target_id_foreign` FOREIGN KEY (`reduction_target_id`) REFERENCES `reduction_targets` (`id`) ON DELETE CASCADE;

ALTER TABLE `usage_tracking`
  ADD CONSTRAINT `fk_usage_tracking_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `users`
  ADD CONSTRAINT `users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

ALTER TABLE `user_active_context`
  ADD CONSTRAINT `fk_user_active_context_company` FOREIGN KEY (`active_company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_user_active_context_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `user_company_roles`
  ADD CONSTRAINT `fk_user_company_roles_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_company_roles_role` FOREIGN KEY (`company_custom_role_id`) REFERENCES `company_custom_roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_company_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS=1;
