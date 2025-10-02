<?php

namespace Database\Factories;

use App\Models\AgricultureData;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgricultureData>
 */
class AgricultureDataFactory extends Factory
{
    protected $model = AgricultureData::class;

    public function definition(): array
    {
        $livestockTypes = [
            'Cattle', 'Sheep', 'Goats', 'Camels', 'Poultry', 'Horses',
            'Buffalo', 'Donkeys', 'Pigs', 'Ducks', 'Geese', 'Turkeys'
        ];

        $feedTypes = [
            'Grass', 'Hay', 'Silage', 'Grain', 'Commercial Feed',
            'Forage', 'Straw', 'Concentrates', 'Mixed Feed', 'Organic Feed'
        ];

        $manureManagement = [
            'Anaerobic Digestion', 'Composting', 'Direct Application',
            'Storage', 'Treatment', 'Burning', 'Landfill'
        ];

        $livestockType = $this->faker->randomElement($livestockTypes);
        $feedType = $this->faker->randomElement($feedTypes);
        $manureMgmt = $this->faker->randomElement($manureManagement);

        $headcountRanges = [
            'Cattle' => [10, 500],
            'Sheep' => [50, 2000],
            'Goats' => [30, 1500],
            'Camels' => [5, 200],
            'Poultry' => [100, 10000],
            'Horses' => [5, 100],
            'Buffalo' => [10, 300],
            'Donkeys' => [5, 50],
            'Pigs' => [20, 500],
            'Ducks' => [50, 2000],
            'Geese' => [20, 500],
            'Turkeys' => [30, 1000]
        ];

        $headcount = $this->faker->numberBetween(
            $headcountRanges[$livestockType][0],
            $headcountRanges[$livestockType][1]
        );

        // Calculate CO2e based on livestock type and management
        $co2eFactors = [
            'Cattle' => 45.0,
            'Sheep' => 8.0,
            'Goats' => 5.0,
            'Camels' => 15.0,
            'Poultry' => 0.1,
            'Horses' => 10.0,
            'Buffalo' => 50.0,
            'Donkeys' => 3.0,
            'Pigs' => 2.0,
            'Ducks' => 0.1,
            'Geese' => 0.2,
            'Turkeys' => 0.1
        ];

        $co2e = $headcount * $co2eFactors[$livestockType];

        return [
            'facility_id' => Facility::factory(),
            'livestock_type' => $livestockType,
            'feed_type' => $feedType,
            'manure_mgmt' => $manureMgmt,
            'headcount' => $headcount,
            'date' => $this->faker->dateTimeBetween('-12 months', 'now')->format('Y-m-d'),
            'co2e' => round($co2e, 2),
        ];
    }
}

