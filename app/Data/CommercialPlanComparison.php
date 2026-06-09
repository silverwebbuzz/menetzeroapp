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
        'client_starter',
        'client_growth',
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
                    'client_starter' => '3',
                    'client_growth' => '10',
                    'client_enterprise' => 'Unlimited',
                ],
            ],
            [
                'label' => 'Users',
                'cells' => [
                    'client_free' => '1',
                    'client_starter' => '5',
                    'client_growth' => '10',
                    'client_enterprise' => 'Unlimited',
                ],
            ],
            [
                'label' => 'Scope 1 & 2 Quick Input',
                'cells' => [
                    'client_free' => true,
                    'client_starter' => true,
                    'client_growth' => true,
                    'client_enterprise' => true,
                ],
            ],
            [
                'label' => 'Bulk CSV / XLS import',
                'cells' => [
                    'client_free' => false,
                    'client_starter' => true,
                    'client_growth' => true,
                    'client_enterprise' => true,
                ],
            ],
            [
                'label' => 'Bulk data export',
                'cells' => [
                    'client_free' => false,
                    'client_starter' => true,
                    'client_growth' => true,
                    'client_enterprise' => true,
                ],
            ],
            [
                'label' => 'Scope 3',
                'cells' => [
                    'client_free' => 'Locked',
                    'client_starter' => '1 entry / category',
                    'client_growth' => '1 entry / category',
                    'client_enterprise' => 'Unlimited',
                ],
            ],
            [
                'label' => 'Help guide',
                'cells' => [
                    'client_free' => 'Basic',
                    'client_starter' => 'Full',
                    'client_growth' => 'Full + disclosures',
                    'client_enterprise' => 'Full + training',
                ],
            ],
            [
                'label' => 'Disclosure forms (IFRS / GRI)',
                'cells' => [
                    'client_free' => 'Preview only',
                    'client_starter' => 'Preview only',
                    'client_growth' => 'Preview + export',
                    'client_enterprise' => 'Full',
                ],
            ],
            [
                'label' => 'Consultant directory',
                'cells' => [
                    'client_free' => 'Teaser',
                    'client_starter' => 'Request intro',
                    'client_growth' => 'Full connect',
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
                    'client_free' => false,
                    'client_starter' => true,
                    'client_growth' => true,
                    'client_enterprise' => true,
                ],
            ],
            [
                'label' => 'MOCCAE S1 & 2 PDF',
                'cells' => [
                    'client_free' => false,
                    'client_starter' => true,
                    'client_growth' => true,
                    'client_enterprise' => true,
                ],
            ],
            [
                'label' => 'Excel results export',
                'cells' => [
                    'client_free' => false,
                    'client_starter' => true,
                    'client_growth' => true,
                    'client_enterprise' => true,
                ],
            ],
            [
                'label' => 'IEQT export (mrv.ae)',
                'cells' => [
                    'client_free' => false,
                    'client_starter' => true,
                    'client_growth' => true,
                    'client_enterprise' => true,
                ],
            ],
            [
                'label' => 'IFRS S1 / S2 PDF',
                'cells' => [
                    'client_free' => false,
                    'client_starter' => false,
                    'client_growth' => true,
                    'client_enterprise' => true,
                ],
            ],
            [
                'label' => 'GRI PDF + content index',
                'cells' => [
                    'client_free' => false,
                    'client_starter' => false,
                    'client_growth' => true,
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
                'name' => 'Starter + Consultant',
                'price' => 'AED 1,000',
                'description' => '~2h data review, methodology checklist, short sign-off memo.',
                'for_plan' => 'Starter',
            ],
            [
                'name' => 'Growth + Consultant',
                'price' => 'AED 2,000',
                'description' => '~4h review including disclosure narrative and export sign-off.',
                'for_plan' => 'Growth',
            ],
        ];
    }

    /**
     * @return array<string, array{name: string, tagline: string}>
     */
    public static function planLabels(): array
    {
        return [
            'client_free' => ['name' => 'Free', 'tagline' => 'Try S1&2 + disclosure forms'],
            'client_starter' => ['name' => 'Starter', 'tagline' => 'MOCCAE-ready inventory & IEQT'],
            'client_growth' => ['name' => 'Growth', 'tagline' => 'IFRS & GRI downloads'],
            'client_enterprise' => ['name' => 'Enterprise', 'tagline' => 'Unlimited Scope 3 & custom'],
        ];
    }
}
