<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmissionFactor;

class UaeEmissionFactorsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌍 Seeding UAE emission factors...');

        $emissionFactors = [
            // Scope 1 - Direct Emissions
            [
                'activity_type' => 'diesel_fuel',
                'unit' => 'litres',
                'factor_value' => 2.680,
                'scope' => 'Scope1',
                'source' => 'IPCC',
                'year' => 2024,
                'region' => 'UAE',
                'description' => 'Diesel fuel combustion emissions'
            ],
            [
                'activity_type' => 'petrol_fuel',
                'unit' => 'litres',
                'factor_value' => 2.310,
                'scope' => 'Scope1',
                'source' => 'IPCC',
                'year' => 2024,
                'region' => 'UAE',
                'description' => 'Petrol/gasoline fuel combustion emissions'
            ],
            [
                'activity_type' => 'natural_gas',
                'unit' => 'm3',
                'factor_value' => 0.202,
                'scope' => 'Scope1',
                'source' => 'IPCC',
                'year' => 2024,
                'region' => 'UAE',
                'description' => 'Natural gas combustion emissions'
            ],
            [
                'activity_type' => 'refrigerant',
                'unit' => 'kg',
                'factor_value' => 1.000,
                'scope' => 'Scope1',
                'source' => 'IPCC',
                'year' => 2024,
                'region' => 'UAE',
                'description' => 'Refrigerant gas emissions (HFCs)'
            ],
            [
                'activity_type' => 'lpg',
                'unit' => 'kg',
                'factor_value' => 1.500,
                'scope' => 'Scope1',
                'source' => 'IPCC',
                'year' => 2024,
                'region' => 'UAE',
                'description' => 'LPG combustion emissions'
            ],

            // Scope 2 - Purchased Energy
            [
                'activity_type' => 'electricity',
                'unit' => 'kWh',
                'factor_value' => 0.424,
                'scope' => 'Scope2',
                'source' => 'MOCCAE',
                'year' => 2024,
                'region' => 'UAE',
                'description' => 'UAE grid electricity emission factor'
            ],
            [
                'activity_type' => 'district_cooling',
                'unit' => 'kWh',
                'factor_value' => 0.300,
                'scope' => 'Scope2',
                'source' => 'MOCCAE',
                'year' => 2024,
                'region' => 'UAE',
                'description' => 'District cooling system emissions'
            ],
            [
                'activity_type' => 'steam',
                'unit' => 'kWh',
                'factor_value' => 0.350,
                'scope' => 'Scope2',
                'source' => 'MOCCAE',
                'year' => 2024,
                'region' => 'UAE',
                'description' => 'Steam generation emissions'
            ],

            // Scope 3 - Other Indirect Emissions
            [
                'activity_type' => 'business_travel_flights',
                'unit' => 'km',
                'factor_value' => 0.285,
                'scope' => 'Scope3',
                'source' => 'IPCC',
                'year' => 2024,
                'region' => 'UAE',
                'description' => 'Business travel by air emissions'
            ],
            [
                'activity_type' => 'car_hire',
                'unit' => 'km',
                'factor_value' => 0.200,
                'scope' => 'Scope3',
                'source' => 'IPCC',
                'year' => 2024,
                'region' => 'UAE',
                'description' => 'Car rental/hire emissions'
            ],
            [
                'activity_type' => 'employee_commuting',
                'unit' => 'km',
                'factor_value' => 0.150,
                'scope' => 'Scope3',
                'source' => 'IPCC',
                'year' => 2024,
                'region' => 'UAE',
                'description' => 'Employee commuting emissions'
            ],
            [
                'activity_type' => 'waste_landfill',
                'unit' => 'tonnes',
                'factor_value' => 1.900,
                'scope' => 'Scope3',
                'source' => 'IPCC',
                'year' => 2024,
                'region' => 'UAE',
                'description' => 'Waste disposal in landfill emissions'
            ],
            [
                'activity_type' => 'waste_incineration',
                'unit' => 'tonnes',
                'factor_value' => 0.600,
                'scope' => 'Scope3',
                'source' => 'IPCC',
                'year' => 2024,
                'region' => 'UAE',
                'description' => 'Waste incineration emissions'
            ],
            [
                'activity_type' => 'waste_recycling',
                'unit' => 'tonnes',
                'factor_value' => 0.200,
                'scope' => 'Scope3',
                'source' => 'IPCC',
                'year' => 2024,
                'region' => 'UAE',
                'description' => 'Waste recycling emissions'
            ],
            [
                'activity_type' => 'water_consumption',
                'unit' => 'm3',
                'factor_value' => 0.500,
                'scope' => 'Scope3',
                'source' => 'MOCCAE',
                'year' => 2024,
                'region' => 'UAE',
                'description' => 'Water consumption and treatment emissions'
            ],
            [
                'activity_type' => 'water_desalination',
                'unit' => 'm3',
                'factor_value' => 1.200,
                'scope' => 'Scope3',
                'source' => 'MOCCAE',
                'year' => 2024,
                'region' => 'UAE',
                'description' => 'Desalinated water emissions (UAE specific)'
            ],
            [
                'activity_type' => 'purchased_goods',
                'unit' => 'kg',
                'factor_value' => 0.100,
                'scope' => 'Scope3',
                'source' => 'IPCC',
                'year' => 2024,
                'region' => 'UAE',
                'description' => 'Purchased goods and services emissions'
            ],
            [
                'activity_type' => 'freight_transport',
                'unit' => 'km',
                'factor_value' => 0.180,
                'scope' => 'Scope3',
                'source' => 'IPCC',
                'year' => 2024,
                'region' => 'UAE',
                'description' => 'Freight transport emissions'
            ],
            [
                'activity_type' => 'hotel_accommodation',
                'unit' => 'nights',
                'factor_value' => 15.000,
                'scope' => 'Scope3',
                'source' => 'IPCC',
                'year' => 2024,
                'region' => 'UAE',
                'description' => 'Hotel accommodation emissions'
            ],

            // UAE-Specific Factors
            [
                'activity_type' => 'solar_electricity',
                'unit' => 'kWh',
                'factor_value' => 0.041,
                'scope' => 'Scope2',
                'source' => 'MOCCAE',
                'year' => 2024,
                'region' => 'UAE',
                'description' => 'Solar PV electricity emissions'
            ],
            [
                'activity_type' => 'wind_electricity',
                'unit' => 'kWh',
                'factor_value' => 0.011,
                'scope' => 'Scope2',
                'source' => 'MOCCAE',
                'year' => 2024,
                'region' => 'UAE',
                'description' => 'Wind electricity emissions'
            ],
            [
                'activity_type' => 'nuclear_electricity',
                'unit' => 'kWh',
                'factor_value' => 0.012,
                'scope' => 'Scope2',
                'source' => 'MOCCAE',
                'year' => 2024,
                'region' => 'UAE',
                'description' => 'Nuclear electricity emissions'
            ],
        ];

        foreach ($emissionFactors as $factor) {
            EmissionFactor::create($factor);
        }

        $this->command->info('✅ ' . count($emissionFactors) . ' UAE emission factors created');
    }
}
