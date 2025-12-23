# Database Design Recommendations for Emission Factors
## Based on IPCC, DEFRA, and Expert Data Analysis

---

## 📊 **Data Structure Analysis**

### **Key Findings from Excel Files:**

1. **GWPs.xlsx**: Global Warming Potentials (AR6) for various gases
2. **Stationary Combustion_1_DEFRA.xlsx**: Fuel combustion factors (Gaseous, Liquid, Solid fuels)
3. **Mobile Combustion_2_DEFRA.xlsx**: Vehicle emission factors by fuel type and vehicle size
4. **Fugitive emission_3_DEFRA.xlsx**: Refrigerant gas emission factors
5. **Scope 2.xlsx**: Electricity, Steam, Heat, Cooling factors (UAE-specific)
6. **Process Emission/**: 23+ industrial process files with IPCC category codes

---

## 🗄️ **Recommended Database Structure**

### **1. `emission_sources_master` Table - ENHANCED**

**Current Structure:**
- id, name, description, scope, category, subcategory, type, is_active

**Required Additions:**

```sql
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
```

---

### **2. `emission_factors` Table - ENHANCED**

**Current Structure:**
- id, emission_source_id, factor_value, unit, calculation_method, region, valid_from, valid_to, is_active, description, calculation_formula

**Required Additions for Multi-Gas Support:**

```sql
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

-- Update existing factor_value to be total_co2e_factor
-- Keep factor_value for backward compatibility

CREATE INDEX `idx_fuel_type` ON `emission_factors` (`fuel_type`, `fuel_category`);
CREATE INDEX `idx_vehicle_type` ON `emission_factors` (`vehicle_type`, `vehicle_size`);
CREATE INDEX `idx_source_standard` ON `emission_factors` (`source_standard`, `region`);
CREATE INDEX `idx_default_priority` ON `emission_factors` (`is_default`, `priority` DESC);
```

**Why Multi-Gas Factors?**
- DEFRA data provides separate CO2, CH4, N2O factors
- Allows recalculation if GWP values change
- Better transparency and accuracy

---

### **3. `gwp_values` Table - NEW**

**Purpose:** Store Global Warming Potentials for different gases and GWP versions

```sql
CREATE TABLE `gwp_values` (
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
```

**Data to Import:**
- All gases from GWPs.xlsx (CO2, CH4, N2O, HFCs, PFCs, SF6, NF3, etc.)
- Support AR6 (current) and potentially AR4/AR5 for historical calculations

---

### **4. `emission_source_form_fields` Table - ENHANCED**

**Current Structure (from model):**
- id, emission_source_id, field_name, field_type, field_label, field_placeholder, field_options, is_required, field_order, validation_rules, help_text

**Required Additions for Conditional Logic:**

```sql
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
  `conditional_logic` JSON NULL COMMENT 'Show/hide rules: {"depends_on": "field_name", "show_if": "value", "hide_if": "value"}',
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
```

**Example Conditional Logic:**
```json
{
  "depends_on": "fuel_known",
  "show_if": "Yes",
  "hide_if": "No",
  "fields_to_show": ["fuel_type", "unit_of_measure", "amount"]
}
```

---

### **5. `unit_conversions` Table - NEW**

**Purpose:** Store unit conversion factors (e.g., litres to tonnes, kWh to cubic metres)

```sql
CREATE TABLE `unit_conversions` (
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
```

**Examples:**
- Natural Gas: cubic metres → kWh (Net CV): ~10.55
- Natural Gas: cubic metres → tonnes: ~0.000717
- Diesel: litres → tonnes: ~0.000835

---

### **6. `measurement_data` Table - MODIFIED**

**Current Constraint Issue:**
- `UNIQUE(measurement_id, emission_source_id)` prevents multiple entries

**Required Change:**

```sql
-- Remove unique constraint to allow multiple entries
ALTER TABLE `measurement_data`
DROP INDEX `unique_measurement_source`;

-- Add new index (non-unique)
CREATE INDEX `idx_measurement_source` ON `measurement_data` (`measurement_id`, `emission_source_id`);

-- Add fields to distinguish entries
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
ADD COLUMN `supplier_emission_factor` DECIMAL(15,6) NULL COMMENT 'Supplier-specific factor (if used)';

CREATE INDEX `idx_entry_date` ON `measurement_data` (`entry_date`);
CREATE INDEX `idx_fuel_type` ON `measurement_data` (`fuel_type`);
CREATE INDEX `idx_emission_factor` ON `measurement_data` (`emission_factor_id`);
```

---

### **7. `emission_factor_selection_rules` Table - NEW**

**Purpose:** Define rules for selecting the best emission factor based on criteria

```sql
CREATE TABLE `emission_factor_selection_rules` (
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
```

**Example Rule:**
```json
{
  "region": "UAE",
  "fuel_type": "Natural Gas",
  "unit": "kWh (Net CV)",
  "prefer_supplier_specific": true
}
```

---

## 📋 **Data Mapping Strategy**

### **Quick Input Menu → Emission Sources:**

| Quick Input Menu | Emission Source Name | Scope | IPCC Code | Fuel Category |
|-----------------|---------------------|-------|-----------|---------------|
| Natural Gas | Natural Gas (Stationary) | Scope 1 | - | Gaseous |
| Fuel | Fuel (Stationary) | Scope 1 | - | Liquid/Gaseous/Solid |
| Vehicle | Vehicle (Mobile) | Scope 1 | - | Mobile Combustion |
| Refrigerants | Refrigerants (Fugitive) | Scope 1 | - | Fugitive |
| Process | Process Emissions | Scope 1 | 2.A.2, 2.B.1, etc. | Process |
| Electricity | Electricity (Purchased) | Scope 2 | - | Electricity |
| Heat, Steam & Cooling | Heat/Steam/Cooling | Scope 2 | - | Energy |
| Flights | Business Travel (Flights) | Scope 3 | - | Travel |
| Public Transport | Employee Commuting | Scope 3 | - | Commuting |
| Home Workers | Remote Work | Scope 3 | - | Remote Work |

---

## 🔄 **Calculation Logic**

### **For Combustion Sources (Stationary/Mobile):**

```
Total CO2e = (CO2_factor × quantity) + 
             (CH4_factor × quantity × CH4_GWP) + 
             (N2O_factor × quantity × N2O_GWP)
```

### **For Process Emissions:**

```
Total CO2e = (Process_EF × quantity) + 
             (CH4_EF × quantity × CH4_GWP) + 
             (N2O_EF × quantity × N2O_GWP)
```

### **For Fugitive Emissions (Refrigerants):**

```
Total CO2e = (Gas_quantity × Gas_GWP)
```

### **For Scope 2 (Electricity):**

```
Total CO2e = (Electricity_EF × kWh_used)
```

---

## 🎯 **Implementation Priority**

### **Phase 1: Core Tables**
1. ✅ Enhance `emission_sources_master` (Quick Input fields)
2. ✅ Enhance `emission_factors` (multi-gas support)
3. ✅ Create `gwp_values` table
4. ✅ Create `emission_source_form_fields` table
5. ✅ Modify `measurement_data` (remove unique constraint)

### **Phase 2: Advanced Features**
6. Create `unit_conversions` table
7. Create `emission_factor_selection_rules` table
8. Implement factor selection logic

### **Phase 3: Data Import**
9. Import GWP values from GWPs.xlsx
10. Import emission factors from DEFRA files
11. Import process emission factors
12. Map Quick Input menu items to emission sources

---

## ❓ **Questions for Decision**

1. **GWP Version:** Use AR6 (latest) or support multiple versions?
2. **Unit Conversions:** Store in database or calculate on-the-fly?
3. **Factor Selection:** Automatic (by priority) or manual selection?
4. **Supplier-Specific Factors:** Allow users to override default factors?
5. **Historical Data:** Support recalculations with different GWP versions?

---

## 📝 **Next Steps**

1. Review and approve this database design
2. Create migration files for all changes
3. Create seeders for:
   - GWP values
   - Emission sources (Quick Input mapping)
   - Default emission factors
   - Form fields for each source
4. Implement factor selection logic
5. Test with sample data

---

**End of Recommendations**

