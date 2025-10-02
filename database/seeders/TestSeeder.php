<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\Facility;
use App\Models\EmissionFactor;

class TestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🧪 Running test seeder...');

        try {
            // Test basic model creation
            $this->command->info('Testing Company model...');
            
            // Check if test company already exists
            $existingCompany = Company::where('slug', 'test-company')->first();
            if ($existingCompany) {
                $this->command->info('✅ Company already exists with ID: ' . $existingCompany->id);
                $company = $existingCompany;
            } else {
                $company = Company::create([
                    'name' => 'Test Company',
                    'slug' => 'test-company',
                    'emirate' => 'Dubai',
                    'sector' => 'Technology',
                    'license_no' => 'DED-TEST',
                    'contact_person' => 'Test Person',
                    'email' => 'test@test.com',
                    'phone' => '+971500000000',
                    'industry' => 'Technology',
                    'employee_count' => 10,
                    'annual_revenue' => 100000,
                    'is_active' => true,
                ]);
                $this->command->info('✅ Company created with ID: ' . $company->id);
            }

            $this->command->info('Testing Facility model...');
            $existingFacility = Facility::where('company_id', $company->id)->where('name', 'Test Facility')->first();
            if ($existingFacility) {
                $this->command->info('✅ Facility already exists with ID: ' . $existingFacility->id);
                $facility = $existingFacility;
            } else {
                $facility = Facility::create([
                    'company_id' => $company->id,
                    'name' => 'Test Facility',
                    'location' => 'Test Location',
                    'type' => 'Office',
                ]);
                $this->command->info('✅ Facility created with ID: ' . $facility->id);
            }

            $this->command->info('Testing EmissionFactor model...');
            $existingEmissionFactor = EmissionFactor::where('category', 'Energy')
                ->where('subcategory', 'Test Electricity')
                ->where('source', 'Test Source')
                ->first();
            if ($existingEmissionFactor) {
                $this->command->info('✅ EmissionFactor already exists with ID: ' . $existingEmissionFactor->id);
                $emissionFactor = $existingEmissionFactor;
            } else {
                $emissionFactor = EmissionFactor::create([
                    'category' => 'Energy',
                    'subcategory' => 'Test Electricity',
                    'factor_value' => 0.500,
                    'unit' => 'kgCO₂e/kWh',
                    'source' => 'Test Source',
                    'year' => 2024,
                    'region' => 'UAE',
                ]);
                $this->command->info('✅ EmissionFactor created with ID: ' . $emissionFactor->id);
            }

            $this->command->info('🎉 Test seeder completed successfully!');
            $this->command->info('📊 Created: 1 company, 1 facility, 1 emission factor');

        } catch (\Exception $e) {
            $this->command->error('❌ Test seeder failed: ' . $e->getMessage());
            $this->command->error('Stack trace: ' . $e->getTraceAsString());
        }
    }
}
