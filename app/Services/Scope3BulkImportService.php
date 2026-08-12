<?php

namespace App\Services;

use App\Models\EmissionSourceMaster;
use App\Models\Location;
use App\Models\MeasurementData;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bulk import for Scope 3 (GHG Protocol categories 1–15).
 *
 * Deliberately separate from Scope12BulkImportService. Scope 1 & 2 resolves an emission
 * factor from six condition columns (region, fuel_category, fuel_type, unit,
 * vehicle_category, vehicle_type) with vehicle-specific special cases. Scope 3 needs
 * only two — fuel_type and unit — because fuel_category / vehicle_* are empty on every
 * Scope 3 factor. Merging the two would push scope-conditional branching through a
 * class that is currently clean.
 *
 * Granularity is one row per (category, period), matching what a compliant report
 * publishes. Per-employee / per-flight detail lives in the workbook's calculator sheets
 * and is summed into a single row before import — measurement_data has nowhere to store
 * it, and assured reports (e.g. DP World 2024) disclose category aggregates only.
 *
 * See documentation/SCOPE3_BULK_IMPORT_PLAN.md.
 */
class Scope3BulkImportService
{
    public const HEADERS = [
        'location_name',
        'fiscal_year',
        'entry_date',
        'category',
        'activity_type',
        'quantity',
        'unit',
        'notes',
    ];

    /**
     * GHG Protocol category number => quick_input_slug.
     *
     * Users think in category numbers ("Cat 6"); the database keys on slug. Both forms
     * are accepted in the `category` column.
     *
     * @var array<int, string>
     */
    public const CATEGORY_SLUGS = [
        1 => 'purchased-goods',
        2 => 'capital-goods',
        3 => 'fuel-energy-related',
        4 => 'upstream-transport',
        5 => 'waste-operations',
        6 => 'business-travel',
        7 => 'employee-commuting',
        8 => 'upstream-leased',
        9 => 'downstream-transport',
        10 => 'processing-sold',
        11 => 'use-sold',
        12 => 'end-of-life',
        13 => 'downstream-leased',
        14 => 'franchises',
        15 => 'investments',
    ];

    public function __construct(
        protected EmissionCalculationService $calculationService,
        protected MeasurementService $measurementService,
        protected SubscriptionService $subscriptionService,
    ) {}

    public static function headerLabels(): array
    {
        return [
            'Location Name *',
            'Fiscal Year *',
            'Entry Date (YYYY-MM-DD)',
            'Category *',
            'Activity Type *',
            'Quantity *',
            'Unit *',
            'Notes',
        ];
    }

    /**
     * Every valid (category, activity_type, unit) combination.
     *
     * Mirrors the seeded emission_factors for Scope 3 — verified against the live
     * database with zero ambiguous triples. This is the contract the Reference sheet
     * publishes and the importer validates against; keep it in step with the factors.
     *
     * @return array<int, array{0: int, 1: string, 2: string, 3: string, 4: string}>
     *         [category number, slug, activity_type, unit, where to find the number]
     */
    public static function referenceCombinations(): array
    {
        return [
            [1, 'purchased-goods', 'Spend - General', 'AED', 'Annual spend on general goods & services (excl. VAT)'],
            [1, 'purchased-goods', 'Spend - Food & Catering', 'AED', 'Annual catering / pantry spend'],
            [1, 'purchased-goods', 'Spend - IT & Electronics', 'AED', 'Annual IT hardware & electronics spend'],
            [1, 'purchased-goods', 'Material - Food', 'tonnes', 'Weight of food purchased'],
            [1, 'purchased-goods', 'Material - Glass', 'tonnes', 'Weight of glass purchased'],
            [1, 'purchased-goods', 'Material - Metals', 'tonnes', 'Weight of metal purchased'],
            [1, 'purchased-goods', 'Material - Paper', 'tonnes', 'Weight of paper purchased'],
            [1, 'purchased-goods', 'Material - Plastics', 'tonnes', 'Weight of plastic purchased'],
            [1, 'purchased-goods', 'Water supply', 'cubic metres', 'Water bill — m³ supplied'],
            [1, 'purchased-goods', 'Water treatment', 'cubic metres', 'Water bill — m³ treated / discharged'],

            [2, 'capital-goods', 'Spend - Capital', 'AED', 'Capex on equipment, vehicles, buildings'],
            [2, 'capital-goods', 'Material - Metals', 'tonnes', 'Weight of metal in capital items'],

            [3, 'fuel-energy-related', 'Electricity T&D losses', 'kWh', 'Same kWh as your Scope 2 electricity'],
            [3, 'fuel-energy-related', 'Electricity WTT', 'kWh', 'Same kWh as your Scope 2 electricity'],
            [3, 'fuel-energy-related', 'Diesel WTT', 'litres', 'Same litres as your Scope 1 diesel'],
            [3, 'fuel-energy-related', 'Petrol WTT', 'litres', 'Same litres as your Scope 1 petrol'],
            [3, 'fuel-energy-related', 'Natural gas WTT', 'cubic metres', 'Same m³ as your Scope 1 natural gas'],

            [4, 'upstream-transport', 'Van', 'tonne.km', 'Freight in: tonnes carried × km travelled'],
            [4, 'upstream-transport', 'HGV', 'tonne.km', 'Freight in: tonnes carried × km travelled'],
            [4, 'upstream-transport', 'HGV Rigid', 'tonne.km', 'Freight in: tonnes carried × km travelled'],
            [4, 'upstream-transport', 'HGV Articulated', 'tonne.km', 'Freight in: tonnes carried × km travelled'],

            [5, 'waste-operations', 'Mixed - Landfill', 'tonnes', 'Waste contractor report — tonnes to landfill'],
            [5, 'waste-operations', 'Mixed - Combustion', 'tonnes', 'Waste contractor report — tonnes incinerated'],
            [5, 'waste-operations', 'Organic - Landfill', 'tonnes', 'Food / green waste to landfill'],
            [5, 'waste-operations', 'Organic - Composting', 'tonnes', 'Food / green waste composted'],
            [5, 'waste-operations', 'Construction - Landfill', 'tonnes', 'Construction & demolition waste'],

            [6, 'business-travel', 'Flight - Domestic', 'passenger.km', 'Use the Calc: Flights sheet'],
            [6, 'business-travel', 'Flight - Short-haul Economy', 'passenger.km', 'Use the Calc: Flights sheet'],
            [6, 'business-travel', 'Flight - Short-haul Business', 'passenger.km', 'Use the Calc: Flights sheet'],
            [6, 'business-travel', 'Flight - Long-haul Economy', 'passenger.km', 'Use the Calc: Flights sheet'],
            [6, 'business-travel', 'Flight - Long-haul Premium Economy', 'passenger.km', 'Use the Calc: Flights sheet'],
            [6, 'business-travel', 'Flight - Long-haul Business', 'passenger.km', 'Use the Calc: Flights sheet'],
            [6, 'business-travel', 'Flight - Long-haul First', 'passenger.km', 'Use the Calc: Flights sheet'],
            [6, 'business-travel', 'Flight - International Economy', 'passenger.km', 'Use the Calc: Flights sheet'],
            [6, 'business-travel', 'Rail - National', 'passenger.km', 'Passengers × km travelled'],
            [6, 'business-travel', 'Rail - International', 'passenger.km', 'Passengers × km travelled'],
            [6, 'business-travel', 'Light rail / Tram', 'passenger.km', 'Metro / tram — passengers × km'],
            [6, 'business-travel', 'Average car', 'km', 'Private / hire car on business — total km'],
            [6, 'business-travel', 'Taxi', 'km', 'Taxi & ride-hailing — total km'],

            [7, 'employee-commuting', 'Average car', 'km', 'Use the Calc: Commuting sheet'],
            [7, 'employee-commuting', 'Motorbike', 'km', 'Use the Calc: Commuting sheet'],
            [7, 'employee-commuting', 'Local bus', 'passenger.km', 'Use the Calc: Commuting sheet'],
            [7, 'employee-commuting', 'Coach', 'passenger.km', 'Use the Calc: Commuting sheet'],
            [7, 'employee-commuting', 'Rail - National', 'passenger.km', 'Use the Calc: Commuting sheet'],
            [7, 'employee-commuting', 'Light rail / Tram', 'passenger.km', 'Metro — use the Calc: Commuting sheet'],
            [7, 'employee-commuting', 'Homeworking', 'FTE working hour', 'Home-working hours across all staff'],

            [8, 'upstream-leased', 'Electricity', 'kWh', 'Leased-in space not already in Scope 2'],
            [8, 'upstream-leased', 'Floor area', 'm2', 'Leased-in floor area where kWh is unknown'],

            [9, 'downstream-transport', 'Van', 'tonne.km', 'Freight out: tonnes carried × km travelled'],
            [9, 'downstream-transport', 'HGV', 'tonne.km', 'Freight out: tonnes carried × km travelled'],
            [9, 'downstream-transport', 'HGV Rigid', 'tonne.km', 'Freight out: tonnes carried × km travelled'],
            [9, 'downstream-transport', 'HGV Articulated', 'tonne.km', 'Freight out: tonnes carried × km travelled'],

            [10, 'processing-sold', 'Energy', 'kWh', 'Energy used by others to process your products'],
            [10, 'processing-sold', 'Spend', 'AED', 'Spend proxy where energy data is unavailable'],

            [11, 'use-sold', 'Electricity', 'kWh', 'Lifetime electricity used by products sold'],

            [12, 'end-of-life', 'Mixed - Landfill', 'tonnes', 'Weight of sold products going to landfill'],
            [12, 'end-of-life', 'Mixed - Combustion', 'tonnes', 'Weight incinerated at end of life'],
            [12, 'end-of-life', 'Organic - Composting', 'tonnes', 'Weight composted at end of life'],

            [13, 'downstream-leased', 'Electricity', 'kWh', 'Space you lease out to others'],
            [13, 'downstream-leased', 'Floor area', 'm2', 'Leased-out floor area where kWh is unknown'],

            [14, 'franchises', 'Electricity', 'kWh', 'Franchisee electricity consumption'],
            [14, 'franchises', 'Revenue', 'AED', 'Franchisee revenue where kWh is unavailable'],

            [15, 'investments', 'Listed equity', 'AED', 'Share of investee emissions by value'],
            [15, 'investments', 'Business loans', 'AED', 'Outstanding loan value'],
            [15, 'investments', 'Project finance', 'AED', 'Project finance value'],
            [15, 'investments', 'Real estate', 'AED', 'Real-estate investment value'],
        ];
    }

    /**
     * Example rows for the Data Entry sheet. Realistic UAE SME figures the user
     * overwrites or deletes.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function sampleRows(): array
    {
        return [
            ['Dubai Head Office', 2025, '2025-12-31', 'Cat 1', 'Spend - General', 850000, 'AED', 'Annual procurement spend excl. VAT — delete if not applicable'],
            ['Dubai Head Office', 2025, '2025-12-31', 'Cat 3', 'Electricity T&D losses', 620000, 'kWh', 'Same kWh total as your Scope 2 electricity'],
            ['Dubai Head Office', 2025, '2025-12-31', 'Cat 5', 'Mixed - Landfill', 24.5, 'tonnes', 'From waste contractor annual report'],
            ['Dubai Head Office', 2025, '2025-12-31', 'Cat 6', 'Flight - Short-haul Economy', 145000, 'passenger.km', 'Total from Calc: Flights sheet'],
            ['Dubai Head Office', 2025, '2025-12-31', 'Cat 7', 'Average car', 480000, 'km', 'Total from Calc: Commuting sheet'],
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function instructionsRows(): array
    {
        return [
            ['MENetZero — Scope 3 Bulk Data Import Guide (UAE)'],
            [''],
            ['WHAT THIS IS FOR'],
            ['Scope 3 = value-chain emissions: what you buy, how goods move, staff travel, and waste.'],
            ['You report ONE TOTAL per category per year — not one row per employee or per flight.'],
            ['This matches how audited reports disclose Scope 3 (e.g. DP World 2024 reports 10 category totals).'],
            [''],
            ['HOW TO USE'],
            ['1. Open "Your Locations" — copy your exact location names.'],
            ['2. Open "Reference" — every valid Category + Activity Type + Unit combination.'],
            ['3. Fill the "Data Entry" sheet. Copy Activity Type and Unit EXACTLY from Reference.'],
            ['4. Delete the example rows that do not apply to you.'],
            ['5. Upload from MENetZero → Input Data → Bulk Import.'],
            [''],
            ['HAVE PER-EMPLOYEE OR PER-FLIGHT DETAIL?'],
            ['Use the two calculator sheets — they are NOT imported, they just do the maths for you:'],
            ['  • "Calc: Commuting" — one row per employee → gives you total km to paste into Data Entry'],
            ['  • "Calc: Flights"   — one row per trip     → gives you total passenger.km'],
            ['Keep your detail in those sheets so next year you only update the numbers.'],
            [''],
            ['REQUIRED COLUMNS'],
            ['• location_name  — exact name from "Your Locations"'],
            ['• fiscal_year    — reporting year e.g. 2025'],
            ['• category       — "Cat 6", "6", or "business-travel" all work'],
            ['• activity_type  — copy EXACTLY from the Reference sheet'],
            ['• quantity       — the number'],
            ['• unit           — copy EXACTLY from the Reference sheet'],
            [''],
            ['UNITS ARE STRICT — these are the only valid units'],
            ['AED · km · passenger.km · tonne.km · tonnes · kWh · litres · cubic metres · m2 · FTE working hour'],
            ['passenger.km = passengers × km      (travel by bus / rail / air)'],
            ['tonne.km     = tonnes × km          (freight)'],
            ['A wrong unit is the most common reason a row is rejected.'],
            [''],
            ['WHERE UAE BUSINESSES FIND THE NUMBERS'],
            ['• Cat 1 Purchased goods  — finance: annual supplier spend (AED, excl. VAT)'],
            ['• Cat 3 Fuel & energy    — reuse your Scope 2 kWh and Scope 1 fuel litres'],
            ['• Cat 5 Waste            — waste contractor annual summary (tonnes)'],
            ['• Cat 6 Business travel  — travel agent / Concur report, or the Calc: Flights sheet'],
            ['• Cat 7 Commuting        — staff survey, or the Calc: Commuting sheet'],
            [''],
            ['TIPS'],
            ['• One row = one category for one period. Split by month only if you have monthly data.'],
            ['• Start with the categories you have data for; you can add more later.'],
            ['• Spend-based rows (AED) are estimates — activity data (kWh, km, tonnes) is better quality.'],
        ];
    }

    /**
     * Import parsed spreadsheet rows.
     *
     * @param  array<int, array<int, mixed>>  $rawRows  First row = headers
     * @return array{imported: int, skipped: int, errors: array<int, string>}
     */
    public function importRows(array $rawRows, int $companyId, int $userId): array
    {
        if (empty($rawRows)) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['The uploaded file contains no data rows.']];
        }

        $headerRow = array_shift($rawRows);
        $columnMap = $this->mapHeaders($headerRow);

        if ($columnMap === null) {
            return [
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['Unrecognised header row. Please use the official template without renaming columns.'],
            ];
        }

        $locations = Location::where('company_id', $companyId)
            ->where('is_active', true)
            ->get()
            ->keyBy(fn ($loc) => strtolower(trim($loc->name)));

        if ($locations->isEmpty()) {
            return [
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['No active locations found. Add a location before importing Scope 3 data.'],
            ];
        }

        $sources = EmissionSourceMaster::where('is_quick_input', true)
            ->where('is_active', true)
            ->where('scope', 'Scope 3')
            ->get()
            ->keyBy('quick_input_slug');

        $imported = 0;
        $skipped = 0;
        $errors = [];

        // canAddScope3Record() counts committed rows, so it cannot see inserts made
        // earlier in this same transaction. Track them here so a single upload can't
        // exceed the per-category cap.
        $addedThisRun = [];

        DB::beginTransaction();
        try {
            foreach ($rawRows as $index => $row) {
                $line = $index + 2;

                try {
                    $parsed = $this->parseRow($row, $columnMap);
                } catch (\InvalidArgumentException $e) {
                    $errors[] = "Row {$line}: {$e->getMessage()}";
                    continue;
                }

                if ($parsed === null) {
                    $skipped++;
                    continue;
                }

                try {
                    $this->importSingleRow($parsed, $locations, $sources, $companyId, $userId, $addedThisRun);
                    $imported++;
                } catch (\Throwable $e) {
                    $errors[] = "Row {$line}: {$e->getMessage()}";
                    Log::warning('Scope 3 bulk import row failed', [
                        'line' => $line,
                        'error' => $e->getMessage(),
                        'row' => $parsed,
                    ]);
                }
            }

            if ($imported === 0) {
                DB::rollBack();

                return [
                    'imported' => 0,
                    'skipped' => $skipped,
                    'errors' => $errors ?: ['No valid data rows found. Check that required columns are filled.'],
                ];
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * @param  array<int, mixed>  $headerRow
     * @return array<string, int>|null
     */
    protected function mapHeaders(array $headerRow): ?array
    {
        $normalise = function ($value) {
            $v = strtolower(trim(preg_replace('/[^a-z0-9_]/i', '_', (string) $value)));
            $v = preg_replace('/_+/', '_', $v);

            return trim($v, '_');
        };

        $aliases = [
            'location' => 'location_name',
            'location_name' => 'location_name',
            'year' => 'fiscal_year',
            'fiscal_year' => 'fiscal_year',
            'date' => 'entry_date',
            'entry_date' => 'entry_date',
            'category' => 'category',
            'ghg_category' => 'category',
            'scope3_category' => 'category',
            'activity_type' => 'activity_type',
            'activity' => 'activity_type',
            'sub_type' => 'activity_type',
            'subtype' => 'activity_type',
            'type' => 'activity_type',
            'amount' => 'quantity',
            'quantity' => 'quantity',
            'unit' => 'unit',
            'unit_of_measure' => 'unit',
            'notes' => 'notes',
            'comments' => 'notes',
        ];

        $map = [];
        foreach ($headerRow as $i => $cell) {
            $key = $normalise($cell);
            if (isset($aliases[$key]) && !isset($map[$aliases[$key]])) {
                $map[$aliases[$key]] = $i;
            }
        }

        foreach (['location_name', 'fiscal_year', 'category', 'activity_type', 'quantity', 'unit'] as $required) {
            if (!isset($map[$required])) {
                return null;
            }
        }

        return $map;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $columnMap
     * @return array<string, mixed>|null  null = blank row, skip silently
     */
    protected function parseRow(array $row, array $columnMap): ?array
    {
        $get = function (string $field) use ($row, $columnMap) {
            if (!isset($columnMap[$field])) {
                return null;
            }
            $val = $row[$columnMap[$field]] ?? null;
            if ($val === null || $val === '') {
                return null;
            }

            return is_string($val) ? trim($val) : $val;
        };

        $locationName = $get('location_name');
        $category = $get('category');
        $quantity = $get('quantity');

        // Comment rows in the template start with '#'.
        if (is_string($locationName) && str_starts_with($locationName, '#')) {
            return null;
        }

        if (!$locationName && !$category && $quantity === null) {
            return null;
        }

        if (!$locationName || !$category || $quantity === null) {
            throw new \InvalidArgumentException('Missing required field (location_name, category, or quantity).');
        }

        if (!is_numeric($quantity) || (float) $quantity < 0) {
            throw new \InvalidArgumentException('Quantity must be a non-negative number.');
        }

        $activityType = $get('activity_type');
        if (!$activityType) {
            throw new \InvalidArgumentException('Activity Type is required — copy it exactly from the Reference sheet.');
        }

        $unit = $get('unit');
        if (!$unit) {
            throw new \InvalidArgumentException('Unit is required — copy it exactly from the Reference sheet.');
        }

        $slug = $this->normaliseCategory((string) $category);
        if (!$slug) {
            throw new \InvalidArgumentException(
                "Unknown category \"{$category}\". Use a Scope 3 category number (1–15) or its name from the Reference sheet."
            );
        }

        return [
            'location_name' => $locationName,
            'fiscal_year' => (int) $get('fiscal_year'),
            'entry_date' => $get('entry_date'),
            'category' => $slug,
            'activity_type' => $activityType,
            'quantity' => (float) $quantity,
            'unit' => $unit,
            'notes' => $get('notes'),
        ];
    }

    /**
     * Resolve "6", "Cat 6", "cat-6", "business-travel" or "Business Travel" to a slug.
     */
    protected function normaliseCategory(string $category): ?string
    {
        $key = strtolower(trim($category));

        // Bare number, or any "cat"-prefixed form.
        if (preg_match('/^(?:cat(?:egory)?[\s\-_.]*)?(\d{1,2})\b/', $key, $m)) {
            $num = (int) $m[1];
            if (isset(self::CATEGORY_SLUGS[$num])) {
                return self::CATEGORY_SLUGS[$num];
            }
        }

        // "&" and "and" are interchangeable in the category names users type
        // ("Purchased Goods & Services"), so fold both away before matching.
        $slugged = str_replace('&', ' and ', $key);
        $slugged = preg_replace('/[\s_]+/', '-', $slugged);
        $slugged = preg_replace('/-+/', '-', trim($slugged, '- '));

        if (in_array($slugged, self::CATEGORY_SLUGS, true)) {
            return $slugged;
        }

        // Tolerate common long-form names for the categories users type out.
        $aliases = [
            'purchased-goods-services' => 'purchased-goods',
            'purchased-goods-and-services' => 'purchased-goods',
            'fuel-energy' => 'fuel-energy-related',
            'fuel-and-energy-related-activities' => 'fuel-energy-related',
            'upstream-transportation' => 'upstream-transport',
            'upstream-transportation-distribution' => 'upstream-transport',
            'upstream-transportation-and-distribution' => 'upstream-transport',
            'upstream-transport-and-distribution' => 'upstream-transport',
            'downstream-transportation' => 'downstream-transport',
            'downstream-transportation-distribution' => 'downstream-transport',
            'downstream-transportation-and-distribution' => 'downstream-transport',
            'downstream-transport-and-distribution' => 'downstream-transport',
            'waste' => 'waste-operations',
            'waste-generated-in-operations' => 'waste-operations',
            'travel' => 'business-travel',
            'commuting' => 'employee-commuting',
            'upstream-leased-assets' => 'upstream-leased',
            'downstream-leased-assets' => 'downstream-leased',
            'processing-of-sold-products' => 'processing-sold',
            'use-of-sold-products' => 'use-sold',
            'end-of-life-treatment' => 'end-of-life',
            'end-of-life-treatment-of-sold-products' => 'end-of-life',
        ];

        return $aliases[$slugged] ?? null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  \Illuminate\Support\Collection  $locations
     * @param  \Illuminate\Support\Collection  $sources
     * @param  array<int, int>  $addedThisRun  emission_source_id => count inserted so far
     */
    protected function importSingleRow(
        array $row,
        $locations,
        $sources,
        int $companyId,
        int $userId,
        array &$addedThisRun
    ): void {
        if ($row['fiscal_year'] < 2000 || $row['fiscal_year'] > 2100) {
            throw new \InvalidArgumentException('Fiscal Year must be between 2000 and 2100.');
        }

        $write = app(PlanEntitlementService::class)->canWriteForReportingYear($companyId, $row['fiscal_year']);
        if (!$write['allowed']) {
            throw new \InvalidArgumentException($write['message'] ?? "Fiscal year {$row['fiscal_year']} is locked for editing.");
        }

        $location = $locations->get(strtolower(trim($row['location_name'])));
        if (!$location) {
            throw new \InvalidArgumentException(
                "Location \"{$row['location_name']}\" not found. Use exact names from the Your Locations sheet."
            );
        }

        $emissionSource = $sources->get($row['category']);
        if (!$emissionSource) {
            throw new \InvalidArgumentException(
                "Scope 3 category \"{$row['category']}\" is not enabled on this system."
            );
        }

        $this->assertWithinPlanLimit($companyId, (int) $emissionSource->id, $emissionSource->name, $addedThisRun);

        $emissionFactor = $this->calculationService->selectEmissionFactor($emissionSource->id, [
            'fuel_type' => $row['activity_type'],
            'unit' => $row['unit'],
        ]);

        if (!$emissionFactor) {
            throw new \InvalidArgumentException(
                'No emission factor for activity type "' . $row['activity_type'] . '" with unit "' . $row['unit']
                . '". Copy both exactly from the Reference sheet.'
            );
        }

        $calculation = $this->calculationService->calculateCO2e($row['quantity'], $emissionFactor, $row['unit']);
        $co2e = $calculation['co2e'] ?? $calculation['total_co2e'] ?? 0;

        $measurement = $this->measurementService->getOrCreateMeasurement($location->id, $row['fiscal_year'], $userId);

        MeasurementData::create([
            'measurement_id' => $measurement->id,
            'emission_source_id' => $emissionSource->id,
            'field_name' => 'quick_input',
            'field_value' => (string) $row['quantity'],
            'quantity' => $row['quantity'],
            'unit' => $row['unit'],
            'calculated_co2e' => $co2e,
            'co2_emissions' => isset($calculation['co2']) && is_numeric($calculation['co2']) ? $calculation['co2'] : null,
            'ch4_emissions' => isset($calculation['ch4']) && is_numeric($calculation['ch4']) ? $calculation['ch4'] : null,
            'n2o_emissions' => isset($calculation['n2o']) && is_numeric($calculation['n2o']) ? $calculation['n2o'] : null,
            'scope' => 'Scope 3',
            'entry_date' => $row['entry_date'] ? Carbon::parse($row['entry_date'])->toDateString() : null,
            'emission_factor_id' => $emissionFactor->id,
            'gwp_version_used' => $emissionFactor->gwp_version ?? 'AR6',
            'calculation_method' => $emissionFactor->calculation_method ?? null,
            'fuel_type' => $row['activity_type'],
            'additional_data' => ['import_source' => 'bulk_upload_scope3'],
            'notes' => $row['notes'],
            'created_by' => $userId,
        ]);

        $addedThisRun[$emissionSource->id] = ($addedThisRun[$emissionSource->id] ?? 0) + 1;

        $this->measurementService->updateMeasurementTotals($measurement->id);
    }

    /**
     * Enforce the per-category record cap, accounting for rows already added in this run.
     *
     * @param  array<int, int>  $addedThisRun
     */
    protected function assertWithinPlanLimit(
        int $companyId,
        int $emissionSourceId,
        string $categoryName,
        array $addedThisRun
    ): void {
        $check = $this->subscriptionService->canAddScope3Record($companyId, $emissionSourceId);

        if (!$check['allowed']) {
            throw new \InvalidArgumentException($check['message'] ?? 'Scope 3 record limit reached for this category.');
        }

        $limit = (int) ($check['limit'] ?? -1);
        if ($limit === -1) {
            return;
        }

        // canAddScope3Record only sees committed rows; add this run's inserts.
        $pending = $addedThisRun[$emissionSourceId] ?? 0;
        if (((int) ($check['used'] ?? 0) + $pending + 1) > $limit) {
            throw new \InvalidArgumentException(
                "Plan limit reached for \"{$categoryName}\" — {$limit} entries per Scope 3 category. "
                . 'Remaining rows for this category were not imported.'
            );
        }
    }

    /**
     * Pick the sheet to import from an uploaded workbook.
     *
     * The template ships with instruction, reference and calculator sheets alongside the
     * data sheet; those must never be treated as data.
     *
     * @param  array<string, array<int, array<int, mixed>>>  $sheets
     * @return array<int, array<int, mixed>>
     */
    public function extractDataSheet(array $sheets): array
    {
        $preferred = ['data entry', 'data', 'template', 'entries', 'sheet1'];
        foreach ($preferred as $name) {
            foreach ($sheets as $title => $rows) {
                if (strtolower(trim($title)) === $name && !empty($rows)) {
                    return $rows;
                }
            }
        }

        $ignored = ['instructions', 'reference', 'your locations', 'examples', 'data guide'];
        foreach ($sheets as $title => $rows) {
            $lower = strtolower(trim($title));
            if (in_array($lower, $ignored, true) || str_starts_with($lower, 'calc:')) {
                continue;
            }
            if (!empty($rows)) {
                return $rows;
            }
        }

        return reset($sheets) ?: [];
    }
}
