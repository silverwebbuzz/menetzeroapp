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

class SimpleUaeSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Creating simple UAE demo data...');

        try {
            // Create companies
            $this->command->info('Creating companies...');
            
            // Check if companies already exist
            $company1 = Company::where('slug', 'greentech-uae')->first();
            if (!$company1) {
                $company1 = Company::create([
                    'name' => 'GreenTech UAE',
                    'slug' => 'greentech-uae',
                    'emirate' => 'Dubai',
                    'sector' => 'Manufacturing',
                    'license_no' => 'DED-12345',
                    'contact_person' => 'Ahmed Al-Rashid',
                    'email' => 'ahmed@greentech.ae',
                    'phone' => '+971500000001',
                    'address' => 'Dubai Industrial City, Building 15',
                    'city' => 'Dubai',
                    'state' => 'Dubai',
                    'country' => 'UAE',
                    'website' => 'https://greentech.ae',
                    'industry' => 'Manufacturing',
                    'employee_count' => 250,
                    'annual_revenue' => 15000000,
                    'is_active' => true,
                ]);
                $this->command->info('✅ GreenTech UAE created');
            } else {
                $this->command->info('✅ GreenTech UAE already exists');
            }

            $company2 = Company::where('slug', 'desert-transport-llc')->first();
            if (!$company2) {
                $company2 = Company::create([
                    'name' => 'Desert Transport LLC',
                    'slug' => 'desert-transport-llc',
                    'emirate' => 'Abu Dhabi',
                    'sector' => 'Logistics',
                    'license_no' => 'AD-98765',
                    'contact_person' => 'Fatima Al-Zahra',
                    'email' => 'fatima@deserttrans.ae',
                    'phone' => '+971500000002',
                    'address' => 'Mussafah Industrial Area, Plot 123',
                    'city' => 'Abu Dhabi',
                    'state' => 'Abu Dhabi',
                    'country' => 'UAE',
                    'website' => 'https://deserttrans.ae',
                    'industry' => 'Logistics',
                    'employee_count' => 180,
                    'annual_revenue' => 8500000,
                    'is_active' => true,
                ]);
                $this->command->info('✅ Desert Transport LLC created');
            } else {
                $this->command->info('✅ Desert Transport LLC already exists');
            }

            $this->command->info('✅ Companies created');

            // Create facilities
            $this->command->info('Creating facilities...');
            
            $facility1 = Facility::where('company_id', $company1->id)->where('name', 'Factory Plant A')->first();
            if (!$facility1) {
                $facility1 = Facility::create([
                    'company_id' => $company1->id,
                    'name' => 'Factory Plant A',
                    'location' => 'Dubai Industrial City',
                    'type' => 'Factory',
                ]);
                $this->command->info('✅ Factory Plant A created');
            } else {
                $this->command->info('✅ Factory Plant A already exists');
            }

            $facility2 = Facility::where('company_id', $company2->id)->where('name', 'Truck Depot')->first();
            if (!$facility2) {
                $facility2 = Facility::create([
                    'company_id' => $company2->id,
                    'name' => 'Truck Depot',
                    'location' => 'Mussafah Industrial Area',
                    'type' => 'Warehouse',
                ]);
                $this->command->info('✅ Truck Depot created');
            } else {
                $this->command->info('✅ Truck Depot already exists');
            }

            $this->command->info('✅ Facilities created');

            // Create emission factors
            $this->command->info('Creating emission factors...');
            
            $electricityFactor = EmissionFactor::where('category', 'Energy')
                ->where('subcategory', 'Grid Electricity')
                ->where('source', 'MOCCAE')
                ->first();
            if (!$electricityFactor) {
                EmissionFactor::create([
                    'category' => 'Energy',
                    'subcategory' => 'Grid Electricity',
                    'factor_value' => 0.424,
                    'unit' => 'kgCO₂e/kWh',
                    'source' => 'MOCCAE',
                    'year' => 2023,
                    'region' => 'UAE',
                ]);
                $this->command->info('✅ Grid Electricity factor created');
            } else {
                $this->command->info('✅ Grid Electricity factor already exists');
            }

            $dieselFactor = EmissionFactor::where('category', 'Transport')
                ->where('subcategory', 'Diesel Fuel')
                ->where('source', 'IPCC')
                ->first();
            if (!$dieselFactor) {
                EmissionFactor::create([
                    'category' => 'Transport',
                    'subcategory' => 'Diesel Fuel',
                    'factor_value' => 2.680,
                    'unit' => 'kgCO₂e/litre',
                    'source' => 'IPCC',
                    'year' => 2023,
                    'region' => 'UAE',
                ]);
                $this->command->info('✅ Diesel Fuel factor created');
            } else {
                $this->command->info('✅ Diesel Fuel factor already exists');
            }

            $this->command->info('✅ Emission factors created');

            // Create energy data
            $this->command->info('Creating energy data...');
            EnergyData::create([
                'facility_id' => $facility1->id,
                'source_type' => 'Electricity',
                'consumption_value' => 15000,
                'unit' => 'kWh',
                'date' => '2024-01-01',
                'co2e' => 6360, // 15000 * 0.424
            ]);

            EnergyData::create([
                'facility_id' => $facility1->id,
                'source_type' => 'Diesel',
                'consumption_value' => 800,
                'unit' => 'Litres',
                'date' => '2024-01-15',
                'co2e' => 2144, // 800 * 2.680
            ]);

            $this->command->info('✅ Energy data created');

            // Create transport data
            $this->command->info('Creating transport data...');
            TransportData::create([
                'facility_id' => $facility2->id,
                'vehicle_type' => 'Truck',
                'fuel_type' => 'Diesel',
                'distance_travelled' => 1200,
                'fuel_consumed' => 400,
                'unit' => 'km/Litres',
                'date' => '2024-02-01',
                'co2e' => 1072, // 400 * 2.680
            ]);

            $this->command->info('✅ Transport data created');

            // Create industrial data
            $this->command->info('Creating industrial data...');
            IndustrialData::create([
                'facility_id' => $facility1->id,
                'process_type' => 'Cement Clinker Production',
                'raw_material' => 'Limestone',
                'quantity' => 100,
                'unit' => 'tonnes',
                'date' => '2024-03-01',
                'co2e' => 90000, // 100 * 900
            ]);

            $this->command->info('✅ Industrial data created');

            // Create waste data
            $this->command->info('Creating waste data...');
            WasteData::create([
                'facility_id' => $facility1->id,
                'waste_type' => 'Food Waste',
                'quantity' => 2,
                'unit' => 'tonnes',
                'disposal_method' => 'Landfill',
                'date' => '2024-03-01',
                'co2e' => 3800, // 2 * 1900
            ]);

            $this->command->info('✅ Waste data created');

            // Create agriculture data
            $this->command->info('Creating agriculture data...');
            AgricultureData::create([
                'facility_id' => $facility1->id,
                'livestock_type' => 'Goats',
                'feed_type' => 'Grass',
                'manure_mgmt' => 'Composting',
                'headcount' => 50,
                'date' => '2024-01-01',
                'co2e' => 250, // 50 * 5
            ]);

            $this->command->info('✅ Agriculture data created');

            // Create reports
            $this->command->info('Creating reports...');
            Report::create([
                'company_id' => $company1->id,
                'type' => 'MOCCAE',
                'period_start' => '2023-01-01',
                'period_end' => '2023-12-31',
                'file_path' => '/reports/moccae_annual_report_2023_greentech-uae.pdf',
            ]);

            $this->command->info('✅ Reports created');

            // Create subscriptions
            $this->command->info('Creating subscriptions...');
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
                    'company_id' => $company1->id,
                    'subscription_plan_id' => $freePlan->id,
                    'status' => 'active',
                    'billing_cycle' => 'annual',
                    'started_at' => now()->subMonths(6),
                    'expires_at' => now()->addMonths(6),
                    'auto_renew' => true,
                ]);
                $this->command->info('✅ Subscriptions created');
            } else {
                $this->command->warn('⚠️  Free subscription plan not found, skipping subscription creation');
            }

            $this->command->info('🎉 Simple UAE demo data created successfully!');
            $this->command->info('📊 Created: 2 companies, 2 facilities, 2 emission factors, 2 energy records, 1 transport record, 1 industrial record, 1 waste record, 1 agriculture record, 1 report, 1 subscription');

        } catch (\Exception $e) {
            $this->command->error('❌ Error creating data: ' . $e->getMessage());
            $this->command->error('Stack trace: ' . $e->getTraceAsString());
        }
    }
}
