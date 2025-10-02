<?php

namespace Database\Factories;

use App\Models\TransportData;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TransportData>
 */
class TransportDataFactory extends Factory
{
    protected $model = TransportData::class;

    public function definition(): array
    {
        $vehicleTypes = [
            'Truck', 'Bus', 'Car', 'Van', 'Motorcycle', 'Heavy Vehicle',
            'Shuttle Bus', 'Delivery Van', 'Fleet Vehicle', 'Company Car'
        ];

        $fuelTypes = ['Diesel', 'Petrol', 'LPG', 'CNG', 'Electric', 'Hybrid'];
        $vehicleType = $this->faker->randomElement($vehicleTypes);
        $fuelType = $this->faker->randomElement($fuelTypes);

        $distanceRanges = [
            'Truck' => [500, 5000],
            'Bus' => [200, 2000],
            'Car' => [100, 1000],
            'Van' => [150, 1500],
            'Motorcycle' => [50, 500],
            'Heavy Vehicle' => [300, 3000],
            'Shuttle Bus' => [100, 800],
            'Delivery Van' => [200, 1200],
            'Fleet Vehicle' => [150, 1000],
            'Company Car' => [100, 800]
        ];

        $distance = $this->faker->numberBetween(
            $distanceRanges[$vehicleType][0],
            $distanceRanges[$vehicleType][1]
        );

        // Calculate fuel consumption based on vehicle type and distance
        $fuelEfficiency = [
            'Truck' => 8, 'Bus' => 12, 'Car' => 15, 'Van' => 10,
            'Motorcycle' => 25, 'Heavy Vehicle' => 6, 'Shuttle Bus' => 14,
            'Delivery Van' => 11, 'Fleet Vehicle' => 13, 'Company Car' => 16
        ];

        $fuelConsumed = $distance / $fuelEfficiency[$vehicleType];

        // Calculate CO2e based on fuel type
        $co2eFactors = [
            'Diesel' => 2.680,
            'Petrol' => 2.310,
            'LPG' => 1.500,
            'CNG' => 1.200,
            'Electric' => 0.100,
            'Hybrid' => 1.200
        ];

        $co2e = $fuelConsumed * $co2eFactors[$fuelType];

        return [
            'facility_id' => Facility::factory(),
            'vehicle_type' => $vehicleType,
            'fuel_type' => $fuelType,
            'distance_travelled' => $distance,
            'fuel_consumed' => round($fuelConsumed, 2),
            'unit' => 'km/Litres',
            'date' => $this->faker->dateTimeBetween('-12 months', 'now')->format('Y-m-d'),
            'uploaded_file' => $this->faker->optional(0.2)->randomElement([
                'fleet_log_feb_2024.xlsx',
                'fuel_receipts_march.pdf',
                'vehicle_tracking_data.xlsx'
            ]),
            'co2e' => round($co2e, 2),
        ];
    }
}

