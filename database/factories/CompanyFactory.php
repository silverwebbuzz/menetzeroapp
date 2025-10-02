<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $industries = [
            'Manufacturing', 'Logistics', 'Retail', 'Construction', 
            'Education', 'Hospitality', 'Healthcare', 'Technology',
            'Real Estate', 'Banking', 'Insurance', 'Consulting'
        ];

        $emirates = [
            'Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman', 
            'Umm Al Quwain', 'Fujairah', 'Ras Al Khaimah'
        ];

        $sectors = [
            'Manufacturing', 'Logistics', 'Retail', 'Construction',
            'Education', 'Hospitality', 'Healthcare', 'Technology',
            'Real Estate', 'Banking', 'Insurance', 'Consulting',
            'Government', 'NGO', 'Agriculture', 'Mining'
        ];

        $name = fake()->company() . ' ' . fake()->randomElement(['UAE', 'Dubai', 'Abu Dhabi', 'Gulf', 'Middle East', 'Emirates']);
        
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => '+971' . $this->faker->numerify('########'),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->randomElement(['Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman']),
            'state' => $this->faker->randomElement($emirates),
            'country' => 'UAE',
            'postal_code' => $this->faker->postcode(),
            'website' => 'https://' . Str::slug($name) . '.ae',
            'description' => $this->faker->paragraph(3),
            'industry' => $this->faker->randomElement($industries),
            'employee_count' => $this->faker->numberBetween(10, 5000),
            'annual_revenue' => $this->faker->randomFloat(2, 100000, 100000000),
            'is_active' => $this->faker->boolean(90),
            'settings' => [
                'timezone' => 'Asia/Dubai',
                'currency' => 'AED',
                'language' => 'en'
            ],
            // UAE specific fields
            'emirate' => $this->faker->randomElement($emirates),
            'sector' => $this->faker->randomElement($sectors),
            'license_no' => $this->faker->randomElement(['DED', 'AD', 'SHJ', 'AJM']) . '-' . $this->faker->numerify('#####'),
            'contact_person' => $this->faker->name(),
        ];
    }
}

