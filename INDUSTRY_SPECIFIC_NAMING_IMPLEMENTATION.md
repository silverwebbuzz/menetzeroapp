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

