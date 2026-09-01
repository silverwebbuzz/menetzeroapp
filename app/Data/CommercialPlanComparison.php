<?php

namespace App\Data;

/**
 * Commercial Plan v1 upgrade comparison tables (COMMERCIAL_PLAN_V1.md §7.3).
 *
 * Cell values: true = ✓, false = —, string = verbatim.
 */
class CommercialPlanComparison
{
    public const PLAN_COLUMNS = [
        'client_free',
        'client_carbon',
        'client_esg',
        'client_enterprise',
    ];

    /**
     * @return array<int, array{label: string, cells: array<string, bool|string>}>
     */
    public static function operationsRows(): array
    {
        return [
            [
                'label' => 'Locations / branches',
                'cells' => [
                    'client_free' => '1',
                    'client_carbon' => '5',
                    'client_esg' => '5',
                    'client_enterprise' => 'Unlimited',
                ],
            ],
            [
                'label' => 'Users',
                'cells' => [
                    'client_free' => '2',
                    'client_carbon' => '10',
                    'client_esg' => '10',
                    'client_enterprise' => 'Unlimited',
                ],
            ],
            [
                'label' => 'Scope 1 & 2 Quick Input',
                'cells' => [
                    'client_free' => true,
                    'client_carbon' => true,
                    'client_esg' => true,
                    'client_enterprise' => true,
                ],
            ],
            [
                'label' => 'Bulk CSV / XLS import',
                'cells' => [
                    'client_free' => false,
                    'client_carbon' => true,
                    'client_esg' => true,
                    'client_enterprise' => true,
                ],
            ],
            [
                'label' => 'Bulk data export',
                'cells' => [
                    'client_free' => false,
                    'client_carbon' => true,
                    'client_esg' => true,
                    'client_enterprise' => true,
                ],
            ],
            [
                'label' => 'Scope 3',
                'cells' => [
                    'client_free' => '1 entry / category',
                    'client_carbon' => '12 entries / category',
                    'client_esg' => '1 entry / category',
                    'client_enterprise' => 'Unlimited',
                ],
            ],
            [
                'label' => 'Help guide',
                'cells' => [
                    'client_free' => 'Basic',
                    'client_carbon' => 'Full',
                    'client_esg' => 'Full + disclosures',
                    'client_enterprise' => 'Full + training',
                ],
            ],
            [
                'label' => 'Disclosure forms (IFRS / GRI)',
                'cells' => [
                    'client_free' => 'Preview only',
                    'client_carbon' => 'Preview only',
                    'client_esg' => 'Preview + export',
                    'client_enterprise' => 'Full',
                ],
            ],
            [
                'label' => 'Consultant directory',
                'cells' => [
                    'client_free' => 'Teaser',
                    'client_carbon' => 'Request intro',
                    'client_esg' => 'Full connect',
                    'client_enterprise' => 'Priority',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{label: string, cells: array<string, bool|string>}>
     */
    public static function downloadRows(): array
    {
        return [
            [
                'label' => 'GHG Inventory PDF',
                'cells' => [
                    'client_free' => 'Watermarked',
                    'client_carbon' => true,
                    'client_esg' => true,
                    'client_enterprise' => true,
                ],
            ],
            [
                'label' => 'MOCCAE S1 & 2 PDF',
                'cells' => [
                    'client_free' => 'Watermarked',
                    'client_carbon' => true,
                    'client_esg' => true,
                    'client_enterprise' => true,
                ],
            ],
            [
                'label' => 'Excel results export',
                'cells' => [
                    'client_free' => 'Watermarked',
                    'client_carbon' => true,
                    'client_esg' => true,
                    'client_enterprise' => true,
                ],
            ],
            [
                'label' => 'IEQT export (mrv.ae)',
                'cells' => [
                    'client_free' => 'Watermarked',
                    'client_carbon' => true,
                    'client_esg' => true,
                    'client_enterprise' => true,
                ],
            ],
            [
                'label' => 'IFRS S1 / S2 PDF',
                'cells' => [
                    'client_free' => false,
                    'client_carbon' => false,
                    'client_esg' => true,
                    'client_enterprise' => true,
                ],
            ],
            [
                'label' => 'GRI PDF + content index',
                'cells' => [
                    'client_free' => false,
                    'client_carbon' => false,
                    'client_esg' => true,
                    'client_enterprise' => true,
                ],
            ],
            [
                'label' => 'UAE ESG Report PDF',
                'cells' => [
                    'client_free' => false,
                    'client_carbon' => false,
                    'client_esg' => true,
                    'client_enterprise' => true,
                ],
            ],
            [
                'label' => 'ESG Scorecard Excel',
                'cells' => [
                    'client_free' => false,
                    'client_carbon' => false,
                    'client_esg' => true,
                    'client_enterprise' => '80+ KPIs',
                ],
            ],
            [
                'label' => 'SASB index CSV',
                'cells' => [
                    'client_free' => false,
                    'client_carbon' => false,
                    'client_esg' => true,
                    'client_enterprise' => true,
                ],
            ],
            [
                'label' => 'GRI index 80+ rows',
                'cells' => [
                    'client_free' => false,
                    'client_carbon' => false,
                    'client_esg' => false,
                    'client_enterprise' => true,
                ],
            ],
            [
                'label' => 'White-label UAE ESG PDF',
                'cells' => [
                    'client_free' => false,
                    'client_carbon' => false,
                    'client_esg' => false,
                    'client_enterprise' => true,
                ],
            ],
            [
                'label' => 'Assurance PDF upload',
                'cells' => [
                    'client_free' => false,
                    'client_carbon' => false,
                    'client_esg' => false,
                    'client_enterprise' => true,
                ],
            ],
            [
                'label' => 'HRIS KPI CSV import',
                'cells' => [
                    'client_free' => false,
                    'client_carbon' => false,
                    'client_esg' => false,
                    'client_enterprise' => true,
                ],
            ],
        ];
    }

    /**
     * Consultant review packs (checkout in Phase B).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function consultantAddOns(): array
    {
        return [
            [
                'pack_type' => 'starter_consultant',
                'name' => 'Starter + Consultant',
                'price' => 'AED 1,000',
                'price_aed' => 1000,
                'description' => '~2h data review, methodology checklist, short sign-off memo.',
                'for_plan' => 'Starter',
            ],
            [
                'pack_type' => 'growth_consultant',
                'name' => 'Growth + Consultant',
                'price' => 'AED 2,000',
                'price_aed' => 2000,
                'description' => '~4h review including disclosure narrative and export sign-off.',
                'for_plan' => 'Growth',
            ],
        ];
    }

    public static function consultantPackByType(string $packType): ?array
    {
        foreach (self::consultantAddOns() as $pack) {
            if (($pack['pack_type'] ?? '') === $packType) {
                return $pack;
            }
        }

        return null;
    }

    /**
     * @return array<string, array{name: string, tagline: string}>
     */
    public static function planLabels(): array
    {
        return [
            'client_free' => ['name' => 'Free', 'tagline' => 'S1&2 full + Scope 3 (1 entry / category)'],
            'client_carbon' => ['name' => 'Carbon', 'tagline' => 'Full Scope 1-3 inventory, MOCCAE & IEQT'],
            'client_esg' => ['name' => 'ESG', 'tagline' => 'Adds IFRS S1 & S2, GRI, SASB, UAE ESG'],
            'client_enterprise' => ['name' => 'Enterprise', 'tagline' => 'Multi-entity groups and assurance'],
        ];
    }
}
