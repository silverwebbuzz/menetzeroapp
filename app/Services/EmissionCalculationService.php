<?php

namespace App\Services;

use App\Models\EmissionFactor;
use App\Models\EmissionFactorSelectionRule;
use App\Models\EmissionGwpValue;
use App\Models\EmissionUnitConversion;
use App\Models\EmissionIndustryLabel;
use Illuminate\Support\Facades\Log;

class EmissionCalculationService
{
    /**
     * Select appropriate emission factor based on conditions
     *
     * @param int $emissionSourceId
     * @param array $conditions (region, fuel_type, unit, industry_category_id, etc.)
     * @return EmissionFactor|null
     */
    public function selectEmissionFactor($emissionSourceId, $conditions = [])
    {
        // Get selection rules for this source
        $rules = EmissionFactorSelectionRule::where('emission_source_id', $emissionSourceId)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();

        // Try to match rules
        foreach ($rules as $rule) {
            if ($this->matchRuleConditions($rule, $conditions)) {
                $factor = EmissionFactor::find($rule->emission_factor_id);
                if ($factor && $factor->is_active) {
                    return $factor;
                }
            }
        }

        // If no rule matches, try to find factor matching conditions directly
        $factorQuery = EmissionFactor::where('emission_source_id', $emissionSourceId)
            ->where('is_active', true);

        // Filter by fuel_category if provided
        if (!empty($conditions['fuel_category'])) {
            $factorQuery->where('fuel_category', $conditions['fuel_category']);
        }

        // Filter by fuel_type if provided (this is critical for Heat/Steam/Cooling and Fuel types)
        if (!empty($conditions['fuel_type'])) {
            $factorQuery->where('fuel_type', $conditions['fuel_type']);
        }
        
        // Filter by vehicle_type if provided (for distance-based vehicle calculations)
        if (!empty($conditions['vehicle_category'])) {
            $factorQuery->where('vehicle_category', $conditions['vehicle_category']);
        }
        if (!empty($conditions['vehicle_type'])) {
            $factorQuery->where('vehicle_type', $conditions['vehicle_type']);
        }
        

        // Check if this is a fugitive emission (refrigerants) - GWP values are global and unit conversion is handled separately
        // $isFugitiveEmission = false;
        // $emissionSource = \App\Models\EmissionSourceMaster::find($emissionSourceId);

        // if ($emissionSource && $emissionSource->emission_type === 'fugitive') {
        //     $isFugitiveEmission = true;
        // }

        // Filter by unit if provided
        // For fugitive emissions (refrigerants), skip unit filtering since:
        // 1. All refrigerant factors use 'kg' as base unit
        // 2. Unit conversion is handled in calculateCO2e method
        // 3. We just need to match by fuel_type (refrigerant type)
        if (!empty($conditions['unit'])) {
            $factorQuery->where('unit', $conditions['unit']);
        }

        // Filter by region if provided
        // Note: For refrigerants (fugitive emissions), GWP values are global, so region filter is less strict
        if (!empty($conditions['region'])) {
            $factorQuery->where('region', $conditions['region']);
        }

        // IMPORTANT: When fuel_type is specified, prioritize exact match over is_default
        // This ensures Steam (0.275000) is selected when Steam is chosen, not Heat (0.226300)
        if (!empty($conditions['fuel_type'])) {
            // First try to get the factor matching fuel_type (prefer default if multiple match)
            $matchingFactor = (clone $factorQuery)
                ->where('is_default', true)
                ->first();
            
            if ($matchingFactor) {
                return $matchingFactor;
            }
            
            // If no default matches, get highest priority matching fuel_type
            $matchingFactor = $factorQuery->orderBy('priority', 'desc')->first();
            if ($matchingFactor) {
                return $matchingFactor;
            }
        }

        // If no fuel_type specified, try to get default factor first
        $defaultFactor = (clone $factorQuery)
            ->where('is_default', true)
            ->first();

        if ($defaultFactor) {
            return $defaultFactor;
        }

        // Fallback: get most common factor (highest priority) matching conditions
        return $factorQuery->orderBy('priority', 'desc')->first();
    }

    /**
     * Match rule conditions with provided conditions
     *
     * @param EmissionFactorSelectionRule $rule
     * @param array $conditions
     * @return bool
     */
    private function matchRuleConditions($rule, $conditions)
    {
        if (!$rule->conditions) {
            return true; // No conditions = match all
        }

        $ruleConditions = is_array($rule->conditions) ? $rule->conditions : json_decode($rule->conditions, true);

        if (!is_array($ruleConditions)) {
            return true;
        }

        foreach ($ruleConditions as $key => $value) {
            if ($value !== null && (!isset($conditions[$key]) || $conditions[$key] != $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate CO2e emissions
     *
     * @param float $quantity
     * @param EmissionFactor $factor
     * @param string $userUnit
     * @param string $gwpVersion
     * @return array ['co2e', 'co2', 'ch4', 'n2o']
     */
    public function calculateCO2e($quantity, $factor, $userUnit = null, $gwpVersion = 'AR6')
    {
        // Convert unit if needed
        $convertedQuantity = $quantity;
        if ($userUnit && $userUnit !== $factor->unit) {
            $convertedQuantity = $this->convertUnit($quantity, $userUnit, $factor->unit);
        }
        
        // If factor has separate gas factors, calculate multi-gas
        if ($factor->co2_factor || $factor->ch4_factor || $factor->n2o_factor) {
            // Use exact factor values with full precision (no rounding until final result)
            $co2Factor = is_string($factor->co2_factor) ? (float) $factor->co2_factor : (float) ($factor->co2_factor ?? 0);
            $ch4Factor = is_string($factor->ch4_factor) ? (float) $factor->ch4_factor : (float) ($factor->ch4_factor ?? 0);
            $n2oFactor = is_string($factor->n2o_factor) ? (float) $factor->n2o_factor : (float) ($factor->n2o_factor ?? 0);
            $co2eFactor = is_string($factor->total_co2e_factor) ? (float) $factor->total_co2e_factor : (float) ($factor->total_co2e_factor ?? 0);
            
            $co2 = (float) $convertedQuantity * $co2Factor;
            $ch4 = (float) $convertedQuantity * $ch4Factor;
            $n2o = (float) $convertedQuantity * $n2oFactor;
            $co2e = (float) $convertedQuantity * $co2eFactor;

            // Get GWP values
            $gwpValues = $this->getGwpValues($gwpVersion);
            $gwpCh4 = $gwpValues['CH4_FOSSIL'] ?? 27.2;
            $gwpN2O = $gwpValues['N2O'] ?? 273;

            // Calculate CO2e with full precision
            // $co2e = $co2 + ($ch4 * $gwpCh4) + ($n2o * $gwpN2O);
           
            // Round only the final results to 6 decimal places for display/storage
            // Format as strings to preserve precision in JSON
            return [
                'co2e' => number_format(round($co2e, 6), 6, '.', ''),
                'co2' => number_format(round($co2, 6), 6, '.', ''),
                'ch4' => number_format(round($ch4, 6), 6, '.', ''),
                'n2o' => number_format(round($n2o, 6), 6, '.', ''),
            ];
        }

        // Single gas factor
        // Use exact factor value with full precision (no rounding until final result)
        $factorValue = $factor->total_co2e_factor ?? $factor->factor_value;
        // Ensure we're using the exact decimal value from database
        $factorValue = is_string($factorValue) ? (float) $factorValue : (float) $factorValue;
        $co2e = (float) $convertedQuantity * $factorValue;

        // Round only the final result to 6 decimal places for display/storage
        // Format as string to preserve precision in JSON
        return [
            'co2e' => number_format(round($co2e, 6), 6, '.', ''),
            'co2' => null,
            'ch4' => null,
            'n2o' => null,
        ];
    }

    /**
     * Convert unit
     *
     * @param float $value
     * @param string $fromUnit
     * @param string $toUnit
     * @return float
     */
    public function convertUnit($value, $fromUnit, $toUnit)
    {
        if ($fromUnit === $toUnit) {
            return $value;
        }

        $conversion = EmissionUnitConversion::where('from_unit', $fromUnit)
            ->where('to_unit', $toUnit)
            ->where('is_active', true)
            ->first();

        if ($conversion) {
            return $value * $conversion->conversion_factor;
        }

        // Try reverse conversion
        $reverseConversion = EmissionUnitConversion::where('from_unit', $toUnit)
            ->where('to_unit', $fromUnit)
            ->where('is_active', true)
            ->first();

        if ($reverseConversion) {
            return $value / $reverseConversion->conversion_factor;
        }

        // If no conversion found, return original value
        Log::warning("Unit conversion not found: {$fromUnit} to {$toUnit}");
        return $value;
    }

    /**
     * Get GWP values for a specific version
     *
     * @param string $version (AR4, AR5, AR6)
     * @return array
     */
    private function getGwpValues($version = 'AR6')
    {
        $gwpValues = EmissionGwpValue::where('gwp_version', $version)
            ->where('is_active', true)
            ->get()
            ->keyBy('gas_code')
            ->map(function ($value) {
                return $value->gwp_100_year;
            })
            ->toArray();

        return $gwpValues;
    }

    /**
     * Get user-friendly name based on industry
     * Supports hierarchical matching (Level 1, 2, 3) and cascading to children
     *
     * @param int $emissionSourceId
     * @param int|null $industryCategoryId
     * @return string|null
     */
    public function getUserFriendlyName($emissionSourceId, $industryCategoryId = null)
    {
        $label = $this->getIndustryLabel($emissionSourceId, $industryCategoryId);
        return $label ? $label->user_friendly_name : null;
    }

    /**
     * Get full industry label with all details (name, description, equipment, etc.)
     * Supports hierarchical matching (Level 1, 2, 3) and cascading to children
     *
     * @param int $emissionSourceId
     * @param int|null $industryCategoryId
     * @return EmissionIndustryLabel|null
     */
    public function getIndustryLabel($emissionSourceId, $industryCategoryId = null)
    {
        if (!$industryCategoryId) {
            return null;
        }

        // Get the company's industry category to determine its level
        $companyCategory = \App\Models\MasterIndustryCategory::find($industryCategoryId);
        if (!$companyCategory) {
            return null;
        }

        // Determine the level of the company's category
        $companyLevel = $this->getCategoryLevel($companyCategory);

        // Try to find exact match first (most specific)
        $label = EmissionIndustryLabel::where('emission_source_id', $emissionSourceId)
            ->where('industry_category_id', $industryCategoryId)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->first();

        if ($label) {
            return $label;
        }

        // Try to find parent category matches (cascading up)
        $parentIds = $this->getParentCategoryIds($companyCategory);
        foreach ($parentIds as $parentId) {
            $parentCategory = \App\Models\MasterIndustryCategory::find($parentId);
            if (!$parentCategory) continue;
            
            $parentLevel = $this->getCategoryLevel($parentCategory);
            
            $label = EmissionIndustryLabel::where('emission_source_id', $emissionSourceId)
                ->where('industry_category_id', $parentId)
                ->where('match_level', $parentLevel)
                ->where('is_active', true)
                ->where(function($q) {
                    $q->where('also_match_children', true)
                      ->orWhereNull('also_match_children');
                })
                ->orderBy('display_order')
                ->first();

            if ($label) {
                return $label;
            }
        }

        return null;
    }

    /**
     * Get category level (1, 2, or 3) based on parent relationships
     *
     * @param \App\Models\MasterIndustryCategory $category
     * @return int
     */
    private function getCategoryLevel($category)
    {
        // Use the level field if it exists, otherwise calculate from parent relationships
        if (isset($category->level) && $category->level) {
            return $category->level;
        }

        // Fallback: calculate from parent relationships
        if (!$category->parent_id) {
            return 1; // Top level
        }

        $parent = \App\Models\MasterIndustryCategory::find($category->parent_id);
        if (!$parent || !$parent->parent_id) {
            return 2; // Second level
        }

        return 3; // Third level
    }

    /**
     * Get all parent category IDs (for cascading up)
     *
     * @param \App\Models\MasterIndustryCategory $category
     * @return array
     */
    private function getParentCategoryIds($category)
    {
        $parentIds = [];
        $current = $category;

        while ($current && $current->parent_id) {
            $parentIds[] = $current->parent_id;
            $current = \App\Models\MasterIndustryCategory::find($current->parent_id);
        }

        return $parentIds;
    }

    /**
     * Get available units for an emission source
     *
     * @param int $emissionSourceId
     * @return array
     */
    public function getAvailableUnits($emissionSourceId)
    {
        return EmissionFactor::where('emission_source_id', $emissionSourceId)
            ->where('is_active', true)
            ->distinct()
            ->pluck('unit')
            ->toArray();
    }
}

