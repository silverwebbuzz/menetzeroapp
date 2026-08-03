<?php

namespace App\Data;

/**
 * Company package choices for "Request a package" (Phase 4).
 * Features only — no AED. Commercial amounts live in admin / offline quotes (§5).
 */
class CompanyPackageOptions
{
    public const CODES = [
        'client_scope_basic',
        'client_scope_pro',
        'client_esg_starter',
        'client_esg_complete',
        'client_enterprise',
    ];

    /**
     * @return array<string, array{name: string, summary: string, features: list<string>}>
     */
    public static function packages(): array
    {
        return [
            'client_scope_basic' => [
                'name' => 'Scope Basic',
                'summary' => 'MOCCAE-ready Scope 1 & 2 inventories with clean exports',
                'features' => [
                    'Up to 3 sites',
                    'Scope 1 & 2 data entry + bulk import',
                    'Clean GHG inventory, MOCCAE PDF, Excel, IEQT',
                    'Best for official UAE inventory workflows',
                ],
            ],
            'client_scope_pro' => [
                'name' => 'Scope Pro',
                'summary' => 'Broader scopes plus ESG disclosure suite',
                'features' => [
                    'Up to 10 sites',
                    'Scope 1–3 capacity beyond Free limits',
                    'ESG disclosure suite exports',
                    'For teams needing deeper value-chain coverage',
                ],
            ],
            'client_esg_starter' => [
                'name' => 'ESG Starter',
                'summary' => 'Full ESG reporting pack for mid-size organisations',
                'features' => [
                    'Up to 5 sites',
                    'Full ESG report set + white-label / assurance options',
                    'IFRS / GRI oriented exports',
                    'For first integrated UAE ESG deliveries',
                ],
            ],
            'client_esg_complete' => [
                'name' => 'ESG Complete',
                'summary' => 'Larger portfolios and consolidation',
                'features' => [
                    'Up to 10 sites',
                    'Multi-entity consolidation options',
                    'Full ESG suite at larger scale',
                    'For groups with multiple legal entities',
                ],
            ],
            'client_enterprise' => [
                'name' => 'Enterprise',
                'summary' => 'Custom implementation and white-label',
                'features' => [
                    'Custom sites, seats, and workflows',
                    'White-label covers and deployment options',
                    'Extended KPI / assurance needs',
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
        $b = 'client_scope_basic';
        $p = 'client_scope_pro';
        $s = 'client_esg_starter';
        $c = 'client_esg_complete';
        $e = 'client_enterprise';

        $row = static function (string $label, array $cells) use ($b, $p, $s, $c, $e): array {
            return [
                'label' => $label,
                'cells' => [
                    $b => $cells[0],
                    $p => $cells[1],
                    $s => $cells[2],
                    $c => $cells[3],
                    $e => $cells[4],
                ],
            ];
        };

        return [
            'columns' => self::CODES,
            'sections' => [
                [
                    'title' => 'Limits & scope',
                    'rows' => [
                        $row('Sites / branches', ['Up to 3', 'Up to 10', 'Up to 5', 'Up to 10', 'Custom']),
                        $row('Legal entities', ['1', 'Up to 3', 'Unlimited', 'Unlimited', 'Custom']),
                        $row('Reporting years', ['2 years', '3 years', '+ 2 baseline', '+ 4 baseline', 'Custom']),
                        $row('Team members', ['5', '15', 'Unlimited', 'Unlimited', 'Custom']),
                        $row('GHG scopes covered', ['1 & 2', '1, 2 & 3', '1, 2 & 3', '1, 2 & 3', '1, 2 & 3']),
                    ],
                ],
                [
                    'title' => 'Data capture',
                    'rows' => [
                        $row('Guided Quick Input', [true, true, true, true, true]),
                        $row('Bulk CSV / Excel import', [true, true, true, true, true]),
                        $row('Evidence & calculation trail', [true, true, true, true, true]),
                        $row('HRIS bulk import template', [false, true, true, true, true]),
                        $row('Advanced / custom import templates', [false, false, true, true, true]),
                        $row('Scope 3 — GHG Protocol categories', [false, true, 'Broad', 'Broad', 'Custom']),
                    ],
                ],
                [
                    'title' => 'Dashboards & insight',
                    'rows' => [
                        $row('Emissions totals & scope cards', [true, true, true, true, true]),
                        $row('Monthly trend & carbon hotspots', [true, true, true, true, true]),
                        $row('Location / branch comparison', [true, true, true, true, true]),
                        $row('Disclosure readiness & executive view', [false, true, true, true, true]),
                    ],
                ],
                [
                    'title' => 'GHG & UAE reporting outputs',
                    'rows' => [
                        $row('Clean GHG Inventory PDF', [true, true, true, true, true]),
                        $row('MOCCAE-focused Scope 1 & 2 PDF', [true, true, true, true, true]),
                        $row('UAE IEQT export preparation', [true, true, true, true, true]),
                        $row('Excel results export', [true, true, true, true, true]),
                        $row('Scope 3 coverage & exclusion notes', [false, true, true, true, true]),
                    ],
                ],
                [
                    'title' => 'ESG disclosure outputs',
                    'rows' => [
                        $row('UAE ESG Report (integrated)', [false, true, true, true, true]),
                        $row('ESG Scorecard (multi-year KPIs)', [false, true, true, true, true]),
                        $row('IFRS S1 & S2 preparation', [false, true, true, true, true]),
                        $row('GRI reporting & content index', [false, true, true, true, true]),
                        $row('SASB sector indexes', [false, true, true, true, true]),
                        $row('White-label covers', [false, false, true, true, true]),
                        $row('Assurance-document support', [false, false, true, true, true]),
                    ],
                ],
                [
                    'title' => 'ESG management',
                    'rows' => [
                        $row('Materiality, stakeholders, supply chain', [false, true, true, true, true]),
                        $row('Climate risks & reduction targets', [false, true, true, true, true]),
                        $row('Expanded KPI library', [false, false, true, true, true]),
                    ],
                ],
                [
                    'title' => 'Team & organisation',
                    'rows' => [
                        $row('Custom roles & permissions', [true, true, true, true, true]),
                        $row('Location-scoped access', [false, true, true, true, true]),
                        $row('Multi-entity consolidation', [false, false, true, true, true]),
                    ],
                ],
            ],
        ];
    }
}
