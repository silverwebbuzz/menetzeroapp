<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Facility>
 */
class FacilityFactory extends Factory
{
    protected $model = Facility::class;

    public function definition(): array
    {
        $types = ['Office', 'Factory', 'Warehouse', 'Retail', 'Campus'];
        $type = $this->faker->randomElement($types);
        
        $locations = [
            'Dubai Industrial City', 'Jebel Ali Free Zone', 'Dubai Silicon Oasis',
            'Abu Dhabi Industrial City', 'Mussafah Industrial Area', 'Sharjah Industrial Area',
            'Ajman Free Zone', 'Ras Al Khaimah Economic Zone', 'Fujairah Free Zone',
            'Dubai Marina', 'Downtown Dubai', 'Business Bay', 'DIFC', 'JLT',
            'Sharjah University City', 'Abu Dhabi University', 'UAE University'
        ];

        $facilityNames = [
            'Main Office', 'Headquarters', 'Central Warehouse', 'Distribution Center',
            'Manufacturing Plant', 'Production Facility', 'Service Center', 'Branch Office',
            'Retail Store', 'Showroom', 'Campus Building', 'Research Center',
            'Data Center', 'Call Center', 'Training Center', 'Main Campus'
        ];

        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->randomElement($facilityNames) . ' ' . $this->faker->randomElement(['A', 'B', 'C', 'Main', 'North', 'South', 'East', 'West']),
            'location' => $this->faker->randomElement($locations),
            'type' => $type,
        ];
    }
}

