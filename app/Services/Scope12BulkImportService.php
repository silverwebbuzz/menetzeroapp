<?php

namespace App\Services;

use App\Models\EmissionSourceMaster;
use App\Models\Location;
use App\Models\MeasurementData;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Scope12BulkImportService
{
    public const HEADERS = [
        'location_name',
        'fiscal_year',
        'entry_date',
        'category',
        'sub_type',
        'quantity',
        'unit',
        'region',
        'fuel_category',
        'vehicle_type',
        'notes',
    ];

    /** @var array<string, string> slug => scope */
    public const CATEGORY_MAP = [
        'natural-gas' => 'Scope 1',
        'natural gas' => 'Scope 1',
        'fuel' => 'Scope 1',
        'vehicle' => 'Scope 1',
        'refrigerants' => 'Scope 1',
        'refrigerant' => 'Scope 1',
        'process' => 'Scope 1',
        'electricity' => 'Scope 2',
        'heat-steam-cooling' => 'Scope 2',
        'heat steam cooling' => 'Scope 2',
        'heat/steam/cooling' => 'Scope 2',
        'district cooling' => 'Scope 2',
    ];

    public function __construct(
        protected EmissionCalculationService $calculationService,
        protected MeasurementService $measurementService,
    ) {}

    public static function headerLabels(): array
    {
        return [
            'Location Name *',
            'Fiscal Year *',
            'Entry Date (YYYY-MM-DD)',
            'Category *',
            'Sub Type',
            'Quantity *',
            'Unit *',
            'Region (Dubai/Abu Dhabi/UAE)',
            'Fuel Category',
            'Vehicle Type',
            'Notes',
        ];
    }

    public static function sampleRows(): array
    {
        return [
            // —— SCOPE 2 ——
            ['Dubai Head Office', 2025, '2025-01-31', 'electricity', '', 50000, 'kWh', 'Dubai', '', '', 'SCOPE 2 | DEWA electricity bill — enter total kWh from bill'],
            ['Abu Dhabi Branch', 2025, '2025-01-31', 'electricity', '', 32000, 'kWh', 'Abu Dhabi', '', '', 'SCOPE 2 | ADDC electricity bill — use Abu Dhabi region'],
            ['Sharjah Warehouse', 2025, '2025-01-31', 'electricity', '', 15000, 'kWh', 'UAE', '', '', 'SCOPE 2 | SEWA or other emirate — use UAE if emirate unknown'],
            ['Dubai Head Office', 2025, '2025-02-28', 'heat-steam-cooling', 'Cooling', 10000, 'kWh', 'UAE', '', '', 'SCOPE 2 | District cooling (Empower/Tabreed) — kWh from supplier bill'],
            ['Dubai Head Office', 2025, '2025-02-28', 'heat-steam-cooling', 'Cooling', 850, 'RT', 'UAE', '', '', 'SCOPE 2 | District cooling — use RT if billed in refrigeration tonnes'],
            // —— SCOPE 1: FUELS & GAS ——
            ['Dubai Head Office', 2025, '2025-01-15', 'natural-gas', 'Natural gas', 5000, 'cubic metres', 'UAE', '', '', 'SCOPE 1 | Gas utility bill — boilers, kitchen (m³ or Nm³)'],
            ['Dubai Head Office', 2025, '2025-01-15', 'natural-gas', 'Natural gas', 52000, 'kWh (Net CV)', 'UAE', '', '', 'SCOPE 1 | Alternative: if gas bill shows kWh instead of m³'],
            ['Dubai Head Office', 2025, '2025-01-20', 'fuel', 'Diesel (100% mineral diesel)', 500, 'litres', 'UAE', 'Liquid fuels', '', 'SCOPE 1 | Generator / forklift — diesel in litres'],
            ['Dubai Head Office', 2025, '2025-06-01', 'fuel', 'Petrol (100% mineral petrol)', 200, 'litres', 'UAE', 'Liquid fuels', '', 'SCOPE 1 | Stationary petrol equipment'],
            ['Dubai Head Office', 2025, '2025-03-05', 'fuel', 'LPG', 120, 'litres', 'UAE', 'Gaseous fuels', '', 'SCOPE 1 | LPG cylinders — catering, heating'],
            // —— SCOPE 1: VEHICLES ——
            ['Dubai Head Office', 2025, '2025-02-01', 'vehicle', 'Diesel', 1200, 'km', 'UAE', '', 'Average (up to 3.5 tonnes)', 'SCOPE 1 | Company van — distance from logbook/ GPS'],
            ['Dubai Head Office', 2025, '2025-02-01', 'vehicle', 'Petrol', 800, 'km', 'UAE', '', 'Average car', 'SCOPE 1 | Company car — petrol, km driven'],
            ['Dubai Head Office', 2025, '2025-02-01', 'vehicle', 'Diesel', 95, 'litres', 'UAE', '', 'Average (up to 3.5 tonnes)', 'SCOPE 1 | Alternative: fuel-card litres if km unknown'],
            // —— SCOPE 1: REFRIGERANTS ——
            ['Dubai Head Office', 2025, '2025-03-10', 'refrigerants', 'HFC-134a', 2.5, 'kg', 'UAE', '', '', 'SCOPE 1 | AC/chiller top-up ONLY — not full system capacity'],
            ['Dubai Head Office', 2025, '2025-04-12', 'refrigerants', 'R-410A', 1.8, 'kg', 'UAE', '', '', 'SCOPE 1 | Split AC R-410A gas refill from service report'],
            ['Dubai Head Office', 2025, '2025-05-01', 'refrigerants', 'HFC-32', 0.5, 'kg', 'UAE', '', '', 'SCOPE 1 | Modern inverter AC — check service invoice for gas type'],
            // —— SCOPE 1: PROCESS (industrial — delete row if not applicable) ——
            ['Dubai Factory', 2025, '2025-01-31', 'process', 'Cement Production', 150, 'tonnes', 'UAE', '', '', 'SCOPE 1 | Industrial only — tonnes cement produced; delete if N/A'],
        ];
    }

    public static function instructionsRows(): array
    {
        return [
            ['MENetZero — Scope 1 & 2 Bulk Data Import Guide (UAE)'],
            [''],
            ['WHO IS THIS FOR?'],
            ['Most UAE businesses need only a few rows: electricity (DEWA/ADDC), diesel/petrol, vehicles, and maybe district cooling.'],
            ['Delete any example rows that do not apply to your business before uploading.'],
            [''],
            ['HOW TO USE'],
            ['1. Download this Excel file (recommended) or CSV.'],
            ['2. Open "Your Locations" — copy your exact location names.'],
            ['3. Open "Examples" — see one sample row per activity type (all Scope 1 & 2 categories covered).'],
            ['4. Fill the "Data Entry" sheet — copy/adapt examples, one row per bill or activity.'],
            ['5. Upload from MENetZero → Input Data → Bulk Import.'],
            [''],
            ['WHERE UAE CLIENTS FIND THE NUMBERS'],
            ['• Electricity (Scope 2) — Total kWh on DEWA, ADDC, or SEWA bill. Set region = Dubai / Abu Dhabi / UAE'],
            ['• District cooling (Scope 2) — kWh or RT on Empower, Tabreed, or district cooling invoice'],
            ['• Natural gas (Scope 1) — m³ or kWh on gas utility bill'],
            ['• Diesel / Petrol (Scope 1) — Litres from fuel invoices, generator logs, or ENOC/ADNOC receipts'],
            ['• LPG (Scope 1) — Litres or kg from cylinder delivery invoices'],
            ['• Vehicles (Scope 1) — Km from fleet log, Salik trips, or fuel-card litres'],
            ['• Refrigerants (Scope 1) — kg gas ADDED during AC service (top-up only, not unit capacity)'],
            ['• Process (Scope 1) — Only factories: tonnes of product (cement, steel, etc.)'],
            [''],
            ['REQUIRED COLUMNS'],
            ['• location_name — exact name from "Your Locations" sheet'],
            ['• fiscal_year — reporting year e.g. 2025'],
            ['• category — see Reference sheet (7 types: natural-gas, fuel, vehicle, refrigerants, process, electricity, heat-steam-cooling)'],
            ['• quantity + unit — numeric amount from your bill'],
            [''],
            ['CATEGORY QUICK GUIDE'],
            ['electricity          → sub_type blank | unit: kWh | region: Dubai or Abu Dhabi'],
            ['heat-steam-cooling   → sub_type: Cooling, Heat, or Steam | unit: kWh or RT'],
            ['natural-gas          → sub_type: Natural gas | unit: cubic metres or kWh (Net CV)'],
            ['fuel                 → sub_type: Diesel/Petrol/LPG (full name in Reference) | unit: litres'],
            ['vehicle              → sub_type: Diesel or Petrol | unit: km (preferred) or litres'],
            ['refrigerants         → sub_type: HFC-134a, R-410A, HFC-32, etc. | unit: kg'],
            ['process              → sub_type: Cement Production, etc. | unit: tonnes | industrial only'],
            [''],
            ['TIPS'],
            ['• One row = one bill or one activity period (e.g. January DEWA bill)'],
            ['• Leave sub_type blank only for electricity'],
            ['• Use region Dubai for DEWA, Abu Dhabi for ADDC — more accurate CO₂ calculation'],
            ['• See "Examples" sheet — includes every category with UAE-realistic dummy data'],
        ];
    }

    public static function referenceRows(): array
    {
        return [
            ['Category', 'Scope', 'sub_type (copy exactly)', 'Unit', 'UAE source / notes'],
            ['electricity', '2', '(leave blank)', 'kWh', 'DEWA / ADDC / SEWA bill total kWh'],
            ['heat-steam-cooling', '2', 'Cooling', 'kWh', 'Empower, Tabreed district cooling bill'],
            ['heat-steam-cooling', '2', 'Cooling', 'RT', 'If supplier bills in refrigeration tonnes'],
            ['heat-steam-cooling', '2', 'Steam', 'kWh', 'Purchased steam — rare in UAE offices'],
            ['heat-steam-cooling', '2', 'Heat', 'kWh', 'Purchased district heating — rare in UAE'],
            ['natural-gas', '1', 'Natural gas', 'cubic metres', 'Gas utility bill (m³)'],
            ['natural-gas', '1', 'Natural gas', 'kWh (Net CV)', 'If bill shows kWh not m³'],
            ['fuel', '1', 'Diesel (100% mineral diesel)', 'litres', 'Generator, forklift, ENOC/ADNOC diesel'],
            ['fuel', '1', 'Petrol (100% mineral petrol)', 'litres', 'Stationary petrol equipment'],
            ['fuel', '1', 'LPG', 'litres', 'LPG cylinders — catering, workshops'],
            ['fuel', '1', 'Propane', 'litres', 'Propane gas bottles'],
            ['vehicle', '1', 'Diesel', 'km', 'Fleet van/truck — odometer or trip log'],
            ['vehicle', '1', 'Diesel', 'litres', 'If only fuel-card data available'],
            ['vehicle', '1', 'Petrol', 'km', 'Company cars — km driven'],
            ['vehicle', '1', 'Petrol', 'litres', 'If only fuel-card data available'],
            ['refrigerants', '1', 'HFC-134a', 'kg', 'AC/chiller service top-up'],
            ['refrigerants', '1', 'R-410A', 'kg', 'Split AC systems (common in UAE)'],
            ['refrigerants', '1', 'HFC-32', 'kg', 'Modern inverter AC units'],
            ['refrigerants', '1', 'R-407C', 'kg', 'Commercial refrigeration'],
            ['process', '1', 'Cement Production', 'tonnes', 'Cement plants only'],
            ['process', '1', 'Steel and Iron Production', 'tonnes', 'Steel manufacturing only'],
            ['process', '1', 'Aluminium Production', 'tonnes', 'Aluminium smelters only'],
            ['process', '1', 'Ammonia Production', 'tonnes', 'Chemical plants only'],
            ['', '', '', '', ''],
            ['region column', '', 'Dubai | Abu Dhabi | Sharjah | UAE', '', 'Dubai=DEWA factor, Abu Dhabi=ADDC, UAE=national average'],
        ];
    }

    /**
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

        $sources = EmissionSourceMaster::where('is_quick_input', true)
            ->whereIn('scope', ['Scope 1', 'Scope 2'])
            ->get()
            ->keyBy('quick_input_slug');

        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rawRows as $index => $row) {
                $line = $index + 2;
                $parsed = $this->parseRow($row, $columnMap);

                if ($parsed === null) {
                    $skipped++;
                    continue;
                }

                try {
                    $this->importSingleRow($parsed, $locations, $sources, $companyId, $userId);
                    $imported++;
                } catch (\Throwable $e) {
                    $errors[] = "Row {$line}: {$e->getMessage()}";
                    Log::warning('Scope12 bulk import row failed', ['line' => $line, 'error' => $e->getMessage(), 'row' => $parsed]);
                }
            }

            if ($imported === 0 && empty($errors)) {
                DB::rollBack();
                return [
                    'imported' => 0,
                    'skipped' => $skipped,
                    'errors' => ['No valid data rows found. Check that required columns are filled.'],
                ];
            }

            if ($imported === 0 && !empty($errors)) {
                DB::rollBack();
                return ['imported' => 0, 'skipped' => $skipped, 'errors' => $errors];
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
            $v = strtolower(trim(preg_replace('/[^a-z0-9_]/', '_', (string) $value)));
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
            'emission_category' => 'category',
            'category' => 'category',
            'type' => 'sub_type',
            'sub_type' => 'sub_type',
            'subtype' => 'sub_type',
            'amount' => 'quantity',
            'quantity' => 'quantity',
            'unit' => 'unit',
            'unit_of_measure' => 'unit',
            'region' => 'region',
            'fuel_category' => 'fuel_category',
            'vehicle_type' => 'vehicle_type',
            'notes' => 'notes',
            'comments' => 'notes',
        ];

        $map = [];
        foreach ($headerRow as $i => $cell) {
            $key = $normalise($cell);
            if (isset($aliases[$key])) {
                $map[$aliases[$key]] = $i;
            }
        }

        foreach (['location_name', 'fiscal_year', 'category', 'quantity', 'unit'] as $required) {
            if (!isset($map[$required])) {
                return null;
            }
        }

        return $map;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $columnMap
     * @return array<string, mixed>|null
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
        $category = strtolower($get('category') ?? '');
        $quantity = $get('quantity');

        if (is_string($locationName) && str_starts_with($locationName, '#')) {
            return null;
        }

        if (!$locationName && !$category && $quantity === null) {
            return null;
        }

        if (!$locationName || !$category || $quantity === null || $quantity === '') {
            throw new \InvalidArgumentException('Missing required field (location_name, category, or quantity).');
        }

        if (!is_numeric($quantity) || (float) $quantity < 0) {
            throw new \InvalidArgumentException('Quantity must be a non-negative number.');
        }

        $unit = $get('unit');
        if (!$unit) {
            throw new \InvalidArgumentException('Unit is required.');
        }

        $slug = $this->normaliseCategory($category);
        if (!$slug) {
            throw new \InvalidArgumentException("Unknown category \"{$category}\".");
        }

        return [
            'location_name' => $locationName,
            'fiscal_year' => (int) $get('fiscal_year'),
            'entry_date' => $get('entry_date'),
            'category' => $slug,
            'sub_type' => $get('sub_type'),
            'quantity' => (float) $quantity,
            'unit' => $unit,
            'region' => $get('region') ?: 'UAE',
            'fuel_category' => $get('fuel_category'),
            'vehicle_type' => $get('vehicle_type'),
            'notes' => $get('notes'),
        ];
    }

    protected function normaliseCategory(string $category): ?string
    {
        $key = strtolower(trim($category));
        $key = str_replace([' ', '_'], '-', $key);

        $map = [
            'natural-gas' => 'natural-gas',
            'naturalgas' => 'natural-gas',
            'gas' => 'natural-gas',
            'fuel' => 'fuel',
            'stationary-fuel' => 'fuel',
            'vehicle' => 'vehicle',
            'vehicles' => 'vehicle',
            'mobile' => 'vehicle',
            'refrigerants' => 'refrigerants',
            'refrigerant' => 'refrigerants',
            'fugitive' => 'refrigerants',
            'process' => 'process',
            'electricity' => 'electricity',
            'electric' => 'electricity',
            'power' => 'electricity',
            'heat-steam-cooling' => 'heat-steam-cooling',
            'heat/steam/cooling' => 'heat-steam-cooling',
            'cooling' => 'heat-steam-cooling',
            'district-cooling' => 'heat-steam-cooling',
        ];

        return $map[$key] ?? null;
    }

    protected function importSingleRow(
        array $row,
        $locations,
        $sources,
        int $companyId,
        int $userId
    ): void {
        if ($row['fiscal_year'] < 2000 || $row['fiscal_year'] > 2100) {
            throw new \InvalidArgumentException('Fiscal year must be between 2000 and 2100.');
        }

        $locationKey = strtolower(trim($row['location_name']));
        $location = $locations->get($locationKey);
        if (!$location) {
            throw new \InvalidArgumentException("Location \"{$row['location_name']}\" not found. Use exact names from Your Locations sheet.");
        }

        $slug = $row['category'];
        $emissionSource = $sources->get($slug);
        if (!$emissionSource) {
            throw new \InvalidArgumentException("Emission source \"{$slug}\" is not configured.");
        }

        $fuelType = $this->resolveFuelType($slug, $row['sub_type']);

        $conditions = [
            'region' => $row['region'],
            'fuel_category' => $row['fuel_category'],
            'fuel_type' => $fuelType,
            'unit' => $row['unit'],
            'vehicle_category' => $slug === 'vehicle' ? 'LDV (Vans, Pickup trucks, SUVs)' : null,
            'vehicle_type' => $row['vehicle_type'] ?: ($slug === 'vehicle' ? 'Average (up to 3.5 tonnes)' : null),
        ];

        if ($slug === 'vehicle' && $fuelType) {
            $conditions['fuel_type'] = $fuelType;
        }

        $emissionFactor = $this->calculationService->selectEmissionFactor($emissionSource->id, array_filter($conditions, fn ($v) => $v !== null && $v !== ''));

        if (!$emissionFactor) {
            throw new \InvalidArgumentException(
                'No emission factor found for category "' . $slug . '", sub_type "' . ($row['sub_type'] ?? '') . '", unit "' . $row['unit'] . '". Check Reference sheet.'
            );
        }

        $calculation = $this->calculationService->calculateCO2e($row['quantity'], $emissionFactor, $row['unit']);
        $co2e = $calculation['co2e'] ?? $calculation['total_co2e'] ?? 0;

        $measurement = $this->measurementService->getOrCreateMeasurement($location->id, $row['fiscal_year']);

        $entryDate = $row['entry_date']
            ? Carbon::parse($row['entry_date'])->toDateString()
            : Carbon::now()->toDateString();

        $additionalData = array_filter([
            'fuel_category' => $row['fuel_category'],
            'energy_type' => $slug === 'heat-steam-cooling' ? ($row['sub_type'] ?: 'Cooling') : null,
            'refrigerant_type' => $slug === 'refrigerants' ? $row['sub_type'] : null,
            'import_source' => 'bulk_upload',
        ]);

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
            'scope' => $emissionSource->scope,
            'entry_date' => $entryDate,
            'emission_factor_id' => $emissionFactor->id,
            'gwp_version_used' => $emissionFactor->gwp_version ?? 'AR6',
            'calculation_method' => $emissionFactor->calculation_method ?? null,
            'fuel_type' => $fuelType,
            'additional_data' => !empty($additionalData) ? $additionalData : null,
            'notes' => $row['notes'],
            'created_by' => $userId,
        ]);

        $this->measurementService->updateMeasurementTotals($measurement->id);
    }

    protected function resolveFuelType(string $slug, ?string $subType): ?string
    {
        return match ($slug) {
            'natural-gas' => $subType ?: 'Natural gas',
            'fuel' => $subType ?: throw new \InvalidArgumentException('sub_type is required for fuel (e.g. Diesel (100% mineral diesel)).'),
            'vehicle' => $subType ?: throw new \InvalidArgumentException('sub_type is required for vehicle (Diesel or Petrol).'),
            'refrigerants' => $subType ?: throw new \InvalidArgumentException('sub_type is required for refrigerants (e.g. HFC-134a).'),
            'process' => $subType ?: throw new \InvalidArgumentException('sub_type is required for process (e.g. Cement Production).'),
            'heat-steam-cooling' => $subType ?: 'Cooling',
            'electricity' => null,
            default => $subType,
        };
    }

    /**
     * Pick the best sheet from an Excel workbook for import.
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

        foreach ($sheets as $title => $rows) {
            $lower = strtolower($title);
            if (in_array($lower, ['instructions', 'reference', 'your locations', 'examples'], true)) {
                continue;
            }
            if (!empty($rows)) {
                return $rows;
            }
        }

        return reset($sheets) ?: [];
    }
}
