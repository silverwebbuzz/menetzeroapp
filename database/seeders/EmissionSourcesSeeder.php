<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmissionSourceMaster;
use App\Models\LocationEmissionBoundary;
use App\Models\Location;

class EmissionSourcesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create emission sources master data
        $emissionSources = [
            // Scope 1 - Direct Emissions
            [
                'name' => 'Natural Gas Consumption',
                'description' => 'Natural gas used for heating, cooking, and other direct combustion activities',
                'scope' => 'Scope 1',
                'category' => 'Energy',
                'subcategory' => 'Natural Gas',
                'type' => 'Direct',
                'is_active' => true,
            ],
            [
                'name' => 'Company Vehicles',
                'description' => 'Fuel consumption from company-owned vehicles',
                'scope' => 'Scope 1',
                'category' => 'Transport',
                'subcategory' => 'Fleet Vehicles',
                'type' => 'Direct',
                'is_active' => true,
            ],
            [
                'name' => 'Refrigerant Leakage',
                'description' => 'Refrigerant gases released from HVAC systems',
                'scope' => 'Scope 1',
                'category' => 'Refrigerants',
                'subcategory' => 'HVAC Systems',
                'type' => 'Direct',
                'is_active' => true,
            ],
            
            // Scope 2 - Indirect Emissions (Purchased Energy)
            [
                'name' => 'Electricity Consumption',
                'description' => 'Grid electricity consumption for lighting, equipment, and cooling',
                'scope' => 'Scope 2',
                'category' => 'Energy',
                'subcategory' => 'Electricity',
                'type' => 'Indirect',
                'is_active' => true,
            ],
            [
                'name' => 'District Cooling',
                'description' => 'District cooling services for air conditioning',
                'scope' => 'Scope 2',
                'category' => 'Energy',
                'subcategory' => 'District Cooling',
                'type' => 'Indirect',
                'is_active' => true,
            ],
            
            // Scope 3 - Other Indirect Emissions
            [
                'name' => 'Business Travel',
                'description' => 'Employee business travel by air, road, and rail',
                'scope' => 'Scope 3',
                'category' => 'Travel',
                'subcategory' => 'Business Travel',
                'type' => 'Indirect',
                'is_active' => true,
            ],
            [
                'name' => 'Waste Disposal',
                'description' => 'Waste generation and disposal activities',
                'scope' => 'Scope 3',
                'category' => 'Waste',
                'subcategory' => 'Waste Management',
                'type' => 'Indirect',
                'is_active' => true,
            ],
            [
                'name' => 'Water Consumption',
                'description' => 'Water consumption and wastewater treatment',
                'scope' => 'Scope 3',
                'category' => 'Water',
                'subcategory' => 'Water Usage',
                'type' => 'Indirect',
                'is_active' => true,
            ],
            [
                'name' => 'Purchased Goods',
                'description' => 'Upstream emissions from purchased goods and services',
                'scope' => 'Scope 3',
                'category' => 'Supply Chain',
                'subcategory' => 'Purchased Goods',
                'type' => 'Indirect',
                'is_active' => true,
            ],
        ];

        foreach ($emissionSources as $source) {
            EmissionSourceMaster::create($source);
        }

        // Create emission boundaries for all locations
        $locations = Location::all();
        
        foreach ($locations as $location) {
            // Get emission sources by scope
            $scope1Sources = EmissionSourceMaster::where('scope', 'Scope 1')->pluck('id')->toArray();
            $scope2Sources = EmissionSourceMaster::where('scope', 'Scope 2')->pluck('id')->toArray();
            $scope3Sources = EmissionSourceMaster::where('scope', 'Scope 3')->pluck('id')->toArray();

            // Create boundaries for each scope
            if (!empty($scope1Sources)) {
                LocationEmissionBoundary::create([
                    'location_id' => $location->id,
                    'scope' => 'Scope 1',
                    'selected_sources' => $scope1Sources,
                ]);
            }

            if (!empty($scope2Sources)) {
                LocationEmissionBoundary::create([
                    'location_id' => $location->id,
                    'scope' => 'Scope 2',
                    'selected_sources' => $scope2Sources,
                ]);
            }

            if (!empty($scope3Sources)) {
                LocationEmissionBoundary::create([
                    'location_id' => $location->id,
                    'scope' => 'Scope 3',
                    'selected_sources' => $scope3Sources,
                ]);
            }
        }

        $this->command->info('Emission sources and boundaries created successfully!');
    }
}