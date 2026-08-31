<?php

namespace App\Services;

use App\Models\Company;

/**
 * The Social pillar dashboard (/social).
 *
 * Like /environmental before it, /social pointed at EsgDashboardController --
 * the whole-ESG programme view -- so a Social URL rendered E+S+G content.
 *
 * STANDARD: GRI, using the codes already mapped in config/disclosure.php.
 *   GRI 2-7   headcount
 *   GRI 401-1 new hires and turnover
 *   GRI 403-9 LTIFR / injuries      403-10 fatalities
 *   GRI 404-1 average training hours
 *   GRI 405-1 diversity of governance bodies and employees
 *   GRI 414-1 new suppliers screened on social criteria
 *
 * STATUS IS DERIVED FROM THE DATA, NOT INVENTED. An indicator is "complete"
 * when the field has a value this year, "missing" when it does not. There is
 * deliberately no "draft" or "in review" state: no review workflow exists for
 * disclosure fields, and showing one would imply an approval step that cannot
 * happen.
 *
 * NOT BUILT, EACH FOR A REASON:
 *   - Headcount by function. No department/function dimension exists anywhere
 *     in the schema; it needs its own table, not a dashboard query.
 *   - Gender pay gap. GRI 405-2, and a genuine gap in the app's 405 coverage
 *     -- but it needs a field before it can be shown.
 *   - "Assign owner" / "Start collection". No data-owner or task model exists.
 */
class SocialDashboardService
{
    /**
     * Indicators shown in the table, in reporting order.
     *
     * Each maps to a real GRI code and a real field in config/disclosure.php.
     * 'format' controls presentation only.
     */
    protected const INDICATORS = [
        ['section' => 'social_hr',     'field' => 'employees_total',                      'label' => 'Total headcount',              'code' => 'GRI 2-7',    'format' => 'int'],
        ['section' => 'social_hr',     'field' => 'employees_new_hires',                  'label' => 'New employee hires',           'code' => 'GRI 401-1',  'format' => 'int'],
        ['section' => 'social_hr',     'field' => 'employees_turnover_percent',           'label' => 'Employee turnover',            'code' => 'GRI 401-1',  'format' => 'percent'],
        ['section' => 'social_hr',     'field' => 'training_hours_avg',                   'label' => 'Training hours per employee',  'code' => 'GRI 404-1',  'format' => 'decimal'],
        ['section' => 'social_hr',     'field' => 'parental_leave_return_rate',           'label' => 'Parental leave return rate',   'code' => 'GRI 401-3',  'format' => 'percent'],
        ['section' => 'diversity',     'field' => 'women_management_percent',             'label' => 'Women in management',          'code' => 'GRI 405-1',  'format' => 'percent'],
        ['section' => 'diversity',     'field' => 'women_workforce_percent',              'label' => 'Women in total workforce',     'code' => 'GRI 405-1',  'format' => 'percent'],
        ['section' => 'health_safety', 'field' => 'ltifr',                                'label' => 'LTIFR',                        'code' => 'GRI 403-9',  'format' => 'decimal'],
        ['section' => 'health_safety', 'field' => 'recordable_injuries',                  'label' => 'Recordable injuries',          'code' => 'GRI 403-9',  'format' => 'int'],
        ['section' => 'health_safety', 'field' => 'fatalities_total',                     'label' => 'Work-related fatalities',      'code' => 'GRI 403-10', 'format' => 'int'],
        ['section' => 'supply_chain',  'field' => 'suppliers_screened_social_percent',    'label' => 'Suppliers screened — social',  'code' => 'GRI 414-1',  'format' => 'percent'],
    ];

    /**
     * GRI 403 fields, for the Health & Safety readiness panel. LTIFR is the
     * only one the config marks required; the rest are what a complete 403
     * disclosure needs.
     */
    protected const HS_FIELDS = [
        'ltifr' => 'LTIFR (403-9)',
        'hours_worked' => 'Total hours worked',
        'recordable_injuries' => 'Recordable injuries',
        'fatalities_total' => 'Fatalities (403-10)',
        'ohs_management_system' => 'OHS management system (403-1)',
    ];

    public function __construct(
        protected DisclosureService $disclosureService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(Company $company, int $fiscalYear): array
    {
        $current = $this->disclosureService->griSectionsContent($company->id, $fiscalYear);
        $prior = $this->disclosureService->griSectionsContent($company->id, $fiscalYear - 1);

        return [
            'fiscal_year' => $fiscalYear,
            'prior_year' => $fiscalYear - 1,
            'kpis' => $this->kpis($current, $prior),
            'indicators' => $this->indicators($current, $prior),
            'health_safety' => $this->healthSafety($current),
        ];
    }

    /**
     * Four headline tiles. Each carries its own comparison where one is
     * meaningful: a headcount change is a PERCENT change, but a turnover
     * change is in PERCENTAGE POINTS -- reporting "turnover fell 14%" when it
     * moved 13.2% -> 11.4% would be wrong.
     *
     * @return list<array<string, mixed>>
     */
    protected function kpis(array $current, array $prior): array
    {
        $headcount = $this->value($current, 'social_hr', 'employees_total');
        $headcountPrior = $this->value($prior, 'social_hr', 'employees_total');

        $turnover = $this->value($current, 'social_hr', 'employees_turnover_percent');
        $turnoverPrior = $this->value($prior, 'social_hr', 'employees_turnover_percent');

        $women = $this->value($current, 'diversity', 'women_management_percent');
        $womenPrior = $this->value($prior, 'diversity', 'women_management_percent');

        $ltifr = $this->value($current, 'health_safety', 'ltifr');
        $ltifrPrior = $this->value($prior, 'health_safety', 'ltifr');

        return [
            [
                'label' => 'Total headcount',
                'code' => 'GRI 2-7',
                'value' => $headcount,
                'display' => $headcount !== null ? number_format($headcount) : null,
                'unit' => 'FTE',
                'delta' => $this->percentChange($headcount, $headcountPrior),
                'delta_kind' => 'percent',
                // More people is not inherently good or bad, so no colour.
                'direction' => 'neutral',
            ],
            [
                'label' => 'Employee turnover',
                'code' => 'GRI 401-1',
                'value' => $turnover,
                'display' => $turnover !== null ? number_format($turnover, 1) : null,
                'unit' => '%',
                'delta' => $this->pointChange($turnover, $turnoverPrior),
                'delta_kind' => 'points',
                'direction' => 'lower_better',
            ],
            [
                'label' => 'Women in management',
                'code' => 'GRI 405-1',
                'value' => $women,
                'display' => $women !== null ? number_format($women, 1) : null,
                'unit' => '%',
                'delta' => $this->pointChange($women, $womenPrior),
                'delta_kind' => 'points',
                'direction' => 'higher_better',
            ],
            [
                'label' => 'LTIFR',
                'code' => 'GRI 403-9',
                'value' => $ltifr,
                'display' => $ltifr !== null ? number_format($ltifr, 2) : null,
                'unit' => null,
                'delta' => $this->pointChange($ltifr, $ltifrPrior),
                'delta_kind' => 'points',
                'direction' => 'lower_better',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function indicators(array $current, array $prior): array
    {
        $rows = [];

        foreach (self::INDICATORS as $spec) {
            $now = $this->value($current, $spec['section'], $spec['field']);
            $was = $this->value($prior, $spec['section'], $spec['field']);

            $rows[] = [
                'label' => $spec['label'],
                'code' => $spec['code'],
                'current' => $now !== null ? $this->format($now, $spec['format']) : null,
                'prior' => $was !== null ? $this->format($was, $spec['format']) : null,
                'complete' => $now !== null,
            ];
        }

        return $rows;
    }

    /**
     * GRI 403 readiness. Replaces the design's "Headcount by function" chart,
     * which cannot be built -- no function dimension exists -- with the panel
     * the design actually needed: which H&S fields are still missing.
     *
     * @return array<string, mixed>
     */
    protected function healthSafety(array $current): array
    {
        $fields = [];
        $done = 0;

        foreach (self::HS_FIELDS as $field => $label) {
            $has = $this->value($current, 'health_safety', $field, true) !== null;
            $fields[] = ['label' => $label, 'complete' => $has];
            $done += $has ? 1 : 0;
        }

        $total = count(self::HS_FIELDS);

        return [
            'fields' => $fields,
            'complete' => $done,
            'total' => $total,
            'percent' => $total > 0 ? (int) round(($done / $total) * 100) : 0,
        ];
    }

    /**
     * A stored value, or null when absent or blank.
     *
     * $allowText lets the OHS narrative field count as present -- it is a
     * textarea, not a number, and 0 is a legitimate numeric answer for
     * injuries and fatalities so empty string is the only "missing" marker.
     */
    protected function value(array $sections, string $section, string $field, bool $allowText = false): float|string|null
    {
        $raw = $sections[$section][$field] ?? null;

        if ($raw === null || $raw === '') {
            return null;
        }

        if ($allowText && ! is_numeric($raw)) {
            return (string) $raw;
        }

        return is_numeric($raw) ? (float) $raw : null;
    }

    /**
     * Percent change, for counts. Null when either year is missing, or when
     * the prior year is zero -- a change from zero has no percentage.
     */
    protected function percentChange(?float $now, ?float $was): ?float
    {
        if ($now === null || $was === null || $was == 0.0) {
            return null;
        }

        return round((($now - $was) / $was) * 100, 1);
    }

    /**
     * Difference in PERCENTAGE POINTS, for rates. A rate moving 13.2 -> 11.4
     * is "-1.8 pts", never "-14%".
     */
    protected function pointChange(?float $now, ?float $was): ?float
    {
        if ($now === null || $was === null) {
            return null;
        }

        return round($now - $was, 1);
    }

    protected function format(float|string $value, string $format): string
    {
        if (! is_numeric($value)) {
            return (string) $value;
        }

        return match ($format) {
            'int' => number_format((float) $value),
            'percent' => number_format((float) $value, 1) . '%',
            default => number_format((float) $value, 2),
        };
    }
}
