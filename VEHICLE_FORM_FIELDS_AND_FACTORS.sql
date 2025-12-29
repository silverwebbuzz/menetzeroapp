-- ============================================================================
-- VEHICLE (MOBILE COMBUSTION) FORM FIELDS AND EMISSION FACTORS
-- ============================================================================
-- This file contains form fields and emission factors for Vehicle (Mobile combustion)
-- Form has conditional logic based on whether user knows fuel amount or not
--
-- Flow 1: User knows fuel amount (Yes)
--   -> Type of Fuel (Petrol/Diesel) -> Unit (litres/tonnes) -> Amount
--
-- Flow 2: User doesn't know fuel amount (No)
--   -> Vehicle Size (Small/Medium/Large car) -> Vehicle Fuel Type -> Unit (km/miles) -> Distance
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

-- 1.2 Do you know the amount of fuel used? (Yes/No) - This is the key conditional field
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `field_options`, `is_required`, `field_order`, `help_text`)
SELECT 
  es.id,
  'know_fuel_amount',
  'select',
  'Do you know the amount of fuel used?',
  'Select an option',
  JSON_ARRAY(
    JSON_OBJECT('value', 'Yes', 'label', 'Yes'),
    JSON_OBJECT('value', 'No', 'label', 'No')
  ),
  1,
  2,
  'To enter the kWhs from electric vehicles please enter this in the electricity tab.'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'vehicle'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `field_options` = VALUES(`field_options`),
  `help_text` = VALUES(`help_text`);

-- 1.3 Type of Fuel / Vehicle (By Size) - Conditional field
-- If know_fuel_amount = 'Yes': Shows fuel types
-- If know_fuel_amount = 'No': Shows vehicle sizes
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `field_options`, `is_required`, `field_order`, `help_text`, `depends_on_field`, `depends_on_value`, `conditional_logic`)
SELECT 
  es.id,
  'fuel_or_vehicle_type',
  'select',
  'Type of Fuel / Vehicle (By Size)',
  'Select an option',
  -- Default options (will be updated dynamically via JavaScript based on know_fuel_amount)
  JSON_ARRAY(
    -- Fuel types (when know_fuel_amount = 'Yes')
    JSON_OBJECT('value', 'Petrol (average biofuel blend)', 'label', 'Petrol (average biofuel blend)'),
    JSON_OBJECT('value', 'Diesel (average biofuel blend)', 'label', 'Diesel (average biofuel blend)')
  ),
  1,
  3,
  'If you answered ''yes'' to the previous question, select the type of fuel used by the vehicle. If you answered ''no'' to the previous question, select the size of the vehicle used. Guidance on vehicle sizes can be found in the FAQs.',
  'know_fuel_amount',
  NULL, -- Will show different options based on know_fuel_amount value
  JSON_OBJECT(
    'show_when', JSON_OBJECT('know_fuel_amount', JSON_ARRAY('Yes', 'No')),
    'options_when_yes', JSON_ARRAY(
      JSON_OBJECT('value', 'Petrol (average biofuel blend)', 'label', 'Petrol (average biofuel blend)'),
      JSON_OBJECT('value', 'Diesel (average biofuel blend)', 'label', 'Diesel (average biofuel blend)')
    ),
    'options_when_no', JSON_ARRAY(
      JSON_OBJECT('value', 'Small car', 'label', 'Small car'),
      JSON_OBJECT('value', 'Medium car', 'label', 'Medium car'),
      JSON_OBJECT('value', 'Large car', 'label', 'Large car'),
      JSON_OBJECT('value', 'Average car', 'label', 'Average car'),
      JSON_OBJECT('value', 'Small Motorbike', 'label', 'Small Motorbike'),
      JSON_OBJECT('value', 'Medium Motorbike', 'label', 'Medium Motorbike'),
      JSON_OBJECT('value', 'Large Motorbike', 'label', 'Large Motorbike'),
      JSON_OBJECT('value', 'Average Motorbike', 'label', 'Average Motorbike')
    )
  )
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'vehicle'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `field_options` = VALUES(`field_options`),
  `help_text` = VALUES(`help_text`),
  `depends_on_field` = VALUES(`depends_on_field`),
  `conditional_logic` = VALUES(`conditional_logic`);

-- 1.4 Vehicle Fuel Type - Only shown when know_fuel_amount = 'No'
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `field_options`, `is_required`, `field_order`, `help_text`, `depends_on_field`, `depends_on_value`)
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
  0, -- Not required when fuel amount is known
  4,
  'Select the type of fuel used by the vehicle.',
  'know_fuel_amount',
  'No' -- Only show when user doesn't know fuel amount
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'vehicle'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `field_options` = VALUES(`field_options`),
  `help_text` = VALUES(`help_text`),
  `depends_on_field` = VALUES(`depends_on_field`),
  `depends_on_value` = VALUES(`depends_on_value`);

-- 1.5 Unit of Measure - Conditional based on know_fuel_amount
-- If know_fuel_amount = 'Yes': litres, tonnes
-- If know_fuel_amount = 'No': km, miles
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `field_options`, `is_required`, `field_order`, `help_text`, `depends_on_field`, `depends_on_value`, `conditional_logic`)
SELECT 
  es.id,
  'unit_of_measure',
  'select',
  'Unit of Measure',
  'Select an option',
  -- Default options (will be updated dynamically)
  JSON_ARRAY(
    JSON_OBJECT('value', 'litres', 'label', 'litres'),
    JSON_OBJECT('value', 'tonnes', 'label', 'tonnes')
  ),
  1,
  5,
  'Select the unit of measure for fuel amount or distance.',
  'know_fuel_amount',
  NULL,
  JSON_OBJECT(
    'show_when', JSON_OBJECT('know_fuel_amount', JSON_ARRAY('Yes', 'No')),
    'options_when_yes', JSON_ARRAY(
      JSON_OBJECT('value', 'litres', 'label', 'litres'),
      JSON_OBJECT('value', 'tonnes', 'label', 'tonnes')
    ),
    'options_when_no', JSON_ARRAY(
      JSON_OBJECT('value', 'km', 'label', 'km'),
      JSON_OBJECT('value', 'miles', 'label', 'miles')
    )
  )
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'vehicle'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `field_options` = VALUES(`field_options`),
  `help_text` = VALUES(`help_text`),
  `depends_on_field` = VALUES(`depends_on_field`),
  `conditional_logic` = VALUES(`conditional_logic`);

-- 1.6 Distance - Only shown when know_fuel_amount = 'No'
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`, `depends_on_field`, `depends_on_value`, `validation_rules`)
SELECT 
  es.id,
  'distance',
  'number',
  'Distance',
  'Enter distance',
  0, -- Not required when fuel amount is known
  6,
  'Distance travelled in the unit of measure specified above.',
  'know_fuel_amount',
  'No',
  JSON_OBJECT('min', 0, 'step', 'any')
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'vehicle'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `help_text` = VALUES(`help_text`),
  `depends_on_field` = VALUES(`depends_on_field`),
  `depends_on_value` = VALUES(`depends_on_value`),
  `validation_rules` = VALUES(`validation_rules`);

-- 1.7 Amount - Only shown when know_fuel_amount = 'Yes'
INSERT INTO `emission_source_form_fields` 
(`emission_source_id`, `field_name`, `field_type`, `field_label`, `field_placeholder`, 
 `is_required`, `field_order`, `help_text`, `depends_on_field`, `depends_on_value`, `validation_rules`)
SELECT 
  es.id,
  'amount',
  'number',
  'Amount',
  'Enter amount',
  0, -- Not required when distance is used
  6,
  'Amount of fuel used in the unit of measure specified above.',
  'know_fuel_amount',
  'Yes',
  JSON_OBJECT('min', 0, 'step', 'any')
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'vehicle'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `help_text` = VALUES(`help_text`),
  `depends_on_field` = VALUES(`depends_on_field`),
  `depends_on_value` = VALUES(`depends_on_value`),
  `validation_rules` = VALUES(`validation_rules`);

-- 1.8 Link field (Additional Data)
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
  7,
  'Link to supporting documents'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'vehicle'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `help_text` = VALUES(`help_text`);

-- 1.9 Comments field (Additional Data)
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
  8,
  'Additional comments or notes'
FROM `emission_sources_master` es
WHERE es.quick_input_slug = 'vehicle'
LIMIT 1
ON DUPLICATE KEY UPDATE 
  `help_text` = VALUES(`help_text`);

-- ============================================================================
-- PART 2: EMISSION FACTORS FOR VEHICLES
-- ============================================================================
-- Emission factors for mobile combustion based on DEFRA 2024
-- Two types:
-- 1. Fuel-based factors (when user knows fuel amount): litres/tonnes -> CO2e
-- 2. Distance-based factors (when user doesn't know fuel amount): km/miles -> CO2e (by vehicle size and fuel type)

-- 2.1 Fuel-based emission factors (Petrol and Diesel in litres/tonnes)
-- These are used when know_fuel_amount = 'Yes'
INSERT INTO `emission_factors` 
(`emission_source_id`, `factor_value`, `total_co2e_factor`, `unit`, `calculation_method`, `region`, 
 `valid_from`, `valid_to`, `is_active`, `description`, `fuel_type`, `fuel_category`, 
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
  CONCAT('CO2e emission factor for ', ef_data.fuel_type, ' in ', ef_data.unit, '. Multi-gas calculation using DEFRA 2024 factors.'),
  ef_data.fuel_type,
  'Liquid fuels',
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
  -- Petrol (average biofuel blend) - litres
  SELECT 'Petrol (average biofuel blend)' as fuel_type, 'litres' as unit, 
         2.313100 as co2_factor, 0.000000 as ch4_factor, 0.000000 as n2o_factor, 
         2.313100 as total_co2e, 1 as is_default, 100 as priority
  UNION ALL
  -- Petrol (average biofuel blend) - tonnes
  SELECT 'Petrol (average biofuel blend)', 'tonnes', 
         3125.000000, 0.000000, 0.000000, 
         3125.000000, 0, 90
  UNION ALL
  -- Diesel (average biofuel blend) - litres
  SELECT 'Diesel (average biofuel blend)', 'litres', 
         2.679200 as co2_factor, 0.000000 as ch4_factor, 0.000000 as n2o_factor, 
         2.679200 as total_co2e, 1 as is_default, 100 as priority
  UNION ALL
  -- Diesel (average biofuel blend) - tonnes
  SELECT 'Diesel (average biofuel blend)', 'tonnes', 
         3169.000000, 0.000000, 0.000000, 
         3169.000000, 0, 90
) ef_data
WHERE es.quick_input_slug = 'vehicle'
ON DUPLICATE KEY UPDATE 
  `factor_value` = VALUES(`factor_value`),
  `total_co2e_factor` = VALUES(`total_co2e_factor`),
  `co2_factor` = VALUES(`co2_factor`),
  `ch4_factor` = VALUES(`ch4_factor`),
  `n2o_factor` = VALUES(`n2o_factor`),
  `description` = VALUES(`description`);

-- 2.2 Distance-based emission factors (by vehicle size and fuel type)
-- These are used when know_fuel_amount = 'No'
-- Factors are in kg CO2e per km or per mile
-- Based on DEFRA 2024 average emission factors by vehicle size
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
  -- Cars - Petrol
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
  -- Cars - Diesel
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
  -- Motorbikes - Petrol
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

