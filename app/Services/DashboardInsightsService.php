<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Location;
use App\Models\Measurement;
use Illuminate\Support\Collection;

class DashboardInsightsService
{
    public function __construct(protected DisclosureService $disclosureService)
    {
    }

    /**
     * @return array{labels: list<string>, values: list<float>, sparklines: array<string, list<float>>}
     */
    public function twelveMonthTrend(Collection $measurements): array
    {
        $labels = [];
        $values = [];
        $byScope = ['total' => [], 'scope1' => [], 'scope2' => [], 'scope3' => []];

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $key = $month->format('Y-m');
            $labels[] = $month->format('M');

            $monthMeasurements = $measurements->filter(
                fn ($m) => $m->period_start && $m->period_start->format('Y-m') === $key
            );

            $total = round((float) $monthMeasurements->sum('total_co2e'), 2);
            $values[] = GhgReportService::kgToTonnes($total);

            $byScope['total'][] = $total;
            $byScope['scope1'][] = (float) $monthMeasurements->sum('scope_1_co2e');
            $byScope['scope2'][] = (float) $monthMeasurements->sum('scope_2_co2e');
            $byScope['scope3'][] = (float) $monthMeasurements->sum('scope_3_co2e');
        }

        $sparklines = [];
        foreach ($byScope as $key => $series) {
            $sparklines[$key] = array_map(
                fn ($v) => round(GhgReportService::kgToTonnes($v), 2),
                $series
            );
        }

        return compact('labels', 'values', 'sparklines');
    }

    /**
     * @return array{current_year: float, previous_year: float, change_pct: float|null}
     */
    public function yearOverYear(Collection $measurements): array
    {
        $currentYear = now()->year;
        $previousYear = $currentYear - 1;

        $current = (float) $measurements
            ->filter(fn ($m) => $m->period_start && $m->period_start->year === $currentYear)
            ->sum('total_co2e');

        $previous = (float) $measurements
            ->filter(fn ($m) => $m->period_start && $m->period_start->year === $previousYear)
            ->sum('total_co2e');

        $changePct = $previous > 0
            ? round((($current - $previous) / $previous) * 100, 1)
            : null;

        return [
            'current_year' => round(GhgReportService::kgToTonnes($current), 2),
            'previous_year' => round(GhgReportService::kgToTonnes($previous), 2),
            'change_pct' => $changePct,
        ];
    }

    /**
     * @return list<array{key: string, title: string, status: string, percent: int, url: string}>
     */
    public function complianceStatus(int $companyId, int $fiscalYear, bool $hasInventoryData): array
    {
        $statusFromPercent = fn (int $percent): string => match (true) {
            $percent >= 100 => 'complete',
            $percent >= 40 => 'pending',
            default => 'draft',
        };

        $s2 = $this->disclosureService->completenessS2($companyId, $fiscalYear);
        $s1 = $this->disclosureService->completenessS1($companyId, $fiscalYear);
        $gri = $this->disclosureService->completenessGri($companyId, $fiscalYear);

        $ghgPercent = $hasInventoryData ? 100 : 0;

        return [
            [
                'key' => 'ghg',
                'title' => 'GHG Inventory',
                'status' => $statusFromPercent($ghgPercent),
                'percent' => $ghgPercent,
                'url' => route('reports.index'),
            ],
            [
                'key' => 'ifrs-s1',
                'title' => 'IFRS S1',
                'status' => $statusFromPercent((int) ($s1['percent'] ?? 0)),
                'percent' => (int) ($s1['percent'] ?? 0),
                'url' => route('disclosures.s1.overview', ['fiscal_year' => $fiscalYear]),
            ],
            [
                'key' => 'ifrs-s2',
                'title' => 'IFRS S2',
                'status' => $statusFromPercent((int) ($s2['percent'] ?? 0)),
                'percent' => (int) ($s2['percent'] ?? 0),
                'url' => route('disclosures.s2.overview', ['fiscal_year' => $fiscalYear]),
            ],
            [
                'key' => 'gri',
                'title' => 'GRI',
                'status' => $statusFromPercent((int) ($gri['percent'] ?? 0)),
                'percent' => (int) ($gri['percent'] ?? 0),
                'url' => route('disclosures.gri.overview', ['fiscal_year' => $fiscalYear]),
            ],
        ];
    }

    /**
     * @return list<array{text: string, priority: string}>
     */
    public function recommendations(?Company $company, Collection $measurements, array $kpis): array
    {
        $items = [];

        if (!$company || Location::where('company_id', $company->id)->count() === 0) {
            $items[] = [
                'text' => 'Add at least one business location to begin emissions tracking and reporting.',
                'priority' => 'high',
            ];
        }

        if (($kpis['total_emissions'] ?? 0) <= 0) {
            $items[] = [
                'text' => 'Enter electricity or fuel data via Quick Input — start with monthly DEWA bills for UAE offices.',
                'priority' => 'high',
            ];
        }

        if (($kpis['scope3_total'] ?? 0) <= 0 && ($kpis['scope1_total'] ?? 0) + ($kpis['scope2_total'] ?? 0) > 0) {
            $items[] = [
                'text' => 'Scope 3 is empty — add business travel, commuting, or purchased goods to complete your value chain footprint.',
                'priority' => 'medium',
            ];
        }

        if (($kpis['scope1_total'] ?? 0) > ($kpis['scope2_total'] ?? 0) * 1.5 && ($kpis['scope1_total'] ?? 0) > 0) {
            $items[] = [
                'text' => 'Scope 1 dominates your footprint — review fleet fuel and on-site diesel consumption at high-emission locations.',
                'priority' => 'medium',
            ];
        }

        if (($kpis['monthly_change'] ?? 0) > 10) {
            $items[] = [
                'text' => 'Emissions increased more than 10% vs last month — validate recent utility bills and fleet entries for anomalies.',
                'priority' => 'high',
            ];
        }

        $draftCount = $measurements->where('status', 'draft')->count();
        if ($draftCount > 0) {
            $items[] = [
                'text' => "You have {$draftCount} draft measurement(s) — submit or verify entries before external reporting.",
                'priority' => 'low',
            ];
        }

        if (empty($items)) {
            $items[] = [
                'text' => 'Inventory looks healthy — proceed to complete IFRS S2 and GRI disclosures for your reporting year.',
                'priority' => 'low',
            ];
        }

        return array_slice($items, 0, 4);
    }
}
