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
