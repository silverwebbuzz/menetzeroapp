<?php

namespace App\Services;

use App\Models\CompanyReportingSetting;
use App\Models\Company;
use App\Models\Measurement;

/**
 * The Environmental pillar dashboard (/environmental).
 *
 * A DIFFERENT QUESTION from the ESG dashboard. EsgDashboardService answers
 * "how is my whole ESG programme doing" across all three pillars; this answers
 * "what are my emissions". /environmental previously reused the former, which
 * is why it showed E+S+G content under an Environmental heading.
 *
 * NOTHING IS REPLACED. The E/S/G scorecards on /dashboard are untouched --
 * that is a different controller, view and partial. This class is additive.
 *
 * STANDARD: GHG Protocol Corporate Standard throughout.
 *   - Scope 1 / 2 / 3 split is required reporting.
 *   - Scope 2 is reported LOCATION-BASED here, which is the default
 *     presentation; the market-based figure is a separate disclosure and
 *     GhgReportService::buildScope2Split() already computes both.
 *   - Scope 3 completeness is measured against the standard's FIFTEEN
 *     categories, using the slug->category mapping already written in
 *     GhgReportService::buildScope3CoverageMatrix().
 *   - The base-year comparison is required, and is taken from the company's
 *     own ReductionTarget rather than assumed.
 *
 * DATA QUALITY: the source table's status comes from the MEASUREMENT's real
 * enum (draft / submitted / under_review / not_verified / verified). There is
 * deliberately no "estimated" status: the schema has no estimated-vs-measured
 * flag, and inventing one would misrepresent data quality. Flagging estimated
 * data IS a GHG Protocol expectation, so this is a real gap -- but a schema
 * change, not a dashboard one.
 */
class EnvironmentalDashboardService
{
    public function __construct(
        protected IfrsS2ReportService $s2ReportService,
        protected GhgReportService $ghgReportService,
        protected ReductionTargetProgressService $targetProgress,
        protected DashboardInsightsService $insights,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(Company $company, int $fiscalYear): array
    {
        $report = $this->s2ReportService->build($company, $fiscalYear);
        $ghg = $report['ghg'] ?? [];

        $measurements = $this->measurementsFor($company, $fiscalYear);
        $priorYear = $fiscalYear - 1;

        return [
            'fiscal_year' => $fiscalYear,
            'prior_year' => $priorYear,
            'kpis' => $this->kpis($company, $fiscalYear, $ghg, $measurements),
            'sources' => $this->sources($company, $fiscalYear, $priorYear),
            'scope_mix' => $this->scopeMix($ghg),
            'trend' => $this->trend($company),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, Measurement>
     */
    protected function measurementsFor(Company $company, int $fiscalYear)
    {
        return Measurement::whereHas('location', fn ($q) => $q->where('company_id', $company->id))
            ->where('fiscal_year', $fiscalYear)
            ->get();
    }

    /**
     * Four headline tiles: gross, then each scope with its share.
     *
     * @return array<string, mixed>
     */
    protected function kpis(Company $company, int $fiscalYear, array $ghg, $measurements): array
    {
        $tonnes = $ghg['scope_tonnes'] ?? [];
        $total = (float) ($ghg['total_tonnes'] ?? 0);
        $hasData = (bool) ($ghg['has_data'] ?? false);

        $share = fn (float $v) => $total > 0 ? (int) round(($v / $total) * 100) : null;

        return [
            'has_data' => $hasData,
            'total' => $total,
            'baseline_change' => $this->baselineChange($company, $fiscalYear),
            'scope1' => [
                'tonnes' => (float) ($tonnes['Scope 1'] ?? 0),
                'share' => $share((float) ($tonnes['Scope 1'] ?? 0)),
            ],
            'scope2' => [
                'tonnes' => (float) ($tonnes['Scope 2'] ?? 0),
                'share' => $share((float) ($tonnes['Scope 2'] ?? 0)),
            ],
            'scope3' => [
                'tonnes' => (float) ($tonnes['Scope 3'] ?? 0),
                'coverage' => $this->scope3Coverage($company, $fiscalYear, $measurements),
            ],
        ];
    }

    /**
     * "-18.0% vs FY22" -- from the company's own target baseline, never a
     * guessed prior year. Null when no target with a baseline exists.
     *
     * @return array{percent: float, base_year: int}|null
     */
    protected function baselineChange(Company $company, int $fiscalYear): ?array
    {
        $progress = $this->targetProgress->build($company, $fiscalYear);
        $target = $this->targetProgress->nextTarget($progress);

        if ($target === null
            || ($target['change_vs_baseline_percent'] ?? null) === null
            || ($target['base_year'] ?? null) === null) {
            return null;
        }

        return [
            'percent' => (float) $target['change_vs_baseline_percent'],
            'base_year' => (int) $target['base_year'],
        ];
    }

    /**
     * Scope 3 completeness against the GHG Protocol's fifteen categories.
     *
     * Counts categories with DATA, and separately how many the company's own
     * policy says are in scope -- an excluded category is not a gap, and the
     * standard requires exclusions to be justified rather than silently
     * dropped.
     *
     * @return array{reported: int, total: int, included: int}
     */
    protected function scope3Coverage(Company $company, int $fiscalYear, $measurements): array
    {
        $settings = CompanyReportingSetting::where('company_id', $company->id)
            ->where('fiscal_year', $fiscalYear)
            ->first();

        $reported = [];
        $included = [];

        foreach ($measurements as $measurement) {
            foreach ($this->ghgReportService->buildScope3CoverageMatrix($measurement, $settings) as $row) {
                if (! empty($row['has_data'])) {
                    $reported[$row['category']] = true;
                }
                if (! empty($row['policy_included'])) {
                    $included[$row['category']] = true;
                }
            }
        }

        return [
            'reported' => count($reported),
            'included' => count($included),
            'total' => count(CompanyReportingSetting::SCOPE3_CATEGORIES),
        ];
    }

    /**
     * Emissions by source, this year against last.
     *
     * buildResultsBreakdown() works on ONE measurement, so results are merged
     * across every measurement in the year and joined to the prior year BY
     * SOURCE NAME -- the only stable key the breakdown exposes.
     *
     * @return list<array<string, mixed>>
     */
    protected function sources(Company $company, int $fiscalYear, int $priorYear): array
    {
        $current = $this->sourceTotals($company, $fiscalYear);
        $prior = $this->sourceTotals($company, $priorYear);

        $names = array_unique(array_merge(array_keys($current), array_keys($prior)));

        $rows = [];
        foreach ($names as $name) {
            $rows[] = [
                'name' => $name,
                'scope' => $current[$name]['scope'] ?? $prior[$name]['scope'] ?? null,
                'current' => $current[$name]['tonnes'] ?? null,
                'prior' => $prior[$name]['tonnes'] ?? null,
                'status' => $current[$name]['status'] ?? null,
                'status_label' => $current[$name]['status_label'] ?? null,
                'status_color' => $current[$name]['status_color'] ?? null,
            ];
        }

        // Largest contributor first: the table is for finding what dominates.
        usort($rows, fn ($a, $b) => ($b['current'] ?? 0) <=> ($a['current'] ?? 0));

        return $rows;
    }

    /**
     * Source name => tonnes + the weakest status among its measurements.
     *
     * WEAKEST, not latest: if any measurement feeding a source is still a
     * draft, the source line is not verified. Reporting the strongest status
     * would overstate assurance.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function sourceTotals(Company $company, int $fiscalYear): array
    {
        $rank = ['draft' => 0, 'submitted' => 1, 'under_review' => 2, 'not_verified' => 3, 'verified' => 4];
        $out = [];

        foreach ($this->measurementsFor($company, $fiscalYear) as $measurement) {
            foreach ($this->ghgReportService->buildResultsBreakdown($measurement) as $scopeRow) {
                foreach ($scopeRow['children'] ?? [] as $child) {
                    $name = $child['name'];

                    if (! isset($out[$name])) {
                        $out[$name] = [
                            'tonnes' => 0.0,
                            'scope' => $scopeRow['name'],
                            'status' => $measurement->status,
                            'status_label' => $measurement->status_display,
                            'status_color' => $measurement->status_color,
                        ];
                    }

                    $out[$name]['tonnes'] += (float) $child['tonnes'];

                    $seen = $rank[$measurement->status] ?? 0;
                    $held = $rank[$out[$name]['status']] ?? 0;
                    if ($seen < $held) {
                        $out[$name]['status'] = $measurement->status;
                        $out[$name]['status_label'] = $measurement->status_display;
                        $out[$name]['status_color'] = $measurement->status_color;
                    }
                }
            }
        }

        return $out;
    }

    /**
     * Emissions by year, stacked by scope.
     *
     * Straight reuse of DashboardInsightsService::yearlyTrend(), the same
     * computation the main dashboard's trend chart uses -- so a year's total
     * here can never disagree with the one shown there.
     *
     * ALL YEARS, not just up to the selected one: the point of a trend is the
     * shape over time, and truncating at the selected year would hide a later
     * year's data from someone reviewing an earlier one.
     *
     * @return array<string, mixed>
     */
    protected function trend(Company $company): array
    {
        $measurements = Measurement::whereHas('location', fn ($q) => $q->where('company_id', $company->id))
            ->get();

        $trend = $this->insights->yearlyTrend($measurements);

        // Per-year maximum drives the bar heights; a zero max would divide by
        // zero in the view, so it is floored at 1.
        $max = 0.0;
        foreach ($trend['values'] ?? [] as $value) {
            $max = max($max, (float) $value);
        }

        return [
            'labels' => $trend['labels'] ?? [],
            'values' => $trend['values'] ?? [],
            'scope1' => $trend['scope1'] ?? [],
            'scope2' => $trend['scope2'] ?? [],
            'scope3' => $trend['scope3'] ?? [],
            'max' => max($max, 1.0),
            'has_multiple' => (bool) ($trend['has_multiple'] ?? false),
        ];
    }

    /**
     * Donut segments. Zero-tonne scopes are kept so the legend always shows
     * all three -- a missing Scope 3 is information, not an empty slot.
     *
     * @return list<array<string, mixed>>
     */
    protected function scopeMix(array $ghg): array
    {
        $tonnes = $ghg['scope_tonnes'] ?? [];
        $total = (float) ($ghg['total_tonnes'] ?? 0);

        $out = [];
        foreach (['Scope 1', 'Scope 2', 'Scope 3'] as $scope) {
            $value = (float) ($tonnes[$scope] ?? 0);
            $out[] = [
                'label' => $scope,
                'tonnes' => $value,
                'percent' => $total > 0 ? round(($value / $total) * 100, 1) : 0.0,
            ];
        }

        return $out;
    }
}
