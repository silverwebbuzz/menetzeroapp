<?php

namespace App\Data;

/**
 * Canonical Commercial Plan v1 definitions (COMMERCIAL_PLAN_V1.md §6).
 *
 * Used by migrations and PlanEntitlementService (C2). DB rows on
 * subscription_plans should match these defaults after migration C1.
 */
class PlanEntitlementDefaults
{
    public const PLAN_CODES = [
        'client_free',
        'client_starter',
        'client_growth',
        'client_enterprise',
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            'client_free' => self::free(),
            'client_starter' => self::starter(),
            'client_growth' => self::growth(),
            'client_enterprise' => self::enterprise(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function forPlanCode(string $planCode): ?array
    {
        return self::definitions()[$planCode] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function entitlementsForPlanCode(string $planCode): ?array
    {
        $definition = self::forPlanCode($planCode);

        return $definition['entitlements'] ?? null;
    }

    /**
     * Approximate INR list price (Razorpay/Cashfree). Admin may override.
     */
    public static function defaultPriceInr(float $priceAnnualAed): float
    {
        return round($priceAnnualAed * 23, 2);
    }

    /**
     * @return array<string, mixed>
     */
    private static function free(): array
    {
        return [
            'plan_name' => 'Free',
            'description' => 'Try Scope 1 & 2 and all disclosure forms — upgrade to export reports.',
            'price_annual' => 0,
            'currency' => 'AED',
            'sort_order' => 1,
            'limits' => [
                'locations' => 1,
                'users' => 1,
                'documents' => 10,
                'scope3_records_per_form' => 0,
                'annual_report_pdf' => 0,
                'historical_years' => 1,
            ],
            'entitlements' => [
                'scope3_mode' => 'locked',
                'bulk_import' => false,
                'bulk_export' => false,
                'help_level' => 'basic',
                'disclosures' => ['access' => true, 'export' => false],
                'exports' => [],
                'consultant_directory' => 'teaser',
                'export_regen' => 'none',
            ],
            'features' => ['disclosures_access'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function starter(): array
    {
        return [
            'plan_name' => 'Starter',
            'description' => 'MOCCAE-ready: GHG reports, IEQT export, bulk import, Scope 3 preview.',
            'price_annual' => 1499,
            'currency' => 'AED',
            'sort_order' => 2,
            'limits' => [
                'locations' => 3,
                'users' => 5,
                'documents' => 50,
                'scope3_records_per_form' => 1,
                'annual_report_pdf' => -1,
                'historical_years' => 2,
            ],
            'entitlements' => [
                'scope3_mode' => 'preview_per_category',
                'bulk_import' => true,
                'bulk_export' => true,
                'help_level' => 'full',
                'disclosures' => ['access' => true, 'export' => false],
                'exports' => ['ghg_pdf', 'moccae_pdf', 'excel', 'ieqt'],
                'consultant_directory' => 'partial',
                'export_regen' => 'subscription_year_unlimited',
            ],
            'features' => ['disclosures_access'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function growth(): array
    {
        return [
            'plan_name' => 'Growth',
            'description' => 'Everything in Starter plus IFRS & GRI report downloads.',
            'price_annual' => 2499,
            'currency' => 'AED',
            'sort_order' => 3,
            'limits' => [
                'locations' => 10,
                'users' => 10,
                'documents' => 200,
                'scope3_records_per_form' => 1,
                'annual_report_pdf' => -1,
                'historical_years' => 5,
            ],
            'entitlements' => [
                'scope3_mode' => 'preview_per_category',
                'bulk_import' => true,
                'bulk_export' => true,
                'help_level' => 'full_disclosures',
                'disclosures' => ['access' => true, 'export' => true],
                'exports' => [
                    'ghg_pdf',
                    'moccae_pdf',
                    'excel',
                    'ieqt',
                    'ifrs_s2_pdf',
                    'ifrs_s1_pdf',
                    'gri_pdf',
                    'gri_content_index',
                ],
                'consultant_directory' => 'full',
                'export_regen' => 'subscription_year_unlimited',
            ],
            'features' => ['disclosures_access', 'ifrs_s2', 'ifrs_s1', 'gri'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function enterprise(): array
    {
        return [
            'plan_name' => 'Enterprise',
            'description' => 'Unlimited Scope 3, multi-site, API — custom pricing (AED 20,000+).',
            'price_annual' => 20000,
            'currency' => 'AED',
            'sort_order' => 4,
            'limits' => [
                'locations' => -1,
                'users' => -1,
                'documents' => -1,
                'scope3_records_per_form' => -1,
                'annual_report_pdf' => -1,
                'historical_years' => -1,
            ],
            'entitlements' => [
                'scope3_mode' => 'full',
                'bulk_import' => true,
                'bulk_export' => true,
                'help_level' => 'full_disclosures',
                'disclosures' => ['access' => true, 'export' => true],
                'exports' => ['*'],
                'consultant_directory' => 'priority',
                'export_regen' => 'subscription_year_unlimited',
            ],
            'features' => ['disclosures_access', 'ifrs_s2', 'ifrs_s1', 'gri'],
        ];
    }
}
