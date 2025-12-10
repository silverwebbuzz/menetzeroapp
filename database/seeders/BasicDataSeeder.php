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
use App\Models\ClientSubscription;
use App\Models\SubscriptionPlan;

class BasicDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Creating basic UAE data...');

        try {
            // Create additional companies
            $this->command->info('Creating additional companies...');
            
            $companies = [];
            for ($i = 1; $i <= 20; $i++) {
                $company = Company::create([
                    'name' => 'Company ' . $i . ' UAE',
                    'slug' => 'company-' . $i . '-uae',
                    'emirate' => $this->getRandomEmirate(),
                    'sector' => $this->getRandomSector(),
                    'license_no' => $this->getRandomLicense(),
                    'contact_person' => 'Contact Person ' . $i,
                    'email' => 'company' . $i . '@example.ae',
                    'phone' => '+971500000' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'address' => 'Address ' . $i,
                    'city' => $this->getRandomCity(),
                    'state' => $this->getRandomEmirate(),
                    'country' => 'UAE',
                    'website' => 'https://company' . $i . '.ae',
                    'industry' => $this->getRandomIndustry(),
                    'employee_count' => rand(10, 500),
                    'annual_revenue' => rand(100000, 10000000),
                    'is_active' => true,
                ]);
                $companies[] = $company;
            }
            $this->command->info('✅ 20 additional companies created');

            // Create facilities for each company
            $this->command->info('Creating facilities...');
            $facilities = [];
            foreach ($companies as $company) {
                $facilityCount = rand(1, 3);
                for ($j = 1; $j <= $facilityCount; $j++) {
                    $facility = Facility::create([
                        'company_id' => $company->id,
                        'name' => 'Facility ' . $j . ' - ' . $company->name,
                        'location' => $this->getRandomLocation(),
                        'type' => $this->getRandomFacilityType(),
                    ]);
                    $facilities[] = $facility;
                }
            }
            $this->command->info('✅ Facilities created for all companies');

            // Create emission factors
            $this->command->info('Creating emission factors...');
            $emissionFactors = [
                ['Energy', 'Solar PV', 0.041, 'kgCO₂e/kWh', 'MOCCAE', 2023, 'UAE'],
                ['Energy', 'Wind Power', 0.011, 'kgCO₂e/kWh', 'MOCCAE', 2023, 'UAE'],
                ['Energy', 'Natural Gas', 0.202, 'kgCO₂e/kWh', 'MOCCAE', 2023, 'UAE'],
                ['Transport', 'Petrol', 2.310, 'kgCO₂e/litre', 'IPCC', 2023, 'UAE'],
                ['Transport', 'LPG', 1.500, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
                ['Waste', 'Landfill Mixed Waste', 1.900, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
                ['Waste', 'Recycling Paper', 0.200, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
                ['Industrial', 'Steel Production', 1.800, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
                ['Industrial', 'Aluminum Production', 8.200, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
                ['Agriculture', 'Cattle Enteric Fermentation', 45.000, 'kgCO₂e/head/year', 'IPCC', 2023, 'UAE'],
            ];

            foreach ($emissionFactors as $factor) {
                EmissionFactor::create([
                    'category' => $factor[0],
                    'subcategory' => $factor[1],
                    'factor_value' => $factor[2],
                    'unit' => $factor[3],
                    'source' => $factor[4],
                    'year' => $factor[5],
                    'region' => $factor[6],
                ]);
            }
            $this->command->info('✅ 10 additional emission factors created');

            // Create activity data for facilities
            $this->command->info('Creating activity data...');
            foreach ($facilities as $facility) {
                // Energy data
                EnergyData::create([
                    'facility_id' => $facility->id,
                    'source_type' => 'Electricity',
                    'consumption_value' => rand(1000, 10000),
                    'unit' => 'kWh',
                    'date' => now()->subDays(rand(1, 30))->format('Y-m-d'),
                    'co2e' => rand(1000, 10000) * 0.424,
                ]);

                // Transport data
                TransportData::create([
                    'facility_id' => $facility->id,
                    'vehicle_type' => 'Truck',
                    'fuel_type' => 'Diesel',
                    'distance_travelled' => rand(100, 1000),
                    'fuel_consumed' => rand(50, 500),
                    'unit' => 'km/Litres',
                    'date' => now()->subDays(rand(1, 30))->format('Y-m-d'),
                    'co2e' => rand(50, 500) * 2.680,
                ]);

                // Waste data
                WasteData::create([
                    'facility_id' => $facility->id,
                    'waste_type' => 'Mixed Waste',
                    'quantity' => rand(1, 10),
                    'unit' => 'tonnes',
                    'disposal_method' => 'Landfill',
                    'date' => now()->subDays(rand(1, 30))->format('Y-m-d'),
                    'co2e' => rand(1, 10) * 1.900,
                ]);
            }
            $this->command->info('✅ Activity data created for all facilities');

            // Create reports and subscriptions
            $this->command->info('Creating reports and subscriptions...');
            foreach ($companies as $company) {
                Report::create([
                    'company_id' => $company->id,
                    'type' => 'Internal',
                    'period_start' => now()->subMonths(3)->format('Y-m-d'),
                    'period_end' => now()->format('Y-m-d'),
                    'file_path' => '/reports/quarterly_report_' . $company->slug . '.pdf',
                ]);

                // Create subscription using ClientSubscription
                $freePlan = SubscriptionPlan::where('plan_category', 'client')
                    ->where(function($query) {
                        $query->where('plan_code', 'free')
                              ->orWhere('plan_code', 'FREE')
                              ->orWhere('price_annual', 0);
                    })
                    ->where('is_active', true)
                    ->first();
                
                if ($freePlan) {
                    ClientSubscription::create([
                        'company_id' => $company->id,
                        'subscription_plan_id' => $freePlan->id,
                        'status' => 'active',
                        'billing_cycle' => 'annual',
                        'started_at' => now()->subMonths(rand(1, 12)),
                        'expires_at' => now()->addMonths(12),
                        'auto_renew' => true,
                    ]);
                }
            }
            $this->command->info('✅ Reports and subscriptions created');

            $this->command->info('🎉 Basic data creation completed!');
            $this->command->info('📊 Created: 20 companies, multiple facilities, 10 emission factors, and activity data');

        } catch (\Exception $e) {
            $this->command->error('❌ Error creating data: ' . $e->getMessage());
        }
    }

    private function getRandomEmirate(): string
    {
        $emirates = ['Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman', 'Umm Al Quwain', 'Fujairah', 'Ras Al Khaimah'];
        return $emirates[array_rand($emirates)];
    }

    private function getRandomSector(): string
    {
        $sectors = ['Manufacturing', 'Logistics', 'Retail', 'Construction', 'Education', 'Hospitality', 'Healthcare', 'Technology'];
        return $sectors[array_rand($sectors)];
    }

    private function getRandomIndustry(): string
    {
        $industries = ['Manufacturing', 'Logistics', 'Retail', 'Construction', 'Education', 'Hospitality', 'Healthcare', 'Technology'];
        return $industries[array_rand($industries)];
    }

    private function getRandomCity(): string
    {
        $cities = ['Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman', 'Ras Al Khaimah', 'Fujairah'];
        return $cities[array_rand($cities)];
    }

    private function getRandomLocation(): string
    {
        $locations = [
            'Dubai Industrial City', 'Jebel Ali Free Zone', 'Abu Dhabi Industrial City',
            'Sharjah Industrial Area', 'Dubai Marina', 'Downtown Dubai'
        ];
        return $locations[array_rand($locations)];
    }

    private function getRandomFacilityType(): string
    {
        $types = ['Office', 'Factory', 'Warehouse', 'Retail', 'Campus'];
        return $types[array_rand($types)];
    }

    private function getRandomLicense(): string
    {
        $prefixes = ['DED', 'AD', 'SHJ', 'AJM', 'UAQ', 'FUJ', 'RAK'];
        return $prefixes[array_rand($prefixes)] . '-' . rand(10000, 99999);
    }

}
