<?php

namespace Database\Factories;

use App\Models\IndustrialData;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IndustrialData>
 */
class IndustrialDataFactory extends Factory
{
    protected $model = IndustrialData::class;

    public function definition(): array
    {
        $processTypes = [
            'Cement Clinker Production', 'Steel Manufacturing', 'Aluminum Smelting',
            'Chemical Processing', 'Plastic Manufacturing', 'Textile Production',
            'Food Processing', 'Pharmaceutical Manufacturing', 'Paper Production',
            'Glass Manufacturing', 'Ceramic Production', 'Rubber Processing'
        ];

        $rawMaterials = [
            'Limestone', 'Iron Ore', 'Bauxite', 'Crude Oil', 'Natural Gas',
            'Coal', 'Wood Pulp', 'Cotton', 'Wheat', 'Soybeans', 'Corn',
            'Plastic Pellets', 'Chemical Compounds', 'Sand', 'Clay', 'Silica'
        ];

        $processType = $this->faker->randomElement($processTypes);
        $rawMaterial = $this->faker->randomElement($rawMaterials);

        $quantityRanges = [
            'Cement Clinker Production' => [50, 1000],
            'Steel Manufacturing' => [100, 2000],
            'Aluminum Smelting' => [200, 1500],
            'Chemical Processing' => [10, 500],
            'Plastic Manufacturing' => [20, 300],
            'Textile Production' => [100, 1000],
            'Food Processing' => [50, 800],
            'Pharmaceutical Manufacturing' => [5, 100],
            'Paper Production' => [200, 1500],
            'Glass Manufacturing' => [100, 800],
            'Ceramic Production' => [50, 400],
            'Rubber Processing' => [30, 200]
        ];

        $quantity = $this->faker->numberBetween(
            $quantityRanges[$processType][0],
            $quantityRanges[$processType][1]
        );

        $units = ['tonnes', 'kg', 'litres', 'cubic meters', 'pieces'];
        $unit = $this->faker->randomElement($units);

        // Calculate CO2e based on process type (simplified)
        $co2eFactors = [
            'Cement Clinker Production' => 0.9,
            'Steel Manufacturing' => 1.8,
            'Aluminum Smelting' => 8.2,
            'Chemical Processing' => 2.5,
            'Plastic Manufacturing' => 3.0,
            'Textile Production' => 0.5,
            'Food Processing' => 0.3,
            'Pharmaceutical Manufacturing' => 1.2,
            'Paper Production' => 0.8,
            'Glass Manufacturing' => 0.6,
            'Ceramic Production' => 0.4,
            'Rubber Processing' => 1.5
        ];

        $co2e = $quantity * $co2eFactors[$processType];

        return [
            'facility_id' => Facility::factory(),
            'process_type' => $processType,
            'raw_material' => $rawMaterial,
            'quantity' => $quantity,
            'unit' => $unit,
            'date' => $this->faker->dateTimeBetween('-12 months', 'now')->format('Y-m-d'),
            'uploaded_file' => $this->faker->optional(0.3)->randomElement([
                'production_report.xlsx',
                'material_usage_march.pdf',
                'process_data_q1_2024.xlsx'
            ]),
            'co2e' => round($co2e, 2),
        ];
    }
}

