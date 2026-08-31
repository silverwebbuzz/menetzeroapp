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
    public function build(Company $company, int $fiscalYear): array
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
