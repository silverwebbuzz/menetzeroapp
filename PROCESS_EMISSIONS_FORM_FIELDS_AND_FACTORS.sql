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

