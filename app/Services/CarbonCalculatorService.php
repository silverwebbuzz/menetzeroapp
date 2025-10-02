<?php

namespace App\Services;

use App\Models\EmissionSource;
use App\Models\EmissionFactor;
use Illuminate\Support\Facades\Log;

class CarbonCalculatorService
{
    /**
     * Calculate CO2e emissions for an emission source
     */
    public function calculateEmissions(EmissionSource $emissionSource): array
    {
        $results = [
            'scope1' => $this->calculateScope1($emissionSource),
            'scope2' => $this->calculateScope2($emissionSource),
            'scope3' => $this->calculateScope3($emissionSource),
        ];

        $results['total'] = $results['scope1'] + $results['scope2'] + $results['scope3'];

        // Update the emission source with calculated values
        $this->updateEmissionSource($emissionSource, $results);

        return $results;
    }

    /**
     * Calculate Scope 1 (Direct Emissions)
     */
    private function calculateScope1(EmissionSource $emissionSource): float
    {
        $scope1Total = 0;

        // Diesel fuel
        if ($emissionSource->diesel_litres) {
            $factor = $this->getEmissionFactor('diesel_fuel', 'litres', 'Scope1');
            $scope1Total += $emissionSource->diesel_litres * $factor;
        }

        // Petrol fuel
        if ($emissionSource->petrol_litres) {
            $factor = $this->getEmissionFactor('petrol_fuel', 'litres', 'Scope1');
            $scope1Total += $emissionSource->petrol_litres * $factor;
        }

        // Natural gas
        if ($emissionSource->natural_gas_m3) {
            $factor = $this->getEmissionFactor('natural_gas', 'm3', 'Scope1');
            $scope1Total += $emissionSource->natural_gas_m3 * $factor;
        }

        // Refrigerant
        if ($emissionSource->refrigerant_kg) {
            $factor = $this->getEmissionFactor('refrigerant', 'kg', 'Scope1');
            $scope1Total += $emissionSource->refrigerant_kg * $factor;
        }

        // Other direct emissions (already in CO2e)
        if ($emissionSource->other_emissions) {
            $scope1Total += $emissionSource->other_emissions;
        }

        return round($scope1Total, 2);
    }

    /**
     * Calculate Scope 2 (Purchased Energy)
     */
    private function calculateScope2(EmissionSource $emissionSource): float
    {
        $scope2Total = 0;

        // Electricity
        if ($emissionSource->electricity_kwh) {
            $factor = $this->getEmissionFactor('electricity', 'kWh', 'Scope2');
            $scope2Total += $emissionSource->electricity_kwh * $factor;
        }

        // District cooling
        if ($emissionSource->district_cooling_kwh) {
            $factor = $this->getEmissionFactor('district_cooling', 'kWh', 'Scope2');
            $scope2Total += $emissionSource->district_cooling_kwh * $factor;
        }

        return round($scope2Total, 2);
    }

    /**
     * Calculate Scope 3 (Other Indirect Emissions)
     */
    private function calculateScope3(EmissionSource $emissionSource): float
    {
        $scope3Total = 0;

        // Business travel - flights
        if ($emissionSource->business_travel_flights_km) {
            $factor = $this->getEmissionFactor('business_travel_flights', 'km', 'Scope3');
            $scope3Total += $emissionSource->business_travel_flights_km * $factor;
        }

        // Car hire
        if ($emissionSource->car_hire_km) {
            $factor = $this->getEmissionFactor('car_hire', 'km', 'Scope3');
            $scope3Total += $emissionSource->car_hire_km * $factor;
        }

        // Waste
        if ($emissionSource->waste_tonnes) {
            $factor = $this->getEmissionFactor('waste_landfill', 'tonnes', 'Scope3');
            $scope3Total += $emissionSource->waste_tonnes * $factor;
        }

        // Water consumption
        if ($emissionSource->water_m3) {
            $factor = $this->getEmissionFactor('water_consumption', 'm3', 'Scope3');
            $scope3Total += $emissionSource->water_m3 * $factor;
        }

        // Purchased goods (already in CO2e)
        if ($emissionSource->purchased_goods) {
            $scope3Total += $emissionSource->purchased_goods;
        }

        return round($scope3Total, 2);
    }

    /**
     * Get emission factor from database
     */
    private function getEmissionFactor(string $activityType, string $unit, string $scope): float
    {
        $factor = EmissionFactor::where('activity_type', $activityType)
            ->where('unit', $unit)
            ->where('scope', $scope)
            ->first();

        if ($factor) {
            return $factor->factor_value;
        }

        // Fallback to default UAE factors if not found in database
        return $this->getDefaultUaeFactor($activityType, $unit, $scope);
    }

    /**
     * Default UAE emission factors (fallback)
     */
    private function getDefaultUaeFactor(string $activityType, string $unit, string $scope): float
    {
        $defaultFactors = [
            // Scope 1
            'diesel_fuel' => ['litres' => 2.68],
            'petrol_fuel' => ['litres' => 2.31],
            'natural_gas' => ['m3' => 0.202],
            'refrigerant' => ['kg' => 1.0],
            
            // Scope 2
            'electricity' => ['kWh' => 0.424],
            'district_cooling' => ['kWh' => 0.3],
            
            // Scope 3
            'business_travel_flights' => ['km' => 0.285],
            'car_hire' => ['km' => 0.2],
            'waste_landfill' => ['tonnes' => 1.9],
            'water_consumption' => ['m3' => 0.5],
        ];

        return $defaultFactors[$activityType][$unit] ?? 0.0;
    }

    /**
     * Update emission source with calculated values
     */
    private function updateEmissionSource(EmissionSource $emissionSource, array $results): void
    {
        $emissionSource->scope1_total = $results['scope1'];
        $emissionSource->scope2_total = $results['scope2'];
        $emissionSource->scope3_total = $results['scope3'];
        $emissionSource->grand_total = $results['total'];
        $emissionSource->save();

        Log::info('Carbon emissions calculated', [
            'emission_source_id' => $emissionSource->id,
            'company' => $emissionSource->company_name,
            'scope1' => $results['scope1'],
            'scope2' => $results['scope2'],
            'scope3' => $results['scope3'],
            'total' => $results['total']
        ]);
    }

    /**
     * Calculate emissions from OCR data
     */
    public function calculateFromOcrData(array $ocrData, EmissionSource $emissionSource): array
    {
        // Update emission source with OCR data
        foreach ($ocrData as $field => $value) {
            if (in_array($field, $emissionSource->getFillable())) {
                $emissionSource->$field = $value;
            }
        }
        $emissionSource->save();

        // Calculate emissions
        return $this->calculateEmissions($emissionSource);
    }

    /**
     * Get emission breakdown by category
     */
    public function getEmissionBreakdown(EmissionSource $emissionSource): array
    {
        $breakdown = [];

        // Scope 1 breakdown
        if ($emissionSource->diesel_litres) {
            $breakdown['diesel_fuel'] = [
                'quantity' => $emissionSource->diesel_litres,
                'unit' => 'litres',
                'factor' => $this->getEmissionFactor('diesel_fuel', 'litres', 'Scope1'),
                'co2e' => $emissionSource->diesel_litres * $this->getEmissionFactor('diesel_fuel', 'litres', 'Scope1'),
                'scope' => 'Scope 1'
            ];
        }

        if ($emissionSource->petrol_litres) {
            $breakdown['petrol_fuel'] = [
                'quantity' => $emissionSource->petrol_litres,
                'unit' => 'litres',
                'factor' => $this->getEmissionFactor('petrol_fuel', 'litres', 'Scope1'),
                'co2e' => $emissionSource->petrol_litres * $this->getEmissionFactor('petrol_fuel', 'litres', 'Scope1'),
                'scope' => 'Scope 1'
            ];
        }

        if ($emissionSource->natural_gas_m3) {
            $breakdown['natural_gas'] = [
                'quantity' => $emissionSource->natural_gas_m3,
                'unit' => 'm3',
                'factor' => $this->getEmissionFactor('natural_gas', 'm3', 'Scope1'),
                'co2e' => $emissionSource->natural_gas_m3 * $this->getEmissionFactor('natural_gas', 'm3', 'Scope1'),
                'scope' => 'Scope 1'
            ];
        }

        // Scope 2 breakdown
        if ($emissionSource->electricity_kwh) {
            $breakdown['electricity'] = [
                'quantity' => $emissionSource->electricity_kwh,
                'unit' => 'kWh',
                'factor' => $this->getEmissionFactor('electricity', 'kWh', 'Scope2'),
                'co2e' => $emissionSource->electricity_kwh * $this->getEmissionFactor('electricity', 'kWh', 'Scope2'),
                'scope' => 'Scope 2'
            ];
        }

        if ($emissionSource->district_cooling_kwh) {
            $breakdown['district_cooling'] = [
                'quantity' => $emissionSource->district_cooling_kwh,
                'unit' => 'kWh',
                'factor' => $this->getEmissionFactor('district_cooling', 'kWh', 'Scope2'),
                'co2e' => $emissionSource->district_cooling_kwh * $this->getEmissionFactor('district_cooling', 'kWh', 'Scope2'),
                'scope' => 'Scope 2'
            ];
        }

        // Scope 3 breakdown
        if ($emissionSource->business_travel_flights_km) {
            $breakdown['business_travel'] = [
                'quantity' => $emissionSource->business_travel_flights_km,
                'unit' => 'km',
                'factor' => $this->getEmissionFactor('business_travel_flights', 'km', 'Scope3'),
                'co2e' => $emissionSource->business_travel_flights_km * $this->getEmissionFactor('business_travel_flights', 'km', 'Scope3'),
                'scope' => 'Scope 3'
            ];
        }

        if ($emissionSource->waste_tonnes) {
            $breakdown['waste'] = [
                'quantity' => $emissionSource->waste_tonnes,
                'unit' => 'tonnes',
                'factor' => $this->getEmissionFactor('waste_landfill', 'tonnes', 'Scope3'),
                'co2e' => $emissionSource->waste_tonnes * $this->getEmissionFactor('waste_landfill', 'tonnes', 'Scope3'),
                'scope' => 'Scope 3'
            ];
        }

        if ($emissionSource->water_m3) {
            $breakdown['water'] = [
                'quantity' => $emissionSource->water_m3,
                'unit' => 'm3',
                'factor' => $this->getEmissionFactor('water_consumption', 'm3', 'Scope3'),
                'co2e' => $emissionSource->water_m3 * $this->getEmissionFactor('water_consumption', 'm3', 'Scope3'),
                'scope' => 'Scope 3'
            ];
        }

        return $breakdown;
    }

    /**
     * Recalculate all emissions for a company
     */
    public function recalculateAllEmissions(string $companyName): array
    {
        $emissionSources = EmissionSource::where('company_name', $companyName)->get();
        $results = [];

        foreach ($emissionSources as $emissionSource) {
            $results[] = $this->calculateEmissions($emissionSource);
        }

        return $results;
    }
}
