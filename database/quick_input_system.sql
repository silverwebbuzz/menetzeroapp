-- ============================================================================
-- Quick Input System - Database Structure and Master Data
-- Based on IPCC, DEFRA, and Expert Data Analysis
-- ============================================================================
-- 
-- This SQL file creates/modifies tables for the Quick Input system and
-- inserts master data for emission factors, GWP values, and form configurations.
--
-- IMPORTANT: 
-- 1. BACKUP YOUR DATABASE before running this script!
-- 2. Run this on development first!
-- 3. Review all INSERT statements and adjust values as needed
-- 4. Ensure master_industry_categories table exists before running this script!
--
-- KEY CHANGES:
-- - emission_industry_labels now uses master_industry_categories (industry_category_id)
--   instead of static industry_sector strings
-- - Supports hierarchical matching (Level 1, 2, or 3) with cascading to children
-- - All emission-related tables prefixed with 'emission_' for consistency
-- ============================================================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================================================
-- PART 1: MODIFY EXISTING TABLES
-- ============================================================================

-- 1.1 Enhance emission_sources_master table
ALTER TABLE `emission_sources_master`
ADD COLUMN IF NOT EXISTS `quick_input_slug` VARCHAR(100) NULL UNIQUE COMMENT 'URL slug (natural-gas, fuel, vehicle, etc.)',
ADD COLUMN IF NOT EXISTS `quick_input_icon` VARCHAR(50) NULL COMMENT 'Icon identifier',
ADD COLUMN IF NOT EXISTS `quick_input_order` INT(11) DEFAULT 0 COMMENT 'Menu display order',
ADD COLUMN IF NOT EXISTS `is_quick_input` TINYINT(1) DEFAULT 0 COMMENT 'Show in Quick Input menu',
ADD COLUMN IF NOT EXISTS `instructions` TEXT NULL COMMENT 'Form instructions text',
ADD COLUMN IF NOT EXISTS `tutorial_link` VARCHAR(255) NULL COMMENT 'Tutorial/documentation link',
ADD COLUMN IF NOT EXISTS `ipcc_category_code` VARCHAR(20) NULL COMMENT 'IPCC category code (e.g., 2.A.2, 2.B.1, 2.C.1)',
ADD COLUMN IF NOT EXISTS `ipcc_sector` VARCHAR(100) NULL COMMENT 'IPCC sector (e.g., Industrial Processes)',
ADD COLUMN IF NOT EXISTS `ipcc_subcategory` VARCHAR(255) NULL COMMENT 'IPCC subcategory description',
ADD COLUMN IF NOT EXISTS `emission_type` ENUM('combustion', 'process', 'fugitive', 'electricity', 'other') NULL COMMENT 'Type of emission',
ADD COLUMN IF NOT EXISTS `default_unit` VARCHAR(50) NULL COMMENT 'Default unit for this source';

-- Create indexes
CREATE INDEX IF NOT EXISTS `idx_quick_input` ON `emission_sources_master` (`is_quick_input`, `quick_input_order`);
CREATE INDEX IF NOT EXISTS `idx_ipcc_category` ON `emission_sources_master` (`ipcc_category_code`);

-- 1.2 Enhance emission_factors table
ALTER TABLE `emission_factors`
ADD COLUMN IF NOT EXISTS `fuel_type` VARCHAR(100) NULL COMMENT 'Fuel type (Natural Gas, Diesel, Petrol, etc.)',
ADD COLUMN IF NOT EXISTS `fuel_category` VARCHAR(50) NULL COMMENT 'Fuel category (Gaseous, Liquid, Solid)',
ADD COLUMN IF NOT EXISTS `vehicle_type` VARCHAR(100) NULL COMMENT 'Vehicle type (Small car, Medium car, etc.)',
ADD COLUMN IF NOT EXISTS `vehicle_size` VARCHAR(50) NULL COMMENT 'Vehicle size category',
ADD COLUMN IF NOT EXISTS `co2_factor` DECIMAL(15,6) NULL COMMENT 'CO2 emission factor (kg CO2/unit)',
ADD COLUMN IF NOT EXISTS `ch4_factor` DECIMAL(15,6) NULL COMMENT 'CH4 emission factor (kg CH4/unit)',
ADD COLUMN IF NOT EXISTS `n2o_factor` DECIMAL(15,6) NULL COMMENT 'N2O emission factor (kg N2O/unit)',
ADD COLUMN IF NOT EXISTS `total_co2e_factor` DECIMAL(15,6) NULL COMMENT 'Total CO2e factor (kg CO2e/unit) - calculated',
ADD COLUMN IF NOT EXISTS `source_standard` ENUM('DEFRA', 'IPCC', 'UAE', 'MOCCAE', 'USEPA', 'Custom') DEFAULT 'DEFRA',
ADD COLUMN IF NOT EXISTS `source_reference` VARCHAR(255) NULL COMMENT 'Reference document/source',
ADD COLUMN IF NOT EXISTS `gwp_version` VARCHAR(20) DEFAULT 'AR6' COMMENT 'GWP version used (AR4, AR5, AR6)',
ADD COLUMN IF NOT EXISTS `is_default` TINYINT(1) DEFAULT 0 COMMENT 'Default factor for this source/region',
ADD COLUMN IF NOT EXISTS `priority` INT(11) DEFAULT 0 COMMENT 'Priority for selection (higher = preferred)';

-- Create indexes for emission_factors
CREATE INDEX IF NOT EXISTS `idx_fuel_type` ON `emission_factors` (`fuel_type`, `fuel_category`);
CREATE INDEX IF NOT EXISTS `idx_vehicle_type` ON `emission_factors` (`vehicle_type`, `vehicle_size`);
CREATE INDEX IF NOT EXISTS `idx_source_standard` ON `emission_factors` (`source_standard`, `region`);
CREATE INDEX IF NOT EXISTS `idx_default_priority` ON `emission_factors` (`is_default`, `priority` DESC);

-- 1.3 Modify measurement_data table (Remove unique constraint, add fields)
-- First, drop the unique constraint if it exists
ALTER TABLE `measurement_data`
DROP INDEX IF EXISTS `unique_measurement_source`;

-- Add new index (non-unique) to allow multiple entries
CREATE INDEX IF NOT EXISTS `idx_measurement_source` ON `measurement_data` (`measurement_id`, `emission_source_id`);

-- Add new fields to measurement_data
ALTER TABLE `measurement_data`
ADD COLUMN IF NOT EXISTS `entry_date` DATE NULL COMMENT 'Date of this specific entry',
ADD COLUMN IF NOT EXISTS `entry_number` INT(11) NULL COMMENT 'Entry sequence number',
ADD COLUMN IF NOT EXISTS `fuel_type` VARCHAR(100) NULL COMMENT 'Fuel type used (if applicable)',
ADD COLUMN IF NOT EXISTS `vehicle_type` VARCHAR(100) NULL COMMENT 'Vehicle type (if applicable)',
ADD COLUMN IF NOT EXISTS `gas_type` VARCHAR(50) NULL COMMENT 'Refrigerant gas type (if applicable)',
ADD COLUMN IF NOT EXISTS `co2_emissions` DECIMAL(15,4) NULL COMMENT 'CO2 emissions (kg)',
ADD COLUMN IF NOT EXISTS `ch4_emissions` DECIMAL(15,4) NULL COMMENT 'CH4 emissions (kg)',
ADD COLUMN IF NOT EXISTS `n2o_emissions` DECIMAL(15,4) NULL COMMENT 'N2O emissions (kg)',
ADD COLUMN IF NOT EXISTS `gwp_version_used` VARCHAR(20) DEFAULT 'AR6' COMMENT 'GWP version used for calculation',
ADD COLUMN IF NOT EXISTS `emission_factor_id` BIGINT(20) UNSIGNED NULL COMMENT 'Reference to emission_factors table',
ADD COLUMN IF NOT EXISTS `calculation_method` VARCHAR(100) NULL COMMENT 'Method used (Tier 1, Tier 2, Tier 3)',
ADD COLUMN IF NOT EXISTS `supplier_emission_factor` DECIMAL(15,6) NULL COMMENT 'Supplier-specific factor (if used)';

-- Create indexes for measurement_data
CREATE INDEX IF NOT EXISTS `idx_entry_date` ON `measurement_data` (`entry_date`);
CREATE INDEX IF NOT EXISTS `idx_fuel_type_md` ON `measurement_data` (`fuel_type`);
CREATE INDEX IF NOT EXISTS `idx_emission_factor` ON `measurement_data` (`emission_factor_id`);

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

-- 2.5 Create emission_industry_labels table (Industry-specific user-friendly naming)
-- Based on MSME Matrix for Scope 1 and 2
-- Links to master_industry_categories for dynamic industry matching
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

-- 3.1 Insert GWP Values (from GWPs.xlsx - AR6 values)
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
-- Note: Adjust IDs based on your existing emission_sources_master data
-- These are examples - you may need to UPDATE existing records instead

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

-- 3.3 Insert Sample Emission Factors (Natural Gas - UAE)
-- Note: You'll need to get the emission_source_id from the INSERT above
-- This is a template - adjust emission_source_id values based on your actual data

-- Example: Natural Gas factors (from Stationary Combustion DEFRA data)
-- Assuming emission_source_id = (SELECT id FROM emission_sources_master WHERE quick_input_slug = 'natural-gas')
INSERT INTO `emission_factors` 
(`emission_source_id`, `factor_value`, `unit`, `region`, `valid_from`, `valid_to`, `is_active`,
 `fuel_type`, `fuel_category`, `co2_factor`, `ch4_factor`, `n2o_factor`, `total_co2e_factor`,
 `source_standard`, `source_reference`, `gwp_version`, `is_default`, `priority`)
SELECT 
  es.id,
  2.066946036,  -- kg CO2e per cubic metre (from DEFRA)
  'cubic metres',
  'UAE',
  2024,
  NULL,
  1,
  'Natural gas',
  'Gaseous',
  2.0627,  -- CO2 factor
  0.00307,  -- CH4 factor (will be converted using GWP)
  0.00095,  -- N2O factor (will be converted using GWP)
  2.066946036,  -- Total CO2e
  'DEFRA',
  'DEFRA 2024 Conversion Factors',
  'AR6',
  1,
  100
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'natural-gas'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `factor_value` = VALUES(`factor_value`),
  `total_co2e_factor` = VALUES(`total_co2e_factor`);

-- Natural Gas - kWh (Net CV)
INSERT INTO `emission_factors` 
(`emission_source_id`, `factor_value`, `unit`, `region`, `valid_from`, `valid_to`, `is_active`,
 `fuel_type`, `fuel_category`, `co2_factor`, `ch4_factor`, `n2o_factor`, `total_co2e_factor`,
 `source_standard`, `source_reference`, `gwp_version`, `is_default`, `priority`)
SELECT 
  es.id,
  0.2027229474,  -- kg CO2e per kWh (Net CV)
  'kWh (Net CV)',
  'UAE',
  2024,
  NULL,
  1,
  'Natural gas',
  'Gaseous',
  0.20229,
  0.00031,
  0.0001,
  0.2027229474,
  'DEFRA',
  'DEFRA 2024 Conversion Factors',
  'AR6',
  0,
  90
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'natural-gas'
LIMIT 1;

-- Natural Gas - kWh (Gross CV)
INSERT INTO `emission_factors` 
(`emission_source_id`, `factor_value`, `unit`, `region`, `valid_from`, `valid_to`, `is_active`,
 `fuel_type`, `fuel_category`, `co2_factor`, `ch4_factor`, `n2o_factor`, `total_co2e_factor`,
 `source_standard`, `source_reference`, `gwp_version`, `is_default`, `priority`)
SELECT 
  es.id,
  0.182980717,  -- kg CO2e per kWh (Gross CV)
  'kWh (Gross CV)',
  'UAE',
  2024,
  NULL,
  1,
  'Natural gas',
  'Gaseous',
  0.18259,
  0.00028,
  0.00009,
  0.182980717,
  'DEFRA',
  'DEFRA 2024 Conversion Factors',
  'AR6',
  0,
  80
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'natural-gas'
LIMIT 1;

-- Natural Gas - tonnes
INSERT INTO `emission_factors` 
(`emission_source_id`, `factor_value`, `unit`, `region`, `valid_from`, `valid_to`, `is_active`,
 `fuel_type`, `fuel_category`, `co2_factor`, `ch4_factor`, `n2o_factor`, `total_co2e_factor`,
 `source_standard`, `source_reference`, `gwp_version`, `is_default`, `priority`)
SELECT 
  es.id,
  2575.748063,  -- kg CO2e per tonne
  'tonnes',
  'UAE',
  2024,
  NULL,
  1,
  'Natural gas',
  'Gaseous',
  2570.42,
  3.8528,
  1.19161,
  2575.748063,
  'DEFRA',
  'DEFRA 2024 Conversion Factors',
  'AR6',
  0,
  70
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'natural-gas'
LIMIT 1;

-- 3.4 Insert Scope 2 Emission Factors (UAE)
-- Electricity - UAE (DEWA)
INSERT INTO `emission_factors` 
(`emission_source_id`, `factor_value`, `unit`, `region`, `valid_from`, `valid_to`, `is_active`,
 `source_standard`, `source_reference`, `gwp_version`, `is_default`, `priority`)
SELECT 
  es.id,
  0.4041,  -- kg CO2e per kWh (from Scope 2.xlsx - DEWA)
  'kWh',
  'UAE',
  2024,
  NULL,
  1,
  'UAE',
  'UAE ele. (DEWA)',
  'AR6',
  1,
  100
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'electricity'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `factor_value` = VALUES(`factor_value`);

-- Electricity - UAE (MOCCAE)
INSERT INTO `emission_factors` 
(`emission_source_id`, `factor_value`, `unit`, `region`, `valid_from`, `valid_to`, `is_active`,
 `source_standard`, `source_reference`, `gwp_version`, `is_default`, `priority`)
SELECT 
  es.id,
  0.495,  -- kg CO2e per kWh (from Scope 2.xlsx - MOCCAE)
  'kWh',
  'UAE',
  2024,
  NULL,
  1,
  'MOCCAE',
  'MOCCAE',
  'AR6',
  0,
  90
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'electricity'
LIMIT 1;

-- Purchased Steam
INSERT INTO `emission_factors` 
(`emission_source_id`, `factor_value`, `unit`, `region`, `valid_from`, `valid_to`, `is_active`,
 `source_standard`, `source_reference`, `gwp_version`, `is_default`, `priority`)
SELECT 
  es.id,
  0.2263,  -- kg CO2e per kWh
  'kWh',
  'UAE',
  2024,
  NULL,
  1,
  'USEPA',
  'USEPA',
  'AR6',
  1,
  100
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'heat-steam-cooling'
LIMIT 1;

-- Purchased Heat
INSERT INTO `emission_factors` 
(`emission_source_id`, `factor_value`, `unit`, `region`, `valid_from`, `valid_to`, `is_active`,
 `source_standard`, `source_reference`, `gwp_version`, `is_default`, `priority`)
SELECT 
  es.id,
  0.2265434,  -- kg CO2e per kWh
  'kWh',
  'UAE',
  2024,
  NULL,
  1,
  'USEPA',
  'USEPA',
  'AR6',
  1,
  100
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'heat-steam-cooling'
LIMIT 1;

-- 3.5 Insert Form Fields for Natural Gas
-- Get emission_source_id for Natural Gas
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
LIMIT 1;

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
LIMIT 1;

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
LIMIT 1;

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
LIMIT 1;

-- 3.6 Insert Industry-Specific User-Friendly Labels (Based on MSME Matrix)
-- These provide industry-specific naming for better user experience
-- Links to master_industry_categories table for dynamic industry matching
-- 
-- Category ID Reference (from master_industry_categories):
-- Level 1 (Sectors): 3=Manufacturing, 6=Construction & Real Estate, 7=Transportation & Logistics,
--                    8=ICT, 10=Healthcare & Life Sciences, 11=Retail & Wholesale, 12=Hospitality & Tourism
-- Level 2 (Industries): 37=Food & Beverage (under Manufacturing), 86=Software & Services (under ICT)
-- Level 3 (Sub-Sectors): 51=Food Processing, 52=Beverage Production, 91=IT Services, 92=Data Centers,
--                        132=Restaurants & Food Services (under Hospitality)

-- Natural Gas - Industry-specific labels
-- Manufacturing (Level 1 - ID 3) - applies to all manufacturing
INSERT INTO `emission_industry_labels` 
(`emission_source_id`, `industry_category_id`, `match_level`, `also_match_children`, `unit_type`, `user_friendly_name`, `user_friendly_description`, `common_equipment`, `typical_units`, `display_order`)
SELECT 
  es.id,
  3, -- Manufacturing (Level 1)
  1, -- Match Level 1
  1, -- Also match children (all manufacturing sub-categories)
  'Main Factory',
  'Natural Gas',
  'Natural gas used in manufacturing processes, boilers, and furnaces',
  'Boilers for hot water/steam, Furnaces, CHP systems',
  'm³, kWh',
  1
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'natural-gas'
LIMIT 1;

-- Food & Beverage - Restaurants (Level 3 - ID 132)
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
LIMIT 1;

-- Food & Beverage - Processing (Level 2 - ID 37) - applies to Food Processing and Beverage Production
INSERT INTO `emission_industry_labels` 
(`emission_source_id`, `industry_category_id`, `match_level`, `also_match_children`, `unit_type`, `user_friendly_name`, `user_friendly_description`, `common_equipment`, `typical_units`, `display_order`)
SELECT 
  es.id,
  37, -- Food & Beverage (Level 2)
  2, -- Match Level 2
  1, -- Also match children (Food Processing, Beverage Production)
  'Processing Plant',
  'Natural Gas',
  'Natural gas used in food processing operations',
  'Boilers, Processing equipment',
  'm³, kWh',
  1
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'natural-gas'
LIMIT 1;

-- Fuel/Diesel - Industry-specific labels
-- Manufacturing (Level 1 - ID 3)
INSERT INTO `emission_industry_labels` 
(`emission_source_id`, `industry_category_id`, `match_level`, `also_match_children`, `unit_type`, `user_friendly_name`, `user_friendly_description`, `common_equipment`, `typical_units`, `display_order`)
SELECT 
  es.id,
  3, -- Manufacturing (Level 1)
  1,
  1,
  'Main Factory',
  'Diesel Generators',
  'Diesel used in backup generators and industrial equipment',
  'Backup generators, Heavy machinery',
  'Liters, kg',
  2
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'fuel'
LIMIT 1;

-- ICT - IT Services (Level 3 - ID 91) - for office buildings
INSERT INTO `emission_industry_labels` 
(`emission_source_id`, `industry_category_id`, `match_level`, `also_match_children`, `unit_type`, `user_friendly_name`, `user_friendly_description`, `common_equipment`, `typical_units`, `display_order`)
SELECT 
  es.id,
  91, -- IT Services (Level 3)
  3,
  0,
  'Office Building',
  'Backup Generators',
  'Diesel used in backup generators for office buildings',
  'Backup generators',
  'Liters',
  1
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'fuel'
LIMIT 1;

-- ICT - Data Centers (Level 3 - ID 92)
INSERT INTO `emission_industry_labels` 
(`emission_source_id`, `industry_category_id`, `match_level`, `also_match_children`, `unit_type`, `user_friendly_name`, `user_friendly_description`, `common_equipment`, `typical_units`, `display_order`)
SELECT 
  es.id,
  92, -- Data Centers (Level 3)
  3,
  0,
  'Data Center',
  'Backup Generators',
  'Diesel used in backup generators for data centers',
  'Backup generators',
  'Liters',
  1
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'fuel'
LIMIT 1;

-- Vehicle - Industry-specific labels
-- Manufacturing (Level 1 - ID 3)
INSERT INTO `emission_industry_labels` 
(`emission_source_id`, `industry_category_id`, `match_level`, `also_match_children`, `unit_type`, `user_friendly_name`, `user_friendly_description`, `common_equipment`, `typical_units`, `display_order`)
SELECT 
  es.id,
  3, -- Manufacturing (Level 1)
  1,
  1,
  'Main Factory',
  'Company Vehicles',
  'Company cars, vans, and trucks used for business operations',
  'Car/SUV/Truck, Petrol/Diesel/LPG',
  'km, Liters',
  3
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'vehicle'
LIMIT 1;

-- ICT - IT Services (Level 3 - ID 91) - for office buildings
INSERT INTO `emission_industry_labels` 
(`emission_source_id`, `industry_category_id`, `match_level`, `also_match_children`, `unit_type`, `user_friendly_name`, `user_friendly_description`, `common_equipment`, `typical_units`, `display_order`)
SELECT 
  es.id,
  91, -- IT Services (Level 3)
  3,
  0,
  'Office Building',
  'Company Cars',
  'Company cars used by employees for business travel',
  'Car/SUV, Petrol/Diesel',
  'km, Liters',
  1
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'vehicle'
LIMIT 1;

-- Restaurants & Food Services (Level 3 - ID 132)
INSERT INTO `emission_industry_labels` 
(`emission_source_id`, `industry_category_id`, `match_level`, `also_match_children`, `unit_type`, `user_friendly_name`, `user_friendly_description`, `common_equipment`, `typical_units`, `display_order`)
SELECT 
  es.id,
  132, -- Restaurants & Food Services (Level 3)
  3,
  0,
  'Restaurant/Kitchen',
  'Delivery Vehicles',
  'Vehicles used for food delivery',
  'Car/Van, Petrol/Diesel',
  'km, Liters',
  1
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'vehicle'
LIMIT 1;

-- Manufacturing - Warehouse context (Level 1 - ID 3, but specific to warehouse operations)
INSERT INTO `emission_industry_labels` 
(`emission_source_id`, `industry_category_id`, `match_level`, `also_match_children`, `unit_type`, `user_friendly_name`, `user_friendly_description`, `common_equipment`, `typical_units`, `display_order`)
SELECT 
  es.id,
  3, -- Manufacturing (Level 1)
  1,
  1,
  'Warehouse',
  'Forklifts',
  'Forklifts and material handling equipment',
  'LPG/Diesel/Electric',
  'kg, Liters, km',
  1
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'vehicle'
LIMIT 1;

-- Refrigerants - Industry-specific labels
-- ICT - IT Services (Level 3 - ID 91) - for office buildings
INSERT INTO `emission_industry_labels` 
(`emission_source_id`, `industry_category_id`, `match_level`, `also_match_children`, `unit_type`, `user_friendly_name`, `user_friendly_description`, `common_equipment`, `typical_units`, `display_order`)
SELECT 
  es.id,
  91, -- IT Services (Level 3)
  3,
  0,
  'Office Building',
  'Refrigerants (AC)',
  'Refrigerant gases used in office air conditioning systems',
  'AC units, HVAC systems',
  'kg',
  2
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'refrigerants'
LIMIT 1;

-- ICT - Data Centers (Level 3 - ID 92)
INSERT INTO `emission_industry_labels` 
(`emission_source_id`, `industry_category_id`, `match_level`, `also_match_children`, `unit_type`, `user_friendly_name`, `user_friendly_description`, `common_equipment`, `typical_units`, `display_order`)
SELECT 
  es.id,
  92, -- Data Centers (Level 3)
  3,
  0,
  'Data Center',
  'Refrigerants (Cooling)',
  'Refrigerant gases used in data center cooling systems',
  'Chillers, Cooling systems',
  'kg',
  1
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'refrigerants'
LIMIT 1;

-- Restaurants & Food Services (Level 3 - ID 132)
INSERT INTO `emission_industry_labels` 
(`emission_source_id`, `industry_category_id`, `match_level`, `also_match_children`, `unit_type`, `user_friendly_name`, `user_friendly_description`, `common_equipment`, `typical_units`, `display_order`)
SELECT 
  es.id,
  132, -- Restaurants & Food Services (Level 3)
  3,
  0,
  'Restaurant/Kitchen',
  'Refrigerators/AC',
  'Refrigerant gases used in restaurant refrigeration and AC systems',
  'Refrigerators, Freezers, AC units',
  'kg',
  2
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'refrigerants'
LIMIT 1;

-- Food & Beverage - Processing (Level 2 - ID 37) - for storage facilities
INSERT INTO `emission_industry_labels` 
(`emission_source_id`, `industry_category_id`, `match_level`, `also_match_children`, `unit_type`, `user_friendly_name`, `user_friendly_description`, `common_equipment`, `typical_units`, `display_order`)
SELECT 
  es.id,
  37, -- Food & Beverage (Level 2)
  2,
  1, -- Also match children (Food Processing, Beverage Production)
  'Storage Facility',
  'Refrigerants',
  'Refrigerant gases used in cold storage facilities',
  'Cold storage units, Freezers',
  'kg',
  1
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'refrigerants'
LIMIT 1;

-- 3.7 Insert Unit Conversions
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

SET FOREIGN_KEY_CHECKS=1;

-- ============================================================================
-- END OF SQL SCRIPT
-- ============================================================================

