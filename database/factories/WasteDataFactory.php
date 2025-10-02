<?php

namespace Database\Factories;

use App\Models\WasteData;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WasteData>
 */
class WasteDataFactory extends Factory
{
    protected $model = WasteData::class;

    public function definition(): array
    {
        $wasteTypes = [
            'Food Waste', 'Plastic', 'Paper', 'Cardboard', 'Glass',
            'Metal', 'Electronic Waste', 'Hazardous Waste', 'Organic Waste',
            'Construction Waste', 'Textile Waste', 'Medical Waste'
        ];

        $disposalMethods = ['Landfill', 'Incineration', 'Recycling', 'Composting'];
        $wasteType = $this->faker->randomElement($wasteTypes);
        $disposalMethod = $this->faker->randomElement($disposalMethods);

        $quantityRanges = [
            'Food Waste' => [0.5, 10],
            'Plastic' => [0.2, 5],
            'Paper' => [0.5, 8],
            'Cardboard' => [0.3, 6],
            'Glass' => [0.1, 3],
            'Metal' => [0.2, 4],
            'Electronic Waste' => [0.1, 2],
            'Hazardous Waste' => [0.05, 1],
            'Organic Waste' => [0.3, 7],
            'Construction Waste' => [1, 50],
            'Textile Waste' => [0.1, 3],
            'Medical Waste' => [0.01, 0.5]
        ];

        $quantity = $this->faker->randomFloat(2,
            $quantityRanges[$wasteType][0],
            $quantityRanges[$wasteType][1]
        );

        $units = ['tonnes', 'kg', 'cubic meters'];
        $unit = $this->faker->randomElement($units);

        // Calculate CO2e based on waste type and disposal method
        $co2eFactors = [
            'Landfill' => [
                'Food Waste' => 1.9, 'Plastic' => 2.5, 'Paper' => 1.2,
                'Cardboard' => 1.0, 'Glass' => 0.3, 'Metal' => 0.1,
                'Electronic Waste' => 3.0, 'Hazardous Waste' => 4.0,
                'Organic Waste' => 1.5, 'Construction Waste' => 0.8,
                'Textile Waste' => 2.0, 'Medical Waste' => 5.0
            ],
            'Incineration' => [
                'Food Waste' => 0.6, 'Plastic' => 2.8, 'Paper' => 1.5,
                'Cardboard' => 1.2, 'Glass' => 0.1, 'Metal' => 0.2,
                'Electronic Waste' => 2.5, 'Hazardous Waste' => 3.5,
                'Organic Waste' => 0.8, 'Construction Waste' => 1.0,
                'Textile Waste' => 2.2, 'Medical Waste' => 4.5
            ],
            'Recycling' => [
                'Food Waste' => 0.2, 'Plastic' => 0.3, 'Paper' => 0.2,
                'Cardboard' => 0.2, 'Glass' => 0.1, 'Metal' => 0.1,
                'Electronic Waste' => 0.5, 'Hazardous Waste' => 1.0,
                'Organic Waste' => 0.1, 'Construction Waste' => 0.3,
                'Textile Waste' => 0.4, 'Medical Waste' => 2.0
            ],
            'Composting' => [
                'Food Waste' => 0.1, 'Plastic' => 0.5, 'Paper' => 0.3,
                'Cardboard' => 0.2, 'Glass' => 0.1, 'Metal' => 0.1,
                'Electronic Waste' => 1.0, 'Hazardous Waste' => 2.0,
                'Organic Waste' => 0.1, 'Construction Waste' => 0.2,
                'Textile Waste' => 0.3, 'Medical Waste' => 1.5
            ]
        ];

        $co2e = $quantity * $co2eFactors[$disposalMethod][$wasteType];

        return [
            'facility_id' => Facility::factory(),
            'waste_type' => $wasteType,
            'quantity' => $quantity,
            'unit' => $unit,
            'disposal_method' => $disposalMethod,
            'date' => $this->faker->dateTimeBetween('-12 months', 'now')->format('Y-m-d'),
            'uploaded_file' => $this->faker->optional(0.25)->randomElement([
                'waste_report_jan_2024.xlsx',
                'disposal_certificates.pdf',
                'waste_tracking_march.xlsx'
            ]),
            'co2e' => round($co2e, 2),
        ];
    }
}

