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
    /**
     * Live self-serve catalogue. Four tiers: Free, Carbon, ESG, Enterprise.
     *
     * GHG accounting and ESG disclosure are different disciplines bought by
     * different people -- a manufacturer filing MOCCAE needs the inventory and
     * nothing else, while a listed company needs the disclosure layer on top.
     * Carbon and ESG split on exactly that line. ESG is a superset: every
     * framework report reads tCO2e from the same inventory, so there is no ESG
     * tier without carbon accounting underneath it.
     */
    public const CURRENT_PLAN_CODES = [
        'client_free',
        'client_carbon',
        'client_esg',
        'client_enterprise',
    ];

    /**
     * Superseded by CURRENT_PLAN_CODES. Kept defined so existing subscribers
     * keep their entitlements and their price -- forPlanCode() must still
     * resolve them. Hidden from checkout via is_active on subscription_plans,
     * the same way consultant_trial was retired in Phase 2. Never delete a
     * code somebody is still paying for.
     */
    public const LEGACY_PLAN_CODES = [
        'client_starter',
        'client_growth',
        'client_scope_basic',
        'client_scope_pro',
        'client_esg_starter',
        'client_esg_complete',
    ];

    /** Every code that must still resolve, live or grandfathered. */
    public const PLAN_CODES = [
        'client_free',
        'client_carbon',
        'client_esg',
        'client_enterprise',
        'client_starter',
        'client_growth',
        'client_scope_basic',
        'client_scope_pro',
        'client_esg_starter',
        'client_esg_complete',
        // Limit template for paid consultant-managed clients (Standard · ≤5 sites).
        'consultant_managed_standard',
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            'client_free' => self::free(),
            'client_carbon' => self::carbon(),
            'client_esg' => self::esg(),
            'client_starter' => self::starter(),
            'client_growth' => self::growth(),
            'client_enterprise' => self::enterprise(),
            'client_scope_basic' => self::scopeBasic(),
            'client_scope_pro' => self::scopePro(),
            'client_esg_starter' => self::esgStarter(),
            'client_esg_complete' => self::esgComplete(),
            'consultant_managed_standard' => self::consultantManagedStandard(),
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
            'description' => 'Scope 1 & 2 full, Scope 3 (1 entry per category), disclosure previews, watermarked GHG/Excel/IEQT trial downloads. Upgrade your package for clean exports.',
            'price_annual' => 0,
            'currency' => 'AED',
            'sort_order' => 1,
            'limits' => [
                'locations' => 1,
                'users' => 2,
                'documents' => 10,
                'scope3_records_per_form' => 1,
                'annual_report_pdf' => 0,
                'historical_years' => 1,
            ],
            'entitlements' => [
                // All Scope 3 categories open; capped at 1 entry per category (preview_per_category).
                'scope3_mode' => 'preview_per_category',
                'bulk_import' => false,
                'bulk_export' => false,
                'help_level' => 'basic',
                'disclosures' => ['access' => true, 'export' => false],
                // Trial downloads — always stamped (see export_watermark).
                'exports' => ['ghg_pdf', 'moccae_pdf', 'excel', 'ieqt'],
                'export_watermark' => true,
                'export_regen' => 'watermarked_trial',
                'consultant_directory' => 'teaser',
            ],
            'features' => ['disclosures_access'],
        ];
    }

    /**
     * Carbon — the GHG inventory and its UAE regulatory filings.
     *
     * Everything needed to measure and file emissions: Scope 1-3, bulk
     * import/export, targets and the pathway, plus GHG / MOCCAE / IEQT / Excel
     * exports. Deliberately NO framework reports -- IFRS, GRI, SASB and the UAE
     * ESG report are the ESG tier, and that boundary is the whole reason this
     * plan exists at a lower price.
     *
     * @return array<string, mixed>
     */
    private static function carbon(): array
    {
        return [
            'plan_name' => 'Carbon',
            'description' => 'Full Scope 1-3 inventory with MOCCAE, IEQT and GHG reports. 5 sites.',
            'price_annual' => 3000,
            'currency' => 'AED',
            'sort_order' => 2,
            'limits' => [
                'locations' => 5,
                'users' => 10,
                'documents' => 100,
                // 12 = one entry per month per Scope 3 category, so a bulk
                // import can carry a full year.
                'scope3_records_per_form' => 12,
                'annual_report_pdf' => -1,
                'historical_years' => 3,
            ],
            'entitlements' => [
                'scope3_mode' => 'preview_per_category',
                'bulk_import' => true,
                'bulk_export' => true,
                'help_level' => 'full',
                // Disclosure screens are reachable so the buyer can see what
                // the ESG tier adds, but nothing is exportable from them.
                'disclosures' => ['access' => true, 'export' => false],
                'exports' => ['ghg_pdf', 'moccae_pdf', 'excel', 'ieqt'],
                'consultant_directory' => 'partial',
                'export_regen' => 'subscription_year_unlimited',
            ],
            'features' => ['disclosures_access'],
        ];
    }

    /**
     * ESG — Carbon plus the disclosure layer.
     *
     * Adds the framework reports: IFRS S1 and S2, GRI with content index, SASB
     * index and the UAE ESG report, along with the registers that feed them
     * (materiality, stakeholders, supply chain, risks). A superset of Carbon,
     * never an alternative to it.
     *
     * @return array<string, mixed>
     */
    private static function esg(): array
    {
        $plan = self::carbon();

        $plan['plan_name'] = 'ESG';
        $plan['description'] = 'Everything in Carbon plus IFRS S1 & S2, GRI, SASB and the UAE ESG report. 5 sites.';
        $plan['price_annual'] = 6500;
        $plan['sort_order'] = 3;

        $plan['limits']['documents'] = 200;
        $plan['limits']['historical_years'] = 5;

        $plan['entitlements']['disclosures'] = ['access' => true, 'export' => true];
        $plan['entitlements']['help_level'] = 'full_disclosures';
        $plan['entitlements']['consultant_directory'] = 'full';
        $plan['entitlements']['exports'] = [
            'ghg_pdf',
            'moccae_pdf',
            'excel',
            'ieqt',
            'ifrs_s2_pdf',
            'ifrs_s1_pdf',
            'gri_pdf',
            'gri_content_index',
            'uae_esg_pdf',
            'esg_scorecard',
            'sasb_index',
        ];

        $plan['features'] = ['disclosures_access', 'ifrs_s2', 'ifrs_s1', 'gri'];

        return $plan;
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
                // 12 = one entry per month per Scope 3 category, so a bulk import can
                // carry a full year. Free stays at 1 as the upgrade lever.
                'scope3_records_per_form' => 12,
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
                // 12 = one entry per month per Scope 3 category, so a bulk import can
                // carry a full year. Inherited by Scope Pro / ESG Starter / ESG Complete.
                'scope3_records_per_form' => 12,
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
                    'uae_esg_pdf',
                    'esg_scorecard',
                    'sasb_index',
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

    /** Company package — Scope Basic (xlsx §5). */
    private static function scopeBasic(): array
    {
        return [
            'plan_name' => 'Scope Basic',
            'description' => 'MOCCAE-ready Scope 1 & 2: clean GHG / MOCCAE / Excel / IEQT, bulk import, up to 3 sites.',
            'price_annual' => 2500,
            'currency' => 'AED',
            'sort_order' => 12,
            'limits' => [
                'locations' => 3,
                'users' => 5,
                'documents' => 50,
                // 12 = one entry per month per Scope 3 category, so a bulk import can
                // carry a full year. Free stays at 1 as the upgrade lever.
                'scope3_records_per_form' => 12,
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
                'export_watermark' => false,
            ],
            'features' => ['disclosures_access'],
        ];
    }

    /** Company package — Scope Pro (xlsx §5). */
    private static function scopePro(): array
    {
        $growth = self::growth();
        $growth['plan_name'] = 'Scope Pro';
        $growth['description'] = 'Broader scopes + ESG disclosure suite exports. Up to 10 sites.';
        $growth['price_annual'] = 4999;
        $growth['sort_order'] = 13;
        $growth['limits']['locations'] = 10;

        return $growth;
    }

    /** Company package — ESG Starter (xlsx §5). */
    private static function esgStarter(): array
    {
        $growth = self::growth();
        $growth['plan_name'] = 'ESG Starter';
        $growth['description'] = 'Full ESG report set for mid-size orgs. Up to 5 sites.';
        $growth['price_annual'] = 18000;
        $growth['sort_order'] = 14;
        $growth['limits']['locations'] = 5;

        return $growth;
    }

    /** Company package — ESG Complete (xlsx §5). */
    private static function esgComplete(): array
    {
        $growth = self::growth();
        $growth['plan_name'] = 'ESG Complete';
        $growth['description'] = 'Larger portfolios and multi-entity options. Up to 10 sites.';
        $growth['price_annual'] = 36000;
        $growth['sort_order'] = 15;
        $growth['limits']['locations'] = 10;

        return $growth;
    }

    /**
     * Paid consultant-managed client limit/entitlement template (Standard · §6.3).
     * Not sold as a direct company plan — used for managed-client limit lookups.
     */
    private static function consultantManagedStandard(): array
    {
        return [
            'plan_name' => 'Consultant Standard (managed client)',
            'description' => 'Standard profile per paid managed client: S1&2, ≤5 sites, clean GHG/MOCCAE/Excel/IEQT.',
            'price_annual' => 0,
            'currency' => 'AED',
            'sort_order' => 90,
            'limits' => [
                'locations' => 5,
                'users' => 5,
                'documents' => 50,
                // 12 = one entry per month per Scope 3 category, matching the direct
                // client plans this managed-client tier mirrors.
                'scope3_records_per_form' => 12,
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
                'consultant_directory' => 'none',
                'export_regen' => 'subscription_year_unlimited',
                'export_watermark' => false,
                'package_profile' => 'standard',
            ],
            'features' => ['disclosures_access', 'consultant_managed'],
        ];
    }
}
