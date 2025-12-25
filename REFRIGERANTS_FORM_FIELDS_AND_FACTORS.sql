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

