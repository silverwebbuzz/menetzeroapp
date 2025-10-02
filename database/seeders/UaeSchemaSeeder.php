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

class UaeSchemaSeeder extends Seeder
{
    public function run(): void
    {
        // Create specific demo companies
        $companies = $this->createDemoCompanies();
        
        // Create facilities for demo companies
        $facilities = $this->createDemoFacilities($companies);
        
        // Create emission factors
        $this->createEmissionFactors();
        
        // Create activity data
        $this->createActivityData($facilities);
        
        // Create reports and subscriptions
        $this->createReportsAndSubscriptions($companies);
    }

    private function createDemoCompanies(): array
    {
        $companies = [];

        // GreenTech UAE
        $companies[] = Company::create([
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

        // Desert Transport LLC
        $companies[] = Company::create([
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

        // Marina Hospitality Group
        $companies[] = Company::create([
            'name' => 'Marina Hospitality Group',
            'slug' => 'marina-hospitality-group',
            'emirate' => 'Dubai',
            'sector' => 'Hospitality',
            'license_no' => 'DED-56789',
            'contact_person' => 'John Smith',
            'email' => 'john@marinahotels.ae',
            'phone' => '+971500000003',
            'address' => 'Marina Walk, Dubai Marina',
            'city' => 'Dubai',
            'state' => 'Dubai',
            'country' => 'UAE',
            'website' => 'https://marinahotels.ae',
            'industry' => 'Hospitality',
            'employee_count' => 320,
            'annual_revenue' => 25000000,
            'is_active' => true,
        ]);

        // Emirates University
        $companies[] = Company::create([
            'name' => 'Emirates University',
            'slug' => 'emirates-university',
            'emirate' => 'Sharjah',
            'sector' => 'Education',
            'license_no' => 'SHJ-99887',
            'contact_person' => 'Dr. Sara Al-Mansouri',
            'email' => 'sara@emu.ac.ae',
            'phone' => '+971500000004',
            'address' => 'University City, Sharjah',
            'city' => 'Sharjah',
            'state' => 'Sharjah',
            'country' => 'UAE',
            'website' => 'https://emu.ac.ae',
            'industry' => 'Education',
            'employee_count' => 450,
            'annual_revenue' => 12000000,
            'is_active' => true,
        ]);

        // Future Retail Malls
        $companies[] = Company::create([
            'name' => 'Future Retail Malls',
            'slug' => 'future-retail-malls',
            'emirate' => 'Dubai',
            'sector' => 'Retail',
            'license_no' => 'DED-34567',
            'contact_person' => 'Rajesh Kumar',
            'email' => 'rajesh@futuremalls.ae',
            'phone' => '+971500000005',
            'address' => 'Downtown Dubai, Sheikh Zayed Road',
            'city' => 'Dubai',
            'state' => 'Dubai',
            'country' => 'UAE',
            'website' => 'https://futuremalls.ae',
            'industry' => 'Retail',
            'employee_count' => 280,
            'annual_revenue' => 18000000,
            'is_active' => true,
        ]);

        return $companies;
    }

    private function createDemoFacilities(array $companies): array
    {
        $facilities = [];

        // GreenTech UAE facilities
        $facilities[] = Facility::create([
            'company_id' => $companies[0]->id,
            'name' => 'Factory Plant A',
            'location' => 'Dubai Industrial City',
            'type' => 'Factory',
        ]);

        $facilities[] = Facility::create([
            'company_id' => $companies[0]->id,
            'name' => 'R&D Center',
            'location' => 'Dubai Silicon Oasis',
            'type' => 'Office',
        ]);

        // Desert Transport facilities
        $facilities[] = Facility::create([
            'company_id' => $companies[1]->id,
            'name' => 'Truck Depot',
            'location' => 'Mussafah Industrial Area',
            'type' => 'Warehouse',
        ]);

        $facilities[] = Facility::create([
            'company_id' => $companies[1]->id,
            'name' => 'Main Office',
            'location' => 'Abu Dhabi City Center',
            'type' => 'Office',
        ]);

        // Marina Hospitality facilities
        $facilities[] = Facility::create([
            'company_id' => $companies[2]->id,
            'name' => 'Marina Hotel Downtown',
            'location' => 'Downtown Dubai',
            'type' => 'Office',
        ]);

        $facilities[] = Facility::create([
            'company_id' => $companies[2]->id,
            'name' => 'Beach Resort',
            'location' => 'Jumeirah Beach',
            'type' => 'Office',
        ]);

        // Emirates University facilities
        $facilities[] = Facility::create([
            'company_id' => $companies[3]->id,
            'name' => 'Main Campus',
            'location' => 'Sharjah University City',
            'type' => 'Campus',
        ]);

        $facilities[] = Facility::create([
            'company_id' => $companies[3]->id,
            'name' => 'Research Farm',
            'location' => 'Al Dhaid',
            'type' => 'Campus',
        ]);

        // Future Retail facilities
        $facilities[] = Facility::create([
            'company_id' => $companies[4]->id,
            'name' => 'Mall of Future',
            'location' => 'Downtown Dubai',
            'type' => 'Retail',
        ]);

        $facilities[] = Facility::create([
            'company_id' => $companies[4]->id,
            'name' => 'Distribution Center',
            'location' => 'Jebel Ali Free Zone',
            'type' => 'Warehouse',
        ]);

        return $facilities;
    }

    private function createEmissionFactors(): void
    {
        $emissionFactors = [
            // Energy
            ['Energy', 'Grid Electricity', 0.424, 'kgCO₂e/kWh', 'MOCCAE', 2023, 'UAE'],
            ['Energy', 'Solar PV', 0.041, 'kgCO₂e/kWh', 'MOCCAE', 2023, 'UAE'],
            ['Energy', 'Natural Gas', 0.202, 'kgCO₂e/kWh', 'MOCCAE', 2023, 'UAE'],
            ['Energy', 'Diesel Generator', 0.778, 'kgCO₂e/kWh', 'MOCCAE', 2023, 'UAE'],
            
            // Transport
            ['Transport', 'Diesel Fuel', 2.680, 'kgCO₂e/litre', 'IPCC', 2023, 'UAE'],
            ['Transport', 'Petrol', 2.310, 'kgCO₂e/litre', 'IPCC', 2023, 'UAE'],
            ['Transport', 'LPG', 1.500, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Transport', 'Electric Vehicle', 0.100, 'kgCO₂e/km', 'MOCCAE', 2023, 'UAE'],
            
            // Waste
            ['Waste', 'Landfill Mixed Waste', 1.900, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Waste', 'Incineration', 0.600, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Waste', 'Recycling Paper', 0.200, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Waste', 'Recycling Plastic', 0.300, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Waste', 'Composting', 0.100, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            
            // Industrial
            ['Industrial', 'Cement Production', 0.900, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Industrial', 'Steel Production', 1.800, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Industrial', 'Aluminum Production', 8.200, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Industrial', 'Chemical Processing', 2.500, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            
            // Agriculture
            ['Agriculture', 'Cattle Enteric Fermentation', 45.000, 'kgCO₂e/head/year', 'IPCC', 2023, 'UAE'],
            ['Agriculture', 'Manure Management', 2.500, 'kgCO₂e/head/year', 'IPCC', 2023, 'UAE'],
            ['Agriculture', 'Rice Cultivation', 1.200, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Agriculture', 'Fertilizer Use', 0.300, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
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
    }

    private function createActivityData(array $facilities): void
    {
        // Energy data for Factory Plant A
        EnergyData::create([
            'facility_id' => $facilities[0]->id,
            'source_type' => 'Electricity',
            'consumption_value' => 15000,
            'unit' => 'kWh',
            'date' => '2024-01-01',
            'co2e' => 6360, // 15000 * 0.424
        ]);

        EnergyData::create([
            'facility_id' => $facilities[0]->id,
            'source_type' => 'Diesel',
            'consumption_value' => 800,
            'unit' => 'Litres',
            'date' => '2024-01-15',
            'co2e' => 2144, // 800 * 2.680
        ]);

        // Energy data for Mall of Future
        EnergyData::create([
            'facility_id' => $facilities[8]->id,
            'source_type' => 'Electricity',
            'consumption_value' => 22000,
            'unit' => 'kWh',
            'date' => '2024-02-01',
            'co2e' => 9328, // 22000 * 0.424
        ]);

        // Transport data for Truck Depot
        TransportData::create([
            'facility_id' => $facilities[2]->id,
            'vehicle_type' => 'Truck',
            'fuel_type' => 'Diesel',
            'distance_travelled' => 1200,
            'fuel_consumed' => 400,
            'unit' => 'km/Litres',
            'date' => '2024-02-01',
            'co2e' => 1072, // 400 * 2.680
        ]);

        // Transport data for Marina Hotel
        TransportData::create([
            'facility_id' => $facilities[4]->id,
            'vehicle_type' => 'Shuttle Bus',
            'fuel_type' => 'Petrol',
            'distance_travelled' => 300,
            'fuel_consumed' => 60,
            'unit' => 'km/Litres',
            'date' => '2024-03-01',
            'co2e' => 138.6, // 60 * 2.310
        ]);

        // Industrial data for Factory Plant A
        IndustrialData::create([
            'facility_id' => $facilities[0]->id,
            'process_type' => 'Cement Clinker Production',
            'raw_material' => 'Limestone',
            'quantity' => 100,
            'unit' => 'tonnes',
            'date' => '2024-03-01',
            'co2e' => 90000, // 100 * 900
        ]);

        // Waste data for Marina Hotel
        WasteData::create([
            'facility_id' => $facilities[4]->id,
            'waste_type' => 'Food Waste',
            'quantity' => 2,
            'unit' => 'tonnes',
            'disposal_method' => 'Landfill',
            'date' => '2024-03-01',
            'co2e' => 3800, // 2 * 1900
        ]);

        // Waste data for Mall of Future
        WasteData::create([
            'facility_id' => $facilities[8]->id,
            'waste_type' => 'Plastic',
            'quantity' => 1.5,
            'unit' => 'tonnes',
            'disposal_method' => 'Recycling',
            'date' => '2024-02-01',
            'co2e' => 450, // 1.5 * 300
        ]);

        // Agriculture data for Research Farm
        AgricultureData::create([
            'facility_id' => $facilities[7]->id,
            'livestock_type' => 'Goats',
            'feed_type' => 'Grass',
            'manure_mgmt' => 'Composting',
            'headcount' => 50,
            'date' => '2024-01-01',
            'co2e' => 250, // 50 * 5
        ]);
    }

    private function createReportsAndSubscriptions(array $companies): void
    {
        foreach ($companies as $company) {
            // Create reports
            Report::create([
                'company_id' => $company->id,
                'type' => 'MOCCAE',
                'period_start' => '2023-01-01',
                'period_end' => '2023-12-31',
                'file_path' => '/reports/moccae_annual_report_2023_' . $company->slug . '.pdf',
            ]);

            Report::create([
                'company_id' => $company->id,
                'type' => 'Internal',
                'period_start' => '2024-01-01',
                'period_end' => '2024-03-31',
                'file_path' => '/reports/quarterly_report_q1_2024_' . $company->slug . '.pdf',
            ]);

            // Create subscriptions
            $planTypes = ['Free', 'Standard', 'Premium'];
            $planType = $planTypes[array_rand($planTypes)];
            
            Subscription::create([
                'company_id' => $company->id,
                'plan_type' => $planType,
                'status' => 'active',
                'stripe_customer_id' => $planType !== 'Free' ? 'cus_' . str_random(14) : null,
                'started_at' => now()->subMonths(rand(1, 12)),
                'expires_at' => now()->addMonths($planType === 'Free' ? 1 : 12),
            ]);
        }
    }
}


