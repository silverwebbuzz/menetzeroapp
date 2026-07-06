# Quick Input Feature - Client-Side Implementation Plan

## 📋 **Executive Summary**

This document outlines the comprehensive implementation plan for the **Quick Input** emission data entry feature on the client side. The Quick Input feature allows users to quickly enter emission data for Scope 1, 2, and 3 emissions through simplified, user-friendly forms, with automatic calculation of CO2e emissions using emission factors and GWP values.

---

## 🎯 **Objectives**

1. **Simplify Data Entry**: Provide intuitive, streamlined forms for common emission sources
2. **Automate Calculations**: Automatically calculate CO2e emissions using emission factors and GWP values
3. **Industry-Specific Naming**: Display user-friendly names based on company's industry category
4. **Real-time Validation**: Validate inputs and provide immediate feedback
5. **Seamless Integration**: Integrate with existing `measurements` and `measurement_data` tables
6. **Permission-Based Access**: Respect user permissions for viewing/adding measurements

---

## 🗂️ **Database Structure Overview**

### **Existing Tables (Already in Use)**
- `measurements` - Stores measurement periods (yearly periods)
- `measurement_data` - Stores individual emission entries
- `locations` - Company locations
- `companies` - Company information (includes industry category)

### **Master Tables (Populated by Admin)**
- `emission_sources_master` - Emission source definitions (Natural Gas, Fuel, Vehicle, etc.)
- `emission_factors` - Emission factors for calculations
- `emission_gwp_values` - Global Warming Potential values (AR4, AR5, AR6)
- `emission_unit_conversions` - Unit conversion factors
- `emission_industry_labels` - Industry-specific user-friendly names
- `emission_source_form_fields` - Dynamic form field definitions
- `emission_factor_selection_rules` - Rules for selecting appropriate emission factors

### **New Tables (May Need to Create)**
- `carbon_emissions` - Alternative storage for Quick Input entries (if separate from measurement_data)
- `carbon_calculations` - Calculation audit trail (optional)

---

## 🏗️ **System Architecture**

### **1. Navigation Structure**

```
Client Navigation Sidebar
├── Dashboard
├── Locations
├── Measurements
├── Quick Input (NEW)
│   ├── Scope 1
│   │   ├── Natural Gas
│   │   ├── Fuel
│   │   ├── Vehicle
│   │   ├── Refrigerants
│   │   └── Process
│   ├── Scope 2
│   │   ├── Electricity
│   │   └── Heat, Steam & Cooling
│   └── Scope 3
│       ├── Flights
│       ├── Public Transport
│       └── Home Workers
```

### **2. Route Structure**

```
GET  /quick-input/{scope}/{slug}           - Show Quick Input form
POST /quick-input/{scope}/{slug}           - Save Quick Input entry
GET  /quick-input/entries                  - List all Quick Input entries
GET  /quick-input/entries/{id}             - View entry details
PUT  /quick-input/entries/{id}             - Update entry
DELETE /quick-input/entries/{id}           - Delete entry
GET  /api/quick-input/factors               - Get emission factors (AJAX)
GET  /api/quick-input/units                 - Get available units (AJAX)
POST /api/quick-input/calculate            - Calculate CO2e (AJAX)
```

### **3. Controller Structure**

```
app/Http/Controllers/
├── QuickInputController.php               - Main controller
│   ├── index()                            - List all entries
│   ├── show($scope, $slug)               - Show form for specific source
│   ├── store(Request $request)            - Save entry
│   ├── update($id, Request $request)      - Update entry
│   ├── destroy($id)                       - Delete entry
│   └── calculate(Request $request)         - AJAX calculation endpoint
```

---

## 📝 **User Flow**

### **Flow 1: Creating a New Quick Input Entry**

```
1. User clicks "Quick Input" → "Scope 1" → "Natural Gas"
   ↓
2. System checks:
   - User has permission to add measurements?
   - Company has active locations?
   ↓
3. If no location/year selected:
   - Show header: "Select Year" + "Select Location" dropdowns
   - User selects Year (e.g., 2024) and Location (e.g., "Dubai Office")
   ↓
4. System creates/finds measurement record:
   - Check if measurement exists for location_id + fiscal_year
   - If not exists → Create new measurement record
   - Store measurement_id in session/state
   ↓
5. Load Quick Input form:
   - Query emission_sources_master WHERE quick_input_slug = 'natural-gas'
   - Query emission_source_form_fields for dynamic form fields
   - Query emission_industry_labels for user-friendly name (based on company's industry)
   - Display form with fields (quantity, unit, fuel_type, etc.)
   ↓
6. User fills form:
   - Quantity: 1000
   - Unit: "cubic metres"
   - Fuel Type: "Natural gas"
   - Entry Date: "2024-03-15"
   - Comments: "Main office heating"
   ↓
7. User clicks "Calculate" or "Add to Footprint":
   - AJAX call to /api/quick-input/calculate
   - System selects appropriate emission factor:
     * Query emission_factor_selection_rules
     * Match conditions (region, fuel_type, unit, etc.)
     * Select factor with highest priority
   - Calculate CO2e:
     * quantity × emission_factor = CO2e
     * If multi-gas (CO2, CH4, N2O):
       - CO2_emissions = quantity × co2_factor
       - CH4_emissions = quantity × ch4_factor
       - N2O_emissions = quantity × n2o_factor
       - Apply GWP values (AR6):
         * CO2e = CO2 + (CH4 × GWP_CH4) + (N2O × GWP_N2O)
   - Return calculated values to frontend
   ↓
8. User reviews calculation and clicks "Add to Footprint":
   - POST to /quick-input/{scope}/{slug}
   - Create measurement_data record:
     * measurement_id
     * emission_source_id
     * quantity, unit
     * calculated_co2e
     * co2_emissions, ch4_emissions, n2o_emissions
     * emission_factor_id (which factor was used)
     * gwp_version_used
     * additional_data (JSON: form field values)
   - Update measurement totals:
     * Recalculate scope totals
     * Update measurement.scope_1_co2e, scope_2_co2e, scope_3_co2e
     * Update measurement.total_co2e
   ↓
9. Show success message and redirect to entries list or form reset
```

### **Flow 2: Viewing Quick Input Entries**

```
1. User clicks "Quick Input" → "View Entries" (or from dashboard)
   ↓
2. System loads entries:
   - Query measurement_data WHERE measurement_id IN (user's measurements)
   - Filter by scope if selected
   - Group by emission source
   - Show summary cards (total CO2e, by scope, by source)
   ↓
3. Display entries in table:
   - Date, Source, Location, Quantity, Unit, CO2e, Actions
   - Filter by: Year, Location, Scope, Source
   - Sort by: Date, CO2e, Source
   ↓
4. User can:
   - View details (modal or page)
   - Edit entry
   - Delete entry
   - Export to CSV/Excel
```

---

## 🔧 **Technical Implementation Steps**

### **Phase 1: Foundation Setup**

#### **Step 1.1: Create Models**
- ✅ `EmissionSourceMaster` (already exists)
- ✅ `EmissionFactor` (already exists)
- ✅ `EmissionGwpValue` (already exists)
- ✅ `EmissionUnitConversion` (already exists)
- ✅ `EmissionIndustryLabel` (already exists)
- ✅ `EmissionFactorSelectionRule` (already exists)
- ✅ `Measurement` (already exists)
- ✅ `MeasurementData` (already exists - may need to extend)

#### **Step 1.2: Create Routes**
```php
// In routes/web.php
Route::middleware(['auth:web', 'setActiveCompany', 'checkCompanyType:client'])->group(function () {
    // Quick Input Routes
    Route::prefix('quick-input')->name('quick-input.')->group(function () {
        Route::get('/{scope}/{slug}', [QuickInputController::class, 'show'])->name('show');
        Route::post('/{scope}/{slug}', [QuickInputController::class, 'store'])->name('store');
        Route::get('/entries', [QuickInputController::class, 'index'])->name('index');
        Route::get('/entries/{id}', [QuickInputController::class, 'view'])->name('view');
        Route::put('/entries/{id}', [QuickInputController::class, 'update'])->name('update');
        Route::delete('/entries/{id}', [QuickInputController::class, 'destroy'])->name('destroy');
    });
    
    // API Routes for AJAX
    Route::prefix('api/quick-input')->name('api.quick-input.')->group(function () {
        Route::post('/calculate', [QuickInputController::class, 'calculate'])->name('calculate');
        Route::get('/factors/{sourceId}', [QuickInputController::class, 'getFactors'])->name('factors');
        Route::get('/units/{sourceId}', [QuickInputController::class, 'getUnits'])->name('units');
    });
});
```

#### **Step 1.3: Update Navigation Links**
- Update `resources/views/layouts/partials/nav-client.blade.php`
- Replace `href="#"` with actual routes:
  - `route('quick-input.show', ['scope' => 1, 'slug' => 'natural-gas'])`
  - `route('quick-input.show', ['scope' => 1, 'slug' => 'fuel'])`
  - etc.

---

### **Phase 2: Core Functionality**

#### **Step 2.1: Create QuickInputController**

**Key Methods:**

1. **`show($scope, $slug)`**
   - Get emission source by `quick_input_slug`
   - Get form fields from `emission_source_form_fields`
   - Get user-friendly name from `emission_industry_labels` (based on company's industry)
   - Get available units and factors
   - Check/create measurement record for selected year/location
   - Return view with form

2. **`store(Request $request)`**
   - Validate input data
   - Get or create measurement record
   - Select appropriate emission factor (using selection rules)
   - Calculate CO2e emissions
   - Create `measurement_data` record
   - Update measurement totals
   - Return success response

3. **`calculate(Request $request)`** (AJAX)
   - Validate input
   - Select emission factor
   - Calculate CO2e
   - Return JSON with calculated values

4. **`index()`**
   - Get all measurement_data entries for user's company
   - Filter by scope, year, location if requested
   - Group and aggregate
   - Return list view

5. **`update($id, Request $request)`**
   - Get existing measurement_data record
   - Validate and update
   - Recalculate if quantity/unit changed
   - Update measurement totals
   - Return success response

6. **`destroy($id)`**
   - Delete measurement_data record
   - Recalculate measurement totals
   - Return success response

#### **Step 2.2: Create Service Classes**

**`app/Services/EmissionCalculationService.php`**
- `selectEmissionFactor($sourceId, $conditions)` - Select appropriate factor
- `calculateCO2e($quantity, $factor, $gwpVersion = 'AR6')` - Calculate emissions
- `convertUnit($value, $fromUnit, $toUnit)` - Unit conversion
- `getUserFriendlyName($sourceId, $industryCategoryId)` - Get industry-specific name

**`app/Services/MeasurementService.php`**
- `getOrCreateMeasurement($locationId, $fiscalYear)` - Get/create measurement record
- `updateMeasurementTotals($measurementId)` - Recalculate totals
- `getMeasurementDataByScope($measurementId, $scope)` - Get entries by scope

#### **Step 2.3: Create Form Builder**

**`app/Services/QuickInputFormBuilder.php`**
- `buildForm($emissionSourceId)` - Build form from `emission_source_form_fields`
- `validateForm($data, $formFields)` - Validate based on field definitions
- `getFieldOptions($fieldName, $sourceId)` - Get dropdown options

---

### **Phase 3: Frontend Implementation**

#### **Step 3.1: Create Views**

**`resources/views/quick-input/show.blade.php`**
- Header: Year selector, Location selector
- Form: Dynamic fields based on `emission_source_form_fields`
- Calculation preview section
- "Calculate" button (AJAX)
- "Add to Footprint" button
- Form validation (client-side + server-side)

**`resources/views/quick-input/index.blade.php`**
- Summary cards (total CO2e, by scope, by source)
- Filters (Year, Location, Scope, Source)
- Entries table
- Pagination
- Export buttons

**`resources/views/quick-input/view.blade.php`**
- Entry details
- Calculation breakdown
- Edit/Delete buttons

**`resources/views/quick-input/edit.blade.php`**
- Pre-filled form
- Same as show.blade.php but with existing data

#### **Step 3.2: Create JavaScript**

**`public/js/quick-input.js`**
- Form validation
- AJAX calculation calls
- Dynamic form field updates
- Unit conversion handling
- Real-time CO2e preview

**Key Functions:**
```javascript
- calculateEmissions(formData) - AJAX call to calculate endpoint
- updateFormFields(sourceId) - Load dynamic fields
- convertUnit(value, fromUnit, toUnit) - Client-side unit conversion
- validateForm() - Client-side validation
```

#### **Step 3.3: Create CSS**

**`public/css/quick-input.css`**
- Form styling
- Calculation preview styling
- Responsive design
- Loading states

---

### **Phase 4: Calculation Logic**

#### **Step 4.1: Emission Factor Selection**

**Algorithm:**
```
1. Get all selection rules for emission_source_id
2. Filter rules by conditions:
   - region matches (or null)
   - fuel_type matches (or null)
   - unit matches (or null)
   - industry_category_id matches (or null)
3. Sort by priority (descending)
4. Select first matching rule
5. Get emission_factor_id from rule
6. If no rule matches, use default factor (lowest priority or most common)
```

#### **Step 4.2: CO2e Calculation**

**Single Gas Factor:**
```
CO2e = quantity × emission_factor
```

**Multi-Gas Factor (CO2, CH4, N2O):**
```
CO2_emissions = quantity × co2_factor
CH4_emissions = quantity × ch4_factor
N2O_emissions = quantity × n2o_factor

Get GWP values (AR6):
- GWP_CO2 = 1
- GWP_CH4 = from emission_gwp_values WHERE gas_code = 'CH4'
- GWP_N2O = from emission_gwp_values WHERE gas_code = 'N2O'

CO2e = CO2_emissions + (CH4_emissions × GWP_CH4) + (N2O_emissions × GWP_N2O)
```

**Unit Conversion:**
```
If user enters unit that doesn't match factor unit:
1. Find conversion in emission_unit_conversions
2. Convert: converted_quantity = quantity × conversion_factor
3. Use converted_quantity for calculation
```

---

### **Phase 5: Integration with Existing System**

#### **Step 5.1: Measurement Totals Update**

**After each Quick Input entry:**
```
1. Get all measurement_data for measurement_id
2. Group by scope
3. Sum calculated_co2e by scope:
   - scope_1_co2e = SUM(calculated_co2e) WHERE scope = 'Scope 1'
   - scope_2_co2e = SUM(calculated_co2e) WHERE scope = 'Scope 2'
   - scope_3_co2e = SUM(calculated_co2e) WHERE scope = 'Scope 3'
4. total_co2e = scope_1_co2e + scope_2_co2e + scope_3_co2e
5. Update measurement record
```

#### **Step 5.2: Permissions Integration**

**Check Permissions:**
- `measurements.view` - Can view Quick Input entries
- `measurements.add` - Can create Quick Input entries
- `measurements.edit` - Can edit Quick Input entries
- `measurements.delete` - Can delete Quick Input entries

**In Controller:**
```php
$this->requirePermission('measurements.add', null, ['measurements.*', 'manage_measurements']);
```

#### **Step 5.3: Location Integration**

- Only show locations where user has access
- Filter entries by location
- Location selector in Quick Input header

---

### **Phase 6: User Experience Enhancements**

#### **Step 6.1: Form Features**
- Auto-save draft entries
- Form field tooltips (from `emission_source_form_fields.description`)
- Unit conversion helper (show converted value)
- Real-time CO2e preview as user types
- Validation messages (clear and helpful)

#### **Step 6.2: Entry Management**
- Bulk delete entries
- Duplicate entry feature
- Entry history/audit trail
- Export to CSV/Excel
- Print-friendly view

#### **Step 6.3: Dashboard Integration**
- Quick Input summary widget
- Recent entries list
- CO2e trends chart
- Scope breakdown pie chart

---

## 📊 **Data Flow Diagram**

```
User Input
    ↓
Quick Input Form
    ↓
Validation (Client + Server)
    ↓
Emission Factor Selection
    ├── Query selection rules
    ├── Match conditions
    └── Select factor
    ↓
Unit Conversion (if needed)
    ├── Query unit conversions
    └── Convert quantity
    ↓
CO2e Calculation
    ├── Single gas: quantity × factor
    └── Multi-gas: CO2 + (CH4 × GWP) + (N2O × GWP)
    ↓
Save to measurement_data
    ↓
Update measurement totals
    ↓
Success Response
```

---

## 🔐 **Security & Validation**

### **Input Validation**
- Server-side validation for all inputs
- Sanitize user inputs
- Validate numeric values (quantity, factors)
- Validate dates
- Validate units (must exist in system)
- Validate emission source IDs

### **Permission Checks**
- Check user has permission to add/edit/delete measurements
- Check user has access to location
- Check measurement belongs to user's company

### **Data Integrity**
- Foreign key constraints
- Transaction wrapping for multi-step operations
- Validation before calculation
- Error handling and logging

---

## 🧪 **Testing Strategy**

### **Unit Tests**
- Emission factor selection logic
- CO2e calculation logic
- Unit conversion logic
- Form validation logic

### **Integration Tests**
- End-to-end Quick Input flow
- Measurement totals update
- Permission checks
- Location filtering

### **User Acceptance Tests**
- Test with real emission data
- Test all Quick Input sources
- Test edge cases (missing factors, invalid units, etc.)
- Test with different industry categories

---

## 📅 **Implementation Timeline**

### **Week 1: Foundation**
- Create routes and controller structure
- Create service classes
- Set up models and relationships
- Update navigation links

### **Week 2: Core Functionality**
- Implement emission factor selection
- Implement CO2e calculation
- Create form builder
- Create basic views

### **Week 3: Frontend**
- Build dynamic forms
- Implement AJAX calculation
- Add form validation
- Create entry list view

### **Week 4: Integration & Polish**
- Integrate with measurement system
- Update measurement totals
- Add permissions
- UI/UX improvements

### **Week 5: Testing & Refinement**
- Unit tests
- Integration tests
- Bug fixes
- Performance optimization

---

## 🚀 **Deployment Checklist**

- [ ] All routes created and tested
- [ ] Controller methods implemented
- [ ] Service classes created
- [ ] Views created and styled
- [ ] JavaScript functionality working
- [ ] Permissions integrated
- [ ] Calculation logic verified
- [ ] Unit tests passing
- [ ] Integration tests passing
- [ ] Documentation updated
- [ ] User training materials prepared

---

## 📚 **Additional Considerations**

### **Performance**
- Cache emission factors and GWP values
- Optimize database queries (use eager loading)
- Paginate entry lists
- Use AJAX for calculations (no page reload)

### **Scalability**
- Consider indexing on frequently queried columns
- Optimize measurement totals calculation (maybe use background jobs)
- Consider caching user-friendly names

### **Future Enhancements**
- Bulk import from CSV/Excel
- API endpoints for external integrations
- Mobile app support
- Advanced reporting and analytics
- Carbon offset recommendations

---

## 📝 **Notes**

- All Quick Input entries are stored in `measurement_data` table (no separate table needed)
- Measurement totals are recalculated on each entry (consider background job for large datasets)
- Industry-specific names are retrieved from `emission_industry_labels` based on company's `industry_category_id`
- GWP version defaults to AR6 but can be configured per company
- Unit conversions are applied automatically when user enters different unit than factor unit

---

**Document Version:** 1.0  
**Last Updated:** 2025-01-XX  
**Author:** Development Team

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

# Industry-Specific User-Friendly Naming Implementation
## Based on MSME Matrix for Scope 1 and 2

---

## 📋 **Overview**

The `emission_industry_labels` table allows the same emission source to have different user-friendly names and descriptions based on the company's industry sector. This improves user experience by showing industry-specific terminology that users understand.

---

## 🎯 **How It Works**

### **Concept:**

Instead of showing technical names like "Fuel (Stationary combustion)" to all users, the system can show:
- **MANUFACTURING:** "Diesel Generators"
- **IT_SERVICES_OFFICES:** "Backup Generators"
- **FOOD_BEVERAGE:** "LPG Cylinders"

### **Example Scenarios:**

#### **Scenario 1: Natural Gas**

| Industry | Unit Type | User-Friendly Name | Description |
|----------|-----------|-------------------|-------------|
| MANUFACTURING | Main Factory | Natural Gas | Natural gas used in manufacturing processes, boilers, and furnaces |
| FOOD_BEVERAGE | Restaurant/Kitchen | Natural Gas | Natural gas used for cooking and heating in restaurant kitchens |
| FOOD_BEVERAGE | Processing Plant | Natural Gas | Natural gas used in food processing operations |

#### **Scenario 2: Fuel/Diesel**

| Industry | Unit Type | User-Friendly Name | Description |
|----------|-----------|-------------------|-------------|
| MANUFACTURING | Main Factory | Diesel Generators | Diesel used in backup generators and industrial equipment |
| IT_SERVICES_OFFICES | Office Building | Backup Generators | Diesel used in backup generators for office buildings |
| IT_SERVICES_OFFICES | Data Center | Backup Generators | Diesel used in backup generators for data centers |

#### **Scenario 3: Vehicles**

| Industry | Unit Type | User-Friendly Name | Description |
|----------|-----------|-------------------|-------------|
| MANUFACTURING | Main Factory | Company Vehicles | Company cars, vans, and trucks used for business operations |
| IT_SERVICES_OFFICES | Office Building | Company Cars | Company cars used by employees for business travel |
| FOOD_BEVERAGE | Restaurant/Kitchen | Delivery Vehicles | Vehicles used for food delivery |
| MANUFACTURING | Warehouse | Forklifts | Forklifts and material handling equipment |

#### **Scenario 4: Refrigerants**

| Industry | Unit Type | User-Friendly Name | Description |
|----------|-----------|-------------------|-------------|
| IT_SERVICES_OFFICES | Office Building | Refrigerants (AC) | Refrigerant gases used in office air conditioning systems |
| IT_SERVICES_OFFICES | Data Center | Refrigerants (Cooling) | Refrigerant gases used in data center cooling systems |
| FOOD_BEVERAGE | Restaurant/Kitchen | Refrigerators/AC | Refrigerant gases used in restaurant refrigeration and AC systems |
| FOOD_BEVERAGE | Storage Facility | Refrigerants | Refrigerant gases used in cold storage facilities |

---

## 🔄 **Implementation Flow**

### **Step 1: Get Company Industry**

```php
$company = $user->getActiveCompany();
$industrySector = $company->sector; // e.g., "MANUFACTURING", "IT_SERVICES_OFFICES"
```

**Note:** You may need to map your `MasterIndustryCategory` sectors to MSME Matrix industry sectors:
- Agriculture, Forestry & Fishing → (map to appropriate sector)
- Manufacturing → MANUFACTURING
- ICT → IT_SERVICES_OFFICES
- Food & Beverage → FOOD_BEVERAGE
- Retail & Wholesale → RETAIL
- Healthcare & Life Sciences → HEALTHCARE
- Construction & Real Estate → CONSTRUCTION
- Hospitality & Tourism → HOSPITALITY
- Transportation & Logistics → LOGISTICS_TRANSPORT
- etc.

### **Step 2: Query Industry Labels**

```php
// Get user-friendly name for emission source based on industry
$industryLabel = EmissionIndustryLabel::where('emission_source_id', $emissionSourceId)
    ->where('industry_sector', $industrySector)
    ->where('is_active', true)
    ->first();

if ($industryLabel) {
    $displayName = $industryLabel->user_friendly_name;
    $description = $industryLabel->user_friendly_description;
    $equipment = $industryLabel->common_equipment;
} else {
    // Fallback to default name from emission_sources_master
    $displayName = $emissionSource->name;
    $description = $emissionSource->description;
}
```

### **Step 3: Display in Forms**

```blade
{{-- In Quick Input form --}}
<h2>{{ $industryLabel->user_friendly_name ?? $emissionSource->name }}</h2>
<p class="help-text">{{ $industryLabel->user_friendly_description ?? $emissionSource->instructions }}</p>

@if($industryLabel && $industryLabel->common_equipment)
    <div class="equipment-hint">
        <strong>Common Equipment:</strong> {{ $industryLabel->common_equipment }}
    </div>
@endif
```

---

## 📊 **Industry Sector Mapping**

### **MSME Matrix Industry Sectors:**

1. **MANUFACTURING** - Manufacturing industries
2. **IT_SERVICES_OFFICES** - IT services, offices, data centers
3. **FOOD_BEVERAGE** - Food and beverage production, restaurants
4. **RETAIL** - Retail and wholesale
5. **HEALTHCARE** - Healthcare and life sciences
6. **CONSTRUCTION** - Construction and real estate
7. **HOSPITALITY** - Hospitality and tourism
8. **AUTOMOTIVE** - Automotive industry
9. **LOGISTICS_TRANSPORT** - Logistics and transportation

### **Mapping from MasterIndustryCategory:**

You'll need to create a mapping function:

```php
function mapIndustryToMSMESector($industryName) {
    $mapping = [
        'Manufacturing' => 'MANUFACTURING',
        'ICT' => 'IT_SERVICES_OFFICES',
        'Food & Beverage' => 'FOOD_BEVERAGE',
        'Retail & Wholesale' => 'RETAIL',
        'Healthcare & Life Sciences' => 'HEALTHCARE',
        'Construction & Real Estate' => 'CONSTRUCTION',
        'Hospitality & Tourism' => 'HOSPITALITY',
        'Transportation & Logistics' => 'LOGISTICS_TRANSPORT',
        // Add more mappings as needed
    ];
    
    return $mapping[$industryName] ?? null;
}
```

---

## ✅ **Benefits of This Approach**

1. **Better UX** - Users see familiar terminology from their industry
2. **Contextual Help** - Shows relevant equipment examples
3. **Easier Data Entry** - Less confusion about what to enter
4. **Flexible** - Can add more industries without code changes
5. **Backward Compatible** - Falls back to default names if no industry label exists
6. **Scalable** - Easy to add more industry sectors and unit types

---

## 🔧 **Implementation Options**

### **Option 1: Automatic (Recommended)**
- System automatically detects company industry
- Queries `emission_industry_labels` table
- Shows industry-specific names in forms
- Falls back to default if no match found

### **Option 2: Manual Selection**
- User can select their industry sector
- System uses selected sector for labels
- Useful if company operates in multiple industries

### **Option 3: Hybrid**
- Automatic detection as default
- User can override if needed
- Best of both worlds

---

## 📝 **Database Structure**

### **Table: `emission_industry_labels`**

```sql
CREATE TABLE `emission_industry_labels` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `emission_source_id` BIGINT(20) UNSIGNED NOT NULL,
  `industry_sector` VARCHAR(100) NULL,
  `unit_type` VARCHAR(100) NULL,
  `user_friendly_name` VARCHAR(255) NOT NULL,
  `user_friendly_description` TEXT NULL,
  `common_equipment` TEXT NULL,
  `typical_units` VARCHAR(255) NULL,
  `display_order` INT(11) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_industry_source` (`industry_sector`, `emission_source_id`)
);
```

---

## 🎯 **Recommendation**

**Yes, this is a good approach!** 

The MSME Matrix provides excellent industry-specific context that will significantly improve user experience. The implementation is:

- ✅ **Feasible** - Simple table structure, easy to query
- ✅ **Scalable** - Can add more industries without code changes
- ✅ **User-Friendly** - Shows familiar terminology
- ✅ **Flexible** - Supports multiple unit types per industry
- ✅ **Backward Compatible** - Falls back gracefully

**Next Steps:**
1. Run the SQL file to create the table
2. Import industry labels from MSME Matrix
3. Create mapping function for industry sectors
4. Update form views to use industry labels
5. Test with different industry sectors

---

**End of Implementation Guide**

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

# MenetZero - Master System Design Document
## Complete Enhancement Specification & Implementation Guide

**Version**: 2.0  
**Status**: Finalized & Consolidated  
**Date**: 2025-01-XX

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Architecture Decisions](#2-architecture-decisions)
3. [URL Structure](#3-url-structure)
4. [Subscription Plans](#4-subscription-plans)
5. [Staff & Access Management](#5-staff--access-management)
6. [Database Design](#6-database-design)
7. [Data Structure & Separation](#7-data-structure--separation)
8. [Performance & Scalability](#8-performance--scalability)
9. [Implementation Roadmap](#9-implementation-roadmap)
10. [Key Services & Models](#10-key-services--models)

---

## 1. System Overview

### Two Separate Entities

#### 1. Clients (MSME - Direct)
- Direct customers of MenetZero
- Manage their own carbon emissions
- Independent accounts and subscriptions
- No relationship with Partners in the system
- **Data Structure**: `companies` → `locations` → `measurements` → `measurement_data`

#### 2. Partners (CA/Consultants)
- Separate customer segment
- **Manage external clients** through Partner panel (add, edit, delete)
- External clients are **NOT MenetZero accounts** - they have no login access
- Partners use MenetZero for their own operations/analytics
- No relationship with MenetZero Clients in the system
- **Data Structure**: `partner_external_clients` → `partner_external_client_locations` → `partner_external_client_measurements`

### Key Principles

1. **Complete Separation**: No cross-entity relationships between MenetZero Clients and Partners
2. **External Client Management**: Partners manage external clients (outside MenetZero) through their panel
3. **No External Client Access**: External clients managed by partners have **NO access** to MenetZero system
4. **Independent Revenue**: Each entity has separate features, plans, and permissions
5. **Single Users Table**: All staff (client, partner, external auditors) in ONE table
6. **Multi-Account Access**: Staff can access multiple companies with different roles per company

---

## 2. Architecture Decisions

### Panel Architecture: Hybrid Approach ✅

**Shared Components:**
- Measurements (view, create, edit)
- Locations (view, create, edit)
- Documents (upload, view, approve)
- Reports (generate, view)

**Entity-Specific Dashboards:**
- **Client Dashboard**: Personal metrics, quick actions
- **Partner Dashboard**: Analytics, external client management (add/edit/delete), usage tracking

**Benefits:**
- Code reusability for shared features
- Full customization for entity-specific features
- Easy to maintain and extend

### Data Architecture: Separate Tables ✅

**Direct Clients:**
- Use existing tables: `companies`, `locations`, `measurements`, `measurement_data`, `document_uploads`, `reports`

**Partner External Clients:**
- Use separate tables: `partner_external_clients`, `partner_external_client_locations`, `partner_external_client_measurements`, etc.

**Benefits:**
- Clean separation
- Fast queries (no complex joins)
- Scalable (handles 10K+ external clients easily)
- Easy to maintain

### Staff Architecture: Single Table + Access Linking ✅

**Single Users Table:**
- All staff in ONE table (client staff, partner staff, external auditors)
- No separate `client_staff` or `partner_staff` tables

**Access Management:**
- `user_company_access` table links users to companies
- Same user can have different roles in different companies
- Workspace switcher for multi-company access

---

## 3. URL Structure

### Base URL
```
https://app.menetzero.com
```

### Client URLs
```
https://app.menetzero.com/dashboard
https://app.menetzero.com/measurements
https://app.menetzero.com/measurements/{id}
https://app.menetzero.com/locations
https://app.menetzero.com/locations/{id}
https://app.menetzero.com/document-uploads
https://app.menetzero.com/reports
https://app.menetzero.com/profile
https://app.menetzero.com/settings
https://app.menetzero.com/staff
https://app.menetzero.com/roles
```

### Partner URLs
```
https://app.menetzero.com/partner/dashboard
https://app.menetzero.com/partner/clients                    → External Client Management
https://app.menetzero.com/partner/clients/create             → Add New External Client
https://app.menetzero.com/partner/clients/{id}               → View/Edit External Client
https://app.menetzero.com/partner/clients/{id}/locations      → Client's Locations
https://app.menetzero.com/partner/clients/{id}/measurements  → Client's Measurements
https://app.menetzero.com/partner/clients/{id}/documents     → Client's Documents
https://app.menetzero.com/partner/clients/{id}/reports       → Client's Reports
https://app.menetzero.com/partner/analytics
https://app.menetzero.com/partner/usage
https://app.menetzero.com/partner/branding
https://app.menetzero.com/partner/settings
https://app.menetzero.com/partner/staff
https://app.menetzero.com/partner/roles
```

### Account Switcher (Multi-Account Access)
```
https://app.menetzero.com/account/selector                   → Account Selection Page
https://app.menetzero.com/account/switch?company_id={id}     → Switch Active Account
```

### Route Implementation
```php
// routes/web.php

// Account Switcher (for multi-company staff)
Route::middleware(['auth'])->group(function () {
    Route::get('/account/selector', [AccountSelectorController::class, 'index'])->name('account.selector');
    Route::post('/account/switch', [AccountSelectorController::class, 'switch'])->name('account.switch');
});

// Client Routes
Route::middleware(['auth', 'client', 'setActiveCompany'])->group(function () {
    Route::get('/dashboard', [ClientDashboardController::class, 'index'])->name('client.dashboard');
    Route::resource('measurements', MeasurementController::class);
    Route::resource('locations', LocationController::class);
    Route::resource('document-uploads', DocumentUploadController::class);
    Route::get('/profile', [ProfileController::class, 'index'])->name('client.profile');
    Route::get('/settings', [ClientSettingsController::class, 'index'])->name('client.settings');
    Route::resource('staff', ClientStaffController::class);
    Route::resource('roles', ClientRoleController::class);
});

// Partner Routes
Route::prefix('partner')->middleware(['auth', 'partner', 'setActiveCompany'])->group(function () {
    Route::get('/dashboard', [PartnerDashboardController::class, 'index'])->name('partner.dashboard');
    
    // External Client Management
    Route::resource('clients', Partner\ExternalClientController::class);
    Route::get('/clients/{client}/locations', [Partner\ExternalClientLocationController::class, 'index'])->name('partner.clients.locations.index');
    Route::post('/clients/{client}/locations', [Partner\ExternalClientLocationController::class, 'store'])->name('partner.clients.locations.store');
    Route::get('/clients/{client}/locations/{location}/measurements', [Partner\ExternalClientMeasurementController::class, 'index'])->name('partner.clients.measurements.index');
    Route::post('/clients/{client}/locations/{location}/measurements', [Partner\ExternalClientMeasurementController::class, 'store'])->name('partner.clients.measurements.store');
    Route::get('/clients/{client}/documents', [Partner\ExternalClientDocumentController::class, 'index'])->name('partner.clients.documents.index');
    Route::get('/clients/{client}/reports', [Partner\ExternalClientReportController::class, 'index'])->name('partner.clients.reports.index');
    
    Route::get('/analytics', [PartnerAnalyticsController::class, 'index'])->name('partner.analytics');
    Route::get('/usage', [PartnerUsageController::class, 'index'])->name('partner.usage');
    Route::get('/branding', [PartnerBrandingController::class, 'index'])->name('partner.branding');
    Route::get('/settings', [PartnerSettingsController::class, 'index'])->name('partner.settings');
    Route::resource('staff', PartnerStaffController::class);
    Route::resource('roles', PartnerRoleController::class);
});
```

---

## 4. Subscription Plans

### Client Plans (MSME)

#### Free Plan
- **Price**: AED 0/year
- **Features**: Configurable by Superadmin
- **Typical Features**:
  - 1 Location
  - 1 User
  - Basic measurements
  - Limited reports

#### Starter Plan
- **Price**: AED 1,199/year
- **Features**: Configurable by Superadmin
- **Typical Features**:
  - 1 Location
  - 2 Staff Users
  - Basic Reporting
  - Manual Data Entry

#### Growth Plan
- **Price**: AED 2,999/year
- **Features**: Configurable by Superadmin
- **Typical Features**:
  - Multi-Location (up to 5)
  - 3 Staff Users
  - Advanced Analytics
  - Basic Reporting

#### Pro Plan
- **Price**: AED 5,999/year
- **Features**: Configurable by Superadmin
- **Typical Features**:
  - Unlimited Locations
  - 5 Staff Users
  - OCR/AI Upload
  - Advanced Analytics
  - Data Archive/Export
  - Unlimited Reports

### Partner Plans (CA/Consultants)

#### Free Plan
- **Price**: AED 0/year
- **Features**: Configurable by Superadmin
- **Typical Features**:
  - **2 External Clients** (add/edit/delete)
  - Basic analytics
  - Limited usage

#### Partner Plan
- **Price**: AED 9,999/year
- **Features**: Configurable by Superadmin
- **Typical Features**:
  - **10 External Clients** (add/edit/delete)
  - Client Management (External)
  - Analytics Dashboard
  - Co-Branded Reports
  - 5 Staff Users
  - Usage Tracking

#### Enterprise Plan
- **Price**: AED 29,999/year
- **Features**: Configurable by Superadmin
- **Typical Features**:
  - **Unlimited External Clients** (add/edit/delete)
  - Unlimited Staff Users
  - White-Label Solution
  - Full API Access
  - Custom Integrations
  - Priority Support
  - Advanced Analytics

### Feature Configuration by Superadmin

**Key Principle**: Superadmin can configure which features belong to which plan through admin panel.

**Implementation:**
- Features stored as JSON in `subscription_plans` table
- Admin UI to enable/disable features per plan
- Dynamic feature checking in application

---

## 5. Staff & Access Management

### Staff Model: Single Users Table ✅

**Key Principle:** ONE users table for ALL staff - client staff, partner staff, external auditors.

**No separate tables needed!** The `user_company_access` table handles the linking.

### Staff Types

#### 1. Single Company Staff
- Staff belongs to ONE company (client or partner)
- `users.company_id` is set
- Role from `users.role` or `users.custom_role_id`
- Standard access, no workspace switcher

#### 2. Multi-Company Staff
- Staff can access MULTIPLE companies
- `users.company_id` is NULL (or set to one, but has additional access)
- Roles from `user_company_access` table (different role per company)
- Workspace switcher shown after login

### Role Assignment

**Single Company Staff:**
- Role assigned by company owner
- Stored in `users.role` or `users.custom_role_id`
- One role for the company

**Multi-Company Staff:**
- Different roles in different companies
- Each company owner assigns role independently
- Stored in `user_company_access.role_id` or `user_company_access.custom_role_id`
- Same user can have:
  - Client 1: Auditor (view only)
  - Client 2: Manager (can edit) - **Different role!**
  - Partner 1: Partner Staff (view)
  - Partner 2: Partner Manager (full access) - **Different role!**

### Default Role Templates

#### Client Default Roles
1. **Owner** - Full access
2. **Manager** - Operational management
3. **Data Entry** - Data input only
4. **Auditor** - View only

#### Partner Default Roles
1. **Partner Admin** - Full partner access
2. **Partner Manager** - Operational management
3. **Partner Staff** - Limited access

### Custom Role Creation

**For Clients:**
- Client admin can create custom roles
- Assign custom permissions
- Use custom terminology (Supervisor, Associate, etc.)
- Roles are company-specific

**For Partners:**
- Partner admin can create custom roles
- Assign custom permissions
- Use custom terminology
- Roles are partner-specific

### Permission System

**Using Spatie Laravel Permission:**
- Roles and Permissions system
- Flexible permission assignment
- Easy to add new permissions for new features
- Caching built-in

**Permission Examples:**
- `measurements.view`
- `measurements.create`
- `measurements.edit`
- `measurements.delete`
- `documents.upload`
- `documents.approve`
- `reports.generate`
- `locations.manage`
- `users.manage`
- `settings.manage`

**Adding New Permissions (Future Features):**
```php
// Example: Adding AI Recommendations feature
Permission::create(['name' => 'ai_recommendations.view']);
Permission::create(['name' => 'ai_recommendations.use']);

// Assign to roles
$owner->givePermissionTo('ai_recommendations.*');
$manager->givePermissionTo(['ai_recommendations.view', 'ai_recommendations.use']);
```

### Workspace Switcher (Multi-Account Access)

**When Shown:**
- User has multiple company access (count > 1 in `user_company_access`)
- Shown after login
- Dropdown in header for quick switching

**How It Works:**
1. User logs in
2. System checks: `user_company_access` count
3. If count > 1: Show account selector
4. User selects account
5. System sets `user_active_context`
6. All queries filtered by active company

---

## 6. Database Design

### Core Tables

#### 1. Companies (Enhanced)
```sql
ALTER TABLE `companies`
ADD COLUMN `company_type` enum('client', 'partner') NOT NULL DEFAULT 'client' AFTER `is_active`,
ADD COLUMN `is_direct_client` tinyint(1) DEFAULT 1 AFTER `company_type`,
ADD INDEX `company_type` (`company_type`);
```

#### 2. Users (Enhanced - Single Table for All Staff)
```sql
ALTER TABLE `users`
ADD COLUMN `user_type` enum('client', 'partner') DEFAULT 'client' AFTER `role`, -- For single-company staff
ADD COLUMN `custom_role_id` bigint(20) UNSIGNED NULL AFTER `user_type`,
ADD COLUMN `external_company_name` varchar(255) NULL AFTER `custom_role_id`, -- Optional: e.g., "Bhavik Certification Company"
ADD COLUMN `notes` text NULL AFTER `external_company_name`,
ADD INDEX `user_type` (`user_type`),
ADD INDEX `custom_role_id` (`custom_role_id`);

-- Key Points:
-- - company_id set = single company staff
-- - company_id NULL + user_company_access records = multi-company staff
-- - No internal/external enum - check access count instead
```

#### 3. Subscription Plans
```sql
CREATE TABLE `subscription_plans` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `plan_code` varchar(50) NOT NULL UNIQUE,
  `plan_name` varchar(255) NOT NULL,
  `plan_category` enum('client', 'partner') NOT NULL,
  `price_annual` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'AED',
  `billing_cycle` enum('annual', 'monthly') DEFAULT 'annual',
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `description` text NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL, -- JSON
  `limits` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL, -- JSON
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plan_code` (`plan_code`),
  KEY `plan_category` (`plan_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 4. Client Subscriptions
```sql
CREATE TABLE `client_subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_plan_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('active', 'cancelled', 'expired', 'suspended', 'trialing') DEFAULT 'active',
  `billing_cycle` enum('annual', 'monthly') DEFAULT 'annual',
  `started_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `auto_renew` tinyint(1) DEFAULT 1,
  `payment_method` varchar(50) NULL,
  `stripe_subscription_id` varchar(255) NULL,
  `stripe_customer_id` varchar(255) NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_active_subscription` (`company_id`, `status`),
  KEY `company_id` (`company_id`),
  KEY `subscription_plan_id` (`subscription_plan_id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 5. Partner Subscriptions
```sql
CREATE TABLE `partner_subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `subscription_plan_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('active', 'cancelled', 'expired', 'suspended', 'trialing') DEFAULT 'active',
  `billing_cycle` enum('annual', 'monthly') DEFAULT 'annual',
  `started_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `auto_renew` tinyint(1) DEFAULT 1,
  `payment_method` varchar(50) NULL,
  `stripe_subscription_id` varchar(255) NULL,
  `stripe_customer_id` varchar(255) NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_active_subscription` (`company_id`, `status`),
  KEY `company_id` (`company_id`),
  KEY `subscription_plan_id` (`subscription_plan_id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 6. User Company Access (Multi-Account Access)
```sql
CREATE TABLE `user_company_access` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `company_type` enum('client', 'partner') NOT NULL,
  `role_id` bigint(20) UNSIGNED NULL, -- Spatie role ID
  `custom_role_id` bigint(20) UNSIGNED NULL, -- Reference to company_custom_roles
  `access_level` enum('view', 'manage', 'full') DEFAULT 'view',
  `status` enum('active', 'suspended', 'revoked') DEFAULT 'active',
  `invited_by` bigint(20) UNSIGNED NULL,
  `invited_at` datetime NOT NULL,
  `last_accessed_at` datetime NULL,
  `notes` text NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_company_unique` (`user_id`, `company_id`),
  KEY `user_id` (`user_id`),
  KEY `company_id` (`company_id`),
  KEY `status` (`status`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 7. User Active Context (Current Account)
```sql
CREATE TABLE `user_active_context` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `active_company_id` bigint(20) UNSIGNED NULL,
  `active_company_type` enum('client', 'partner') NULL,
  `last_switched_at` datetime NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`active_company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 8. Company Invitations
```sql
CREATE TABLE `company_invitations` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `company_type` enum('client', 'partner') NOT NULL,
  `email` varchar(255) NOT NULL,
  `role_id` bigint(20) UNSIGNED NULL,
  `custom_role_id` bigint(20) UNSIGNED NULL,
  `access_level` enum('view', 'manage', 'full') DEFAULT 'view',
  `token` varchar(255) NOT NULL UNIQUE,
  `status` enum('pending', 'accepted', 'rejected', 'expired') DEFAULT 'pending',
  `invited_by` bigint(20) UNSIGNED NOT NULL,
  `invited_at` datetime NOT NULL,
  `expires_at` datetime NULL,
  `accepted_at` datetime NULL,
  `accepted_by_user_id` bigint(20) UNSIGNED NULL,
  `notes` text NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_email_pending` (`company_id`, `email`, `status`),
  KEY `email` (`email`),
  KEY `token` (`token`),
  KEY `status` (`status`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`accepted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 9. Role Templates (Default Roles)
```sql
CREATE TABLE `role_templates` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `template_code` varchar(50) NOT NULL UNIQUE,
  `template_name` varchar(255) NOT NULL,
  `description` text NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL, -- JSON array
  `category` enum('client', 'partner', 'both') DEFAULT 'client',
  `is_system_template` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_code` (`template_code`),
  KEY `category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 10. Company Custom Roles
```sql
CREATE TABLE `company_custom_roles` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `role_name` varchar(255) NOT NULL,
  `description` text NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL, -- JSON array
  `based_on_template` varchar(50) NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `is_active` (`is_active`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 11. Feature Flags
```sql
CREATE TABLE `feature_flags` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `feature_code` varchar(100) NOT NULL,
  `is_enabled` tinyint(1) DEFAULT 0,
  `enabled_at` datetime NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_feature` (`company_id`, `feature_code`),
  KEY `company_id` (`company_id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 12. Usage Tracking
```sql
CREATE TABLE `usage_tracking` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) UNSIGNED NOT NULL,
  `resource_type` enum('location', 'user', 'document', 'report', 'api_call', 'measurement') NOT NULL,
  `resource_id` bigint(20) UNSIGNED NULL,
  `action` varchar(100) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `period` enum('daily', 'monthly', 'yearly') DEFAULT 'monthly',
  `period_start` date NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `resource_type` (`resource_type`),
  KEY `period_start` (`period_start`),
  KEY `company_period` (`company_id`, `period`, `period_start`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 7. Data Structure & Separation

### Direct Clients Data Structure

```
companies (1 company = 1 MenetZero client)
  ├── locations (1 company has many locations)
  │     └── measurements (1 location has many measurements)
  │           └── measurement_data (1 measurement has many data entries)
  ├── document_uploads (1 company has many documents)
  └── reports (1 company has many reports)
```

**Tables Used:**
- `companies` (existing)
- `locations` (existing)
- `measurements` (existing)
- `measurement_data` (existing)
- `document_uploads` (existing)
- `reports` (existing)

### Partner External Clients Data Structure

```
partner_company (1 partner)
  └── partner_external_clients (1 partner has MANY external clients)
        ├── Client 1
        │     ├── partner_external_client_locations (Client 1 has many locations)
        │     │     └── partner_external_client_measurements (Each location has many measurements)
        │     │           └── partner_external_client_measurement_data (Each measurement has many data entries)
        │     ├── partner_external_client_documents (Client 1 has many documents)
        │     ├── partner_external_client_reports (Client 1 has many reports)
        │     └── partner_external_client_emission_boundaries (Client 1 has emission boundaries)
        │
        ├── Client 2
        │     └── (Complete separate data structure)
        │
        └── Client N (Each client has completely separate data)
```

**Tables Used:**
- `partner_external_clients` (NEW)
- `partner_external_client_locations` (NEW)
- `partner_external_client_measurements` (NEW)
- `partner_external_client_measurement_data` (NEW)
- `partner_external_client_documents` (NEW)
- `partner_external_client_reports` (NEW)
- `partner_external_client_emission_boundaries` (NEW)

### Key Relationship Hierarchy

**Direct Clients:**
```
Company → Locations → Measurements → Measurement Data
```

**Partner External Clients:**
```
Partner → External Clients → Locations → Measurements → Measurement Data
```

### Complete Data Flow Example

**Partner ABC with 10 External Clients:**
```
Partner ABC (partner_company_id = 5)
│
├── External Client 1 (partner_external_client_id = 1)
│   ├── Location 1: Head Office (location_id = 1)
│   │   ├── Measurement 1: Jan 2025 (measurement_id = 1)
│   │   │   ├── Data Entry 1: Emission Source 1, quantity = 1000
│   │   │   ├── Data Entry 2: Emission Source 2, quantity = 500
│   │   │   └── Data Entry 3: Emission Source 3, quantity = 200
│   │   ├── Measurement 2: Feb 2025
│   │   └── Measurement 3: Mar 2025
│   ├── Location 2: Warehouse
│   │   └── Measurements...
│   ├── Document 1: DEWA_Bill_Jan.pdf
│   ├── Document 2: Fuel_Receipt_Feb.pdf
│   └── Report 1: Annual_Report_2025.pdf
│
├── External Client 2 (partner_external_client_id = 2)
│   └── (Complete separate data structure)
│
└── ... External Client 10
      └── (Complete separate data structure)
```

### Database Tables for Partner External Clients

#### 1. Partner External Clients
```sql
CREATE TABLE `partner_external_clients` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_company_id` bigint(20) UNSIGNED NOT NULL,
  `client_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) NULL,
  `email` varchar(255) NULL,
  `phone` varchar(255) NULL,
  `industry` varchar(255) NULL,
  `sector` varchar(255) NULL,
  `address` text NULL,
  `city` varchar(255) NULL,
  `country` varchar(255) NULL,
  `status` enum('active', 'inactive', 'archived') DEFAULT 'active',
  `notes` text NULL,
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `partner_company_id` (`partner_company_id`),
  KEY `status` (`status`),
  FOREIGN KEY (`partner_company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 2. Partner External Client Locations
```sql
CREATE TABLE `partner_external_client_locations` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_external_client_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) NULL,
  `city` varchar(255) NULL,
  `country` varchar(255) NULL,
  `location_type` varchar(255) NULL,
  `staff_count` int(11) NULL,
  `staff_work_from_home` tinyint(1) DEFAULT 0,
  `work_from_home_percentage` decimal(5,2) NULL,
  `fiscal_year_start` varchar(16) DEFAULT 'January',
  `is_head_office` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `receives_utility_bills` tinyint(1) DEFAULT 0,
  `pays_electricity_proportion` tinyint(1) DEFAULT 0,
  `shared_building_services` tinyint(1) DEFAULT 0,
  `reporting_period` int(11) NULL,
  `measurement_frequency` enum('Annually','Half Yearly','Quarterly','Monthly') DEFAULT 'Annually',
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `partner_external_client_id` (`partner_external_client_id`),
  KEY `is_active` (`is_active`),
  FOREIGN KEY (`partner_external_client_id`) REFERENCES `partner_external_clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 3. Partner External Client Measurements
```sql
CREATE TABLE `partner_external_client_measurements` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_external_client_location_id` bigint(20) UNSIGNED NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `frequency` enum('monthly','quarterly','half_yearly','annually') NOT NULL,
  `status` enum('draft','submitted','under_review','not_verified','verified') DEFAULT 'draft',
  `fiscal_year` int(11) NOT NULL,
  `fiscal_year_start_month` varchar(16) NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `notes` text NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `staff_count` int(11) NULL,
  `staff_work_from_home` tinyint(1) DEFAULT 0,
  `work_from_home_percentage` decimal(5,2) NULL,
  `total_co2e` decimal(15,6) DEFAULT 0.000000,
  `scope_1_co2e` decimal(15,6) DEFAULT 0.000000,
  `scope_2_co2e` decimal(15,6) DEFAULT 0.000000,
  `scope_3_co2e` decimal(15,6) DEFAULT 0.000000,
  `co2e_calculated_at` timestamp NULL,
  `emission_source_co2e` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `partner_external_client_location_id` (`partner_external_client_location_id`),
  KEY `status` (`status`),
  KEY `fiscal_year` (`fiscal_year`),
  FOREIGN KEY (`partner_external_client_location_id`) REFERENCES `partner_external_client_locations` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 4. Partner External Client Measurement Data
```sql
CREATE TABLE `partner_external_client_measurement_data` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_external_client_measurement_id` bigint(20) UNSIGNED NOT NULL,
  `emission_source_id` int(11) NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `field_value` text NULL,
  `field_type` varchar(50) DEFAULT 'text',
  `created_by` bigint(20) UNSIGNED NULL,
  `updated_by` bigint(20) UNSIGNED NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `partner_external_client_measurement_id` (`partner_external_client_measurement_id`),
  KEY `emission_source_id` (`emission_source_id`),
  FOREIGN KEY (`partner_external_client_measurement_id`) REFERENCES `partner_external_client_measurements` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`emission_source_id`) REFERENCES `emission_sources_master` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 5. Partner External Client Documents
```sql
CREATE TABLE `partner_external_client_documents` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_external_client_id` bigint(20) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` enum('pdf','jpg','jpeg','png') NOT NULL,
  `file_size` int(11) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `source_type` enum('dewa','electricity','fuel','waste','water','transport','other') NOT NULL,
  `document_category` enum('bill','receipt','invoice','statement','contract','other') DEFAULT 'bill',
  `extracted_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `processed_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `ocr_confidence` decimal(5,2) NULL,
  `ocr_processed_at` timestamp NULL,
  `ocr_attempts` int(11) DEFAULT 0,
  `ocr_error_message` text NULL,
  `status` enum('pending','processing','extracted','reviewed','approved','rejected','integrated','failed') DEFAULT 'pending',
  `approved_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `approved_by` bigint(20) UNSIGNED NULL,
  `approved_at` timestamp NULL,
  `rejection_reason` text NULL,
  `partner_external_client_measurement_id` bigint(20) UNSIGNED NULL,
  `integration_status` enum('pending','integrated','failed') DEFAULT 'pending',
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `partner_external_client_id` (`partner_external_client_id`),
  KEY `status` (`status`),
  FOREIGN KEY (`partner_external_client_id`) REFERENCES `partner_external_clients` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 6. Partner External Client Reports
```sql
CREATE TABLE `partner_external_client_reports` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_external_client_id` bigint(20) UNSIGNED NOT NULL,
  `report_type` varchar(100) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `file_path` varchar(500) NULL,
  `generated_at` datetime NULL,
  `generated_by` bigint(20) UNSIGNED NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `partner_external_client_id` (`partner_external_client_id`),
  KEY `report_type` (`report_type`),
  FOREIGN KEY (`partner_external_client_id`) REFERENCES `partner_external_clients` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 7. Partner External Client Emission Boundaries
```sql
CREATE TABLE `partner_external_client_emission_boundaries` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `partner_external_client_location_id` bigint(20) UNSIGNED NOT NULL,
  `scope` enum('Scope 1','Scope 2','Scope 3') NOT NULL,
  `selected_sources` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `partner_external_client_location_id` (`partner_external_client_location_id`),
  FOREIGN KEY (`partner_external_client_location_id`) REFERENCES `partner_external_client_locations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 8. Performance & Scalability

### Scale Analysis

**Your Scenario:**
- 100 Partners
- Each Partner has 100 External Clients
- Total: **10,000 External Clients**
- Each Client has: Multiple Locations, Measurements, Documents

### Database Size Estimate

```
partner_external_clients: 10,000 records × ~500 bytes = ~5 MB
partner_external_client_locations: 50,000 records × ~1 KB = ~50 MB
partner_external_client_measurements: 500,000 records × ~2 KB = ~1 GB
partner_external_client_measurement_data: 5,000,000 records × ~500 bytes = ~2.5 GB
partner_external_client_documents: 100,000 records × ~1 KB = ~100 MB
partner_external_client_reports: 20,000 records × ~500 bytes = ~10 MB

Total: ~3.7 GB (Very manageable!)
```

### Query Performance with Proper Indexing

**Critical Indexes:**
```sql
-- Partner external clients
CREATE INDEX idx_partner_clients_partner ON partner_external_clients(partner_company_id, status);

-- Partner external client locations
CREATE INDEX idx_partner_locations_client ON partner_external_client_locations(partner_external_client_id, is_active);

-- Partner external client measurements
CREATE INDEX idx_partner_measurements_location ON partner_external_client_measurements(partner_external_client_location_id, period_start, period_end);
CREATE INDEX idx_partner_measurements_status ON partner_external_client_measurements(status, fiscal_year);

-- Partner external client documents
CREATE INDEX idx_partner_documents_client ON partner_external_client_documents(partner_external_client_id, status);
```

**Query Performance:**
- Get partner's clients: **1-5ms** ✅
- Get client's locations: **1-5ms** ✅
- Get client's measurements: **10-50ms** ✅
- Get client's documents: **5-20ms** ✅

### Why Single Table Approach is Best

**Performance Benchmarks:**
| Records | Query Time | Status |
|---------|------------|--------|
| 10,000 | 1-5ms | ⚡ Excellent |
| 100,000 | 5-20ms | ✅ Very Good |
| 1,000,000 | 20-100ms | ✅ Good |

**Your Scale:** 10,000 clients = **1-5ms query time** ✅

**Benefits:**
- ✅ Fast queries with proper indexing
- ✅ Simple code structure
- ✅ Easy maintenance
- ✅ Scalable to 1M+ records
- ✅ Industry standard approach

**When to Reconsider:**
- Only if you reach 10+ million records per table
- Only if query time consistently > 500ms

---

## 9. Implementation Roadmap

### Phase 1: Database Foundation (Week 1-2)
- [ ] Create `subscription_plans` table
- [ ] Create `client_subscriptions` table
- [ ] Create `partner_subscriptions` table
- [ ] Create `partner_external_clients` table
- [ ] Create `partner_external_client_locations` table
- [ ] Create `partner_external_client_measurements` table
- [ ] Create `partner_external_client_measurement_data` table
- [ ] Create `partner_external_client_documents` table
- [ ] Create `partner_external_client_reports` table
- [ ] Create `partner_external_client_emission_boundaries` table
- [ ] Create `user_company_access` table
- [ ] Create `user_active_context` table
- [ ] Create `company_invitations` table
- [ ] Create `role_templates` table
- [ ] Create `company_custom_roles` table
- [ ] Create `feature_flags` table
- [ ] Create `usage_tracking` table
- [ ] Modify `companies` table
- [ ] Modify `users` table
- [ ] Add critical indexes
- [ ] Seed default subscription plans
- [ ] Seed default role templates

### Phase 2: Subscription Management (Week 3-4)
- [ ] Create SubscriptionPlan model
- [ ] Create ClientSubscription model
- [ ] Create PartnerSubscription model
- [ ] Create SubscriptionService
- [ ] Create FeatureFlagService
- [ ] Implement plan selection
- [ ] Implement subscription activation
- [ ] Implement feature checking middleware
- [ ] Build subscription management UI

### Phase 3: Role Management (Week 5-6)
- [ ] Create RoleTemplate model
- [ ] Create CompanyCustomRole model
- [ ] Create RoleManagementService
- [ ] Build role management UI (Client)
- [ ] Build role management UI (Partner)
- [ ] Implement custom role creation
- [ ] Implement permission assignment
- [ ] Integrate with Spatie Permission

### Phase 4: Multi-Account Access (Week 7-8)
- [ ] Create UserCompanyAccess model
- [ ] Create UserActiveContext model
- [ ] Create CompanyInvitation model
- [ ] Create AccountSelectorController
- [ ] Create CompanyInvitationService
- [ ] Build invitation system
- [ ] Build workspace switcher UI
- [ ] Implement account switching logic
- [ ] Update middleware for context switching

### Phase 5: Panel Separation (Week 9-10)
- [ ] Create ClientDashboardController
- [ ] Create PartnerDashboardController
- [ ] Create Partner\ExternalClientController
- [ ] Create Partner\ExternalClientLocationController
- [ ] Create Partner\ExternalClientMeasurementController
- [ ] Create Partner\ExternalClientDocumentController
- [ ] Create Partner\ExternalClientReportController
- [ ] Build client dashboard UI
- [ ] Build partner dashboard UI
- [ ] Build external client management UI
- [ ] Implement route separation
- [ ] Implement middleware for entity types
- [ ] Update shared components

### Phase 6: Usage Tracking (Week 11)
- [ ] Create UsageTracking model
- [ ] Create UsageTrackingService
- [ ] Implement usage tracking for all resources
- [ ] Build usage analytics UI
- [ ] Implement limit checking

### Phase 7: Testing & Optimization (Week 12-13)
- [ ] Unit tests
- [ ] Integration tests
- [ ] Performance testing
- [ ] Database optimization
- [ ] Caching implementation
- [ ] Query optimization

### Phase 8: Documentation & Deployment (Week 14)
- [ ] API documentation
- [ ] User guides
- [ ] Admin documentation
- [ ] Deployment plan
- [ ] Migration scripts

---

## 10. Key Services & Models

### Models

#### Subscription Models
```php
// app/Models/SubscriptionPlan.php
// app/Models/ClientSubscription.php
// app/Models/PartnerSubscription.php
```

#### Partner External Client Models
```php
// app/Models/PartnerExternalClient.php
// app/Models/PartnerExternalClientLocation.php
// app/Models/PartnerExternalClientMeasurement.php
// app/Models/PartnerExternalClientMeasurementData.php
// app/Models/PartnerExternalClientDocument.php
// app/Models/PartnerExternalClientReport.php
// app/Models/PartnerExternalClientEmissionBoundary.php
```

#### Access Management Models
```php
// app/Models/UserCompanyAccess.php
// app/Models/UserActiveContext.php
// app/Models/CompanyInvitation.php
// app/Models/RoleTemplate.php
// app/Models/CompanyCustomRole.php
```

#### Existing Models (Enhanced)
```php
// app/Models/User.php (enhanced with multi-account methods)
// app/Models/Company.php (enhanced with company_type)
// app/Models/Location.php (existing)
// app/Models/Measurement.php (existing)
// app/Models/DocumentUpload.php (existing)
```

### Services

#### SubscriptionService
```php
class SubscriptionService
{
    public function subscribeClient($companyId, $planId);
    public function subscribePartner($companyId, $planId);
    public function getActiveSubscription($companyId, $type);
    public function checkFeatureAccess($companyId, $featureCode);
    public function getPlanLimits($companyId);
    public function canAddMoreClients($partnerId);
    public function getClientLimit($partnerId);
}
```

#### FeatureFlagService
```php
class FeatureFlagService
{
    public function enableFeature($companyId, $featureCode);
    public function disableFeature($companyId, $featureCode);
    public function isFeatureEnabled($companyId, $featureCode);
    public function getEnabledFeatures($companyId);
}
```

#### RoleManagementService
```php
class RoleManagementService
{
    public function getAvailableTemplates($companyType);
    public function createCustomRole($companyId, $roleName, $permissions);
    public function updateCustomRole($roleId, $data);
    public function deleteCustomRole($roleId);
    public function assignRoleToUser($user, $role, $companyId = null);
}
```

#### PartnerExternalClientService
```php
class PartnerExternalClientService
{
    public function addExternalClient($partnerId, $clientData);
    public function updateExternalClient($clientId, $clientData);
    public function deleteExternalClient($clientId);
    public function getExternalClients($partnerId);
    public function canAddMoreClients($partnerId);
    public function getClientLimit($partnerId);
    public function getClientCount($partnerId);
}
```

#### CompanyInvitationService
```php
class CompanyInvitationService
{
    public function inviteUser($companyId, $email, $roleId, $invitedBy);
    public function acceptInvitation($token, $userId = null);
    public function revokeAccess($userCompanyAccessId);
    public function updateAccessRole($userCompanyAccessId, $roleId);
}
```

#### UsageTrackingService
```php
class UsageTrackingService
{
    public function trackUsage($companyId, $resourceType, $action, $quantity = 1);
    public function getUsage($companyId, $period = 'monthly');
    public function checkLimit($companyId, $resourceType);
    public function getRemainingQuota($companyId, $resourceType);
}
```

---

## Complete System Flow

### Direct Client Flow
```
1. Client registers → Creates company account
2. Client subscribes → Selects plan (Free/Starter/Growth/Pro)
3. Client adds staff → Assigns roles
4. Staff adds locations → Creates measurements
5. Staff uploads documents → Processes data
6. System generates reports
```

### Partner External Client Flow
```
1. Partner registers → Creates partner account
2. Partner subscribes → Selects plan (Free/Partner/Enterprise)
3. Partner adds external clients → Up to limit (2/10/unlimited)
4. Partner staff selects client → Adds client data
5. Partner staff adds locations → For selected client
6. Partner staff adds measurements → For selected location
7. Partner staff uploads documents → For selected client
8. System generates reports → Per client
9. Each client has completely separate data
```

### Multi-Account Staff Flow
```
1. Company owner invites staff → Email invitation
2. Staff accepts invitation → Creates/links account
3. System creates user_company_access record
4. Staff logs in → Sees account selector (if multiple access)
5. Staff selects account → System sets active context
6. Staff works in selected account → All queries filtered
7. Staff can switch accounts → Via dropdown
```

---

## Summary

### Finalized Architecture ✅

1. **Two Separate Entities**: Clients and Partners (complete separation)
2. **URL Structure**: `/dashboard` for clients, `/partner/dashboard` for partners
3. **Subscription Plans**:
   - Clients: Free, Starter, Growth, Pro (configurable by superadmin)
   - Partners: Free (2 clients), Partner (10 clients), Enterprise (unlimited) - configurable by superadmin
4. **Data Separation**: Separate tables for direct clients vs partner external clients
5. **Single Users Table**: All staff in one table
6. **Multi-Account Access**: Staff can access multiple companies with different roles
7. **Performance**: Single table approach handles 10K+ external clients easily
8. **Scalability**: Designed to handle thousands of users

### Key Principles

- **Separation**: Complete separation between clients and partners
- **Flexibility**: Configurable plans, features, and roles
- **Scalability**: Designed to handle thousands of users
- **Extensibility**: Easy to add new features and permissions
- **Performance**: Optimized with proper indexing and caching

---

## Next Steps

1. ✅ Review this master document
2. ✅ Confirm all design decisions
3. ✅ Begin Phase 1 implementation (Database Foundation)
4. ✅ Proceed with development phase by phase

---

**Document Status**: Finalized & Consolidated ✅  
**Ready for Implementation**: Yes 🚀

