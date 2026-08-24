<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\EmissionFactor;
use App\Models\EmissionSourceMaster;
use App\Models\EsgKpiSnapshot;
use App\Models\Location;
use App\Models\MeasurementData;
use App\Models\ReductionTarget;
use App\Services\DisclosureService;
use App\Services\EsgScorecardService;
use App\Services\MeasurementService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Prior-year (2024 & 2025) history for a demo company so the 3-year ESG
 * scorecard, the dashboard target card, and year-on-year comparisons have
 * something real to show.
 *
 * ConsultantFullDemoSeeder seeds only the current reporting year, which leaves
 * every "2024 / 2025" column showing "—". This backfills those two years with a
 * declining trend so improvement is visible rather than flat.
 *
 * Demo data only — never wire this into a migration or DatabaseSeeder::run().
 * Run explicitly:
 *   php artisan db:seed --class=FalconHistoricalDataSeeder
 */
class FalconHistoricalDataSeeder extends Seeder
{
    /**
     * Company to backfill. Overridable via FALCON_DEMO_COMPANY env var so the
     * same seeder can serve another demo client.
     */
    private const DEFAULT_COMPANY = 'Falcon Industrial Parks';

    /**
     * Multipliers applied to current-year figures, per year.
     *
     * Emissions and resource use were HIGHER in earlier years, so the current
     * year reads as a genuine reduction against the target rather than a flat
     * line. Social/governance metrics improve in the opposite direction.
     */
    private const YEAR_FACTORS = [
        2024 => 1.32,
        2025 => 1.15,
    ];

    public function run(): void
    {
        $companyName = env('FALCON_DEMO_COMPANY', self::DEFAULT_COMPANY);

        $company = Company::where('name', $companyName)->first();

        if (!$company) {
            $this->command?->error("Company \"{$companyName}\" not found. Run ConsultantFullDemoSeeder first.");

            return;
        }

        $currentYear = (int) date('Y');

        $locations = Location::where('company_id', $company->id)->get();

        if ($locations->isEmpty()) {
            $this->command?->error("No locations for \"{$companyName}\". Run ConsultantFullDemoSeeder first.");

            return;
        }

        $userId = $this->resolveUserId($company);
        $measurementService = app(MeasurementService::class);

        $totalEntries = 0;

        foreach (self::YEAR_FACTORS as $year => $factor) {
            foreach ($locations as $location) {
                $measurement = $measurementService->getOrCreateMeasurement($location->id, $year, $userId);
                $totalEntries += $this->seedEmissionEntries($measurement->id, $userId, $year, $factor);
                $measurementService->updateMeasurementTotals($measurement->id);
            }

            $this->seedGriEnvironmental($company, $year, $factor);
            $this->seedGriSocial($company, $year, $factor);
            $this->seedScorecardManual($company, $year, $factor);
            $this->seedKpiSnapshots($company, $year, $factor);

            $this->command?->info("Seeded {$year} history for {$companyName}");
        }

        $this->rebaseReductionTarget($company, $currentYear);

        $this->command?->info("Done — {$totalEntries} historical emission entries across " . count(self::YEAR_FACTORS) . ' years.');
    }

    /**
     * Mirrors the current year's entries, scaled by the year factor, so scope
     * splits stay proportionate and the trend is smooth.
     */
    private function seedEmissionEntries(int $measurementId, int $userId, int $year, float $factor): int
    {
        if (!Schema::hasTable('measurement_data')) {
            return 0;
        }

        if (MeasurementData::where('measurement_id', $measurementId)->exists()) {
            return MeasurementData::where('measurement_id', $measurementId)->count();
        }

        $samples = [
            ['slug' => 'natural-gas', 'quantity' => 1200, 'unit' => 'litres', 'fallback_co2e' => 3200, 'scope' => 'Scope 1'],
            ['slug' => 'diesel', 'quantity' => 850, 'unit' => 'litres', 'fallback_co2e' => 2280, 'scope' => 'Scope 1'],
            ['slug' => 'lpg', 'quantity' => 300, 'unit' => 'kg', 'fallback_co2e' => 900, 'scope' => 'Scope 1'],
            ['slug' => 'electricity', 'quantity' => 45000, 'unit' => 'kWh', 'fallback_co2e' => 19800, 'scope' => 'Scope 2'],
            ['slug' => 'district-cooling', 'quantity' => 18000, 'unit' => 'kWh', 'fallback_co2e' => 5400, 'scope' => 'Scope 2'],
        ];

        $created = 0;

        foreach ($samples as $sample) {
            $source = EmissionSourceMaster::query()
                ->where('is_quick_input', true)
                ->where(function ($q) use ($sample) {
                    $q->where('quick_input_slug', $sample['slug'])
                        ->orWhere('name', 'like', '%' . str_replace('-', ' ', $sample['slug']) . '%');
                })
                ->first()
                ?? EmissionSourceMaster::query()->where('is_quick_input', true)->where('scope', $sample['scope'])->first();

            $factorModel = $source
                ? EmissionFactor::query()->where('emission_source_id', $source->id)->where('is_active', true)->first()
                : null;

            $quantity = round($sample['quantity'] * $factor, 2);

            $co2e = $factorModel
                ? round($quantity * (float) $factorModel->factor_value, 4)
                : round($sample['fallback_co2e'] * $factor, 4);

            if ($co2e <= 0) {
                $co2e = round($sample['fallback_co2e'] * $factor, 4);
            }

            MeasurementData::create([
                'measurement_id' => $measurementId,
                'emission_source_id' => $source?->id,
                'field_name' => 'quick_input',
                'field_value' => (string) $quantity,
                'quantity' => $quantity,
                'unit' => $sample['unit'],
                'calculated_co2e' => $co2e,
                'scope' => $sample['scope'],
                'emission_factor_id' => $factorModel?->id,
                'entry_date' => $year . '-06-01',
                'notes' => 'Seeded historical demo entry',
                'created_by' => $userId,
            ]);

            $created++;
        }

        // Scope 3 — same filters as the demo seeder so totals reconcile with the
        // reported GHG Protocol categories.
        $scope3Sources = EmissionSourceMaster::query()
            ->where('scope', 'Scope 3')
            ->where('is_quick_input', true)
            ->where('is_active', true)
            ->whereNotNull('subcategory')
            ->orderBy('quick_input_order')
            ->limit(6)
            ->get();

        foreach ($scope3Sources as $index => $source) {
            $factorModel = EmissionFactor::query()
                ->where('emission_source_id', $source->id)
                ->where('is_active', true)
                ->first();

            // Never fabricate a CO2e without a factor — an entry with no
            // emission_factor_id has no auditable methodology behind it.
            if (!$factorModel) {
                continue;
            }

            $quantity = round((100 + ($index * 25)) * $factor, 2);
            $co2e = round($quantity * (float) $factorModel->factor_value, 4);

            MeasurementData::create([
                'measurement_id' => $measurementId,
                'emission_source_id' => $source->id,
                'field_name' => 'quick_input',
                'field_value' => (string) $quantity,
                'quantity' => $quantity,
                'unit' => $factorModel->unit,
                'calculated_co2e' => $co2e,
                'scope' => 'Scope 3',
                'emission_factor_id' => $factorModel->id,
                'entry_date' => $year . '-06-01',
                'notes' => 'Seeded historical demo Scope 3 entry (' . $source->category . ')',
                'created_by' => $userId,
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * GRI energy / water / waste — feeds the scorecard's environmental rows.
     */
    private function seedGriEnvironmental(Company $company, int $year, float $factor): void
    {
        $service = app(DisclosureService::class);

        $service->saveSection($company->id, $year, 'energy', [
            'total_energy_gj' => round(612 * $factor, 2),
            'renewable_energy_gj' => 0,
            'renewable_percent' => 0,
            'energy_intensity_value' => round(3.4 * $factor, 2),
            'energy_intensity_denominator' => 'per AED million revenue',
            'methodology_notes' => 'Historical demo data — converted using standard IEA conversion factors.',
        ], 'gri');

        $service->saveSection($company->id, $year, 'water', [
            'withdrawal_total_m3' => round(4200 * $factor, 2),
            'withdrawal_surface_m3' => 0,
            'withdrawal_groundwater_m3' => 0,
            'withdrawal_municipal_m3' => round(4200 * $factor, 2),
            'discharge_total_m3' => round(3100 * $factor, 2),
            'consumption_total_m3' => round(1100 * $factor, 2),
            'water_stressed_areas_notes' => 'Historical demo data — municipal supply only.',
        ], 'gri');

        // Recycling improves over time, so the earlier years divide by the factor.
        $service->saveSection($company->id, $year, 'waste', [
            'waste_hazardous_tonnes' => round(1.2 * $factor, 2),
            'waste_non_hazardous_tonnes' => round(28.5 * $factor, 2),
            'waste_total_tonnes' => round(29.7 * $factor, 2),
            'waste_recycled_tonnes' => round(9.8 / $factor, 2),
            'waste_reuse_tonnes' => round(1.5 / $factor, 2),
            'waste_landfill_tonnes' => round(17.2 * $factor, 2),
            'waste_incineration_tonnes' => round(1.2 * $factor, 2),
        ], 'gri');
    }

    /**
     * Headcount and safety history — feeds the scorecard's social rows.
     */
    private function seedGriSocial(Company $company, int $year, float $factor): void
    {
        $service = app(DisclosureService::class);

        $service->saveSection($company->id, $year, 'social_hr', [
            'employees_total' => (int) round(180 / $factor),
            'employees_new_hires' => (int) round(22 / $factor),
            'employees_turnover_percent' => round(9.5 * $factor, 1),
            'training_hours_avg' => round(11.7 / $factor, 1),
            'parental_leave_return_rate' => round(88 / $factor, 1),
            'benefits_summary' => 'Historical demo data — medical cover, annual leave, and end-of-service benefits.',
        ], 'gri');

        $service->saveSection($company->id, $year, 'diversity', [
            'women_management_percent' => round(18 / $factor, 1),
            'women_workforce_percent' => round(28 / $factor, 1),
            'board_diversity_percent' => round(14 / $factor, 1),
        ], 'gri');
    }

    /**
     * Manual scorecard metrics (EsgScorecardService::saveManual).
     */
    private function seedScorecardManual(Company $company, int $year, float $factor): void
    {
        $scorecard = app(EsgScorecardService::class);

        $scorecard->saveManual($company->id, $year, 'environment', [
            'environmental_incidents' => $factor > 1.2 ? 2 : 1,
        ]);

        $scorecard->saveManual($company->id, $year, 'social', [
            'community_investment_aed' => (int) round(120000 / $factor),
        ]);

        $scorecard->saveManual($company->id, $year, 'governance', [
            'supplier_audits' => (int) round(6 / $factor),
        ]);
    }

    /**
     * Enterprise KPI snapshots — the ESG Depth / scorecard detail metrics.
     */
    private function seedKpiSnapshots(Company $company, int $year, float $factor): void
    {
        $metrics = [
            'environment' => [
                'spills_count' => $factor > 1.2 ? 1 : 0,
                'environmental_fines_aed' => $factor > 1.2 ? 15000 : 0,
                'iso14001_certified_sites' => 0,
            ],
            'social' => [
                'community_investment_esg' => (int) round(120000 / $factor),
                'community_beneficiaries' => (int) round(400 / $factor),
                'stakeholder_engagements' => (int) round(4 / $factor),
                'employees_uae' => (int) round(145 / $factor),
                'employees_gcc' => (int) round(25 / $factor),
                'employees_other_regions' => (int) round(10 / $factor),
                'contractors_total' => (int) round(30 * $factor),
                'volunteer_hours' => (int) round(120 / $factor),
                'absenteeism_rate' => round(2.1 * $factor, 2),
                'employee_engagement_score' => (int) round(74 / $factor),
            ],
            'governance' => [
                'whistleblower_reports' => $factor > 1.2 ? 2 : 1,
                'political_contributions_aed' => 0,
                'human_rights_incidents' => 0,
                'sustainability_linked_finance_aed' => 0,
                'esg_targets_active' => (int) round(4 / $factor),
                'supplier_audits_env' => (int) round(4 / $factor),
                'supplier_audits_social' => (int) round(4 / $factor),
                'tax_transparency_disclosed' => 0,
            ],
        ];

        foreach ($metrics as $category => $values) {
            foreach ($values as $metricKey => $value) {
                EsgKpiSnapshot::updateOrCreate(
                    ['company_id' => $company->id, 'fiscal_year' => $year, 'metric_key' => $metricKey],
                    ['category' => $category, 'value' => $value, 'unit' => null, 'source' => EsgKpiSnapshot::SOURCE_MANUAL]
                );
            }
        }
    }

    /**
     * The demo target baselines in the CURRENT year, which leaves the dashboard
     * target card with no trajectory to measure against. Re-baseline it to the
     * earliest seeded year so on-track / behind-schedule can actually be tested.
     */
    private function rebaseReductionTarget(Company $company, int $currentYear): void
    {
        $baseYear = min(array_keys(self::YEAR_FACTORS));

        $target = ReductionTarget::where('company_id', $company->id)
            ->where('status', 'active')
            ->orderBy('target_year')
            ->first();

        if (!$target) {
            return;
        }

        // Baseline = actual Scope 1 & 2 for the base year, so "% vs baseline"
        // reflects the seeded inventory instead of an unrelated constant.
        $baselineTonnes = $this->scope12Tonnes($company, $baseYear);

        if ($baselineTonnes === null || $baselineTonnes <= 0) {
            return;
        }

        // FALCON_DEMO_TARGET=strict makes the same inventory read "Behind
        // schedule", so the red path on the dashboard card can be tested too.
        $strict = env('FALCON_DEMO_TARGET') === 'strict';
        $reductionPercent = $strict ? 60 : 30;

        $target->update([
            'base_year' => $baseYear,
            'baseline_tco2e' => round($baselineTonnes, 4),
            'target_tco2e' => round($baselineTonnes * (1 - ($reductionPercent / 100)), 4),
            'reduction_percent' => $reductionPercent,
            'target_year' => $strict ? $currentYear + 1 : max((int) $target->target_year, $currentYear + 4),
        ]);

        $this->command?->info(
            "Re-baselined reduction target to {$baseYear} (" . round($baselineTonnes, 2) . ' tCO₂e).'
        );
    }

    /**
     * Sum of Scope 1 + Scope 2 actuals for a year, across the company's locations.
     */
    private function scope12Tonnes(Company $company, int $year): ?float
    {
        $locationIds = Location::where('company_id', $company->id)->pluck('id');

        if ($locationIds->isEmpty()) {
            return null;
        }

        // calculated_co2e is stored in KG — targets are expressed in tonnes.
        $kg = (float) MeasurementData::query()
            ->whereIn('scope', ['Scope 1', 'Scope 2'])
            ->whereHas('measurement', function ($q) use ($locationIds, $year) {
                $q->whereIn('location_id', $locationIds)->where('fiscal_year', $year);
            })
            ->sum('calculated_co2e');

        return $kg > 0 ? \App\Services\GhgReportService::kgToTonnes($kg) : null;
    }

    private function resolveUserId(Company $company): int
    {
        $userId = \App\Models\User::where('company_id', $company->id)->value('id')
            ?? \App\Models\User::query()->value('id');

        return (int) ($userId ?? 1);
    }
}
