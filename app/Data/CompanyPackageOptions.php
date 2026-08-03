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
}
