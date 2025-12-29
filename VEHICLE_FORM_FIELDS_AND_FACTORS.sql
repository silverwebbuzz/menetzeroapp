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
