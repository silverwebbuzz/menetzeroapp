<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmissionFactorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $emissionFactors = [
            // Fuel factors
            [
                'name' => 'Gasoline',
                'category' => 'fuel',
                'subcategory' => 'gasoline',
                'unit' => 'liters',
                'co2_factor' => 2.31,
                'ch4_factor' => 0.0,
                'n2o_factor' => 0.0,
                'total_gwp' => 2.31,
                'source' => 'EPA',
                'year' => 2024,
                'region' => 'US',
                'description' => 'Gasoline combustion emission factor',
                'is_active' => true,
            ],
            [
                'name' => 'Diesel',
                'category' => 'fuel',
                'subcategory' => 'diesel',
                'unit' => 'liters',
                'co2_factor' => 2.68,
                'ch4_factor' => 0.0,
                'n2o_factor' => 0.0,
                'total_gwp' => 2.68,
                'source' => 'EPA',
                'year' => 2024,
                'region' => 'US',
                'description' => 'Diesel combustion emission factor',
                'is_active' => true,
            ],
            [
                'name' => 'Natural Gas',
                'category' => 'fuel',
                'subcategory' => 'natural_gas',
                'unit' => 'cubic_meters',
                'co2_factor' => 1.96,
                'ch4_factor' => 0.0,
                'n2o_factor' => 0.0,
                'total_gwp' => 1.96,
                'source' => 'EPA',
                'year' => 2024,
                'region' => 'US',
                'description' => 'Natural gas combustion emission factor',
                'is_active' => true,
            ],
            // Electricity factors
            [
                'name' => 'Grid Electricity (US Average)',
                'category' => 'electricity',
                'subcategory' => 'grid',
                'unit' => 'kWh',
                'co2_factor' => 0.409,
                'ch4_factor' => 0.0,
                'n2o_factor' => 0.0,
                'total_gwp' => 0.409,
                'source' => 'EPA',
                'year' => 2024,
                'region' => 'US',
                'description' => 'US average grid electricity emission factor',
                'is_active' => true,
            ],
            // Transportation factors
            [
                'name' => 'Car Travel (Gasoline)',
                'category' => 'transportation',
                'subcategory' => 'car',
                'unit' => 'miles',
                'co2_factor' => 0.411,
                'ch4_factor' => 0.0,
                'n2o_factor' => 0.0,
                'total_gwp' => 0.411,
                'source' => 'EPA',
                'year' => 2024,
                'region' => 'US',
                'description' => 'Passenger car travel emission factor',
                'is_active' => true,
            ],
            [
                'name' => 'Air Travel (Domestic)',
                'category' => 'transportation',
                'subcategory' => 'airplane',
                'unit' => 'miles',
                'co2_factor' => 0.255,
                'ch4_factor' => 0.0,
                'n2o_factor' => 0.0,
                'total_gwp' => 0.255,
                'source' => 'EPA',
                'year' => 2024,
                'region' => 'US',
                'description' => 'Domestic air travel emission factor',
                'is_active' => true,
            ],
            // Waste factors
            [
                'name' => 'Landfill Waste',
                'category' => 'waste',
                'subcategory' => 'landfill',
                'unit' => 'kg',
                'co2_factor' => 0.5,
                'ch4_factor' => 0.0,
                'n2o_factor' => 0.0,
                'total_gwp' => 0.5,
                'source' => 'EPA',
                'year' => 2024,
                'region' => 'US',
                'description' => 'Landfill waste emission factor',
                'is_active' => true,
            ],
        ];

        foreach ($emissionFactors as $factor) {
            \App\Models\EmissionFactor::create($factor);
        }
    }
}
