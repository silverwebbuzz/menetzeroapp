<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmissionSourceMaster;

class EmissionSourcesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $emissionSources = [
            // Scope 1 Sources
            [
                'name' => 'Onsite Wastewater Treatment',
                'description' => 'Emissions from the on-site treatment of wastewater',
                'scope' => 'Scope 1',
                'category' => null,
                'subcategory' => null,
                'type' => null,
            ],
            [
                'name' => 'Natural Gas - Stationary Combustion',
                'description' => 'Emissions from the combustion of natural gas for the purpose of producing electricity, generating steam, or providing useful heat (e.g. for cooking)',
                'scope' => 'Scope 1',
                'category' => null,
                'subcategory' => null,
                'type' => null,
            ],
            [
                'name' => 'Refrigerants',
                'description' => 'Emissions from the unintential release of GHGs including HFCs from refrigeration, air conditioning, and cooling',
                'scope' => 'Scope 1',
                'category' => null,
                'subcategory' => null,
                'type' => null,
            ],
            [
                'name' => 'Diesel - Stationary Combustion',
                'description' => 'Emissions from the combustion of diesel for the purpose of producing electricity, generating steam, or providing useful heat',
                'scope' => 'Scope 1',
                'category' => null,
                'subcategory' => null,
                'type' => null,
            ],
            [
                'name' => 'Company owned vehicles',
                'description' => 'Emissions from the combustion of fuel in company-owned or controlled mobile combustion sources (cars, trucks, buses, trains, airplanes, ships, etc.)',
                'scope' => 'Scope 1',
                'category' => null,
                'subcategory' => null,
                'type' => null,
            ],
            [
                'name' => 'Cylindrical Gas - Stationary Combustion',
                'description' => 'Emissions from the combustion of propane gas for the purpose of producing electricity, generating steam, or providing useful heat (e.g. for cooking)',
                'scope' => 'Scope 1',
                'category' => null,
                'subcategory' => null,
                'type' => null,
            ],

            // Scope 2 Sources
            [
                'name' => 'Electricity - Own Generation',
                'description' => 'Emissions from electricity generated directly from yourself',
                'scope' => 'Scope 2',
                'category' => null,
                'subcategory' => null,
                'type' => null,
            ],
            [
                'name' => 'Electricity - Direct purchase',
                'description' => 'Emissions from electricity purchased directly from the energy retailer',
                'scope' => 'Scope 2',
                'category' => null,
                'subcategory' => null,
                'type' => null,
            ],
            [
                'name' => 'Electricity Fleet - Mobility',
                'description' => 'Emissions from energy used by electric-driven vehicles',
                'scope' => 'Scope 2',
                'category' => null,
                'subcategory' => null,
                'type' => null,
            ],
            [
                'name' => 'Heat supply (District heat)',
                'description' => 'Emissions from HVAC, Lighting, Equipment, Lifts, hot water and other energy consumption in shared spaces of office buildings',
                'scope' => 'Scope 2',
                'category' => null,
                'subcategory' => null,
                'type' => null,
            ],

            // Scope 3 Sources - Upstream
            [
                'name' => 'Cleaning Services and Chemicals',
                'description' => 'Emissions from cleaning services and chemicals used for cleaning',
                'scope' => 'Scope 3',
                'category' => '3.1 Purchased goods and services',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'Postage & Couriers',
                'description' => 'Emissions from the transport and delivery of postage and couriers',
                'scope' => 'Scope 3',
                'category' => '3.1 Purchased goods and services',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'Food and Catering',
                'description' => 'Embodied emissions of food and drinks consumed by employees at work',
                'scope' => 'Scope 3',
                'category' => '3.1 Purchased goods and services',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'Software and Cloud Services',
                'description' => 'Emissions from cloud computing services including Amazon Web Services, Microsoft Azure, Google Cloud Platform or any other service relating to a company\'s digital infrastructure',
                'scope' => 'Scope 3',
                'category' => '3.1 Purchased goods and services',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'Professional Services',
                'description' => 'Emissions from the use of professional services including marketing and distribution services, accounting services, advertising and promotion services, and legal services etc.',
                'scope' => 'Scope 3',
                'category' => '3.1 Purchased goods and services',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'Telephone & Internet',
                'description' => 'Upstream emissions from telephone & internet use',
                'scope' => 'Scope 3',
                'category' => '3.1 Purchased goods and services',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'Horticulture and Agriculture',
                'description' => 'Emissions from horticulture and agriculture services and materials',
                'scope' => 'Scope 3',
                'category' => '3.1 Purchased goods and services',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'Construction and Material Services',
                'description' => 'Emissions from construction activities and materials used for construction activities',
                'scope' => 'Scope 3',
                'category' => '3.1 Purchased goods and services',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'Water',
                'description' => 'Emissions from the extraction, treatment, and delivery of purchased water',
                'scope' => 'Scope 3',
                'category' => '3.1 Purchased goods and services',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'Printing & Stationary',
                'description' => 'Embodied emissions of stationery consumed in the office',
                'scope' => 'Scope 3',
                'category' => '3.1 Purchased goods and services',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'Packaging',
                'description' => 'The environmental impact of packaging materials includes raw material extraction, primary production of the material, the packaging formation process, recycling, and waste management',
                'scope' => 'Scope 3',
                'category' => '3.1 Purchased goods and services',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'Paper',
                'description' => 'Embodied emissions of paper consumed in the business activities',
                'scope' => 'Scope 3',
                'category' => '3.1 Purchased goods and services',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'Office Furniture',
                'description' => 'All upstream (cradle-to-gate) emissions of purchased furniture',
                'scope' => 'Scope 3',
                'category' => '3.1 Purchased goods and services',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'International Accomodation',
                'description' => 'Emissions from HVAC, Lighting, Equipment, Lifts, hot water and other energy consumption in accommodation',
                'scope' => 'Scope 3',
                'category' => '3.1 Purchased goods and services',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'Other purchased goods and services',
                'description' => 'Other purchased goods and services',
                'scope' => 'Scope 3',
                'category' => '3.1 Purchased goods and services',
                'subcategory' => null,
                'type' => 'upstream',
            ],

            // 3.2 Capital goods
            [
                'name' => 'Machinery and vehicles',
                'description' => 'Emissions from machinery repair, hire, and maintenance',
                'scope' => 'Scope 3',
                'category' => '3.2 Capital goods',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'Real estate services',
                'description' => 'Real estate services',
                'scope' => 'Scope 3',
                'category' => '3.2 Capital goods',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'Computer and related services',
                'description' => 'Computer and related services',
                'scope' => 'Scope 3',
                'category' => '3.2 Capital goods',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'ICT Services and Equipment',
                'description' => 'Embodied emissions from IT equipment purchasing and the use of computer and technical services',
                'scope' => 'Scope 3',
                'category' => '3.2 Capital goods',
                'subcategory' => null,
                'type' => 'upstream',
            ],

            // 3.3 Fuel and energy related activities
            [
                'name' => 'Electricity - At client site',
                'description' => 'Emissions from electricity purchased directly from the energy retailer',
                'scope' => 'Scope 3',
                'category' => '3.3 Fuel and energy related activities',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'Working from Home',
                'description' => 'Emissions from employees working from a home office space',
                'scope' => 'Scope 3',
                'category' => '3.3 Fuel and energy related activities',
                'subcategory' => null,
                'type' => 'upstream',
            ],

            // 3.4 Upstream transportation and distribution
            [
                'name' => 'Client travel',
                'description' => 'Client travel',
                'scope' => 'Scope 3',
                'category' => '3.4 Upstream transportation and distribution',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'Freight (upstream)',
                'description' => 'Emissions from the transportation and distribution of upstream goods and services',
                'scope' => 'Scope 3',
                'category' => '3.4 Upstream transportation and distribution',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'E-commerce Shipping (upstream)',
                'description' => 'Emissions from the transport and delivery of postage and couriers for e-commerce (e.g. Ship it, Sendle)',
                'scope' => 'Scope 3',
                'category' => '3.4 Upstream transportation and distribution',
                'subcategory' => null,
                'type' => 'upstream',
            ],

            // 3.5 Waste generated in operations
            [
                'name' => 'Waste',
                'description' => 'Emissions from the disposal or recycling and treatment of waste generated by the company\'s operations',
                'scope' => 'Scope 3',
                'category' => '3.5 Waste generated in operations',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'Wastewater',
                'description' => 'Emissions from the downstream treatment of wastewater generated by the companys operations',
                'scope' => 'Scope 3',
                'category' => '3.5 Waste generated in operations',
                'subcategory' => null,
                'type' => 'upstream',
            ],

            // 3.6 Business travel
            [
                'name' => 'Taxis & Rideshare',
                'description' => 'Emissions from fuel consumed in vehicles to transport employees - not owned by the business',
                'scope' => 'Scope 3',
                'category' => '3.6 Business travel',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'Public Transport',
                'description' => 'Public Transport',
                'scope' => 'Scope 3',
                'category' => '3.6 Business travel',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'Car Travel - Non Company Owned Vehicles',
                'description' => 'Emissions from fuel consumed in vehicles used to transport employees not owned by the business including rental and personal vehicles',
                'scope' => 'Scope 3',
                'category' => '3.6 Business travel',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'Air Travel',
                'description' => 'Portion of commercial flight\'s emissions attributable to employee travel',
                'scope' => 'Scope 3',
                'category' => '3.6 Business travel',
                'subcategory' => null,
                'type' => 'upstream',
            ],

            // 3.7 Employee commuting
            [
                'name' => 'Staff Commuting',
                'description' => 'Emissions from fuel consumed in vehicles transporting employees between their homes and worksites',
                'scope' => 'Scope 3',
                'category' => '3.7 Employee commuting',
                'subcategory' => null,
                'type' => 'upstream',
            ],

            // 3.8 Upstream leased assets
            [
                'name' => 'Roads and landscape',
                'description' => 'Emissions from materials used to construct roads and infrastructure projects',
                'scope' => 'Scope 3',
                'category' => '3.8 Upstream leased assets',
                'subcategory' => null,
                'type' => 'upstream',
            ],
            [
                'name' => 'Domestic Accommodation and Venue Hire',
                'description' => 'Emissions from HVAC, Lighting, Equipment, Lifts, hot water and other energy consumption in accommodation and venue hire',
                'scope' => 'Scope 3',
                'category' => '3.8 Upstream leased assets',
                'subcategory' => null,
                'type' => 'upstream',
            ],

            // Downstream Sources
            [
                'name' => 'E-commerce Shipping (downstream)',
                'description' => 'Emissions from the transport and delivery of postage and couriers for e-commerce (e.g. Ship it, Sendle)',
                'scope' => 'Scope 3',
                'category' => '3.9 Downstream transportation and distribution',
                'subcategory' => null,
                'type' => 'downstream',
            ],
            [
                'name' => 'Freight (downstream)',
                'description' => 'Emissions from the transportation and distribution of upstream goods and services',
                'scope' => 'Scope 3',
                'category' => '3.9 Downstream transportation and distribution',
                'subcategory' => null,
                'type' => 'downstream',
            ],
            [
                'name' => '3.10 Processing of sold products',
                'description' => 'Post-sale activities altering products',
                'scope' => 'Scope 3',
                'category' => '3.10 Processing of sold products',
                'subcategory' => null,
                'type' => 'downstream',
            ],
            [
                'name' => '3.11 Use of sold products',
                'description' => 'Carbon emissions resulting from customers\' use of products purchased from the company',
                'scope' => 'Scope 3',
                'category' => '3.11 Use of sold products',
                'subcategory' => null,
                'type' => 'downstream',
            ],
            [
                'name' => '3.12 End-of-life treatment of sold products',
                'description' => 'Environmental impact assessment of products\' disposal after consumer use',
                'scope' => 'Scope 3',
                'category' => '3.12 End-of-life treatment of sold products',
                'subcategory' => null,
                'type' => 'downstream',
            ],
            [
                'name' => '3.13 Downstream leased assets',
                'description' => 'Leased assets used downstream in supply chain operations',
                'scope' => 'Scope 3',
                'category' => '3.13 Downstream leased assets',
                'subcategory' => null,
                'type' => 'downstream',
            ],
            [
                'name' => '3.14 Franchisees',
                'description' => 'Indirect emissions from activities of franchisees that occur as a result of the company\'s operations',
                'scope' => 'Scope 3',
                'category' => '3.14 Franchisees',
                'subcategory' => null,
                'type' => 'downstream',
            ],
            [
                'name' => '3.15 Investments',
                'description' => 'Emissions produced from investments in activities such as supply chains and infrastructure',
                'scope' => 'Scope 3',
                'category' => '3.15 Investments',
                'subcategory' => null,
                'type' => 'downstream',
            ],
        ];

        foreach ($emissionSources as $source) {
            EmissionSourceMaster::firstOrCreate(
                ['name' => $source['name'], 'scope' => $source['scope']],
                $source
            );
        }
    }
}
