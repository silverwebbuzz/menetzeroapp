<?php

namespace Database\Factories;

use App\Models\EnergyData;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EnergyData>
 */
class EnergyDataFactory extends Factory
{
    protected $model = EnergyData::class;

    public function definition(): array
    {
        $sourceTypes = ['Electricity', 'Diesel', 'LPG', 'Gasoline', 'Other'];
        $sourceType = $this->faker->randomElement($sourceTypes);

        $consumptionRanges = [
            'Electricity' => [5000, 50000],
            'Diesel' => [100, 2000],
            'LPG' => [50, 500],
            'Gasoline' => [200, 1000],
            'Other' => [100, 1000]
        ];

        $units = [
            'Electricity' => 'kWh',
            'Diesel' => 'Litres',
            'LPG' => 'kg',
            'Gasoline' => 'Litres',
            'Other' => 'units'
        ];

        $consumption = $this->faker->numberBetween(
            $consumptionRanges[$sourceType][0],
            $consumptionRanges[$sourceType][1]
        );

        // Calculate CO2e based on typical UAE emission factors
        $co2eFactors = [
            'Electricity' => 0.424,
            'Diesel' => 2.680,
            'LPG' => 1.500,
            'Gasoline' => 2.310,
            'Other' => 1.000
        ];

        $co2e = $consumption * $co2eFactors[$sourceType];

        return [
            'facility_id' => Facility::factory(),
            'source_type' => $sourceType,
            'consumption_value' => $consumption,
            'unit' => $units[$sourceType],
            'date' => $this->faker->dateTimeBetween('-12 months', 'now')->format('Y-m-d'),
            'uploaded_file' => $this->faker->optional(0.3)->randomElement([
                'energy_consumption_jan_2024.xlsx',
                'utility_bills_q1_2024.pdf',
                'meter_readings_march.xlsx'
            ]),
            'co2e' => round($co2e, 2),
        ];
    }
}

