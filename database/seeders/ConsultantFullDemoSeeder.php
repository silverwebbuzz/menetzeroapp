<?php

namespace Database\Seeders;

use App\Data\ConsultantAgencyPlanMatrix;
use App\Data\PlanEntitlementDefaults;
use App\Models\AdminPackageAssignment;
use App\Models\ClimateOpportunity;
use App\Models\ClimateRisk;
use App\Models\Company;
use App\Models\CompanyReportingSetting;
use App\Models\Consultant;
use App\Models\ConsultantClientEngagement;
use App\Models\ConsultantSubscription;
use App\Models\EmissionFactor;
use App\Models\EmissionSourceMaster;
use App\Models\EnergyData;
use App\Models\EsgKpiSnapshot;
use App\Models\EsgSustainabilityTarget;
use App\Models\Facility;
use App\Models\HrisKpiImportLog;
use App\Models\IndustrialData;
use App\Models\Location;
use App\Models\LocationEmissionBoundary;
use App\Models\MaterialSustainabilityTopic;
use App\Models\Measurement;
use App\Models\MeasurementAuditTrail;
use App\Models\MeasurementData;
use App\Models\ReductionTarget;
use App\Models\StakeholderEngagement;
use App\Models\SubscriptionPlan;
use App\Models\SupplyChainSupplier;
use App\Models\SustainabilityRisk;
use App\Models\TransitionAction;
use App\Models\TransportData;
use App\Models\User;
use App\Models\WasteData;
use App\Services\ConsultantAccountService;
use App\Services\ConsultantAgencyClientService;
use App\Services\ConsultantAgencySubscriptionService;
use App\Services\DisclosureService;
use App\Services\EsgScorecardService;
use App\Services\MeasurementService;
use App\Services\SasbIndexService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Multi-package consultant demo (CONSULTANT_MULTI_PACKAGE_PLAN Phase 5).
 *
 * Seeds Silver Webbuzz Sustainability Practice with:
 * - consultant_free (1 slot)
 * - all five depth plans × 5 slots each (Basic, Pro, ESG Starter, ESG Complete, Enterprise)
 * - 25 managed clients (5 companies per depth) with full module demo data each
 *
 * Login: demo.full@menetzero.com / FullDemo1!
 * Run: php artisan db:seed --class=ConsultantFullDemoSeeder
 */
class ConsultantFullDemoSeeder extends Seeder
{
    public const EMAIL = 'demo.full@menetzero.com';

    public const PASSWORD = 'FullDemo1!';

    /** Depth capacity: consultant plan code => mirrored client package + demo firm prefixes. */
    private const DEPTH_CAPACITY = [
        'consultant_scope_basic' => [
            'client_package' => 'client_scope_basic',
            'slots' => 5,
            'firms' => [
                ['name' => 'Desert Logistics LLC', 'display' => 'Desert Logistics', 'emirate' => 'Dubai', 'sector' => 'Logistics', 'industry' => 'Freight'],
                ['name' => 'Falaj Catering Co', 'display' => 'Falaj Catering', 'emirate' => 'Abu Dhabi', 'sector' => 'Hospitality', 'industry' => 'Food service'],
                ['name' => 'Coral Retail Group', 'display' => 'Coral Retail', 'emirate' => 'Sharjah', 'sector' => 'Retail', 'industry' => 'FMCG'],
                ['name' => 'Qarnas Workshops LLC', 'display' => 'Qarnas Workshops', 'emirate' => 'Ajman', 'sector' => 'Manufacturing', 'industry' => 'Metal fab'],
                ['name' => 'Saha Clinics FZ', 'display' => 'Saha Clinics', 'emirate' => 'Dubai', 'sector' => 'Healthcare', 'industry' => 'Clinics'],
            ],
        ],
        'consultant_scope_pro' => [
            'client_package' => 'client_scope_pro',
            'slots' => 5,
            'firms' => [
                ['name' => 'Marina Hotels LLC', 'display' => 'Marina Hotels', 'emirate' => 'Dubai', 'sector' => 'Hospitality', 'industry' => 'Hotels'],
                ['name' => 'Barakah Farms LLC', 'display' => 'Barakah Farms', 'emirate' => 'Abu Dhabi', 'sector' => 'Agriculture', 'industry' => 'Farming'],
                ['name' => 'Noor Properties PJSC', 'display' => 'Noor Properties', 'emirate' => 'Abu Dhabi', 'sector' => 'Real Estate', 'industry' => 'Property'],
                ['name' => 'Gulf Pack Industries', 'display' => 'Gulf Pack', 'emirate' => 'Ras Al Khaimah', 'sector' => 'Manufacturing', 'industry' => 'Packaging'],
                ['name' => 'Horizon Schools FZE', 'display' => 'Horizon Schools', 'emirate' => 'Dubai', 'sector' => 'Education', 'industry' => 'K-12'],
            ],
        ],
        'consultant_esg_starter' => [
            'client_package' => 'client_esg_starter',
            'slots' => 5,
            'firms' => [
                ['name' => 'Yas Investments LLC', 'display' => 'Yas Investments', 'emirate' => 'Abu Dhabi', 'sector' => 'Finance', 'industry' => 'Asset management'],
                ['name' => 'Pearl Telecom LLC', 'display' => 'Pearl Telecom', 'emirate' => 'Dubai', 'sector' => 'Telecom', 'industry' => 'ISP'],
                ['name' => 'Liwa Construction LLC', 'display' => 'Liwa Construction', 'emirate' => 'Abu Dhabi', 'sector' => 'Construction', 'industry' => 'Buildings'],
                ['name' => 'Safina Shipping LLC', 'display' => 'Safina Shipping', 'emirate' => 'Fujairah', 'sector' => 'Maritime', 'industry' => 'Shipping'],
                ['name' => 'Zahra Beauty Brands', 'display' => 'Zahra Beauty', 'emirate' => 'Dubai', 'sector' => 'Retail', 'industry' => 'Cosmetics'],
            ],
        ],
        'consultant_esg_complete' => [
            'client_package' => 'client_esg_complete',
            'slots' => 5,
            'firms' => [
                ['name' => 'Emirates Fibre Group', 'display' => 'Emirates Fibre', 'emirate' => 'Dubai', 'sector' => 'Manufacturing', 'industry' => 'Materials'],
                ['name' => 'Capital Markets House', 'display' => 'Capital Markets House', 'emirate' => 'Abu Dhabi', 'sector' => 'Finance', 'industry' => 'Brokerage'],
                ['name' => 'Oasis Data Centres LLC', 'display' => 'Oasis Data Centres', 'emirate' => 'Dubai', 'sector' => 'Technology', 'industry' => 'Data centres'],
                ['name' => 'Mujrim Energy Services', 'display' => 'Mujrim Energy', 'emirate' => 'Abu Dhabi', 'sector' => 'Energy', 'industry' => 'Oilfield services'],
                ['name' => 'Wahat Retail Holdings', 'display' => 'Wahat Retail', 'emirate' => 'Sharjah', 'sector' => 'Retail', 'industry' => 'Department stores'],
            ],
        ],
        'consultant_enterprise' => [
            'client_package' => 'client_enterprise',
            'slots' => 5,
            'firms' => [
                ['name' => 'Al Noor Trading LLC', 'display' => 'Al Noor Trading', 'emirate' => 'Dubai', 'sector' => 'Trading & Distribution', 'industry' => 'Wholesale trade'],
                ['name' => 'Bay Gate Holdings PJSC', 'display' => 'Bay Gate Holdings', 'emirate' => 'Dubai', 'sector' => 'Conglomerate', 'industry' => 'Diversified'],
                ['name' => 'Crescent Aviation LLC', 'display' => 'Crescent Aviation', 'emirate' => 'Abu Dhabi', 'sector' => 'Aviation', 'industry' => 'MRO'],
                ['name' => 'Dune Mobility Platforms', 'display' => 'Dune Mobility', 'emirate' => 'Dubai', 'sector' => 'Mobility', 'industry' => 'EV fleet'],
                ['name' => 'Falcon Industrial Parks', 'display' => 'Falcon Parks', 'emirate' => 'Abu Dhabi', 'sector' => 'Industrial', 'industry' => 'Parks'],
            ],
        ],
    ];

    public function run(): void
    {
        $this->ensureConsultantAgencyPlans();

        $reportingYear = (int) date('Y');

        $consultant = $this->ensureConsultant();

        $accountService = app(ConsultantAccountService::class);
        ['user' => $user, 'company' => $consultantOrg] = $accountService->ensureLinked($consultant);

        $subscriptionService = app(ConsultantAgencySubscriptionService::class);
        $subscriptionService->ensureFreeTrialSubscription($consultantOrg);

        $depthSubs = [];
        foreach (self::DEPTH_CAPACITY as $planCode => $meta) {
            $depthSubs[$planCode] = $this->ensureDepthSubscription(
                $consultantOrg,
                $planCode,
                (int) $meta['slots'],
                (string) $meta['client_package'],
                $reportingYear,
            );
        }

        $clientService = app(ConsultantAgencyClientService::class);
        $measurementService = app(MeasurementService::class);

        $clientCount = 0;
        $totalEntries = 0;
        $locationNames = [];

        foreach (self::DEPTH_CAPACITY as $planCode => $meta) {
            $subscription = $depthSubs[$planCode];
            foreach ($meta['firms'] as $firm) {
                $engagement = $this->ensureEngagementForFirm(
                    $clientService,
                    $consultantOrg,
                    $subscription,
                    $firm,
                    $reportingYear,
                );
                $managed = $engagement->managedCompany;
                $this->enrichManagedCompanyProfile($managed, $firm);

                $locations = $this->ensureLocations($managed, $reportingYear);
                foreach ($locations as $location) {
                    $locationNames[$location->name] = true;
                    $measurement = $measurementService->getOrCreateMeasurement($location->id, $reportingYear, $user->id);
                    $totalEntries += $this->seedEmissionEntries($measurement, $user->id, $reportingYear);
                    $measurementService->updateMeasurementTotals($measurement->id);
                }

                $this->seedLegacyFacilityData($managed, $locations, $reportingYear);
                $this->seedReportingSettings($managed, $reportingYear);
                $this->seedDisclosures($managed, $reportingYear);
                $this->seedClimateRisksAndOpportunities($managed, $reportingYear);
                $this->seedReductionTargets($managed, $reportingYear);
                $this->seedMaterialTopics($managed, $reportingYear);
                $this->seedEsgDepth($managed, $reportingYear);
                $this->seedSasbSector($managed, $reportingYear);
                $this->seedEsgKpiSnapshots($managed, $reportingYear);
                $this->seedHrisImportLog($managed, $reportingYear, $user->id);
                $this->seedAuditTrail($locations, $user->id);
                $this->seedAdminAssignment($consultant, $consultantOrg, $managed, $subscription, $reportingYear);
                $clientCount++;
                $this->command?->info("Seeded {$firm['name']} under {$planCode}");
            }
        }

        $this->printSummary($consultantOrg, $reportingYear, $clientCount, $totalEntries, count($locationNames));
    }

    private function ensureConsultant(): Consultant
    {
        $consultant = Consultant::where('email', self::EMAIL)->first();

        if ($consultant) {
            return $consultant;
        }

        return Consultant::create([
            'name' => 'Demo Consultant',
            'email' => self::EMAIL,
            'password' => Hash::make(self::PASSWORD),
            'phone' => '+971501234567',
            'company_name' => 'Silver Webbuzz Sustainability Practice',
            'trade_license_number' => 'DEMO-TL-001',
            'bio' => 'Demo consultant account for multi-package managed-client testing (all five depth packs × 5 slots).',
            'emirates' => ['dubai', 'abu_dhabi'],
            'languages' => ['en', 'ar'],
            'specialties' => ['moccae', 'ghg_protocol', 'ifrs_s2'],
            'experience_years' => 8,
            'has_moccae_experience' => true,
            'status' => 'draft',
            'is_active' => true,
        ]);
    }

    private function ensureDepthSubscription(
        Company $consultantOrg,
        string $planCode,
        int $slots,
        string $clientPackage,
        int $reportingYear,
    ): ConsultantSubscription {
        $existing = ConsultantSubscription::forConsultant($consultantOrg->id)
            ->active()
            ->whereHas('plan', fn ($q) => $q->where('plan_code', $planCode))
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            if ((int) $existing->slot_limit < $slots) {
                $existing->update(['slot_limit' => $slots]);
            }

            $meta = $existing->metadata ?? [];
            $meta['managed_client_package_code'] = $clientPackage;
            $meta['client_package_code'] = $clientPackage;
            $meta['provision_note'] = 'Phase 5 multi-package demo seed';
            $existing->update(['metadata' => $meta]);

            return $existing->fresh(['plan']);
        }

        return app(ConsultantAgencySubscriptionService::class)->grantDepthSubscription(
            $consultantOrg,
            $planCode,
            $slots,
            $reportingYear,
            [
                'managed_client_package_code' => $clientPackage,
                'client_package_code' => $clientPackage,
                'provision_note' => 'Phase 5 multi-package demo seed',
                'seeded_by' => 'ConsultantFullDemoSeeder',
            ],
        );
    }

    /**
     * @param  array{name: string, display: string, emirate: string, sector: string, industry: string}  $firm
     */
    private function ensureEngagementForFirm(
        ConsultantAgencyClientService $clientService,
        Company $consultantOrg,
        ConsultantSubscription $subscription,
        array $firm,
        int $reportingYear,
    ): ConsultantClientEngagement {
        $existing = ConsultantClientEngagement::query()
            ->with('managedCompany')
            ->where('consultant_company_id', $consultantOrg->id)
            ->where('consultant_subscription_id', $subscription->id)
            ->whereHas('managedCompany', fn ($q) => $q->where('name', $firm['name']))
            ->first();

        if ($existing) {
            return $existing;
        }

        return $clientService->create($consultantOrg, [
            'name' => $firm['name'],
            'display_name' => $firm['display'],
            'primary_reporting_year' => $reportingYear,
            'consultant_subscription_id' => $subscription->id,
            'country' => 'United Arab Emirates',
            'emirate' => $firm['emirate'],
            'sector' => $firm['sector'],
            'industry' => $firm['industry'],
            'contact_person' => 'Demo Contact',
            'description' => 'Phase 5 demo managed client — ' . $firm['display'] . ' (' . $subscription->plan?->plan_code . ').',
        ]);
    }

    /**
     * @param  array{name?: string, display?: string, emirate?: string, sector?: string, industry?: string}|null  $firm
     */
    private function enrichManagedCompanyProfile(Company $managed, ?array $firm = null): void
    {
        $slug = \Illuminate\Support\Str::slug($firm['display'] ?? $managed->name ?? 'demo');
        $managed->update([
            'phone' => '+971 4 123 4567',
            'address' => 'Office 1402, Bay Gate Tower, Business Bay',
            'city' => $firm['emirate'] ?? 'Dubai',
            'postal_code' => '00000',
            'website' => 'https://' . $slug . '.example.ae',
            'business_subcategory' => $firm['industry'] ?? 'Import & Distribution',
            'employee_count' => 180,
            'annual_revenue' => 45000000,
            'license_no' => 'DED-1234567',
        ]);
    }

    /**
     * @return array<int, Location>
     */
    private function ensureLocations(Company $managed, int $reportingYear): array
    {
        $definitions = [
            [
                'name' => 'Dubai Head Office',
                'address' => 'Business Bay, Dubai',
                'city' => 'Dubai',
                'location_type' => 'Office',
                'is_head_office' => true,
                'staff_count' => 25,
            ],
            [
                'name' => 'Abu Dhabi Warehouse',
                'address' => 'Mussafah Industrial Area, Abu Dhabi',
                'city' => 'Abu Dhabi',
                'location_type' => 'Warehouse',
                'is_head_office' => false,
                'staff_count' => 40,
            ],
            [
                'name' => 'Sharjah Distribution Center',
                'address' => 'Sajaa Industrial Area, Sharjah',
                'city' => 'Sharjah',
                'location_type' => 'Warehouse',
                'is_head_office' => false,
                'staff_count' => 15,
            ],
        ];

        $locations = [];

        foreach ($definitions as $definition) {
            $location = Location::firstOrCreate(
                ['company_id' => $managed->id, 'name' => $definition['name']],
                [
                    'address' => $definition['address'],
                    'city' => $definition['city'],
                    'country' => 'United Arab Emirates',
                    'location_type' => $definition['location_type'],
                    'is_head_office' => $definition['is_head_office'],
                    'is_active' => true,
                    'staff_count' => $definition['staff_count'],
                    'staff_work_from_home' => false,
                    'work_from_home_percentage' => 0,
                    'receives_utility_bills' => true,
                    'pays_electricity_proportion' => false,
                    'shared_building_services' => false,
                    'fiscal_year_start' => 'January',
                    'reporting_period' => $reportingYear,
                    'measurement_frequency' => 'Annually',
                ]
            );

            foreach (['Scope 1', 'Scope 2', 'Scope 3'] as $scope) {
                $sourceIds = EmissionSourceMaster::query()->where('scope', $scope)->limit(5)->pluck('id')->all();

                if (empty($sourceIds)) {
                    continue;
                }

                LocationEmissionBoundary::updateOrCreate(
                    ['location_id' => $location->id, 'scope' => $scope],
                    ['selected_sources' => $sourceIds]
                );
            }

            $locations[] = $location;
        }

        return $locations;
    }

    private function seedEmissionEntries(Measurement $measurement, int $userId, int $year): int
    {
        if (!Schema::hasTable('measurement_data')) {
            return 0;
        }

        if (MeasurementData::where('measurement_id', $measurement->id)->exists()) {
            return MeasurementData::where('measurement_id', $measurement->id)->count();
        }

        $created = 0;

        $scope12Samples = [
            ['slug' => 'natural-gas', 'quantity' => 1200, 'unit' => 'litres', 'fallback_co2e' => 3200, 'scope' => 'Scope 1'],
            ['slug' => 'diesel', 'quantity' => 850, 'unit' => 'litres', 'fallback_co2e' => 2280, 'scope' => 'Scope 1'],
            ['slug' => 'lpg', 'quantity' => 300, 'unit' => 'kg', 'fallback_co2e' => 900, 'scope' => 'Scope 1'],
            ['slug' => 'electricity', 'quantity' => 45000, 'unit' => 'kWh', 'fallback_co2e' => 19800, 'scope' => 'Scope 2'],
            ['slug' => 'district-cooling', 'quantity' => 18000, 'unit' => 'kWh', 'fallback_co2e' => 5400, 'scope' => 'Scope 2'],
        ];

        foreach ($scope12Samples as $sample) {
            $source = EmissionSourceMaster::query()
                ->where('is_quick_input', true)
                ->where(function ($q) use ($sample) {
                    $q->where('quick_input_slug', $sample['slug'])
                        ->orWhere('name', 'like', '%' . str_replace('-', ' ', $sample['slug']) . '%');
                })
                ->first()
                ?? EmissionSourceMaster::query()->where('is_quick_input', true)->where('scope', $sample['scope'])->first();

            $factor = $source
                ? EmissionFactor::query()->where('emission_source_id', $source->id)->where('is_active', true)->first()
                : null;

            $co2e = $factor
                ? round((float) $sample['quantity'] * (float) $factor->factor_value, 2)
                : (float) $sample['fallback_co2e'];

            if ($co2e <= 0) {
                $co2e = (float) $sample['fallback_co2e'];
            }

            MeasurementData::create([
                'measurement_id' => $measurement->id,
                'emission_source_id' => $source?->id,
                'field_name' => 'quick_input',
                'field_value' => (string) $sample['quantity'],
                'quantity' => $sample['quantity'],
                'unit' => $sample['unit'],
                'calculated_co2e' => $co2e,
                'scope' => $sample['scope'],
                'emission_factor_id' => $factor?->id,
                'entry_date' => $year . '-06-01',
                'notes' => 'Seeded demo entry for full testing',
                'created_by' => $userId,
            ]);

            $created++;
        }

        $scope3Sources = EmissionSourceMaster::query()->where('scope', 'Scope 3')->limit(6)->get();

        foreach ($scope3Sources as $index => $source) {
            $factor = EmissionFactor::query()->where('emission_source_id', $source->id)->where('is_active', true)->first();
            $quantity = 100 + ($index * 25);
            $co2e = $factor
                ? round($quantity * (float) $factor->factor_value, 2)
                : (1500 + ($index * 250));

            MeasurementData::create([
                'measurement_id' => $measurement->id,
                'emission_source_id' => $source->id,
                'field_name' => 'quick_input',
                'field_value' => (string) $quantity,
                'quantity' => $quantity,
                'unit' => $factor?->unit ?? 'units',
                'calculated_co2e' => $co2e,
                'scope' => 'Scope 3',
                'emission_factor_id' => $factor?->id,
                'entry_date' => $year . '-06-01',
                'notes' => 'Seeded demo Scope 3 entry (' . $source->category . ')',
                'created_by' => $userId,
            ]);

            $created++;
        }

        if ($scope3Sources->isEmpty()) {
            $this->command?->warn(
                'No Scope 3 emission sources found — run `php artisan menetzero:install-scope3` to seed the '
                . '15 GHG Protocol Scope 3 categories, then re-run this seeder to populate Scope 3 demo entries.'
            );
        }

        return $created;
    }

    /**
     * @param  array<int, Location>  $locations
     */
    private function seedLegacyFacilityData(Company $managed, array $locations, int $reportingYear): void
    {
        if (!Schema::hasTable('facilities')) {
            return;
        }

        $date = $reportingYear . '-06-15';

        foreach ($locations as $index => $location) {
            $facility = Facility::firstOrCreate(
                ['company_id' => $managed->id, 'name' => $location->name],
                [
                    'location' => $location->city ?? 'Dubai',
                    'type' => $location->is_head_office ? 'Office' : 'Warehouse',
                ]
            );

            if (EnergyData::where('facility_id', $facility->id)->exists()) {
                continue;
            }

            EnergyData::create([
                'facility_id' => $facility->id,
                'source_type' => 'Electricity',
                'consumption_value' => 15000 + ($index * 5000),
                'unit' => 'kWh',
                'date' => $date,
                'co2e' => 6600 + ($index * 2200),
            ]);

            TransportData::create([
                'facility_id' => $facility->id,
                'vehicle_type' => 'Delivery Van',
                'fuel_type' => 'Diesel',
                'distance_travelled' => 2500,
                'fuel_consumed' => 300,
                'unit' => 'litres',
                'date' => $date,
                'co2e' => 804,
            ]);

            IndustrialData::create([
                'facility_id' => $facility->id,
                'process_type' => 'Packaging',
                'raw_material' => 'Cardboard',
                'quantity' => 1200,
                'unit' => 'kg',
                'date' => $date,
                'co2e' => 360,
            ]);

            WasteData::create([
                'facility_id' => $facility->id,
                'waste_type' => 'General waste',
                'quantity' => 800,
                'unit' => 'kg',
                'disposal_method' => 'Landfill',
                'date' => $date,
                'co2e' => 440,
            ]);

        }
    }

    private function seedReportingSettings(Company $managed, int $reportingYear): void
    {
        $fullPolicy = collect(CompanyReportingSetting::SCOPE3_CATEGORIES)
            ->map(fn ($label, $cat) => [
                'category' => (int) $cat,
                'label' => $label,
                'included' => true,
                'reason' => null,
            ])
            ->values()
            ->all();

        CompanyReportingSetting::updateOrCreate(
            ['company_id' => $managed->id, 'fiscal_year' => $reportingYear],
            [
                'organisational_boundary' => 'operational_control',
                'consolidation_approach' => 'operational_control',
                'base_year' => $reportingYear,
                'base_year_rationale' => 'First full year of comprehensive Scope 1, 2, and 3 data collection across all sites.',
                'recalculation_policy' => 'Base year emissions are recalculated if a structural change (acquisition, divestment) shifts total emissions by more than 5%.',
                'gwp_version' => 'AR6',
                'scope3_category_policy' => $fullPolicy,
                'sasb_sector' => 'TR-RO',
            ]
        );
    }

    private function seedDisclosures(Company $managed, int $reportingYear): void
    {
        $service = app(DisclosureService::class);

        $service->saveSection($managed->id, $reportingYear, 'governance', [
            'board_oversight_body' => 'Board Sustainability Committee',
            'board_climate_integration' => 'Climate risk is a standing agenda item at quarterly board meetings and is factored into capital allocation and site-selection decisions.',
            'management_accountable_role' => 'Chief Operating Officer',
            'board_climate_expertise' => 'One independent director holds a certification in climate risk management; the committee is advised by an external sustainability consultant.',
            'target_oversight' => 'The committee reviews progress against the 2030 reduction target quarterly and approves any revisions to interim milestones.',
            'remuneration_linked' => 'Senior management annual bonus includes a 10% weighting tied to emissions-intensity reduction targets.',
            'oversight_frequency' => 'Quarterly',
        ], 'ifrs_s2');

        $service->saveSection($managed->id, $reportingYear, 'strategy', [
            'risks_short_term' => 'Rising diesel and electricity tariffs increasing operating costs at the Abu Dhabi and Sharjah warehouses.',
            'risks_medium_term' => 'Potential UAE carbon pricing mechanisms affecting logistics and freight costs.',
            'risks_long_term' => 'Physical risk from extreme heat affecting warehouse cooling loads and staff working conditions.',
            'opportunities_summary' => 'Rooftop solar potential at the Abu Dhabi warehouse; fleet electrification for last-mile delivery vehicles.',
            'business_model_impact' => 'Increased demand from clients for low-carbon logistics options is opening new service-line opportunities.',
            'financial_impact' => 'Estimated AED 450,000 annual exposure to energy price volatility across the three sites.',
            'transition_plan_summary' => 'Phased rollout of LED retrofits, solar PV at the Abu Dhabi site, and EV pilot for 20% of the delivery fleet by 2027.',
            'transition_resources' => 'AED 1.2M capital budget allocated over three years; partnership with a local solar EPC contractor.',
            'scenario_analysis_done' => 'Planned',
            'scenarios_used' => '1.5°C and 2°C IEA scenarios',
            'resilience_assessment' => 'Initial screening indicates moderate resilience; a full scenario-based assessment is scheduled for next fiscal year.',
        ], 'ifrs_s2');

        $service->saveSection($managed->id, $reportingYear, 'risk_management', [
            'identify_process' => 'Annual risk workshop with site managers and the sustainability consultant to identify climate-related risks.',
            'assess_process' => 'Risks scored on likelihood and financial impact using the enterprise risk register methodology.',
            'prioritise_process' => 'Top risks ranked by combined likelihood/impact score and reviewed by the Sustainability Committee.',
            'monitor_process' => 'Quarterly KPI tracking against the reduction target and energy cost benchmarks per site.',
            'erm_integration' => 'Climate risks are logged in the same enterprise risk register used for operational and financial risks.',
        ], 'ifrs_s2');

        $service->saveSection($managed->id, $reportingYear, 'governance', [
            'board_oversight_body' => 'Board Sustainability Committee',
            'sustainability_integration' => 'Sustainability performance is reviewed alongside financial performance at each quarterly board meeting.',
            'management_accountable_role' => 'Chief Operating Officer',
            'material_topics_oversight' => 'The committee reviews the materiality assessment annually and approves any changes to the material topics list.',
            'remuneration_linked' => 'Included in the 10% ESG-linked component of senior management bonuses.',
            'oversight_frequency' => 'Quarterly',
            'climate_cross_reference' => 'See IFRS S2 governance disclosure for climate-specific oversight detail.',
        ], 'ifrs_s1');

        $service->saveSection($managed->id, $reportingYear, 'strategy', [
            'risks_short_term' => 'Energy cost volatility and driver workplace-heat-safety compliance costs.',
            'risks_medium_term' => 'Warehouse worker health & safety standards tightening under UAE labour regulations.',
            'risks_long_term' => 'Long-term water availability for facility operations in a water-scarce region.',
            'opportunities_summary' => 'Employer-of-choice positioning through strong workforce welfare and safety programmes.',
            'business_model_impact' => 'Client RFPs increasingly require supplier ESG disclosures, making sustainability a commercial differentiator.',
            'financial_impact' => 'Estimated AED 150,000 annual cost of enhanced heat-safety measures across warehouse sites.',
            'resources_allocated' => 'Dedicated HSE officer and annual training budget of AED 80,000.',
            'climate_cross_reference' => 'Physical climate risk (extreme heat) assessed jointly with the IFRS S2 strategy disclosure.',
        ], 'ifrs_s1');

        $service->saveSection($managed->id, $reportingYear, 'risk_management', [
            'identify_process' => 'Annual materiality workshop covering environmental, social, and governance topics with site managers.',
            'assess_process' => 'Topics scored on stakeholder concern and business impact using a 5-point scale.',
            'prioritise_process' => 'Material topics ranked and presented to the Sustainability Committee for sign-off.',
            'monitor_process' => 'KPI dashboard reviewed quarterly; material topics reassessed annually.',
            'erm_integration' => 'Sustainability risks are logged in the enterprise risk register alongside operational risks.',
            'material_topics_process' => 'Materiality determined via internal workshop plus informal client and employee feedback, reviewed annually.',
        ], 'ifrs_s1');

        $service->saveSection($managed->id, $reportingYear, 'material_topics_process', [
            'process_description' => 'Materiality assessed annually via a cross-functional workshop weighing stakeholder concern against business impact.',
            'stakeholders_consulted' => 'Employees, key clients, warehouse landlords, and the sustainability advisory consultant.',
            'review_frequency' => 'Annually',
        ], 'gri');

        $service->saveSection($managed->id, $reportingYear, 'general', [
            'org_name' => 'Al Noor Trading LLC',
            'entities_included' => 'Dubai Head Office, Abu Dhabi Warehouse, Sharjah Distribution Center — 100% operational control.',
            'activities_value_chain' => 'Wholesale import and distribution of building materials and industrial supplies across the UAE.',
            'governance_structure' => 'Board of Directors with a dedicated Sustainability Committee reporting quarterly.',
            'governance_sustainability_role' => 'Chief Operating Officer holds day-to-day sustainability accountability, reporting to the Board Sustainability Committee.',
            'ethics_compliance' => 'Code of conduct covering anti-bribery, fair dealing, and whistleblower protection, reviewed annually.',
            'stakeholder_engagement' => 'Regular engagement with employees (town halls), clients (account reviews), and landlords (facility management meetings).',
        ], 'gri');

        $service->saveSection($managed->id, $reportingYear, 'energy', [
            'total_energy_gj' => 612,
            'renewable_energy_gj' => 0,
            'renewable_percent' => 0,
            'energy_intensity_value' => 3.4,
            'energy_intensity_denominator' => 'per AED million revenue',
            'methodology_notes' => 'Energy consumption converted from kWh/litres to GJ using standard IEA conversion factors.',
        ], 'gri');

        $service->saveSection($managed->id, $reportingYear, 'water', [
            'withdrawal_total_m3' => 4200,
            'withdrawal_surface_m3' => 0,
            'withdrawal_groundwater_m3' => 0,
            'withdrawal_municipal_m3' => 4200,
            'discharge_total_m3' => 3100,
            'consumption_total_m3' => 1100,
            'water_stressed_areas_notes' => 'All three sites are located in a water-stressed region per WRI Aqueduct; municipal supply only.',
        ], 'gri');

        $service->saveSection($managed->id, $reportingYear, 'waste', [
            'waste_hazardous_tonnes' => 1.2,
            'waste_non_hazardous_tonnes' => 28.5,
            'waste_total_tonnes' => 29.7,
            'waste_recycled_tonnes' => 9.8,
            'waste_reuse_tonnes' => 1.5,
            'waste_landfill_tonnes' => 17.2,
            'waste_incineration_tonnes' => 1.2,
        ], 'gri');

        $service->saveSection($managed->id, $reportingYear, 'social_hr', [
            'employees_total' => 180,
            'employees_new_hires' => 22,
            'employees_turnover_percent' => 11.5,
            'training_hours_avg' => 14,
            'parental_leave_return_rate' => 95,
            'benefits_summary' => 'Health insurance, annual leave above statutory minimum, and an employee wellness programme.',
        ], 'gri');

        $service->saveSection($managed->id, $reportingYear, 'diversity', [
            'women_management_percent' => 28,
            'women_workforce_percent' => 22,
            'board_diversity_percent' => 20,
            'board_diversity_notes' => 'One of five board seats held by a woman director; actively recruiting for greater representation.',
            'age_diversity_notes' => 'Workforce spans 21–58 years; median age 34.',
        ], 'gri');

        $service->saveSection($managed->id, $reportingYear, 'health_safety', [
            'hours_worked' => 374400,
            'recordable_injuries' => 3,
            'ltifr' => 8.0,
            'fatalities_employees' => 0,
            'fatalities_contractors' => 0,
            'fatalities_total' => 0,
            'ohs_management_system' => 'Site-level HSE management system aligned with UAE OSHAD framework; annual third-party audit.',
        ], 'gri');

        $service->saveSection($managed->id, $reportingYear, 'supply_chain', [
            'supplier_screening_policy' => 'All suppliers above AED 100,000 annual spend screened against environmental and labour-practice criteria.',
            'suppliers_screened_environmental_percent' => 65,
            'suppliers_screened_social_percent' => 60,
            'human_rights_due_diligence' => 'Supplier code of conduct requires compliance with UAE labour law and prohibits forced/child labour.',
            'scope3_cat1_spend_aed' => 28000000,
            'supplier_audit_summary' => 'Six key suppliers audited this year; no critical non-conformances identified.',
        ], 'gri');

        $service->saveSection($managed->id, $reportingYear, 'governance_metrics', [
            'ethics_incidents' => 0,
            'ethics_training_percent' => 88,
            'data_breaches' => 0,
            'collective_bargaining_percent' => 0,
            'compliance_notes' => 'No regulatory fines or non-compliance incidents recorded during the reporting year.',
        ], 'gri');

        $service->saveSection($managed->id, $reportingYear, 'about_report', [
            'report_purpose' => 'To transparently disclose Al Noor Trading LLC\'s environmental, social, and governance performance for stakeholders.',
            'reporting_boundary' => 'Operational control across all three UAE sites (Dubai, Abu Dhabi, Sharjah).',
            'frameworks_used' => 'GHG Protocol, IFRS S1/S2, GRI Standards, UAE ESG reporting guidance.',
            'assurance_status' => 'Planned',
            'assurance_scope' => 'Scope 1 and 2 GHG inventory, targeted for third-party limited assurance next reporting cycle.',
            'report_approval' => 'Approved by the Board Sustainability Committee.',
            'contact_feedback' => 'sustainability@alnoortrading.example.ae',
        ], 'esg_report');

        $service->saveSection($managed->id, $reportingYear, 'leadership_message', [
            'author_name' => 'Ahmed Al Noor, Managing Director',
            'statement' => 'Sustainability is central to how we operate — from reducing our carbon footprint across our UAE facilities to investing in our people. This report marks our first comprehensive disclosure across GHG, social, and governance performance, and sets the baseline for the targets we are committing to over the next decade.',
        ], 'esg_report');

        $service->saveSection($managed->id, $reportingYear, 'about_company', [
            'company_overview' => 'Al Noor Trading LLC is a UAE-based wholesale trading and distribution company serving the construction and industrial supplies sector.',
            'activities_value_chain' => 'Import, warehousing, and last-mile distribution of building materials and industrial supplies to contractors and retailers across the UAE.',
            'operating_locations' => 'Dubai (head office), Abu Dhabi (warehouse), Sharjah (distribution center).',
        ], 'esg_report');

        $service->saveSection($managed->id, $reportingYear, 'esg_strategy', [
            'strategy_summary' => 'A three-pillar strategy focused on decarbonising operations, strengthening workforce welfare, and embedding responsible sourcing across the supply chain.',
            'priority_themes' => 'Energy transition, water stewardship, workforce safety, supplier responsibility.',
        ], 'esg_report');

        $service->saveSection($managed->id, $reportingYear, 'future_outlook', [
            'outlook' => 'Over the next three years, Al Noor Trading plans to install rooftop solar at the Abu Dhabi warehouse, pilot electric delivery vehicles, and pursue third-party assurance on its GHG inventory.',
        ], 'esg_report');

        $service->saveSection($managed->id, $reportingYear, 'awards', [
            'awards_list' => 'Shortlisted, Dubai Chamber Sustainability Recognition Programme (this reporting year).',
        ], 'esg_report');

        $service->saveSection($managed->id, $reportingYear, 'community_impact', [
            'total_investment_aed' => 120000,
            'beneficiaries_count' => 400,
            'investment_methodology' => 'Direct cash contributions and in-kind donations of surplus materials to local community initiatives.',
            'assurance_notes' => 'Community investment figures are self-reported and not independently assured.',
        ], 'esg_report');
    }

    private function seedClimateRisksAndOpportunities(Company $managed, int $reportingYear): void
    {
        $risks = [
            [
                'name' => 'Energy price volatility',
                'risk_type' => 'transition',
                'time_horizon' => 'short',
                'description' => 'Rising electricity and diesel tariffs increasing warehouse operating costs.',
                'financial_impact' => 'Estimated AED 450,000 annual exposure across the three sites.',
                'likelihood' => 'high',
                'mitigation' => 'Solar PV feasibility study underway for the Abu Dhabi warehouse; energy efficiency retrofits planned.',
                'owner' => 'Chief Operating Officer',
                'status' => 'monitoring',
            ],
            [
                'name' => 'Extreme heat affecting warehouse operations',
                'risk_type' => 'physical',
                'time_horizon' => 'medium',
                'description' => 'Rising summer temperatures increasing cooling loads and affecting outdoor loading-bay staff safety.',
                'financial_impact' => 'Estimated AED 150,000 annual cost of enhanced cooling and heat-safety measures.',
                'likelihood' => 'high',
                'mitigation' => 'Enhanced HSE heat-safety protocol, shaded loading bays, and adjusted shift scheduling in peak summer months.',
                'owner' => 'HSE Manager',
                'status' => 'open',
            ],
            [
                'name' => 'Potential UAE carbon pricing',
                'risk_type' => 'transition',
                'time_horizon' => 'long',
                'description' => 'Possible future carbon pricing mechanisms affecting freight and logistics costs.',
                'financial_impact' => 'Not yet quantified — monitoring regulatory developments.',
                'likelihood' => 'medium',
                'mitigation' => 'Tracking regulatory developments via industry association membership; scenario planning underway.',
                'owner' => 'Chief Operating Officer',
                'status' => 'monitoring',
            ],
        ];

        foreach ($risks as $risk) {
            ClimateRisk::updateOrCreate(
                ['company_id' => $managed->id, 'fiscal_year' => $reportingYear, 'name' => $risk['name']],
                $risk
            );
        }

        $opportunities = [
            [
                'name' => 'Rooftop solar at Abu Dhabi warehouse',
                'category' => 'Energy transition',
                'description' => 'Rooftop solar PV installation could offset a significant share of warehouse electricity demand.',
                'potential_impact' => 'Estimated 20–25% reduction in Abu Dhabi site Scope 2 emissions once operational.',
                'actions' => 'Feasibility study commissioned; targeting installation within 18 months.',
            ],
            [
                'name' => 'Fleet electrification for last-mile delivery',
                'category' => 'Fleet transition',
                'description' => 'Piloting electric delivery vans for last-mile distribution routes.',
                'potential_impact' => 'Potential to eliminate Scope 1 emissions from 20% of the delivery fleet by 2027.',
                'actions' => 'Evaluating EV van models and charging infrastructure requirements at the Dubai depot.',
            ],
            [
                'name' => 'Low-carbon logistics as a client differentiator',
                'category' => 'Commercial',
                'description' => 'Growing client demand for suppliers with credible sustainability disclosures.',
                'potential_impact' => 'Positioned to win RFPs that require ESG disclosure as a qualification criterion.',
                'actions' => 'Publishing this ESG report and pursuing third-party GHG assurance to strengthen client proposals.',
            ],
        ];

        foreach ($opportunities as $opportunity) {
            ClimateOpportunity::updateOrCreate(
                ['company_id' => $managed->id, 'fiscal_year' => $reportingYear, 'name' => $opportunity['name']],
                $opportunity
            );
        }
    }

    private function seedReductionTargets(Company $managed, int $reportingYear): ?ReductionTarget
    {
        $target = ReductionTarget::updateOrCreate(
            ['company_id' => $managed->id, 'name' => 'UAE Net Zero 2050 aligned interim target'],
            [
                'target_type' => 'absolute',
                'scope_coverage' => 'scope12',
                'base_year' => $reportingYear,
                'target_year' => $reportingYear + 6,
                'baseline_tco2e' => 32.6,
                'target_tco2e' => 22.8,
                'reduction_percent' => 30,
                'sbti_aligned' => false,
                'status' => 'active',
            ]
        );

        $actions = [
            [
                'title' => 'LED retrofit — all three sites',
                'description' => 'Replace legacy lighting with LED fixtures across office, warehouse, and distribution center.',
                'action_type' => 'efficiency',
                'planned_year' => $reportingYear + 1,
                'capex_aed' => 85000,
                'opex_aed' => 5000,
                'expected_reduction_tco2e' => 2.1,
                'status' => 'planned',
            ],
            [
                'title' => 'Rooftop solar PV — Abu Dhabi warehouse',
                'description' => 'Install rooftop solar to offset grid electricity demand at the largest site.',
                'action_type' => 'renewable_energy',
                'planned_year' => $reportingYear + 2,
                'capex_aed' => 650000,
                'opex_aed' => 15000,
                'expected_reduction_tco2e' => 6.4,
                'status' => 'planned',
            ],
            [
                'title' => 'EV pilot — 20% of delivery fleet',
                'description' => 'Replace a portion of diesel delivery vans with electric equivalents.',
                'action_type' => 'fleet_transition',
                'planned_year' => $reportingYear + 3,
                'capex_aed' => 420000,
                'opex_aed' => 20000,
                'expected_reduction_tco2e' => 1.8,
                'status' => 'planned',
            ],
        ];

        foreach ($actions as $action) {
            TransitionAction::updateOrCreate(
                ['reduction_target_id' => $target->id, 'company_id' => $managed->id, 'title' => $action['title']],
                $action
            );
        }

        return $target;
    }

    private function seedMaterialTopics(Company $managed, int $reportingYear): void
    {
        $topics = [
            'water' => ['is_material' => true, 'impact_materiality' => 'medium', 'financial_materiality' => 'low', 'rationale' => 'Operations located in a water-stressed region; municipal supply dependency.'],
            'biodiversity' => ['is_material' => false, 'impact_materiality' => 'low', 'financial_materiality' => 'low', 'rationale' => 'Sites are in existing industrial zones with no direct biodiversity impact.'],
            'supply_chain' => ['is_material' => true, 'impact_materiality' => 'high', 'financial_materiality' => 'medium', 'rationale' => 'Significant Scope 3 exposure through purchased goods; client scrutiny of supplier practices.'],
            'workforce' => ['is_material' => true, 'impact_materiality' => 'high', 'financial_materiality' => 'medium', 'rationale' => 'Labour-intensive warehouse operations; talent retention is a key business risk.'],
            'health_safety' => ['is_material' => true, 'impact_materiality' => 'high', 'financial_materiality' => 'medium', 'rationale' => 'Warehouse and logistics operations carry inherent physical safety risk.'],
            'anti_corruption' => ['is_material' => false, 'impact_materiality' => 'low', 'financial_materiality' => 'low', 'rationale' => 'Low exposure given limited public-sector dealings.'],
            'community' => ['is_material' => false, 'impact_materiality' => 'medium', 'financial_materiality' => 'low', 'rationale' => 'Modest community investment programme; not a core strategic focus.'],
            'waste' => ['is_material' => true, 'impact_materiality' => 'medium', 'financial_materiality' => 'low', 'rationale' => 'Packaging waste is significant across three distribution sites.'],
            'energy' => ['is_material' => true, 'impact_materiality' => 'high', 'financial_materiality' => 'high', 'rationale' => 'Direct link to both emissions performance and operating cost exposure.'],
            'climate' => ['is_material' => true, 'impact_materiality' => 'high', 'financial_materiality' => 'high', 'rationale' => 'Cross-cutting material topic covering both physical and transition risk.'],
        ];

        app(DisclosureService::class)->syncMaterialityMatrix($managed->id, $reportingYear, $topics);
    }

    private function seedEsgDepth(Company $managed, int $reportingYear): void
    {
        $engagements = [
            ['stakeholder_group' => 'Employees', 'engagement_method' => 'Quarterly town halls', 'frequency' => 'quarterly', 'topics_discussed' => 'Workplace safety, benefits, career development', 'outcomes' => 'Introduced enhanced heat-safety protocol for summer months.'],
            ['stakeholder_group' => 'Key clients', 'engagement_method' => 'Account review meetings', 'frequency' => 'quarterly', 'topics_discussed' => 'Service quality, sustainability credentials, pricing', 'outcomes' => 'Two clients requested formal ESG disclosure as part of vendor qualification.'],
            ['stakeholder_group' => 'Suppliers', 'engagement_method' => 'Annual supplier day', 'frequency' => 'annual', 'topics_discussed' => 'Code of conduct compliance, pricing, logistics coordination', 'outcomes' => 'Rolled out supplier code of conduct to top 20 suppliers by spend.'],
            ['stakeholder_group' => 'Local community', 'engagement_method' => 'Community outreach programme', 'frequency' => 'ad_hoc', 'topics_discussed' => 'Local hiring, in-kind material donations', 'outcomes' => 'Donated surplus building materials to two community construction projects.'],
        ];

        foreach ($engagements as $engagement) {
            StakeholderEngagement::updateOrCreate(
                ['company_id' => $managed->id, 'fiscal_year' => $reportingYear, 'stakeholder_group' => $engagement['stakeholder_group']],
                $engagement + ['last_engaged_at' => $reportingYear . '-06-01']
            );
        }

        $targets = [
            ['name' => 'Reduce non-hazardous waste to landfill', 'target_category' => 'waste', 'metric_label' => 'Waste to landfill (tonnes)', 'baseline_value' => 17.2, 'target_value' => 10.0, 'unit' => 'tonnes'],
            ['name' => 'Increase women in management', 'target_category' => 'diversity', 'metric_label' => 'Women in management (%)', 'baseline_value' => 28, 'target_value' => 40, 'unit' => '%'],
            ['name' => 'Reduce municipal water withdrawal', 'target_category' => 'water', 'metric_label' => 'Water withdrawal (m³)', 'baseline_value' => 4200, 'target_value' => 3400, 'unit' => 'm³'],
            ['name' => 'Increase renewable electricity share', 'target_category' => 'energy', 'metric_label' => 'Renewable electricity (%)', 'baseline_value' => 0, 'target_value' => 25, 'unit' => '%'],
        ];

        foreach ($targets as $target) {
            EsgSustainabilityTarget::updateOrCreate(
                ['company_id' => $managed->id, 'name' => $target['name']],
                $target + ['base_year' => $reportingYear, 'target_year' => $reportingYear + 5, 'status' => 'active', 'notes' => 'Seeded demo target.']
            );
        }

        $suppliers = [
            ['supplier_name' => 'Gulf Building Materials Co.', 'category' => 'goods', 'spend_aed' => 8500000, 'country' => 'United Arab Emirates', 'scope3_category' => 1, 'screening_status' => 'passed', 'human_rights_assessed' => true, 'environmental_assessed' => true],
            ['supplier_name' => 'Al Reem Freight Forwarding', 'category' => 'services', 'spend_aed' => 2100000, 'country' => 'United Arab Emirates', 'scope3_category' => 4, 'screening_status' => 'passed', 'human_rights_assessed' => true, 'environmental_assessed' => false],
            ['supplier_name' => 'Global Industrial Supplies Ltd.', 'category' => 'goods', 'spend_aed' => 6200000, 'country' => 'India', 'scope3_category' => 1, 'screening_status' => 'in_progress', 'human_rights_assessed' => false, 'environmental_assessed' => false],
            ['supplier_name' => 'Fleet Leasing Partners LLC', 'category' => 'capital', 'spend_aed' => 950000, 'country' => 'United Arab Emirates', 'scope3_category' => 2, 'screening_status' => 'passed', 'human_rights_assessed' => true, 'environmental_assessed' => true],
        ];

        foreach ($suppliers as $supplier) {
            SupplyChainSupplier::updateOrCreate(
                ['company_id' => $managed->id, 'fiscal_year' => $reportingYear, 'supplier_name' => $supplier['supplier_name']],
                $supplier + ['notes' => 'Seeded demo supplier record.']
            );
        }
    }

    private function seedSasbSector(Company $managed, int $reportingYear): void
    {
        app(SasbIndexService::class)->saveSector($managed->id, $reportingYear, 'TR-RO');
    }

    private function seedEsgKpiSnapshots(Company $managed, int $reportingYear): void
    {
        $manualEnvironment = [
            'environmental_incidents' => 0,
        ];
        $manualSocial = [
            'community_investment_aed' => 120000,
        ];
        $manualGovernance = [
            'supplier_audits' => 6,
        ];

        $scorecard = app(EsgScorecardService::class);
        $scorecard->saveManual($managed->id, $reportingYear, 'environment', $manualEnvironment);
        $scorecard->saveManual($managed->id, $reportingYear, 'social', $manualSocial);
        $scorecard->saveManual($managed->id, $reportingYear, 'governance', $manualGovernance);

        $enterpriseManual = [
            'environment' => [
                'spills_count' => 0,
                'environmental_fines_aed' => 0,
                'iso14001_certified_sites' => 0,
            ],
            'social' => [
                'community_investment_esg' => 120000,
                'community_beneficiaries' => 400,
                'stakeholder_engagements' => 4,
                'employees_uae' => 145,
                'employees_gcc' => 25,
                'employees_other_regions' => 10,
                'contractors_total' => 30,
                'volunteer_hours' => 120,
                'absenteeism_rate' => 2.1,
                'employee_engagement_score' => 74,
            ],
            'governance' => [
                'whistleblower_reports' => 0,
                'political_contributions_aed' => 0,
                'human_rights_incidents' => 0,
                'sustainability_linked_finance_aed' => 0,
                'esg_targets_active' => 4,
                'supplier_audits_env' => 4,
                'supplier_audits_social' => 4,
                'tax_transparency_disclosed' => 0,
            ],
        ];

        foreach ($enterpriseManual as $category => $metrics) {
            foreach ($metrics as $metricKey => $value) {
                EsgKpiSnapshot::updateOrCreate(
                    ['company_id' => $managed->id, 'fiscal_year' => $reportingYear, 'metric_key' => $metricKey],
                    ['category' => $category, 'value' => $value, 'unit' => null, 'source' => EsgKpiSnapshot::SOURCE_MANUAL]
                );
            }
        }

        // HRIS-sourced metrics — simulates data imported via the HRIS CSV import feature.
        $hrisMetrics = [
            'recordable_injuries' => 3,
            'hours_worked' => 374400,
            'fatalities_employees' => 0,
            'fatalities_contractors' => 0,
            'parental_leave_return_rate' => 95,
            'men_workforce_percent' => 78,
            'employees_under_30_percent' => 32,
            'employees_30_50_percent' => 54,
            'employees_over_50_percent' => 14,
            'new_hires_women_percent' => 27,
            'training_spend_aed' => 65000,
        ];

        foreach ($hrisMetrics as $metricKey => $value) {
            EsgKpiSnapshot::updateOrCreate(
                ['company_id' => $managed->id, 'fiscal_year' => $reportingYear, 'metric_key' => $metricKey],
                ['category' => 'social', 'value' => $value, 'unit' => null, 'source' => EsgKpiSnapshot::SOURCE_HRIS]
            );
        }

        $scorecard->syncEnterpriseAutoSnapshots($managed, $reportingYear);
    }

    private function seedHrisImportLog(Company $managed, int $reportingYear, int $userId): void
    {
        if (HrisKpiImportLog::where('company_id', $managed->id)->where('fiscal_year', $reportingYear)->exists()) {
            return;
        }

        HrisKpiImportLog::create([
            'company_id' => $managed->id,
            'fiscal_year' => $reportingYear,
            'imported_by' => $userId,
            'filename' => 'workforce_kpis_' . $reportingYear . '.csv',
            'source_system' => 'Workday',
            'rows_imported' => 11,
            'rows_skipped' => 0,
            'errors' => [],
        ]);
    }

    /**
     * @param  array<int, Location>  $locations
     */
    private function seedAuditTrail(array $locations, int $userId): void
    {
        foreach ($locations as $location) {
            $measurement = Measurement::where('location_id', $location->id)->first();

            if (!$measurement) {
                continue;
            }

            if (MeasurementAuditTrail::where('measurement_id', $measurement->id)->exists()) {
                continue;
            }

            MeasurementAuditTrail::create([
                'measurement_id' => $measurement->id,
                'action' => 'created',
                'old_values' => null,
                'new_values' => ['status' => 'draft'],
                'changed_by' => $userId,
                'changed_at' => now(),
                'reason' => 'Seeded demo measurement for full testing',
            ]);

            MeasurementAuditTrail::create([
                'measurement_id' => $measurement->id,
                'action' => 'data_added',
                'old_values' => null,
                'new_values' => ['entries_added' => MeasurementData::where('measurement_id', $measurement->id)->count()],
                'changed_by' => $userId,
                'changed_at' => now(),
                'reason' => 'Seeded demo emission entries across Scope 1, 2, and 3',
            ]);
        }
    }

    private function seedAdminAssignment(
        Consultant $consultant,
        Company $consultantOrg,
        Company $managed,
        ConsultantSubscription $subscription,
        int $reportingYear
    ): void {
        if (!Schema::hasTable('admin_package_assignments')) {
            return;
        }

        AdminPackageAssignment::firstOrCreate(
            [
                'consultant_id' => $consultant->id,
                'consultant_subscription_id' => $subscription->id,
            ],
            [
                'admin_id' => null,
                'company_id' => $managed->id,
                'subscription_plan_id' => $subscription->subscription_plan_id,
                'target_type' => 'consultant',
                'contract_year' => $reportingYear,
                'duration_months' => 12,
                'note' => 'Phase 5 multi-package demo seed — depth capacity row.',
                'status' => 'approved',
                'client_subscription_id' => null,
                'metadata' => ['seeded_by' => 'ConsultantFullDemoSeeder'],
            ]
        );
    }

    /**
     * Upsert consultant agency pack rows (including consultant_trial and consultant_50).
     * Production may be missing these if agency migrations were not run.
     */
    private function ensureConsultantAgencyPlans(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            throw new RuntimeException('subscription_plans table missing — run migrations first.');
        }

        foreach (ConsultantAgencyPlanMatrix::packDefinitions() as $code => $definition) {
            $priceAnnual = (float) $definition['price_annual'];
            $priceInr = PlanEntitlementDefaults::defaultPriceInr($priceAnnual);

            SubscriptionPlan::updateOrCreate(
                ['plan_code' => $code],
                [
                    'plan_name' => $definition['plan_name'],
                    'plan_category' => $definition['plan_category'],
                    'description' => $definition['description'],
                    'price_annual' => $priceAnnual,
                    'price_inr' => $priceInr,
                    'currency' => $definition['currency'],
                    'billing_cycle' => $definition['billing_cycle'],
                    'is_active' => $definition['is_active'],
                    'sort_order' => $definition['sort_order'],
                    'limits' => $definition['limits'],
                    'entitlements' => $definition['entitlements'],
                    'features' => $definition['features'],
                ]
            );
        }

        $free = SubscriptionPlan::where('plan_code', ConsultantAgencyPlanMatrix::FREE_CODE)->first();

        if (!$free?->isConsultantAgencyPack()) {
            throw new RuntimeException(
                'consultant_free plan is missing or has wrong plan_category. Expected plan_category=consultant_agency.'
            );
        }

        foreach (array_keys(self::DEPTH_CAPACITY) as $depthCode) {
            $plan = SubscriptionPlan::where('plan_code', $depthCode)->first();
            if (!$plan?->isConsultantAgencyPack()) {
                throw new RuntimeException(
                    "{$depthCode} plan is missing or has wrong plan_category. Run Phase 1 catalog migration."
                );
            }
        }

        $this->command?->info('Consultant agency plans verified (free + ' . count(self::DEPTH_CAPACITY) . ' depth packs).');
    }

    private function printSummary(
        Company $consultantOrg,
        int $reportingYear,
        int $clientCount,
        int $totalEntries,
        int $uniqueLocations,
    ): void {
        $this->command?->info('');
        $this->command?->info('✅ Multi-package consultant demo seeded (Phase 5)');
        $this->command?->table(
            ['Item', 'Value'],
            [
                ['Consultant login', self::EMAIL],
                ['Password', self::PASSWORD],
                ['Consultant portal', '/consultant/login'],
                ['Agency org', $consultantOrg->name . ' (id ' . $consultantOrg->id . ')'],
                ['Depth packs', 'Basic / Pro / ESG Starter / ESG Complete / Enterprise × 5 slots each'],
                ['Managed clients', (string) $clientCount . ' (5 firms per depth)'],
                ['Reporting year (PRY)', (string) $reportingYear],
                ['Unique location names', (string) $uniqueLocations],
                ['Emission entries (all sites)', (string) $totalEntries],
                ['Per-client data', 'Locations, Scope 1–3, disclosures, climate, ESG KPIs, HRIS log'],
            ]
        );
        $this->command?->info('Test: sign in → Managed clients → pick package depth → enter workspace.');
        $this->command?->info('Re-run is mostly idempotent (reuses capacity rows and firms by name).');
    }
}
