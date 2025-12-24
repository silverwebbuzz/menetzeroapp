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

