<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyReportingSetting;
use App\Models\Location;

/**
 * The three E / S / G cards at the top of the Overview page.
 *
 * Pass 1 of the design-canvas Overview panel
 * (Menetzero-Redesign/MeNetZero Redesign.dc.html). Renders ONLY metrics that
 * already exist and carry a real framework code. Anything not yet collected
 * returns null and the card shows "not collected" -- the design's own amber
 * treatment -- rather than a zero, which would read as a measured result.
 *
 * WHAT THE HEADLINE NUMBER IS. It is DATA COMPLETENESS, not an ESG rating:
 * the share of that pillar's disclosure checks that have data, straight from
 * EsgDashboardService::score*(). There is no recognised GRI or SCA scoring
 * methodology behind an "82", and presenting one as a score would invite a
 * client to read it as an external rating. Completeness is honest, is
 * computable from config, and tells the user what is left to fill in.
 *
 * DELIBERATELY ABSENT (Pass 3 -- each needs a decision, not just code):
 *   - Board independence. The app stores board_diversity_percent, which is
 *     WOMEN on the board. Independence is NON-EXECUTIVE directors. Showing
 *     one under the other's label would be a misstatement in a regulated
 *     disclosure, so this card omits it until the field exists.
 *   - Policies "4 of 9". gov.policies is a disclosure SECTION EDITOR, not a
 *     register; there are no rows to count and no standard defining nine.
 *   - "ESG on board agenda: Quarterly". Not a GRI or SCA metric; needs a new
 *     enum before it can be shown.
 *
 * Everything here is READ-ONLY and additive. No existing service, view or
 * route changes behaviour because this class exists.
 */
class EsgPerformanceCardService
{
    public function __construct(
        protected EsgDashboardService $dashboard,
        protected EmissionsIntensityService $intensity,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(Company $company, int $fiscalYear, array $trend = []): array
    {
        $data = $this->dashboard->build($company, $fiscalYear);
        $rows = $this->scorecardRows($data['scorecard'] ?? [], $fiscalYear);
        $tonnes = (float) ($data['ghg_summary']['total_tonnes'] ?? 0);
        $hasGhg = (bool) ($data['ghg_summary']['has_data'] ?? false);

        return [
            'fiscal_year' => $fiscalYear,
            'context' => $this->context($company, $fiscalYear),
            'pillars' => [
                $this->environmental($data, $rows, $tonnes, $hasGhg, $company, $fiscalYear),
                $this->social($data, $rows),
                $this->governance($data, $rows),
            ],
            'frameworks' => $this->frameworks($data, $hasGhg, $fiscalYear),
            'pathway' => $this->pathway($data, $trend, $fiscalYear),
        ];
    }

    /**
     * Emissions against the reduction pathway.
     *
     * STANDARD. The pathway is a STRAIGHT LINE from the target's base year to
     * its target year -- "linear annual reduction", the convention the GHG
     * Protocol, SBTi and IFRS S2 all use for presenting a trajectory. SBTi
     * additionally requires a minimum annual rate, which is why sbti_aligned
     * is surfaced: an SBTi-validated target means the slope has been checked
     * against that floor. This method does NOT validate the rate; it draws the
     * target the company actually set.
     *
     * The line ends at the target's own tonnage, NOT at zero, unless the
     * target itself is zero. Drawing to zero would misrepresent a 50%
     * reduction target as a net-zero commitment.
     *
     * SCOPE COVERAGE IS THE TARGET'S, NOT THE TOTAL.
     * ReductionTargetProgressService::actualForCoverage() already sums only
     * the scopes a target covers, so a Scope 1+2 target is not measured
     * against a total that includes Scope 3. The coverage is labelled on the
     * card so the reader knows which scopes the line represents.
     *
     * PROJECTION IS LABELLED "at current rate", NEVER AS A FINDING. It
     * extrapolates the average annual change across the observed years, which
     * may be a handful of points projected across decades. It is arithmetic,
     * not a forecast, and it is omitted entirely when emissions are flat or
     * rising -- "net zero in 2400" is noise, and a rising trend has no
     * crossing point at all.
     *
     * Returns null when the company has no active reduction target: an
     * emissions chart with no pathway is the existing dashboard's job.
     *
     * @return array<string, mixed>|null
     */
    protected function pathway(array $data, array $trend, int $fiscalYear): ?array
    {
        $target = $data['next_target'] ?? null;

        // An EMPTY STATE rather than null: the card still renders, with an
        // empty plot and a prompt, so the user can see that a pathway will
        // appear here once a reduction target exists. Hiding it entirely
        // leaves no clue the feature is there at all.
        if ($target === null || ($target['baseline_tco2e'] ?? null) === null) {
            return [
                'empty' => true,
                'reason' => $target === null
                    ? 'No reduction target set yet.'
                    : 'This target has no baseline tonnage.',
                'cta_url' => \Illuminate\Support\Facades\Route::has('disclosures.s2.targets.index')
                    ? route('disclosures.s2.targets.index', ['fiscal_year' => $fiscalYear])
                    : null,
            ];
        }

        $baseYear = $target['base_year'] ?? null;
        $targetYear = $target['target_year'] ?? null;
        $baseline = (float) $target['baseline_tco2e'];
        $targetTonnes = $target['target_tco2e'] !== null ? (float) $target['target_tco2e'] : null;

        if ($baseYear === null || $targetYear === null || $targetYear <= $baseYear) {
            return [
                'empty' => true,
                'reason' => 'Target needs a base year and a later target year.',
                'cta_url' => \Illuminate\Support\Facades\Route::has('disclosures.s2.targets.index')
                    ? route('disclosures.s2.targets.index', ['fiscal_year' => $fiscalYear])
                    : null,
            ];
        }

        // Actual series, from the trend the dashboard already computed.
        $actual = [];
        foreach ($trend['labels'] ?? [] as $i => $label) {
            $year = (int) $label;
            if ($year >= $baseYear) {
                $actual[$year] = round((float) ($trend['values'][$i] ?? 0), 1);
            }
        }

        return [
            'empty' => false,
            'base_year' => $baseYear,
            'target_year' => $targetYear,
            'baseline' => round($baseline, 1),
            'target_tonnes' => $targetTonnes !== null ? round($targetTonnes, 1) : null,
            'current' => $target['current_tco2e'] !== null ? round((float) $target['current_tco2e'], 1) : null,
            'current_year' => $fiscalYear,
            'reduction_percent' => $target['change_vs_baseline_percent'] ?? null,
            'scope_label' => $target['scope_label'] ?? null,
            'sbti_aligned' => (bool) ($target['sbti_aligned'] ?? false),
            'target_is_derived' => (bool) ($target['target_is_derived'] ?? false),
            'actual' => $actual,
            'required' => $this->requiredLine($baseYear, $baseline, $targetYear, $targetTonnes),
            'projection' => $this->projectedYear($actual, $targetTonnes),
            // Carried over from the Net Zero Progress panel this card
            // replaces, so removing that panel loses nothing:
            //   achieved_percent -- share of the required reduction delivered
            //   years_remaining  -- to the target year
            'achieved_percent' => $target['achieved_percent'] ?? null,
            'years_remaining' => max(0, $targetYear - $fiscalYear),
        ];
    }

    /**
     * Straight line from baseline to the target's own tonnage.
     *
     * @return array<int, float>
     */
    protected function requiredLine(int $baseYear, float $baseline, int $targetYear, ?float $targetTonnes): array
    {
        $end = $targetTonnes ?? 0.0;
        $span = $targetYear - $baseYear;
        $line = [];

        // Endpoints plus each decade, so a 2022-2050 axis stays readable
        // instead of carrying 28 points.
        $years = [$baseYear];
        for ($y = (int) (ceil($baseYear / 10) * 10); $y < $targetYear; $y += 10) {
            $years[] = $y;
        }
        $years[] = $targetYear;

        foreach (array_unique($years) as $year) {
            $progress = ($year - $baseYear) / $span;
            $line[$year] = round($baseline + (($end - $baseline) * $progress), 1);
        }

        return $line;
    }

    /**
     * The year emissions reach the target AT THE CURRENT AVERAGE RATE.
     *
     * Null unless there are at least two observations AND emissions are
     * actually falling -- a flat or rising trend never reaches the target, and
     * a projection into the 2300s would be noise presented as insight.
     */
    protected function projectedYear(array $actual, ?float $targetTonnes): ?int
    {
        if (count($actual) < 2) {
            return null;
        }

        $years = array_keys($actual);
        $firstYear = $years[0];
        $lastYear = $years[array_key_last($years)];
        $first = $actual[$firstYear];
        $last = $actual[$lastYear];
        $span = $lastYear - $firstYear;

        if ($span < 1 || $last >= $first) {
            return null;
        }

        $perYear = ($first - $last) / $span;
        $end = $targetTonnes ?? 0.0;

        if ($perYear <= 0 || $last <= $end) {
            return null;
        }

        $projected = (int) ceil($lastYear + (($last - $end) / $perYear));

        // Beyond a century the number stops meaning anything.
        return $projected <= $lastYear + 100 ? $projected : null;
    }

    /**
     * Framework readiness bars.
     *
     * Pure reuse: DisclosureService already computes a weighted percent for
     * IFRS S2, IFRS S1 and GRI, and EsgDashboardService already returns all
     * three. Nothing here recomputes completeness.
     *
     * TWO ROWS NEED A RULE RATHER THAN A LOOKUP, and both are stated plainly:
     *
     *   GHG Protocol -- there is no completeness weighting for the inventory,
     *   so readiness is the share of the three scopes that have data. A
     *   company with Scope 1 and 2 but no Scope 3 reads 67%, which is the
     *   honest answer: its inventory is incomplete under the Protocol.
     *
     *   UAE ESG (SCA) -- UaeEsgReportService composes its report FROM S2, S1
     *   and GRI and publishes no percent of its own, so this is the mean of
     *   those three. It is a derived figure, not a separate assessment, and
     *   is labelled as composed in the view.
     *
     * @return list<array<string, mixed>>
     */
    protected function frameworks(array $data, bool $hasGhg, int $fiscalYear): array
    {
        $fw = $data['frameworks'] ?? [];
        $s2 = (int) ($fw['ifrs_s2']['percent'] ?? 0);
        $s1 = (int) ($fw['ifrs_s1']['percent'] ?? 0);
        $gri = (int) ($fw['gri']['percent'] ?? 0);

        $scopes = $data['ghg_summary'] ?? [];
        $withData = 0;
        foreach (['scope1', 'scope2', 'scope3'] as $scope) {
            if ((float) ($scopes[$scope] ?? 0) > 0) {
                $withData++;
            }
        }
        $ghgPercent = $hasGhg ? (int) round(($withData / 3) * 100) : 0;

        $year = ['fiscal_year' => $fiscalYear];

        return [
            $this->framework('IFRS S2 — Climate', $s2, 'e', 'disclosures.s2.overview', $year),
            $this->framework('GHG Protocol inventory', $ghgPercent, 'e', 'reports.index', []),
            $this->framework('IFRS S1 — Sustainability', $s1, 'g', 'disclosures.s1.overview', $year),
            $this->framework('GRI Standards', $gri, 's', 'disclosures.gri.overview', $year),
            $this->framework(
                'UAE ESG (SCA) report',
                (int) round(($s2 + $s1 + $gri) / 3),
                'g',
                'disclosures.uae-esg.overview',
                $year,
                'Composed from IFRS S2, IFRS S1 and GRI'
            ),
        ];
    }

    /**
     * A row whose route is missing is still shown, without a link -- readiness
     * is information in its own right and must not vanish because a route was
     * renamed.
     */
    protected function framework(string $label, int $percent, string $pillar, string $route, array $params, ?string $note = null): array
    {
        return [
            'label' => $label,
            'percent' => max(0, min(100, $percent)),
            'pillar' => $pillar,
            'note' => $note,
            'url' => \Illuminate\Support\Facades\Route::has($route) ? route($route, $params) : null,
        ];
    }

    /**
     * "Al Maktoum Industries LLC · FY 2025 · 7 sites · consolidated, operational control"
     */
    protected function context(Company $company, int $fiscalYear): array
    {
        $sites = Location::where('company_id', $company->id)
            ->where('is_active', true)
            ->count();

        // Queried directly, as EmissionsIntensityService does -- Company has
        // no reportingSetting relation, and the row is per fiscal year.
        $approach = CompanyReportingSetting::where('company_id', $company->id)
            ->where('fiscal_year', $fiscalYear)
            ->value('consolidation_approach');

        return [
            'company' => $company->name,
            'fiscal_year' => $fiscalYear,
            'sites' => $sites,
            // Null rather than a guessed default: the consolidation approach
            // is a methodology statement and must not be invented.
            'consolidation' => is_string($approach) && $approach !== ''
                ? str_replace('_', ' ', $approach)
                : null,
        ];
    }

    /**
     * Flatten the scorecard's categories into key => current-year value.
     *
     * @return array<string, float|null>
     */
    protected function scorecardRows(array $scorecard, int $fiscalYear): array
    {
        $out = [];

        foreach ($scorecard['categories'] ?? [] as $category) {
            foreach ($category['rows'] ?? [] as $row) {
                $key = $row['key'] ?? null;
                if ($key === null) {
                    continue;
                }

                $value = $row['values'][$fiscalYear] ?? null;
                $out[$key] = is_numeric($value) ? (float) $value : null;
            }
        }

        return $out;
    }

    protected function environmental(array $data, array $rows, float $tonnes, bool $hasGhg, Company $company, int $fiscalYear): array
    {
        // forYear() returns null when no intensity denominator is configured.
        $intensity = $hasGhg ? $this->intensity->forYear($company, $fiscalYear, $tonnes) : null;

        return [
            'code' => 'E',
            'pillar' => 'e',
            'title' => 'Environmental',
            'subtitle' => 'Climate, energy, waste, water',
            'percent' => $data['environmental']['percent'] ?? 0,
            'checks' => $data['environmental']['checks'] ?? [],
            'metrics' => [
                $this->metric('Gross emissions', $hasGhg ? $tonnes : null, 'tCO2e', 1),
                $this->metric(
                    'Intensity',
                    $intensity['value'] ?? null,
                    $intensity['unit'] ?? null,
                    4
                ),
                $this->metric('Renewable share', $rows['renewable_energy_percent'] ?? null, '%', 0),
            ],
        ];
    }

    protected function social(array $data, array $rows): array
    {
        return [
            'code' => 'S',
            'pillar' => 's',
            'title' => 'Social',
            'subtitle' => 'Workforce, safety, community',
            'percent' => $data['social']['percent'] ?? 0,
            'checks' => $data['social']['checks'] ?? [],
            'metrics' => [
                $this->metric('Headcount', $rows['employees_total'] ?? null, null, 0),
                $this->metric('Employee turnover', $rows['turnover_percent'] ?? null, '%', 1),
                $this->metric('LTIFR', $rows['ltifr'] ?? null, null, 2, 'GRI 403-9'),
                $this->metric('Women in management', $rows['women_management_percent'] ?? null, '%', 1, 'GRI 405-1'),
            ],
        ];
    }

    protected function governance(array $data, array $rows): array
    {
        return [
            'code' => 'G',
            'pillar' => 'g',
            'title' => 'Governance',
            'subtitle' => 'Board, ethics, policy, risk',
            'percent' => $data['governance']['percent'] ?? 0,
            'checks' => $data['governance']['checks'] ?? [],
            'metrics' => [
                $this->metric('Women on board', $rows['board_women_percent'] ?? null, '%', 1, 'GRI 405-1'),
                $this->metric('Ethics incidents', $rows['ethics_incidents'] ?? null, null, 0, 'GRI 205-3'),
                $this->metric('Data breaches', $rows['data_breaches'] ?? null, null, 0, 'GRI 418-1'),
                $this->metric('Supplier audits', $rows['supplier_audits'] ?? null, null, 0),
            ],
        ];
    }

    /**
     * A null value renders as "not collected" -- never as 0, which would read
     * as a measured result of zero.
     */
    protected function metric(string $label, ?float $value, ?string $unit, int $decimals, ?string $code = null): array
    {
        return [
            'label' => $label,
            'code' => $code,
            'collected' => $value !== null,
            'display' => $value === null
                ? 'not collected'
                : number_format($value, $decimals) . ($unit ? ' ' . $unit : ''),
        ];
    }
}
