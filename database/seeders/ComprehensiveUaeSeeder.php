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

class ComprehensiveUaeSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating comprehensive UAE dataset...');

        // Create emission factors first
        $this->createEmissionFactors();
        $this->command->info('✓ Emission factors created');

        // Create 100+ companies with facilities
        $companies = $this->createCompanies(100);
        $this->command->info('✓ ' . count($companies) . ' companies created');

        // Create facilities for each company
        $facilities = $this->createFacilities($companies);
        $this->command->info('✓ ' . count($facilities) . ' facilities created');

        // Create activity data
        $this->createActivityData($facilities);
        $this->command->info('✓ Activity data created');

        // Create reports and subscriptions
        $this->createReportsAndSubscriptions($companies);
        $this->command->info('✓ Reports and subscriptions created');

        $this->command->info('🎉 Comprehensive UAE dataset created successfully!');
    }

    private function createEmissionFactors(): void
    {
        $emissionFactors = [
            // Energy
            ['Energy', 'Grid Electricity', 0.424, 'kgCO₂e/kWh', 'MOCCAE', 2023, 'UAE'],
            ['Energy', 'Solar PV', 0.041, 'kgCO₂e/kWh', 'MOCCAE', 2023, 'UAE'],
            ['Energy', 'Wind Power', 0.011, 'kgCO₂e/kWh', 'MOCCAE', 2023, 'UAE'],
            ['Energy', 'Natural Gas', 0.202, 'kgCO₂e/kWh', 'MOCCAE', 2023, 'UAE'],
            ['Energy', 'Diesel Generator', 0.778, 'kgCO₂e/kWh', 'MOCCAE', 2023, 'UAE'],
            ['Energy', 'Coal', 0.820, 'kgCO₂e/kWh', 'IPCC', 2023, 'UAE'],
            
            // Transport
            ['Transport', 'Diesel Fuel', 2.680, 'kgCO₂e/litre', 'IPCC', 2023, 'UAE'],
            ['Transport', 'Petrol', 2.310, 'kgCO₂e/litre', 'IPCC', 2023, 'UAE'],
            ['Transport', 'LPG', 1.500, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Transport', 'CNG', 1.200, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Transport', 'Electric Vehicle', 0.100, 'kgCO₂e/km', 'MOCCAE', 2023, 'UAE'],
            ['Transport', 'Hybrid Vehicle', 1.200, 'kgCO₂e/km', 'MOCCAE', 2023, 'UAE'],
            ['Transport', 'Aviation Fuel', 3.150, 'kgCO₂e/litre', 'IPCC', 2023, 'UAE'],
            
            // Waste
            ['Waste', 'Landfill Mixed Waste', 1.900, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Waste', 'Incineration', 0.600, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Waste', 'Recycling Paper', 0.200, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Waste', 'Recycling Plastic', 0.300, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Waste', 'Recycling Metal', 0.100, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Waste', 'Composting', 0.100, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Waste', 'Anaerobic Digestion', 0.200, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            
            // Industrial
            ['Industrial', 'Cement Production', 0.900, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Industrial', 'Steel Production', 1.800, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Industrial', 'Aluminum Production', 8.200, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Industrial', 'Chemical Processing', 2.500, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Industrial', 'Plastic Manufacturing', 3.000, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Industrial', 'Textile Production', 0.500, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Industrial', 'Food Processing', 0.300, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Industrial', 'Paper Production', 0.800, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Industrial', 'Glass Manufacturing', 0.600, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Industrial', 'Ceramic Production', 0.400, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            
            // Agriculture
            ['Agriculture', 'Cattle Enteric Fermentation', 45.000, 'kgCO₂e/head/year', 'IPCC', 2023, 'UAE'],
            ['Agriculture', 'Sheep Enteric Fermentation', 8.000, 'kgCO₂e/head/year', 'IPCC', 2023, 'UAE'],
            ['Agriculture', 'Goat Enteric Fermentation', 5.000, 'kgCO₂e/head/year', 'IPCC', 2023, 'UAE'],
            ['Agriculture', 'Camel Enteric Fermentation', 15.000, 'kgCO₂e/head/year', 'IPCC', 2023, 'UAE'],
            ['Agriculture', 'Manure Management', 2.500, 'kgCO₂e/head/year', 'IPCC', 2023, 'UAE'],
            ['Agriculture', 'Rice Cultivation', 1.200, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Agriculture', 'Fertilizer Use', 0.300, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
            ['Agriculture', 'Pesticide Use', 0.100, 'kgCO₂e/kg', 'IPCC', 2023, 'UAE'],
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

    private function createCompanies(int $count): array
    {
        $companies = [];
        
        $industries = [
            'Manufacturing', 'Logistics', 'Retail', 'Construction', 
            'Education', 'Hospitality', 'Healthcare', 'Technology',
            'Real Estate', 'Banking', 'Insurance', 'Consulting',
            'Government', 'NGO', 'Agriculture', 'Mining'
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

        for ($i = 0; $i < $count; $i++) {
            $industry = fake()->randomElement($industries);
            $emirate = fake()->randomElement($emirates);
            $sector = fake()->randomElement($sectors);
            
            $name = fake()->company() . ' ' . fake()->randomElement(['UAE', 'Dubai', 'Abu Dhabi', 'Gulf', 'Middle East', 'Emirates', 'Group', 'Holdings', 'LLC', 'Ltd']);
            
            $companies[] = Company::create([
                'name' => $name,
                'slug' => \Illuminate\Support\Str::slug($name),
                'emirate' => $emirate,
                'sector' => $sector,
                'license_no' => fake()->randomElement(['DED', 'AD', 'SHJ', 'AJM', 'UAQ', 'FUJ', 'RAK']) . '-' . fake()->numerify('#####'),
                'contact_person' => fake()->name(),
                'email' => fake()->unique()->companyEmail(),
                'phone' => '+971' . fake()->numerify('########'),
                'address' => fake()->streetAddress(),
                'city' => fake()->randomElement(['Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman', 'Ras Al Khaimah', 'Fujairah']),
                'state' => $emirate,
                'country' => 'UAE',
                'postal_code' => fake()->postcode(),
                'website' => 'https://' . \Illuminate\Support\Str::slug($name) . '.ae',
                'description' => fake()->paragraph(3),
                'industry' => $industry,
                'employee_count' => fake()->numberBetween(10, 5000),
                'annual_revenue' => fake()->randomFloat(2, 100000, 100000000),
                'is_active' => fake()->boolean(90),
                'settings' => [
                    'timezone' => 'Asia/Dubai',
                    'currency' => 'AED',
                    'language' => 'en'
                ],
            ]);
        }

        return $companies;
    }

    private function createFacilities(array $companies): array
    {
        $facilities = [];
        
        $facilityTypes = ['Office', 'Factory', 'Warehouse', 'Retail', 'Campus'];
        $locations = [
            'Dubai Industrial City', 'Jebel Ali Free Zone', 'Dubai Silicon Oasis',
            'Abu Dhabi Industrial City', 'Mussafah Industrial Area', 'Sharjah Industrial Area',
            'Ajman Free Zone', 'Ras Al Khaimah Economic Zone', 'Fujairah Free Zone',
            'Dubai Marina', 'Downtown Dubai', 'Business Bay', 'DIFC', 'JLT',
            'Sharjah University City', 'Abu Dhabi University', 'UAE University',
            'Dubai Healthcare City', 'Dubai International Financial Centre',
            'Abu Dhabi Global Market', 'Sharjah Research Technology and Innovation Park'
        ];

        foreach ($companies as $company) {
            // Create 1-3 facilities per company
            $facilityCount = fake()->numberBetween(1, 3);
            
            for ($j = 0; $j < $facilityCount; $j++) {
                $type = fake()->randomElement($facilityTypes);
                $facilityNames = [
                    'Main Office', 'Headquarters', 'Central Warehouse', 'Distribution Center',
                    'Manufacturing Plant', 'Production Facility', 'Service Center', 'Branch Office',
                    'Retail Store', 'Showroom', 'Campus Building', 'Research Center',
                    'Data Center', 'Call Center', 'Training Center', 'Main Campus',
                    'Factory A', 'Factory B', 'Warehouse A', 'Warehouse B',
                    'Office Building', 'Service Center', 'Operations Center'
                ];

                $facilities[] = Facility::create([
                    'company_id' => $company->id,
                    'name' => fake()->randomElement($facilityNames) . ' ' . fake()->randomElement(['A', 'B', 'C', 'Main', 'North', 'South', 'East', 'West', 'Central']),
                    'location' => fake()->randomElement($locations),
                    'type' => $type,
                ]);
            }
        }

        return $facilities;
    }

    private function createActivityData(array $facilities): void
    {
        $this->command->info('Creating energy data...');
        $this->createEnergyData($facilities);
        
        $this->command->info('Creating transport data...');
        $this->createTransportData($facilities);
        
        $this->command->info('Creating industrial data...');
        $this->createIndustrialData($facilities);
        
        $this->command->info('Creating waste data...');
        $this->createWasteData($facilities);
        
        $this->command->info('Creating agriculture data...');
        $this->createAgricultureData($facilities);
    }

    private function createEnergyData(array $facilities): void
    {
        foreach ($facilities as $facility) {
            // Create 3-12 energy records per facility
            $recordCount = fake()->numberBetween(3, 12);
            
            for ($i = 0; $i < $recordCount; $i++) {
                $sourceType = fake()->randomElement(['Electricity', 'Diesel', 'LPG', 'Gasoline', 'Other']);
                
                $consumptionRanges = [
                    'Electricity' => [5000, 50000],
                    'Diesel' => [100, 2000],
                    'LPG' => [50, 500],
                    'Gasoline' => [200, 1000],
                    'Other' => [100, 1000]
                ];

                $consumption = fake()->numberBetween(
                    $consumptionRanges[$sourceType][0],
                    $consumptionRanges[$sourceType][1]
                );

                $units = [
                    'Electricity' => 'kWh',
                    'Diesel' => 'Litres',
                    'LPG' => 'kg',
                    'Gasoline' => 'Litres',
                    'Other' => 'units'
                ];

                $co2eFactors = [
                    'Electricity' => 0.424,
                    'Diesel' => 2.680,
                    'LPG' => 1.500,
                    'Gasoline' => 2.310,
                    'Other' => 1.000
                ];

                $co2e = $consumption * $co2eFactors[$sourceType];

                EnergyData::create([
                    'facility_id' => $facility->id,
                    'source_type' => $sourceType,
                    'consumption_value' => $consumption,
                    'unit' => $units[$sourceType],
                    'date' => fake()->dateTimeBetween('-12 months', 'now')->format('Y-m-d'),
                    'uploaded_file' => fake()->optional(0.3)->randomElement([
                        'energy_consumption_jan_2024.xlsx',
                        'utility_bills_q1_2024.pdf',
                        'meter_readings_march.xlsx'
                    ]),
                    'co2e' => round($co2e, 2),
                ]);
            }
        }
    }

    private function createTransportData(array $facilities): void
    {
        foreach ($facilities as $facility) {
            // Create 2-8 transport records per facility
            $recordCount = fake()->numberBetween(2, 8);
            
            for ($i = 0; $i < $recordCount; $i++) {
                $vehicleTypes = [
                    'Truck', 'Bus', 'Car', 'Van', 'Motorcycle', 'Heavy Vehicle',
                    'Shuttle Bus', 'Delivery Van', 'Fleet Vehicle', 'Company Car'
                ];
                
                $fuelTypes = ['Diesel', 'Petrol', 'LPG', 'CNG', 'Electric', 'Hybrid'];
                $vehicleType = fake()->randomElement($vehicleTypes);
                $fuelType = fake()->randomElement($fuelTypes);

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

                $distance = fake()->numberBetween(
                    $distanceRanges[$vehicleType][0],
                    $distanceRanges[$vehicleType][1]
                );

                $fuelEfficiency = [
                    'Truck' => 8, 'Bus' => 12, 'Car' => 15, 'Van' => 10,
                    'Motorcycle' => 25, 'Heavy Vehicle' => 6, 'Shuttle Bus' => 14,
                    'Delivery Van' => 11, 'Fleet Vehicle' => 13, 'Company Car' => 16
                ];

                $fuelConsumed = $distance / $fuelEfficiency[$vehicleType];

                $co2eFactors = [
                    'Diesel' => 2.680,
                    'Petrol' => 2.310,
                    'LPG' => 1.500,
                    'CNG' => 1.200,
                    'Electric' => 0.100,
                    'Hybrid' => 1.200
                ];

                $co2e = $fuelConsumed * $co2eFactors[$fuelType];

                TransportData::create([
                    'facility_id' => $facility->id,
                    'vehicle_type' => $vehicleType,
                    'fuel_type' => $fuelType,
                    'distance_travelled' => $distance,
                    'fuel_consumed' => round($fuelConsumed, 2),
                    'unit' => 'km/Litres',
                    'date' => fake()->dateTimeBetween('-12 months', 'now')->format('Y-m-d'),
                    'uploaded_file' => fake()->optional(0.2)->randomElement([
                        'fleet_log_feb_2024.xlsx',
                        'fuel_receipts_march.pdf',
                        'vehicle_tracking_data.xlsx'
                    ]),
                    'co2e' => round($co2e, 2),
                ]);
            }
        }
    }

    private function createIndustrialData(array $facilities): void
    {
        // Only create industrial data for manufacturing facilities
        $manufacturingFacilities = array_filter($facilities, function($facility) {
            return $facility->type === 'Factory';
        });

        foreach ($manufacturingFacilities as $facility) {
            // Create 1-5 industrial records per manufacturing facility
            $recordCount = fake()->numberBetween(1, 5);
            
            for ($i = 0; $i < $recordCount; $i++) {
                $processTypes = [
                    'Cement Clinker Production', 'Steel Manufacturing', 'Aluminum Smelting',
                    'Chemical Processing', 'Plastic Manufacturing', 'Textile Production',
                    'Food Processing', 'Pharmaceutical Manufacturing', 'Paper Production',
                    'Glass Manufacturing', 'Ceramic Production', 'Rubber Processing'
                ];

                $rawMaterials = [
                    'Limestone', 'Iron Ore', 'Bauxite', 'Crude Oil', 'Natural Gas',
                    'Coal', 'Wood Pulp', 'Cotton', 'Wheat', 'Soybeans', 'Corn',
                    'Plastic Pellets', 'Chemical Compounds', 'Sand', 'Clay', 'Silica'
                ];

                $processType = fake()->randomElement($processTypes);
                $rawMaterial = fake()->randomElement($rawMaterials);

                $quantityRanges = [
                    'Cement Clinker Production' => [50, 1000],
                    'Steel Manufacturing' => [100, 2000],
                    'Aluminum Smelting' => [200, 1500],
                    'Chemical Processing' => [10, 500],
                    'Plastic Manufacturing' => [20, 300],
                    'Textile Production' => [100, 1000],
                    'Food Processing' => [50, 800],
                    'Pharmaceutical Manufacturing' => [5, 100],
                    'Paper Production' => [200, 1500],
                    'Glass Manufacturing' => [100, 800],
                    'Ceramic Production' => [50, 400],
                    'Rubber Processing' => [30, 200]
                ];

                $quantity = fake()->numberBetween(
                    $quantityRanges[$processType][0],
                    $quantityRanges[$processType][1]
                );

                $units = ['tonnes', 'kg', 'litres', 'cubic meters', 'pieces'];
                $unit = fake()->randomElement($units);

                $co2eFactors = [
                    'Cement Clinker Production' => 0.9,
                    'Steel Manufacturing' => 1.8,
                    'Aluminum Smelting' => 8.2,
                    'Chemical Processing' => 2.5,
                    'Plastic Manufacturing' => 3.0,
                    'Textile Production' => 0.5,
                    'Food Processing' => 0.3,
                    'Pharmaceutical Manufacturing' => 1.2,
                    'Paper Production' => 0.8,
                    'Glass Manufacturing' => 0.6,
                    'Ceramic Production' => 0.4,
                    'Rubber Processing' => 1.5
                ];

                $co2e = $quantity * $co2eFactors[$processType];

                IndustrialData::create([
                    'facility_id' => $facility->id,
                    'process_type' => $processType,
                    'raw_material' => $rawMaterial,
                    'quantity' => $quantity,
                    'unit' => $unit,
                    'date' => fake()->dateTimeBetween('-12 months', 'now')->format('Y-m-d'),
                    'uploaded_file' => fake()->optional(0.3)->randomElement([
                        'production_report.xlsx',
                        'material_usage_march.pdf',
                        'process_data_q1_2024.xlsx'
                    ]),
                    'co2e' => round($co2e, 2),
                ]);
            }
        }
    }

    private function createWasteData(array $facilities): void
    {
        foreach ($facilities as $facility) {
            // Create 2-6 waste records per facility
            $recordCount = fake()->numberBetween(2, 6);
            
            for ($i = 0; $i < $recordCount; $i++) {
                $wasteTypes = [
                    'Food Waste', 'Plastic', 'Paper', 'Cardboard', 'Glass',
                    'Metal', 'Electronic Waste', 'Hazardous Waste', 'Organic Waste',
                    'Construction Waste', 'Textile Waste', 'Medical Waste'
                ];

                $disposalMethods = ['Landfill', 'Incineration', 'Recycling', 'Composting'];
                $wasteType = fake()->randomElement($wasteTypes);
                $disposalMethod = fake()->randomElement($disposalMethods);

                $quantityRanges = [
                    'Food Waste' => [0.5, 10],
                    'Plastic' => [0.2, 5],
                    'Paper' => [0.5, 8],
                    'Cardboard' => [0.3, 6],
                    'Glass' => [0.1, 3],
                    'Metal' => [0.2, 4],
                    'Electronic Waste' => [0.1, 2],
                    'Hazardous Waste' => [0.05, 1],
                    'Organic Waste' => [0.3, 7],
                    'Construction Waste' => [1, 50],
                    'Textile Waste' => [0.1, 3],
                    'Medical Waste' => [0.01, 0.5]
                ];

                $quantity = fake()->randomFloat(2,
                    $quantityRanges[$wasteType][0],
                    $quantityRanges[$wasteType][1]
                );

                $units = ['tonnes', 'kg', 'cubic meters'];
                $unit = fake()->randomElement($units);

                $co2eFactors = [
                    'Landfill' => [
                        'Food Waste' => 1.9, 'Plastic' => 2.5, 'Paper' => 1.2,
                        'Cardboard' => 1.0, 'Glass' => 0.3, 'Metal' => 0.1,
                        'Electronic Waste' => 3.0, 'Hazardous Waste' => 4.0,
                        'Organic Waste' => 1.5, 'Construction Waste' => 0.8,
                        'Textile Waste' => 2.0, 'Medical Waste' => 5.0
                    ],
                    'Incineration' => [
                        'Food Waste' => 0.6, 'Plastic' => 2.8, 'Paper' => 1.5,
                        'Cardboard' => 1.2, 'Glass' => 0.1, 'Metal' => 0.2,
                        'Electronic Waste' => 2.5, 'Hazardous Waste' => 3.5,
                        'Organic Waste' => 0.8, 'Construction Waste' => 1.0,
                        'Textile Waste' => 2.2, 'Medical Waste' => 4.5
                    ],
                    'Recycling' => [
                        'Food Waste' => 0.2, 'Plastic' => 0.3, 'Paper' => 0.2,
                        'Cardboard' => 0.2, 'Glass' => 0.1, 'Metal' => 0.1,
                        'Electronic Waste' => 0.5, 'Hazardous Waste' => 1.0,
                        'Organic Waste' => 0.1, 'Construction Waste' => 0.3,
                        'Textile Waste' => 0.4, 'Medical Waste' => 2.0
                    ],
                    'Composting' => [
                        'Food Waste' => 0.1, 'Plastic' => 0.5, 'Paper' => 0.3,
                        'Cardboard' => 0.2, 'Glass' => 0.1, 'Metal' => 0.1,
                        'Electronic Waste' => 1.0, 'Hazardous Waste' => 2.0,
                        'Organic Waste' => 0.1, 'Construction Waste' => 0.2,
                        'Textile Waste' => 0.3, 'Medical Waste' => 1.5
                    ]
                ];

                $co2e = $quantity * $co2eFactors[$disposalMethod][$wasteType];

                WasteData::create([
                    'facility_id' => $facility->id,
                    'waste_type' => $wasteType,
                    'quantity' => $quantity,
                    'unit' => $unit,
                    'disposal_method' => $disposalMethod,
                    'date' => fake()->dateTimeBetween('-12 months', 'now')->format('Y-m-d'),
                    'uploaded_file' => fake()->optional(0.25)->randomElement([
                        'waste_report_jan_2024.xlsx',
                        'disposal_certificates.pdf',
                        'waste_tracking_march.xlsx'
                    ]),
                    'co2e' => round($co2e, 2),
                ]);
            }
        }
    }

    private function createAgricultureData(array $facilities): void
    {
        // Only create agriculture data for campus/education facilities
        $campusFacilities = array_filter($facilities, function($facility) {
            return $facility->type === 'Campus';
        });

        foreach ($campusFacilities as $facility) {
            // Create 1-3 agriculture records per campus facility
            $recordCount = fake()->numberBetween(1, 3);
            
            for ($i = 0; $i < $recordCount; $i++) {
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

                $livestockType = fake()->randomElement($livestockTypes);
                $feedType = fake()->randomElement($feedTypes);
                $manureMgmt = fake()->randomElement($manureManagement);

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

                $headcount = fake()->numberBetween(
                    $headcountRanges[$livestockType][0],
                    $headcountRanges[$livestockType][1]
                );

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

                AgricultureData::create([
                    'facility_id' => $facility->id,
                    'livestock_type' => $livestockType,
                    'feed_type' => $feedType,
                    'manure_mgmt' => $manureMgmt,
                    'headcount' => $headcount,
                    'date' => fake()->dateTimeBetween('-12 months', 'now')->format('Y-m-d'),
                    'co2e' => round($co2e, 2),
                ]);
            }
        }
    }

    private function createReportsAndSubscriptions(array $companies): void
    {
        foreach ($companies as $company) {
            // Create 2-4 reports per company
            $reportCount = fake()->numberBetween(2, 4);
            
            for ($i = 0; $i < $reportCount; $i++) {
                $types = ['MOCCAE', 'GRI', 'Internal'];
                $type = fake()->randomElement($types);

                $periodStart = fake()->dateTimeBetween('-2 years', '-6 months');
                $periodEnd = fake()->dateTimeBetween($periodStart, 'now');

                $fileNames = [
                    'MOCCAE' => [
                        'moccae_annual_report_2023.pdf',
                        'carbon_footprint_assessment_2024.pdf',
                        'sustainability_report_moccae_2023.pdf'
                    ],
                    'GRI' => [
                        'gri_sustainability_report_2023.pdf',
                        'gri_standards_compliance_2024.pdf',
                        'global_reporting_initiative_2023.pdf'
                    ],
                    'Internal' => [
                        'internal_carbon_audit_2024.pdf',
                        'quarterly_sustainability_report.pdf',
                        'environmental_impact_assessment.pdf'
                    ]
                ];

                Report::create([
                    'company_id' => $company->id,
                    'type' => $type,
                    'period_start' => $periodStart->format('Y-m-d'),
                    'period_end' => $periodEnd->format('Y-m-d'),
                    'file_path' => '/reports/' . fake()->randomElement($fileNames[$type]),
                ]);
            }

            // Create subscription
            $planTypes = ['Free', 'Standard', 'Premium'];
            $planType = fake()->randomElement($planTypes);
            $statuses = ['active', 'cancelled', 'trialing'];
            $status = fake()->randomElement($statuses);
            
            $startedAt = fake()->dateTimeBetween('-2 years', '-1 month');
            
            $expiryMonths = [
                'Free' => 1,
                'Standard' => 12,
                'Premium' => 12
            ];

            $expiresAt = fake()->dateTimeBetween(
                $startedAt,
                $startedAt->modify('+' . $expiryMonths[$planType] . ' months')
            );

            $stripeCustomerId = null;
            if (in_array($planType, ['Standard', 'Premium']) && $status === 'active') {
                $stripeCustomerId = 'cus_' . fake()->regexify('[A-Za-z0-9]{14}');
            }

            Subscription::create([
                'company_id' => $company->id,
                'plan_type' => $planType,
                'status' => $status,
                'stripe_customer_id' => $stripeCustomerId,
                'started_at' => $startedAt,
                'expires_at' => $expiresAt,
            ]);
        }
    }
}

