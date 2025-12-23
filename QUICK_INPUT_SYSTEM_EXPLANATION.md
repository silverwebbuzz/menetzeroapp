# Quick Input System - How It Works

## 📋 **Overview**

The Quick Input system allows users to quickly enter emission data for different sources (Natural Gas, Fuel, Vehicles, etc.) with automatic calculation of CO2e emissions using IPCC and DEFRA emission factors.

---

## 🗄️ **Master Tables Used**

### **1. `emission_sources_master`**
**Purpose:** Defines all emission source types (Natural Gas, Fuel, Vehicles, etc.)

**Key Fields:**
- `id` - Unique identifier
- `name` - Display name (e.g., "Natural Gas (Stationary combustion)")
- `scope` - Scope 1, 2, or 3
- `quick_input_slug` - URL identifier (e.g., "natural-gas")
- `is_quick_input` - Whether it appears in Quick Input menu
- `quick_input_order` - Display order in menu
- `instructions` - Form instructions text
- `emission_type` - Type: combustion, process, fugitive, electricity, other
- `default_unit` - Default unit for this source

**Example Record:**
```sql
id: 1
name: "Natural Gas (Stationary combustion)"
scope: "Scope 1"
quick_input_slug: "natural-gas"
is_quick_input: 1
quick_input_order: 1
instructions: "Enter the amount of energy used..."
emission_type: "combustion"
default_unit: "cubic metres"
```

---

### **2. `emission_factors`**
**Purpose:** Stores emission factors for calculating CO2e from activity data

**Key Fields:**
- `id` - Unique identifier
- `emission_source_id` - Links to `emission_sources_master`
- `factor_value` - Total CO2e factor (kg CO2e per unit) - **backward compatibility**
- `total_co2e_factor` - Total CO2e factor (kg CO2e per unit) - **new field**
- `unit` - Unit of measure (e.g., "cubic metres", "kWh", "litres", "km")
- `region` - Region (e.g., "UAE", "UK", "Global")
- `fuel_type` - Fuel type (e.g., "Natural gas", "Diesel", "Petrol")
- `fuel_category` - Category (e.g., "Gaseous", "Liquid", "Solid")
- `co2_factor` - CO2 emission factor (kg CO2 per unit)
- `ch4_factor` - CH4 emission factor (kg CH4 per unit)
- `n2o_factor` - N2O emission factor (kg N2O per unit)
- `source_standard` - Source (DEFRA, IPCC, UAE, MOCCAE, USEPA, Custom)
- `gwp_version` - GWP version used (AR4, AR5, AR6)
- `is_default` - Default factor for this source/region
- `priority` - Selection priority (higher = preferred)

**Example Record:**
```sql
id: 101
emission_source_id: 1 (Natural Gas)
total_co2e_factor: 2.066946036
unit: "cubic metres"
region: "UAE"
fuel_type: "Natural gas"
fuel_category: "Gaseous"
co2_factor: 2.0627
ch4_factor: 0.00307
n2o_factor: 0.00095
source_standard: "DEFRA"
gwp_version: "AR6"
is_default: 1
priority: 100
```

---

### **3. `emission_gwp_values`**
**Purpose:** Stores Global Warming Potential (GWP) values for different gases

**Key Fields:**
- `id` - Unique identifier
- `gas_name` - Gas name (e.g., "CO₂", "CH₄ (fossil)", "N₂O", "HFC-134a")
- `gas_code` - Gas code (e.g., "CO2", "CH4_FOSSIL", "N2O", "HFC134A")
- `gwp_version` - IPCC version (AR4, AR5, AR6)
- `gwp_100_year` - 100-year GWP value
- `is_kyoto_protocol` - Whether it's a Kyoto Protocol gas

**Example Record:**
```sql
id: 1
gas_name: "CH₄ (fossil)"
gas_code: "CH4_FOSSIL"
gwp_version: "AR6"
gwp_100_year: 27.20
is_kyoto_protocol: 1
```

**Usage:** Used to convert CH4 and N2O emissions to CO2e:
- CH4 emissions (kg) × CH4_GWP = CH4 CO2e (kg)
- N2O emissions (kg) × N2O_GWP = N2O CO2e (kg)

**Note:** Table renamed from `gwp_values` to `emission_gwp_values` for consistency with other emission-related tables.

---

### **4. `emission_source_form_fields`**
**Purpose:** Defines form fields for each emission source (dynamic forms)

**Key Fields:**
- `id` - Unique identifier
- `emission_source_id` - Links to `emission_sources_master`
- `field_name` - Field identifier (e.g., "unit_of_measure", "amount")
- `field_type` - Type: text, number, select, textarea, date, checkbox, radio
- `field_label` - Display label
- `field_options` - JSON array of options for select/radio fields
- `is_required` - Whether field is required
- `field_order` - Display order
- `conditional_logic` - JSON for show/hide rules
- `depends_on_field` - Field this depends on
- `depends_on_value` - Value that triggers this field

**Example Record:**
```sql
id: 1
emission_source_id: 1 (Natural Gas)
field_name: "unit_of_measure"
field_type: "select"
field_label: "Unit of Measure"
field_options: [{"value": "tonnes", "label": "tonnes"}, {"value": "kWh (Net CV)", "label": "kWh (Net CV)"}, ...]
is_required: 1
field_order: 1
```

---

### **5. `emission_unit_conversions`**
**Purpose:** Stores unit conversion factors (e.g., cubic metres to kWh)

**Note:** Table renamed from `unit_conversions` to `emission_unit_conversions` for consistency with other emission-related tables.

**Key Fields:**
- `id` - Unique identifier
- `from_unit` - Source unit (e.g., "cubic metres")
- `to_unit` - Target unit (e.g., "kWh (Net CV)")
- `conversion_factor` - Multiply from_unit by this to get to_unit
- `fuel_type` - Fuel-specific conversion (if applicable)
- `region` - Region-specific conversion (if applicable)

**Example Record:**
```sql
id: 1
from_unit: "cubic metres"
to_unit: "kWh (Net CV)"
conversion_factor: 10.55
fuel_type: "Natural gas"
region: "UAE"
```

---

### **6. `emission_industry_labels` Table - NEW**

**Purpose:** Provides industry-specific user-friendly naming for emission sources (Based on MSME Matrix)

**Key Fields:**
- `id` - Unique identifier
- `emission_source_id` - Links to `emission_sources_master`
- `industry_category_id` - Links to `master_industry_categories.id` (can be Level 1, 2, or 3)
- `match_level` - 1 = Sector (Level 1), 2 = Industry (Level 2), 3 = Subcategory (Level 3)
- `also_match_children` - If 1, rule also applies to all child categories
- `unit_type` - Unit type context (e.g., "Main Factory", "Office Building", "Restaurant/Kitchen", "Data Center", "Warehouse")
- `user_friendly_name` - User-friendly name for this emission source in this industry context
- `user_friendly_description` - Industry-specific description
- `common_equipment` - Common equipment/use cases for this industry
- `typical_units` - Typical units used in this industry context
- `display_order` - Display order within industry

**Example Records:**
```sql
-- For MANUFACTURING sector (Level 1)
id: 1
emission_source_id: 1 (Natural Gas)
industry_category_id: 3   -- Manufacturing (Level 1)
match_level: 1
also_match_children: 1
unit_type: "Main Factory"
user_friendly_name: "Natural Gas"
user_friendly_description: "Natural gas used in manufacturing processes, boilers, and furnaces"
common_equipment: "Boilers for hot water/steam, Furnaces, CHP systems"
typical_units: "m³, kWh"

-- For ICT / IT Services (Level 3)
id: 2
emission_source_id: 2 (Fuel)
industry_category_id: 91  -- IT Services (Level 3)
match_level: 3
also_match_children: 0
unit_type: "Office Building"
user_friendly_name: "Backup Generators"
user_friendly_description: "Diesel used in backup generators for office buildings"
common_equipment: "Backup generators"
typical_units: "Liters"

-- For FOOD & BEVERAGE / Restaurants (Level 3)
id: 3
emission_source_id: 3 (Vehicle)
industry_category_id: 132 -- Restaurants & Food Services (Level 3)
match_level: 3
also_match_children: 0
unit_type: "Restaurant/Kitchen"
user_friendly_name: "Delivery Vehicles"
user_friendly_description: "Vehicles used for food delivery"
common_equipment: "Car/Van, Petrol/Diesel"
typical_units: "km, Liters"
```

**How It Works:**
- Same emission source (e.g., "Fuel") can have different user-friendly names based on industry
- Examples:
  - "Fuel" → "Diesel Generators" (MANUFACTURING)
  - "Fuel" → "Backup Generators" (IT Services, Data Centers)
  - "Vehicle" → "Company Vehicles" (MANUFACTURING)
  - "Vehicle" → "Company Cars" (IT Services / Offices)
  - "Vehicle" → "Delivery Vehicles" (Food & Beverage)
  - "Vehicle" → "Forklifts" (Manufacturing - Warehouse)

**Benefits:**
- ✅ Better user experience - industry-specific terminology users understand
- ✅ Contextual help - shows relevant equipment examples for their industry
- ✅ Easier data entry - users see familiar terms instead of technical jargon
- ✅ Flexible - can add more industries/sectors as needed
- ✅ Based on MSME Matrix & `master_industry_categories`

---

### **7. `emission_factor_selection_rules` Table (Planned Use)**

**Purpose:** Optional rule layer to choose the *best* emission factor when multiple rows in `emission_factors` match a given Quick Input entry.

**Status (Important for Future Work):**
- The table is **created in `quick_input_system.sql` but left empty on purpose**.
- All current Quick Input flows select factors directly from `emission_factors` using filters plus `is_default` and `priority`.
- We will start populating this table **later**, when you want more advanced selection logic (e.g., different factors by Emirate, industry, or supplier).

**Planned Usage Examples:**
- “For Natural Gas in UAE with unit = `kWh (Net CV)`, prefer DEFRA 2024 factor.”
- “For Electricity in UAE, Data Centers (industry_category_id = 92) must use MOCCAE factor, others use DEWA factor.”
- “For a specific supplier, override the default factor when `additional_data.supplier = 'XYZ'`.”

**Key Fields:**
- `id` - Unique identifier
- `emission_source_id` - Links to `emission_sources_master`
- `rule_name` - Human-readable rule name
- `priority` - Higher = applied first
- `conditions` - JSON specifying when the rule applies (e.g., `{"region": "UAE", "unit": "kWh", "industry_category_id": 92}`)
- `emission_factor_id` - Specific factor to use when conditions match
- `is_active` - Whether the rule is active

**Note for Future Enhancements:**  
When you later ask to refine Quick Input factor selection (per Emirate, per industry, per supplier, etc.), we should **start from this table** (`emission_factor_selection_rules`) and add `INSERT` statements there, instead of changing the base schema or overwriting existing factors.

## 📊 **Where Measurements Are Stored**

### **1. `measurements` Table**
**Purpose:** Stores measurement periods (yearly periods for Quick Input)

**Key Fields:**
- `id` - Unique identifier
- `location_id` - Links to location
- `period_start` - Period start date (e.g., "2024-01-01")
- `period_end` - Period end date (e.g., "2024-12-31")
- `frequency` - "annually" (for Quick Input)
- `fiscal_year` - Year (e.g., 2024)
- `status` - Status: draft, submitted, verified, etc.
- `total_co2e` - Total CO2e for this measurement period
- `scope_1_co2e` - Scope 1 total
- `scope_2_co2e` - Scope 2 total
- `scope_3_co2e` - Scope 3 total

**Example Record:**
```sql
id: 1001
location_id: 5 (Dubai Office)
period_start: "2024-01-01"
period_end: "2024-12-31"
frequency: "annually"
fiscal_year: 2024
status: "draft"
total_co2e: 0.00 (calculated from measurement_data)
```

**How It's Created:**
- When user selects Year and Location in Quick Input header
- System checks if measurement period exists for that location/year
- If not, creates a new measurement record automatically
- If yes, uses existing measurement record

---

### **2. `measurement_data` Table**
**Purpose:** Stores individual emission entries (one row per Quick Input entry)

**Key Fields:**
- `id` - Unique identifier
- `measurement_id` - Links to `measurements` table
- `emission_source_id` - Links to `emission_sources_master` (e.g., Natural Gas)
- `quantity` - Amount entered by user (e.g., 1000 cubic metres)
- `unit` - Unit used (e.g., "cubic metres")
- `calculated_co2e` - Calculated CO2e emissions (kg)
- `scope` - Scope 1, 2, or 3
- `entry_date` - Date of this specific entry
- `fuel_type` - Fuel type used (if applicable)
- `vehicle_type` - Vehicle type (if applicable)
- `gas_type` - Refrigerant gas type (if applicable)
- `co2_emissions` - CO2 emissions (kg) - separate tracking
- `ch4_emissions` - CH4 emissions (kg) - separate tracking
- `n2o_emissions` - N2O emissions (kg) - separate tracking
- `emission_factor_id` - Links to `emission_factors` table (which factor was used)
- `gwp_version_used` - GWP version used for calculation (AR6)
- `additional_data` - JSON for form field values (unit_of_measure, link, comments, etc.)
- `notes` - Notes/comments

**Example Record:**
```sql
id: 5001
measurement_id: 1001 (2024 measurement for Dubai Office)
emission_source_id: 1 (Natural Gas)
quantity: 1000.0000
unit: "cubic metres"
calculated_co2e: 2066.9460 (1000 × 2.066946036)
scope: "Scope 1"
entry_date: "2024-03-15"
fuel_type: "Natural gas"
co2_emissions: 2062.7000 (1000 × 2.0627)
ch4_emissions: 0.0031 (1000 × 0.00307)
n2o_emissions: 0.0010 (1000 × 0.00095)
emission_factor_id: 101 (Natural Gas - cubic metres - UAE)
gwp_version_used: "AR6"
additional_data: {"unit_of_measure": "cubic metres", "link": "https://...", "comments": "..."}
```

**Important:** 
- **Multiple entries allowed** - Removed unique constraint on (measurement_id, emission_source_id)
- Each Quick Input "Add to Footprint" creates a new `measurement_data` record
- All entries for same source/year/location are stored separately

---

## 🔄 **How The System Works - Step by Step**

### **Step 1: User Selects Year & Location**
```
User selects:
- Year: 2024
- Location: Dubai Office
```

**System Action:**
1. Check if `measurements` record exists for location_id + fiscal_year = 2024
2. If not exists → Create new `measurements` record:
   ```sql
   INSERT INTO measurements (location_id, period_start, period_end, frequency, fiscal_year, status)
   VALUES (5, '2024-01-01', '2024-12-31', 'annually', 2024, 'draft')
   ```
3. Store measurement_id in session/state

---

### **Step 2: User Clicks "Natural Gas" from Quick Input Menu**
```
User clicks: "Natural Gas" menu item
```

**System Action:**
1. Query `emission_sources_master` WHERE `quick_input_slug` = 'natural-gas'
2. Get emission_source_id (e.g., 1)
3. Query `emission_source_form_fields` WHERE `emission_source_id` = 1
4. Build form dynamically from form fields
5. Display form with:
   - Unit of Measure dropdown (from field_options)
   - Amount input field
   - Link input field
   - Comments textarea

---

### **Step 3: User Enters Data and Submits**
```
User enters:
- Unit of Measure: "cubic metres"
- Amount: 1000
- Link: "https://sharepoint.com/bill.pdf"
- Comments: "January bill"
```

**System Action:**

**3.1 Find Emission Factor:**
```sql
SELECT * FROM emission_factors
WHERE emission_source_id = 1
  AND unit = 'cubic metres'
  AND region = 'UAE'
  AND is_active = 1
ORDER BY is_default DESC, priority DESC
LIMIT 1
```
Result: `emission_factor_id = 101` with:
- `total_co2e_factor = 2.066946036`
- `co2_factor = 2.0627`
- `ch4_factor = 0.00307`
- `n2o_factor = 0.00095`

**3.2 Get GWP Values:**
```sql
SELECT gwp_100_year FROM emission_gwp_values
WHERE gas_code = 'CH4_FOSSIL' AND gwp_version = 'AR6'
-- Result: 27.20

SELECT gwp_100_year FROM emission_gwp_values
WHERE gas_code = 'N2O' AND gwp_version = 'AR6'
-- Result: 273.00
```

**3.3 Calculate Emissions:**
```
CO2 emissions = 1000 × 2.0627 = 2062.7 kg CO2
CH4 emissions = 1000 × 0.00307 = 3.07 kg CH4
N2O emissions = 1000 × 0.00095 = 0.95 kg N2O

CH4 CO2e = 3.07 × 27.20 = 83.504 kg CO2e
N2O CO2e = 0.95 × 273.00 = 259.35 kg CO2e

Total CO2e = 2062.7 + 83.504 + 259.35 = 2405.554 kg CO2e
```

**3.4 Insert into measurement_data:**
```sql
INSERT INTO measurement_data (
  measurement_id,
  emission_source_id,
  quantity,
  unit,
  calculated_co2e,
  scope,
  entry_date,
  fuel_type,
  co2_emissions,
  ch4_emissions,
  n2o_emissions,
  emission_factor_id,
  gwp_version_used,
  additional_data,
  notes
) VALUES (
  1001,  -- measurement_id (from Step 1)
  1,     -- emission_source_id (Natural Gas)
  1000.0000,
  'cubic metres',
  2405.5540,  -- calculated total CO2e
  'Scope 1',
  '2024-03-15',  -- current date
  'Natural gas',
  2062.7000,  -- CO2
  3.0700,     -- CH4
  0.9500,     -- N2O
  101,        -- emission_factor_id used
  'AR6',
  '{"unit_of_measure": "cubic metres", "link": "https://sharepoint.com/bill.pdf", "comments": "January bill"}',
  'January bill'
)
```

**3.5 Update measurement totals:**
```sql
UPDATE measurements
SET 
  total_co2e = (
    SELECT SUM(calculated_co2e) 
    FROM measurement_data 
    WHERE measurement_id = 1001
  ),
  scope_1_co2e = (
    SELECT SUM(calculated_co2e) 
    FROM measurement_data 
    WHERE measurement_id = 1001 AND scope = 'Scope 1'
  ),
  scope_2_co2e = (
    SELECT SUM(calculated_co2e) 
    FROM measurement_data 
    WHERE measurement_id = 1001 AND scope = 'Scope 2'
  ),
  scope_3_co2e = (
    SELECT SUM(calculated_co2e) 
    FROM measurement_data 
    WHERE measurement_id = 1001 AND scope = 'Scope 3'
  )
WHERE id = 1001
```

---

### **Step 4: Display Results Table**
```
User sees Results table with all Natural Gas entries for 2024/Dubai Office
```

**System Action:**
```sql
SELECT 
  md.id,
  md.entry_date,
  md.quantity,
  md.unit,
  md.calculated_co2e,
  md.additional_data->>'$.comments' as comment,
  es.name as emission_source_name
FROM measurement_data md
JOIN emission_sources_master es ON md.emission_source_id = es.id
WHERE md.measurement_id = 1001
  AND md.emission_source_id = 1  -- Natural Gas
ORDER BY md.entry_date DESC, md.created_at DESC
```

**Result:**
| Date Added | Emissions (tCO₂e) | Details | Comment | Actions |
|------------|-------------------|---------|---------|---------|
| 2024-03-15 | 2.41 | 1000 cubic metres | January bill | Edit/Delete |

---

## 🔗 **Data Flow Diagram**

```
┌─────────────────┐
│ User Selects    │
│ Year & Location │
└────────┬────────┘
         │
         ▼
┌─────────────────┐      ┌──────────────────┐
│ measurements    │◄─────│ Check/Create     │
│ (Yearly Period) │      │ Measurement       │
└────────┬────────┘      └──────────────────┘
         │
         │ measurement_id
         ▼
┌─────────────────┐
│ User Clicks     │
│ "Natural Gas"   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐      ┌──────────────────┐
│ emission_       │◄─────│ Get Form Fields  │
│ sources_master  │      │ for Dynamic Form │
└────────┬────────┘      └──────────────────┘
         │
         │ emission_source_id
         ▼
┌─────────────────┐
│ User Enters     │
│ Data & Submits  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐      ┌──────────────────┐
│ emission_       │◄─────│ Find Best        │
│ factors         │      │ Emission Factor  │
└────────┬────────┘      └──────────────────┘
         │
         │ emission_factor_id
         ▼
┌─────────────────┐      ┌──────────────────┐
│ emission_gwp_   │◄─────│ Get GWP Values   │
│ values           │      │ for Calculation  │
│ (for CH4, N2O)   │      └──────────────────┘
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Calculate       │
│ CO2e Emissions  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ measurement_    │
│ data            │◄─────│ Store Entry      │
│ (Individual     │      │                  │
│  Entry)         │      │                  │
└────────┬────────┘      └──────────────────┘
         │
         ▼
┌─────────────────┐
│ Update          │
│ measurements    │◄─────│ Recalculate      │
│ totals          │      │ Totals           │
└─────────────────┘      └──────────────────┘
```

---

## 📝 **Summary**

### **Master Tables:**
1. **`emission_sources_master`** - Defines emission source types (Natural Gas, Fuel, etc.)
2. **`emission_factors`** - Stores emission factors for calculations
3. **`emission_gwp_values`** - Stores GWP values for gas conversions (renamed from `gwp_values`)
4. **`emission_source_form_fields`** - Defines dynamic form fields
5. **`emission_unit_conversions`** - Stores unit conversion factors (renamed from `unit_conversions`)
6. **`emission_industry_labels`** - Industry-specific user-friendly naming (NEW - based on MSME Matrix)

### **Measurement Storage:**
1. **`measurements`** - Stores yearly measurement periods (one per location/year)
2. **`measurement_data`** - Stores individual emission entries (multiple per measurement)

### **Key Points:**
- ✅ **Multiple entries allowed** - No unique constraint on measurement_data
- ✅ **Automatic calculation** - Uses emission factors and GWP values
- ✅ **Multi-gas tracking** - Separate CO2, CH4, N2O emissions stored
- ✅ **Dynamic forms** - Form fields defined in database
- ✅ **Yearly periods** - One measurement per location/year
- ✅ **Auto-update totals** - Measurement totals recalculated on each entry
- ✅ **Industry-specific naming** - User-friendly labels based on company industry (MSME Matrix)
- ✅ **Consistent naming** - All emission-related tables prefixed with `emission_`

---

**End of Explanation**

