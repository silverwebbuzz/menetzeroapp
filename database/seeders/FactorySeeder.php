<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Facility;
use App\Models\EmissionFactor;
use App\Models\EnergyData;
use App\Models\TransportData;
use App\Models\IndustrialData;
use App\Models\WasteData;
use App\Models\AgricultureData;
use App\Models\Report;
use App\Models\Subscription;

class FactorySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🏭 Creating data using factories...');

        try {
            // Create emission factors first
            $this->command->info('Creating emission factors...');
            EmissionFactor::factory(20)->create();
            $this->command->info('✅ 20 emission factors created');

            // Create companies with facilities
            $this->command->info('Creating companies and facilities...');
            $companies = Company::factory(50)->create();
            
            foreach ($companies as $company) {
                // Create 1-3 facilities per company
                $facilityCount = rand(1, 3);
                Facility::factory($facilityCount)->create([
                    'company_id' => $company->id
                ]);
            }
            
            $this->command->info('✅ 50 companies and their facilities created');

            // Get all facilities for activity data
            $facilities = Facility::all();

            // Create energy data
            $this->command->info('Creating energy data...');
            foreach ($facilities as $facility) {
                EnergyData::factory(rand(3, 8))->create([
                    'facility_id' => $facility->id
                ]);
            }
            $this->command->info('✅ Energy data created');

            // Create transport data
            $this->command->info('Creating transport data...');
            foreach ($facilities as $facility) {
                TransportData::factory(rand(2, 5))->create([
                    'facility_id' => $facility->id
                ]);
            }
            $this->command->info('✅ Transport data created');

            // Create industrial data for factory facilities
            $this->command->info('Creating industrial data...');
            $factoryFacilities = $facilities->where('type', 'Factory');
            foreach ($factoryFacilities as $facility) {
                IndustrialData::factory(rand(1, 3))->create([
                    'facility_id' => $facility->id
                ]);
            }
            $this->command->info('✅ Industrial data created');

            // Create waste data
            $this->command->info('Creating waste data...');
            foreach ($facilities as $facility) {
                WasteData::factory(rand(2, 4))->create([
                    'facility_id' => $facility->id
                ]);
            }
            $this->command->info('✅ Waste data created');

            // Create agriculture data for campus facilities
            $this->command->info('Creating agriculture data...');
            $campusFacilities = $facilities->where('type', 'Campus');
            foreach ($campusFacilities as $facility) {
                AgricultureData::factory(rand(1, 2))->create([
                    'facility_id' => $facility->id
                ]);
            }
            $this->command->info('✅ Agriculture data created');

            // Create reports and subscriptions
            $this->command->info('Creating reports and subscriptions...');
            foreach ($companies as $company) {
                Report::factory(rand(2, 4))->create([
                    'company_id' => $company->id
                ]);
                
                Subscription::factory(1)->create([
                    'company_id' => $company->id
                ]);
            }
            $this->command->info('✅ Reports and subscriptions created');

            $this->command->info('🎉 Factory-based data creation completed!');
            $this->command->info('📊 Created: 50 companies, multiple facilities, 20 emission factors, and activity data across all categories');

        } catch (\Exception $e) {
            $this->command->error('❌ Error creating data: ' . $e->getMessage());
            $this->command->error('Stack trace: ' . $e->getTraceAsString());
        }
    }
}
