<?php

namespace App\Data;

/**
 * Company package choices for "Request a package" (Phase 4).
 * Features only — no AED. Commercial amounts live in admin / offline quotes (§5).
 */
class CompanyPackageOptions
{
    /**
     * Codes a request may carry. Validated on submit, so a retired code left
     * here would let somebody activate a plan that is no longer sold.
     */
    public const CODES = [
        'client_carbon',
        'client_esg',
        'client_enterprise',
    ];

    /**
     * @return array<string, array{name: string, summary: string, features: list<string>}>
     */
    /**
     * Packages a company can request, in ladder order.
     *
     * The four-tier catalogue (section 65). Carbon is the GHG inventory and its
     * UAE filings; ESG adds the framework reports on top. Codes match
     * PlanEntitlementDefaults, so a request maps straight to a plan.
     *
     * Retired codes are deliberately absent: this list is what a NEW request
     * may select, and offering a plan that is no longer sold would activate a
     * subscription nobody can renew. Existing subscribers keep their plan --
     * grandfathering lives in PlanEntitlementDefaults::LEGACY_PLAN_CODES, not
     * here.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function packages(): array
    {
        return [
            'client_carbon' => [
                'name' => 'Carbon',
                'summary' => 'Full Scope 1-3 inventory with UAE regulatory reports',
                'features' => [
                    'Up to 5 sites, 10 users',
                    'Scope 1, 2 and 3 data entry with bulk import',
                    'GHG inventory, MOCCAE PDF, IEQT and Excel exports',
                    'Reduction targets and net-zero pathway',
                    'For companies filing an official UAE inventory',
                ],
            ],
            'client_esg' => [
                'name' => 'ESG',
                'summary' => 'Everything in Carbon plus the disclosure frameworks',
                'features' => [
                    'Up to 5 sites, 10 users, 5 years of history',
                    'IFRS S1 and S2, GRI with content index, SASB index',
                    'UAE ESG report',
                    'Materiality matrix, stakeholder, supply chain and risk registers',
                    'For companies publishing an ESG or sustainability report',
                ],
            ],
            'client_enterprise' => [
                'name' => 'Enterprise',
                'summary' => 'Multi-entity groups and assurance-ready reporting',
                'features' => [
                    'Unlimited sites and seats',
                    'Multi-entity consolidation',
                    'Assurance support and evidence trail',
                    'SSO, custom emission factors, white-label covers',
                    'Talk through requirements with MENetZero',
                ],
            ],
        ];
    }

    public static function label(string $code): string
    {
        return self::packages()[$code]['name'] ?? $code;
    }

    /** Optional extras the company can tick (no prices). */
    public static function extraOptions(): array
    {
        return [
            'extra_sites' => 'Additional sites beyond the package default',
            'extra_seats' => 'Extra team seats',
            'scope3_intensity' => 'Higher Scope 3 / value-chain intensity',
            'white_label' => 'White-label report covers',
            'assurance' => 'Assurance PDF upload / workflow',
        ];
    }

    /**
     * Feature comparison matrix aligned to documentation/MENetZero_Features_Pricing.xlsx
     * (Client Plans sheet) — capabilities only, no AED.
     *
     * Cell values: true = included, false = not included, string = limited / note.
     *
     * @return array{
     *   columns: list<string>,
     *   sections: list<array{title: string, rows: list<array{label: string, cells: array<string, bool|string>}>}>
     * }
     */
    public static function comparisonMatrix(): array
    {
        // Three columns, matching packages(): Carbon, ESG, Enterprise.
        $c = 'client_carbon';
        $g = 'client_esg';
        $e = 'client_enterprise';

        $row = static function (string $label, array $cells) use ($c, $g, $e): array {
            return [
                'label' => $label,
                'cells' => [
                    $c => $cells[0],
                    $g => $cells[1],
                    $e => $cells[2],
                ],
            ];
        };

        return [
            'columns' => self::CODES,
            'sections' => [
                [
                    'title' => 'Limits & scope',
                    'rows' => [
                        $row('Sites / branches', ['Up to 5', 'Up to 5', 'Unlimited']),
                        $row('Legal entities', ['1', '1', 'Unlimited']),
                        $row('Reporting years', ['3 years', '5 years', 'Unlimited']),
                        $row('Team members', ['10', '10', 'Unlimited']),
                        $row('GHG scopes covered', ['1, 2 & 3', '1, 2 & 3', '1, 2 & 3']),
                    ],
                ],
                [
                    'title' => 'Data capture',
                    'rows' => [
                        $row('Guided Quick Input', [true, true, true]),
                        $row('Bulk CSV / Excel import', [true, true, true]),
                        $row('Evidence & calculation trail', [true, true, true]),
                        $row('HRIS bulk import template', [false, true, true]),
                        $row('Advanced / custom import templates', [false, false, true]),
                        $row('Scope 3 — GHG Protocol categories', [true, true, 'Custom']),
                    ],
                ],
                [
                    'title' => 'Dashboards & insight',
                    'rows' => [
                        $row('Emissions totals & scope cards', [true, true, true]),
                        $row('Monthly trend & carbon hotspots', [true, true, true]),
                        $row('Location / branch comparison', [true, true, true]),
                        $row('Disclosure readiness & executive view', [false, true, true]),
                    ],
                ],
                [
                    'title' => 'GHG & UAE reporting outputs',
                    'rows' => [
                        $row('Clean GHG Inventory PDF', [true, true, true]),
                        $row('MOCCAE-focused Scope 1 & 2 PDF', [true, true, true]),
                        $row('UAE IEQT export preparation', [true, true, true]),
                        $row('Excel results export', [true, true, true]),
                        $row('Scope 3 coverage & exclusion notes', [true, true, true]),
                    ],
                ],
                [
                    'title' => 'ESG disclosure outputs',
                    'rows' => [
                        $row('UAE ESG Report (integrated)', [false, true, true]),
                        $row('ESG Scorecard (multi-year KPIs)', [false, true, true]),
                        $row('IFRS S1 & S2 preparation', [false, true, true]),
                        $row('GRI reporting & content index', [false, true, true]),
                        $row('SASB sector indexes', [false, true, true]),
                        $row('White-label covers', [false, false, true]),
                        $row('Assurance-document support', [false, false, true]),
                    ],
                ],
                [
                    'title' => 'ESG management',
                    'rows' => [
                        $row('Materiality, stakeholders, supply chain', [false, true, true]),
                        $row('Climate risks & reduction targets', [false, true, true]),
                        $row('Expanded KPI library', [false, true, true]),
                    ],
                ],
                [
                    'title' => 'Team & organisation',
                    'rows' => [
                        $row('Custom roles & permissions', [true, true, true]),
                        $row('Location-scoped access', [true, true, true]),
                        $row('Multi-entity consolidation', [false, false, true]),
                    ],
                ],
            ],
        ];
    }
}
