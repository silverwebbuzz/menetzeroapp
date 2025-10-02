<?php

namespace Database\Factories;

use App\Models\EmissionFactor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmissionFactor>
 */
class EmissionFactorFactory extends Factory
{
    protected $model = EmissionFactor::class;

    public function definition(): array
    {
        $categories = [
            'Energy' => [
                'Grid Electricity', 'Solar PV', 'Wind Power', 'Natural Gas', 'Diesel Generator'
            ],
            'Transport' => [
                'Diesel Fuel', 'Petrol', 'LPG', 'CNG', 'Electric Vehicle'
            ],
            'Waste' => [
                'Landfill Mixed Waste', 'Incineration', 'Recycling Paper', 'Recycling Plastic', 'Composting'
            ],
            'Industrial' => [
                'Cement Production', 'Steel Production', 'Aluminum Production', 'Chemical Processing'
            ],
            'Agriculture' => [
                'Cattle Enteric Fermentation', 'Manure Management', 'Rice Cultivation', 'Fertilizer Use'
            ]
        ];

        $category = fake()->randomElement(array_keys($categories));
        $subcategory = fake()->randomElement($categories[$category]);

        $factorValues = [
            'Grid Electricity' => 0.424,
            'Solar PV' => 0.041,
            'Wind Power' => 0.011,
            'Natural Gas' => 0.202,
            'Diesel Generator' => 0.778,
            'Diesel Fuel' => 2.680,
            'Petrol' => 2.310,
            'LPG' => 1.500,
            'CNG' => 1.200,
            'Electric Vehicle' => 0.100,
            'Landfill Mixed Waste' => 1.900,
            'Incineration' => 0.600,
            'Recycling Paper' => 0.200,
            'Recycling Plastic' => 0.300,
            'Composting' => 0.100,
            'Cement Production' => 0.900,
            'Steel Production' => 1.800,
            'Aluminum Production' => 8.200,
            'Chemical Processing' => 2.500,
            'Cattle Enteric Fermentation' => 45.000,
            'Manure Management' => 2.500,
            'Rice Cultivation' => 1.200,
            'Fertilizer Use' => 0.300
        ];

        $units = [
            'Grid Electricity' => 'kgCO₂e/kWh',
            'Solar PV' => 'kgCO₂e/kWh',
            'Wind Power' => 'kgCO₂e/kWh',
            'Natural Gas' => 'kgCO₂e/kWh',
            'Diesel Generator' => 'kgCO₂e/kWh',
            'Diesel Fuel' => 'kgCO₂e/litre',
            'Petrol' => 'kgCO₂e/litre',
            'LPG' => 'kgCO₂e/kg',
            'CNG' => 'kgCO₂e/kg',
            'Electric Vehicle' => 'kgCO₂e/km',
            'Landfill Mixed Waste' => 'kgCO₂e/kg',
            'Incineration' => 'kgCO₂e/kg',
            'Recycling Paper' => 'kgCO₂e/kg',
            'Recycling Plastic' => 'kgCO₂e/kg',
            'Composting' => 'kgCO₂e/kg',
            'Cement Production' => 'kgCO₂e/kg',
            'Steel Production' => 'kgCO₂e/kg',
            'Aluminum Production' => 'kgCO₂e/kg',
            'Chemical Processing' => 'kgCO₂e/kg',
            'Cattle Enteric Fermentation' => 'kgCO₂e/head/year',
            'Manure Management' => 'kgCO₂e/head/year',
            'Rice Cultivation' => 'kgCO₂e/kg',
            'Fertilizer Use' => 'kgCO₂e/kg'
        ];

        $sources = ['MOCCAE', 'IPCC', 'EPA', 'DEFRA', 'ADNOC', 'DEWA', 'FEWA', 'SENR'];
        $regions = ['UAE', 'Dubai', 'Abu Dhabi', 'Northern Emirates'];

        return [
            'category' => $category,
            'subcategory' => $subcategory,
            'factor_value' => $factorValues[$subcategory] ?? fake()->randomFloat(4, 0.01, 50.0),
            'unit' => $units[$subcategory] ?? 'kgCO₂e/kg',
            'source' => fake()->randomElement($sources),
            'year' => fake()->numberBetween(2020, 2024),
            'region' => fake()->randomElement($regions),
        ];
    }
}

