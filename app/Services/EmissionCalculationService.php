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

        // If no rule matches, get default factor
        $defaultFactor = EmissionFactor::where('emission_source_id', $emissionSourceId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();

        if ($defaultFactor) {
            return $defaultFactor;
        }

        // Fallback: get most common factor (highest priority)
        return EmissionFactor::where('emission_source_id', $emissionSourceId)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->first();
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
            $co2 = $convertedQuantity * ($factor->co2_factor ?? 0);
            $ch4 = $convertedQuantity * ($factor->ch4_factor ?? 0);
            $n2o = $convertedQuantity * ($factor->n2o_factor ?? 0);

            // Get GWP values
            $gwpValues = $this->getGwpValues($gwpVersion);
            $gwpCh4 = $gwpValues['CH4_FOSSIL'] ?? 27.2;
            $gwpN2O = $gwpValues['N2O'] ?? 273;

            $co2e = $co2 + ($ch4 * $gwpCh4) + ($n2o * $gwpN2O);

            return [
                'co2e' => round($co2e, 6),
                'co2' => round($co2, 6),
                'ch4' => round($ch4, 6),
                'n2o' => round($n2o, 6),
            ];
        }

        // Single gas factor
        $factorValue = $factor->total_co2e_factor ?? $factor->factor_value;
        $co2e = $convertedQuantity * $factorValue;

        return [
            'co2e' => round($co2e, 6),
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
     *
     * @param int $emissionSourceId
     * @param int|null $industryCategoryId
     * @return string|null
     */
    public function getUserFriendlyName($emissionSourceId, $industryCategoryId = null)
    {
        if (!$industryCategoryId) {
            return null;
        }

        $label = EmissionIndustryLabel::where('emission_source_id', $emissionSourceId)
            ->where('industry_category_id', $industryCategoryId)
            ->where('is_active', true)
            ->first();

        return $label ? $label->user_friendly_name : null;
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

