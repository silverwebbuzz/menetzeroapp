-- =====================================================
-- Quick Input Feature - Final MySQL Queries
-- Based on existing database structure
-- =====================================================

-- =====================================================
-- 1. Add missing columns to measurement_data table
-- =====================================================
-- Note: Your measurement_data table already has:
-- - entry_date, fuel_type, vehicle_type, gas_type
-- - co2_emissions, ch4_emissions, n2o_emissions
-- - gwp_version_used, emission_factor_id
-- - created_by, updated_by
--
-- We need to add:
-- - calculated_co2e (for total CO2e)
-- - scope (Scope 1, 2, or 3)
-- - quantity (direct column, not just in field_value)
-- - unit (direct column, not just in field_value)

-- Add calculated_co2e column
ALTER TABLE `measurement_data` 
ADD COLUMN `calculated_co2e` DECIMAL(15, 4) NULL DEFAULT NULL COMMENT 'Total CO2e calculated value' AFTER `n2o_emissions`;

-- Add scope column
ALTER TABLE `measurement_data` 
ADD COLUMN `scope` ENUM('Scope 1', 'Scope 2', 'Scope 3') NULL DEFAULT NULL COMMENT 'Emission scope' AFTER `calculated_co2e`;

-- Add quantity column (direct column for Quick Input)
ALTER TABLE `measurement_data` 
ADD COLUMN `quantity` DECIMAL(15, 4) NULL DEFAULT NULL COMMENT 'Quantity value for Quick Input entries' AFTER `scope`;

-- Add unit column (direct column for Quick Input)
ALTER TABLE `measurement_data` 
ADD COLUMN `unit` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Unit of measurement (kWh, liters, kg, etc.)' AFTER `quantity`;

-- Add notes column (if it doesn't exist)
ALTER TABLE `measurement_data` 
ADD COLUMN `notes` TEXT NULL DEFAULT NULL COMMENT 'Additional notes' AFTER `unit`;

-- Add additional_data column (JSON for storing form field data)
ALTER TABLE `measurement_data` 
ADD COLUMN `additional_data` JSON NULL DEFAULT NULL COMMENT 'Additional form field data as JSON' AFTER `notes`;

-- Add is_offset column (boolean for offset tracking)
ALTER TABLE `measurement_data` 
ADD COLUMN `is_offset` TINYINT(1) NULL DEFAULT 0 COMMENT 'Whether this emission is offset' AFTER `additional_data`;

-- =====================================================
-- 2. Add indexes for better performance
-- =====================================================

-- Index on measurement_id and scope for faster scope queries
CREATE INDEX `idx_measurement_data_scope` ON `measurement_data` (`measurement_id`, `scope`);

-- Index on scope alone for filtering
CREATE INDEX `idx_measurement_data_scope_only` ON `measurement_data` (`scope`);

-- =====================================================
-- 3. Add foreign key constraints (if not already exist)
-- =====================================================

-- Foreign key for emission_factor_id (if constraint doesn't exist)
-- Check first: SHOW CREATE TABLE measurement_data;
-- If constraint doesn't exist, uncomment below:
-- ALTER TABLE `measurement_data` 
-- ADD CONSTRAINT `measurement_data_emission_factor_id_foreign` 
-- FOREIGN KEY (`emission_factor_id`) REFERENCES `emission_factors` (`id`) ON DELETE SET NULL;

-- =====================================================
-- Notes:
-- =====================================================
-- 1. The measurements table already has all required columns:
--    - total_co2e, scope_1_co2e, scope_2_co2e, scope_3_co2e
--    - co2e_calculated_at
--    No changes needed for measurements table!
--
-- 2. If you get "Duplicate column name" errors, that column already exists - skip it
--
-- 3. If you get "Duplicate key name" errors, that index already exists - skip it
--
-- 4. The measurement_data table structure supports both:
--    - Old key-value structure (field_name, field_value)
--    - New Quick Input structure (quantity, unit, calculated_co2e, scope)
--
-- 5. After running these queries, the Quick Input feature will work!

