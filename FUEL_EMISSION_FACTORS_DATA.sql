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

