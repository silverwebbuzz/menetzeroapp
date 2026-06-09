<?php

namespace App\Services;

use App\Models\CompanyReportingSetting;
use App\Models\Measurement;
use App\Models\MeasurementData;
use Illuminate\Support\Facades\DB;

class GhgReportService
{
    /** All stored CO₂e values in the database are kg CO₂e. */
    public static function kgToTonnes(?float $kg): float
    {
        return round(((float) $kg) / 1000, 4);
    }

    public static function formatTonnes(?float $kg, int $decimals = 2): string
    {
        return number_format(self::kgToTonnes($kg), $decimals);
    }

    /**
     * Build complete report payload for web view and PDF export.
     */
    public function build(Measurement $measurement): array
    {
        $measurement->load(['location.company', 'location']);

        $scopeKg = [
            'Scope 1' => (float) ($measurement->scope_1_co2e ?? 0),
            'Scope 2' => (float) ($measurement->scope_2_co2e ?? 0),
            'Scope 3' => (float) ($measurement->scope_3_co2e ?? 0),
        ];

        $totalKg = (float) ($measurement->total_co2e ?? array_sum($scopeKg));
        $scope12Kg = $scopeKg['Scope 1'] + $scopeKg['Scope 2'];

        $scopesTonnes = collect($scopeKg)->map(fn ($v) => self::kgToTonnes($v))->all();

        $resultsBreakdown = $this->buildResultsBreakdown($measurement);
        $activityRegister = $this->buildActivityRegister($measurement);

        $emissionSourceData = MeasurementData::with('emissionSource:id,name,scope')
            ->select('emission_source_id', DB::raw('SUM(calculated_co2e) as total_co2e'))
            ->where('measurement_id', $measurement->id)
            ->groupBy('emission_source_id')
            ->get()
            ->filter(fn ($row) => $row->total_co2e > 0 && $row->emissionSource)
            ->map(function ($row) use ($totalKg) {
                $kg = (float) $row->total_co2e;
                return [
                    'label' => $row->emissionSource->name,
                    'scope' => $row->emissionSource->scope,
                    'kg' => $kg,
                    'tonnes' => self::kgToTonnes($kg),
                    'percent' => $totalKg > 0 ? round(($kg / $totalKg) * 100, 1) : 0,
                ];
            })->values();

        $scope12Tonnes = self::kgToTonnes($scope12Kg);
        $scopePercentages = collect($scopeKg)->map(
            fn ($v) => $totalKg > 0 ? round(($v / $totalKg) * 100, 1) : 0
        )->values()->all();

        $scope2Split = $this->buildScope2Split($measurement);
        $company = $measurement->location->company;
        $reportingSettings = CompanyReportingSetting::where('company_id', $company->id)
            ->where('fiscal_year', $measurement->fiscal_year)
            ->first();

        return [
            'measurement' => $measurement,
            'location' => $measurement->location,
            'reporting_period' => $this->reportingPeriodLabel($measurement),
            'scope_kg' => $scopeKg,
            'scope_tonnes' => $scopesTonnes,
            'scope_12_kg' => $scope12Kg,
            'scope_12_tonnes' => $scope12Tonnes,
            'total_kg' => $totalKg,
            'total_tonnes' => self::kgToTonnes($totalKg),
            'scope_percentages' => $scopePercentages,
            'scope_raw_tonnes' => array_map(fn ($v) => number_format(self::kgToTonnes($v), 2), array_values($scopeKg)),
            'results_breakdown' => $resultsBreakdown,
            'activity_register' => $activityRegister,
            'emission_source_data' => $emissionSourceData,
            'methodology' => $this->methodologyStatement(),
            'has_scope_3' => $scopeKg['Scope 3'] > 0,
            'scope_3_categories' => $this->buildScope3Categories($measurement),
            'scope3_coverage_matrix' => $this->buildScope3CoverageMatrix($measurement, $reportingSettings),
            'scope2_location_kg' => $scope2Split['location_kg'],
            'scope2_location_tonnes' => $scope2Split['location_tonnes'],
            'scope2_market_kg' => $scope2Split['market_kg'],
            'scope2_market_tonnes' => $scope2Split['market_tonnes'],
            'reporting_settings' => $reportingSettings,
            'biogenic_kg' => $this->sumBiogenicKg($measurement),
            'biogenic_tonnes' => self::kgToTonnes($this->sumBiogenicKg($measurement)),
            'entry_count' => $activityRegister->count(),
        ];
    }

    public function buildScope2Split(Measurement $measurement): array
    {
        $rows = MeasurementData::where('measurement_id', $measurement->id)
            ->where('scope', 'Scope 2')
            ->where('calculated_co2e', '>', 0)
            ->get();

        $locationKg = (float) $rows->where(fn ($r) => $r->scope2_method !== 'market')->sum('calculated_co2e');
        $marketKg = (float) $rows->where(fn ($r) => $r->scope2_method === 'market')->sum('calculated_co2e');

        if ($marketKg <= 0) {
            $marketKg = $locationKg;
        }

        return [
            'location_kg' => $locationKg,
            'market_kg' => $marketKg,
            'location_tonnes' => self::kgToTonnes($locationKg),
            'market_tonnes' => self::kgToTonnes($marketKg),
        ];
    }

    public function sumBiogenicKg(Measurement $measurement): float
    {
        return (float) MeasurementData::where('measurement_id', $measurement->id)
            ->where('is_biogenic', true)
            ->sum('calculated_co2e');
    }

    public function buildScope3CoverageMatrix(Measurement $measurement, ?CompanyReportingSetting $settings)
    {
        $policy = collect($settings?->scope3_category_policy ?? CompanyReportingSetting::defaultScope3Policy())
            ->keyBy('category');

        $dataBySlug = MeasurementData::with('emissionSource:id,quick_input_slug,subcategory')
            ->where('measurement_id', $measurement->id)
            ->where('scope', 'Scope 3')
            ->where('calculated_co2e', '>', 0)
            ->get()
            ->groupBy(fn ($row) => $row->emissionSource->quick_input_slug ?? 'unknown');

        $slugToCategory = [
            'purchased-goods' => 1,
            'capital-goods' => 2,
            'fuel-energy-related' => 3,
            'upstream-transport' => 4,
            'waste-operations' => 5,
            'business-travel' => 6,
            'flights' => 6,
            'employee-commuting' => 7,
            'public-transport' => 7,
            'home-workers' => 7,
            'upstream-leased' => 8,
            'downstream-transport' => 9,
            'processing-sold' => 10,
            'use-sold' => 11,
            'end-of-life' => 12,
            'downstream-leased' => 13,
            'franchises' => 14,
            'investments' => 15,
        ];

        $reportedCategories = [];
        foreach ($dataBySlug as $slug => $entries) {
            if (isset($slugToCategory[$slug])) {
                $reportedCategories[$slugToCategory[$slug]] = true;
            }
        }

        return collect(CompanyReportingSetting::SCOPE3_CATEGORIES)->map(function ($label, $cat) use ($policy, $reportedCategories) {
            $row = $policy->get($cat, ['included' => false, 'reason' => null]);

            return [
                'category' => $cat,
                'label' => $label,
                'policy_included' => (bool) ($row['included'] ?? false),
                'exclusion_reason' => $row['reason'] ?? null,
                'has_data' => !empty($reportedCategories[$cat]),
                'status' => !empty($reportedCategories[$cat])
                    ? 'Reported'
                    : (($row['included'] ?? false) ? 'Included — no data yet' : 'Excluded'),
            ];
        })->values();
    }

    /**
     * Scope 3 broken down by GHG Protocol category (source subcategory, e.g. "Cat 6 – Business Travel"),
     * with a data-quality flag per line (activity-based vs spend-based).
     *
     * Data quality is inferred from the emission factor: spend-based factors are priced per
     * currency (unit = AED) or use modelled EEIO/PCAF intensities (source_standard = Custom);
     * everything else is treated as activity-based (physical units, DEFRA/IPCC/UAE factors).
     */
    public function buildScope3Categories(Measurement $measurement)
    {
        $rows = MeasurementData::with(['emissionSource:id,name,scope,subcategory', 'emissionFactor:id,unit,source_standard'])
            ->where('measurement_id', $measurement->id)
            ->where('scope', 'Scope 3')
            ->where('calculated_co2e', '>', 0)
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $categories = $rows->groupBy(fn ($row) => optional($row->emissionSource)->subcategory
                ?: (optional($row->emissionSource)->name ?? 'Other'))
            ->map(function ($group, $categoryName) {
                $kg = (float) $group->sum('calculated_co2e');

                // A category is "spend-based" if every contributing entry uses a spend/modelled factor.
                $hasActivity = $group->contains(fn ($row) => !$this->isSpendBasedFactor($row->emissionFactor));
                $hasSpend = $group->contains(fn ($row) => $this->isSpendBasedFactor($row->emissionFactor));
                $quality = $hasActivity && $hasSpend ? 'Mixed' : ($hasActivity ? 'Activity-based' : 'Spend-based');

                return [
                    'category' => $categoryName,
                    'kg' => $kg,
                    'tonnes' => self::kgToTonnes($kg),
                    'data_quality' => $quality,
                    'entry_count' => $group->count(),
                ];
            })
            ->filter(fn ($row) => $row['kg'] > 0)
            ->sortByDesc('kg')
            ->values();

        return $categories;
    }

    /**
     * Detect a spend-based / modelled emission factor.
     */
    private function isSpendBasedFactor($factor): bool
    {
        if (!$factor) {
            return false;
        }

        $unit = strtolower(trim((string) $factor->unit));
        if (in_array($unit, ['aed', 'usd', '$', 'per aed', 'per usd'], true)) {
            return true;
        }

        // EEIO (EXIOBASE) and PCAF intensities are stored as Custom.
        return strtoupper((string) $factor->source_standard) === 'CUSTOM';
    }

    public function buildResultsBreakdown(Measurement $measurement): array
    {
        $breakdownByScope = MeasurementData::with('emissionSource:id,name,scope')
            ->select('emission_source_id', DB::raw('SUM(calculated_co2e) as total_co2e'))
            ->where('measurement_id', $measurement->id)
            ->groupBy('emission_source_id')
            ->get()
            ->groupBy(fn ($row) => $row->emissionSource->scope ?? 'Unknown');

        $results = [];

        foreach (['Scope 1', 'Scope 2', 'Scope 3'] as $scope) {
            $children = [];
            $scopeKg = 0;

            foreach ($breakdownByScope[$scope] ?? [] as $row) {
                $kg = (float) $row->total_co2e;
                if ($kg <= 0) {
                    continue;
                }
                $children[] = [
                    'name' => $row->emissionSource->name,
                    'kg' => $kg,
                    'tonnes' => self::kgToTonnes($kg),
                ];
                $scopeKg += $kg;
            }

            $results[] = [
                'name' => $scope,
                'kg' => $scopeKg,
                'tonnes' => self::kgToTonnes($scopeKg),
                'children' => $children,
            ];
        }

        return $results;
    }

    public function buildActivityRegister(Measurement $measurement)
    {
        return MeasurementData::with(['emissionSource:id,name,scope', 'emissionFactor'])
            ->where('measurement_id', $measurement->id)
            ->where('calculated_co2e', '>', 0)
            ->orderBy('scope')
            ->orderBy('entry_date')
            ->get()
            ->map(function ($row) {
                $factor = $row->emissionFactor;
                return [
                    'entry_date' => $row->entry_date?->format('Y-m-d') ?? '—',
                    'scope' => $row->scope,
                    'source' => $row->emissionSource->name ?? '—',
                    'activity' => $row->fuel_type ?: ($row->emissionSource->name ?? '—'),
                    'quantity' => number_format((float) $row->quantity, 2),
                    'unit' => $row->unit ?? '—',
                    'factor_value' => $factor ? number_format((float) $factor->factor_value, 4) : '—',
                    'factor_unit' => $factor->unit ?? '—',
                    'methodology' => $factor->source_standard ?? $row->calculation_method ?? '—',
                    'reference' => $factor->source_reference ?? '—',
                    'gwp' => $row->gwp_version_used ?? ($factor->gwp_version ?? 'AR6'),
                    'kg' => (float) $row->calculated_co2e,
                    'tonnes' => self::kgToTonnes($row->calculated_co2e),
                    'notes' => $row->notes ?? '',
                    'scope2_method' => $row->scope2_method,
                    'is_biogenic' => (bool) $row->is_biogenic,
                ];
            });
    }

    public function reportingPeriodLabel(Measurement $measurement): string
    {
        if ($measurement->period_start && $measurement->period_end) {
            return $measurement->period_start->format('d M Y') . ' – ' . $measurement->period_end->format('d M Y');
        }

        return 'Calendar year ' . $measurement->fiscal_year;
    }

    public function methodologyStatement(): array
    {
        return [
            'framework' => 'GHG Protocol Corporate Standard & IPCC 2006 Guidelines (2019 Refinement)',
            'factors' => 'MOCCAE-aligned / UAE utility factors (DEWA, EAD/ADDC, IEA national grid) and IPCC AR6 GWP values',
            'scopes' => 'Scope 1 (direct) and Scope 2 (purchased energy) — location-based method for electricity',
            'gwp' => 'IPCC AR6 (100-year GWP)',
            'disclaimer' => 'This inventory summary is prepared using MENetZero for internal reporting and as a working draft for MOCCAE IEQT submission. Official legal submission must be completed at mrv.ae using the Integrated Emissions Quantification Tool (IEQT).',
        ];
    }

    /**
     * Restrict report output to Scope 1 & 2 for MOCCAE / IEQT submission format.
     */
    public function applyMoccaeOnly(array $report): array
    {
        $scope12Kg = $report['scope_12_kg'];
        $scope12Tonnes = $report['scope_12_tonnes'];

        $scope1Kg = $report['scope_kg']['Scope 1'];
        $scope2Kg = $report['scope_kg']['Scope 2'];

        $report['moccae_only'] = true;
        $report['export_mode_label'] = 'MOCCAE Scope 1 & 2';
        $report['display_total_tonnes'] = $scope12Tonnes;
        $report['display_total_kg'] = $scope12Kg;

        $report['results_breakdown'] = array_values(array_filter(
            $report['results_breakdown'],
            fn ($scope) => in_array($scope['name'], ['Scope 1', 'Scope 2'], true)
        ));

        $report['activity_register'] = $report['activity_register']
            ->filter(fn ($row) => in_array($row['scope'], ['Scope 1', 'Scope 2'], true))
            ->values();

        $report['emission_source_data'] = $report['emission_source_data']
            ->filter(fn ($row) => in_array($row['scope'], ['Scope 1', 'Scope 2'], true))
            ->values();

        $report['scope_tonnes'] = [
            'Scope 1' => self::kgToTonnes($scope1Kg),
            'Scope 2' => self::kgToTonnes($scope2Kg),
        ];

        $report['scope_percentages'] = $scope12Kg > 0
            ? [
                round(($scope1Kg / $scope12Kg) * 100, 1),
                round(($scope2Kg / $scope12Kg) * 100, 1),
            ]
            : [0, 0];

        $report['scope_raw_tonnes'] = [
            number_format(self::kgToTonnes($scope1Kg), 2),
            number_format(self::kgToTonnes($scope2Kg), 2),
        ];

        $report['entry_count'] = $report['activity_register']->count();
        $report['has_scope_3'] = false;
        $report['scope_3_categories'] = collect();

        return $report;
    }

    public function finalizeReport(array $report, bool $moccaeOnly = false): array
    {
        $report['moccae_only'] = $moccaeOnly;
        $report['export_mode_label'] = $moccaeOnly ? 'MOCCAE Scope 1 & 2' : 'Full inventory (all scopes)';
        $report['display_total_tonnes'] = $moccaeOnly ? $report['scope_12_tonnes'] : $report['total_tonnes'];
        $report['display_total_kg'] = $moccaeOnly ? $report['scope_12_kg'] : $report['total_kg'];

        return $moccaeOnly ? $this->applyMoccaeOnly($report) : $report;
    }
}
