<?php

namespace App\Services;

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
            'entry_count' => $activityRegister->count(),
        ];
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
}
