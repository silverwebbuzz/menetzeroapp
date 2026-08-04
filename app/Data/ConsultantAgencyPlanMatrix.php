<?php

namespace App\Data;

/**
 * Consultant agency pack definitions.
 *
 * Target catalog (documentation/CONSULTANT_MULTI_PACKAGE_PLAN.md):
 *   consultant_free (Phase 2 rename from consultant_trial) · consultant_1 (admin QA)
 *   consultant_scope_* / consultant_enterprise — one subscription row per purchase
 *   (own slot_limit + expiry); entitlements mirror matching client_* packages.
 *
 * Legacy packs (consultant_5/10/25/50, consultant_entity, consultant_managed_standard)
 * remain in definitions only for historical forPlanCode lookups (inactive / deleted from DB in Phase 2).
 */
class ConsultantAgencyPlanMatrix
{
    /** @deprecated Use TARGET_PLAN_CODES; kept for older migrations referencing PLAN_CODES */
    public const PLAN_CODES = [
        'consultant_trial',
        'consultant_1',
        'consultant_entity',
        'consultant_5',
        'consultant_10',
        'consultant_25',
        'consultant_50',
    ];

    /** Live catalog after Phase 1–2. */
    public const TARGET_PLAN_CODES = [
        'consultant_free',
        'consultant_1',
        'consultant_scope_basic',
        'consultant_scope_pro',
        'consultant_esg_starter',
        'consultant_esg_complete',
        'consultant_enterprise',
    ];

    public const DEPTH_PLAN_CODES = [
        'consultant_scope_basic',
        'consultant_scope_pro',
        'consultant_esg_starter',
        'consultant_esg_complete',
        'consultant_enterprise',
    ];

    /** Request form uses company codes; activation writes consultant_* rows. */
    public const CLIENT_DEPTH_TO_CONSULTANT = [
        'client_scope_basic' => 'consultant_scope_basic',
        'client_scope_pro' => 'consultant_scope_pro',
        'client_esg_starter' => 'consultant_esg_starter',
        'client_esg_complete' => 'consultant_esg_complete',
        'client_enterprise' => 'consultant_enterprise',
        'client_free' => 'consultant_free',
    ];

    public const CONSULTANT_DEPTH_TO_CLIENT = [
        'consultant_scope_basic' => 'client_scope_basic',
        'consultant_scope_pro' => 'client_scope_pro',
        'consultant_esg_starter' => 'client_esg_starter',
        'consultant_esg_complete' => 'client_esg_complete',
        'consultant_enterprise' => 'client_enterprise',
        'consultant_free' => 'client_free',
        'consultant_trial' => 'client_free',
    ];

    /** Canonical free plan (renamed from consultant_trial in Phase 2). */
    public const FREE_TRIAL_CODE = 'consultant_free';

    public const FREE_CODE = 'consultant_free';

    /** @deprecated Removed from DB in Phase 2 — kept for historical string matches only */
    public const LEGACY_TRIAL_CODE = 'consultant_trial';

    public const FREE_TRIAL_SLOTS = 1;

    /**
     * Complimentary demo / QA pack — one managed client with full access.
     * Admin-assigned only (not shown on self-serve).
     */
    public const DEMO_PACK_CODE = 'consultant_1';

    public const DEMO_PACK_SLOTS = 1;

    /** @deprecated Phase 3+ activates consultant_scope_* rows instead */
    public const ENTITY_PLAN_CODE = 'consultant_entity';

    public const ENTITY_PLAN_BASE_SLOTS = 1;

    public const ENTERPRISE_CODE = 'consultant_enterprise';

    /** AED per extra slot (pro-rata to contract 31 Dec) — legacy pack path. */
    public const EXTRA_SLOT_PRICE_AED = 1299;

    /** AED to unlock a new PRY for an existing managed client mid-contract. */
    public const REPORTING_YEAR_UNLOCK_PRICE_AED = 999;

    /** Max users on the consultant organisation (not per managed client). */
    public const CONSULTANT_ORG_USER_LIMIT = 10;

    /** @deprecated Prefer client_scope_basic limits via CONSULTANT_DEPTH_TO_CLIENT */
    public const MANAGED_CLIENT_TEMPLATE = 'consultant_managed_standard';

    /** Standard default sites per paid managed client (legacy Standard band). */
    public const STANDARD_SITES_PER_ENTITY = 5;

    public static function consultantPlanForClientDepth(string $clientPackageCode): ?string
    {
        return self::CLIENT_DEPTH_TO_CONSULTANT[$clientPackageCode] ?? null;
    }

    public static function clientDepthForConsultantPlan(string $consultantPlanCode): ?string
    {
        return self::CONSULTANT_DEPTH_TO_CLIENT[$consultantPlanCode] ?? null;
    }

    public static function isDepthPlan(string $planCode): bool
    {
        return in_array($planCode, self::DEPTH_PLAN_CODES, true);
    }

    public static function isFreePlan(string $planCode): bool
    {
        return in_array($planCode, [self::FREE_CODE, self::FREE_TRIAL_CODE, self::LEGACY_TRIAL_CODE], true);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function packDefinitions(): array
    {
        return [
            'consultant_free' => self::freePack(),
            'consultant_1' => self::demoPack(),
            'consultant_scope_basic' => self::depthPack('consultant_scope_basic', 'client_scope_basic', 20),
            'consultant_scope_pro' => self::depthPack('consultant_scope_pro', 'client_scope_pro', 21),
            'consultant_esg_starter' => self::depthPack('consultant_esg_starter', 'client_esg_starter', 22),
            'consultant_esg_complete' => self::depthPack('consultant_esg_complete', 'client_esg_complete', 23),
            'consultant_enterprise' => self::depthPack('consultant_enterprise', 'client_enterprise', 24),
            // Legacy definitions (inactive) — for migrations / historical forPlanCode lookups
            'consultant_trial' => self::legacyTrialAliasPack(),
            'consultant_entity' => self::entityPack(),
            'consultant_5' => self::pack(5, 1299, 6495, 1, 'Consultant 5 (legacy)', 'Legacy pack — retired; use consultant_scope_* rows'),
            'consultant_10' => self::pack(10, 999, 9990, 2, 'Consultant 10 (legacy)', 'Legacy pack — retired; use consultant_scope_* rows'),
            'consultant_25' => self::pack(25, 899, 22475, 3, 'Consultant 25 (legacy)', 'Legacy pack — retired; use consultant_scope_* rows'),
            'consultant_50' => self::pack(50, 799, 39950, 4, 'Consultant 50 (legacy)', 'Legacy pack — retired; use consultant_scope_* rows'),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function forPlanCode(string $planCode): ?array
    {
        return self::packDefinitions()[$planCode] ?? null;
    }

    public static function slotCountForPlanCode(string $planCode): int
    {
        if (self::isFreePlan($planCode)) {
            return self::FREE_TRIAL_SLOTS;
        }

        if ($planCode === self::DEMO_PACK_CODE) {
            return self::DEMO_PACK_SLOTS;
        }

        if ($planCode === self::ENTITY_PLAN_CODE) {
            return self::ENTITY_PLAN_BASE_SLOTS;
        }

        if (self::isDepthPlan($planCode)) {
            // Activation sets slot_limit from request qty; base definition = 1.
            return 1;
        }

        return (int) (self::forPlanCode($planCode)['consultant_slot_count'] ?? 0);
    }

    /**
     * Entitlements for the one free trial managed client (mirrors direct client_free).
     *
     * @return array<string, mixed>
     */
    public static function trialManagedClientEntitlements(): array
    {
        $free = PlanEntitlementDefaults::entitlementsForPlanCode('client_free') ?? [];

        return array_merge($free, [
            'channel' => 'consultant_managed',
            'consultant_directory' => 'none',
            'provision_type' => 'free_trial',
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function selectablePacks(): array
    {
        $hidden = [
            self::FREE_TRIAL_CODE,
            self::FREE_CODE,
            self::DEMO_PACK_CODE,
            self::ENTITY_PLAN_CODE,
            'consultant_5',
            'consultant_10',
            'consultant_25',
            'consultant_50',
        ];

        return array_values(array_filter(
            self::packDefinitions(),
            function (array $pack) use ($hidden) {
                $code = $pack['plan_code'] ?? '';

                return !in_array($code, $hidden, true) && self::isDepthPlan($code);
            },
        ));
    }

    /**
     * Entitlements applied to each paid managed client (legacy Standard — §6.3).
     *
     * @return array<string, mixed>
     */
    public static function managedClientEntitlements(): array
    {
        return self::standardManagedClientEntitlements();
    }

    /**
     * Managed-client entitlements for a requested package profile (company-style or consultant depth codes).
     *
     * @return array<string, mixed>
     */
    public static function managedClientEntitlementsForPackage(?string $packageCode): array
    {
        if (!$packageCode || $packageCode === 'consultant_managed_standard') {
            return self::standardManagedClientEntitlements();
        }

        $clientCode = self::CONSULTANT_DEPTH_TO_CLIENT[$packageCode] ?? $packageCode;

        if ($clientCode === 'client_free' || self::isFreePlan($packageCode)) {
            return self::trialManagedClientEntitlements();
        }

        $fromPlan = PlanEntitlementDefaults::entitlementsForPlanCode($clientCode);
        if (!$fromPlan) {
            return self::standardManagedClientEntitlements();
        }

        $limits = PlanEntitlementDefaults::forPlanCode($clientCode)['limits'] ?? [];

        return array_merge($fromPlan, [
            'channel' => 'consultant_managed',
            'consultant_directory' => 'none',
            'pry_export_only' => true,
            'package_profile' => $clientCode,
            'export_watermark' => false,
            'limits_hint' => $limits,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function standardManagedClientEntitlements(): array
    {
        $standard = PlanEntitlementDefaults::entitlementsForPlanCode('client_scope_basic')
            ?? PlanEntitlementDefaults::entitlementsForPlanCode(self::MANAGED_CLIENT_TEMPLATE)
            ?? PlanEntitlementDefaults::entitlementsForPlanCode('client_starter')
            ?? [];

        return array_merge($standard, [
            'channel' => 'consultant_managed',
            'consultant_directory' => 'none',
            'pry_export_only' => true,
            'package_profile' => 'scope_basic',
            'export_watermark' => false,
            'standard_sites_per_entity' => self::STANDARD_SITES_PER_ENTITY,
        ]);
    }

    /**
     * Admin demo pack (consultant_1) — full Growth for special complimentary demos.
     *
     * @return array<string, mixed>
     */
    public static function demoManagedClientEntitlements(): array
    {
        $growth = PlanEntitlementDefaults::entitlementsForPlanCode('client_growth')
            ?? PlanEntitlementDefaults::entitlementsForPlanCode('client_scope_pro')
            ?? [];

        return array_merge($growth, [
            'channel' => 'consultant_managed',
            'consultant_directory' => 'none',
            'pry_export_only' => true,
            'package_profile' => 'demo_full',
        ]);
    }

    /**
     * @deprecated Historical alias for forPlanCode('consultant_trial') lookups only.
     *
     * @return array<string, mixed>
     */
    private static function legacyTrialAliasPack(): array
    {
        $base = self::freePack();
        $base['plan_code'] = self::LEGACY_TRIAL_CODE;
        $base['plan_name'] = 'Free trial (legacy code)';
        $base['description'] = 'Removed in Phase 2 — use consultant_free.';
        $base['is_active'] = false;
        $base['sort_order'] = 99;

        return $base;
    }

    /**
     * Free trial — one managed client on client_free rules. Always retained when paid depth rows are added.
     *
     * @return array<string, mixed>
     */
    private static function freePack(): array
    {
        return [
            'plan_code' => self::FREE_CODE,
            'plan_name' => 'Free trial',
            'description' => 'One managed client on Free rules (mirrors client_free). Always retained when paid depth rows are added.',
            'plan_category' => 'consultant_agency',
            'price_annual' => 0,
            'price_per_slot_aed' => 0,
            'consultant_slot_count' => self::FREE_TRIAL_SLOTS,
            'currency' => 'AED',
            'sort_order' => 4,
            'billing_cycle' => 'annual',
            'is_active' => true,
            'limits' => [
                'users' => self::CONSULTANT_ORG_USER_LIMIT,
                'consultant_slots' => self::FREE_TRIAL_SLOTS,
                'locations' => -1,
                'documents' => -1,
            ],
            'entitlements' => [
                'channel' => 'consultant_agency_pack',
                'provision_type' => 'free_trial',
                'consultant_slot_count' => self::FREE_TRIAL_SLOTS,
                'managed_client_template' => 'client_free',
                'mirrors_client_plan' => 'client_free',
            ],
            'features' => ['consultant_agency', 'managed_clients', 'free_trial'],
        ];
    }

    /**
     * Complimentary demo pack — one managed client with FULL access. Admin only.
     *
     * @return array<string, mixed>
     */
    private static function demoPack(): array
    {
        return [
            'plan_code' => self::DEMO_PACK_CODE,
            'plan_name' => 'Demo / QA — 1 client full access',
            'description' => 'Admin/QA only: one managed client with full access for testing. Not sold.',
            'plan_category' => 'consultant_agency',
            'price_annual' => 0,
            'price_per_slot_aed' => 0,
            'consultant_slot_count' => self::DEMO_PACK_SLOTS,
            'currency' => 'AED',
            'sort_order' => 6,
            'billing_cycle' => 'annual',
            'is_active' => true,
            'limits' => [
                'users' => self::CONSULTANT_ORG_USER_LIMIT,
                'consultant_slots' => self::DEMO_PACK_SLOTS,
                'locations' => -1,
                'documents' => -1,
            ],
            'entitlements' => [
                'channel' => 'consultant_agency_pack',
                'consultant_slot_count' => self::DEMO_PACK_SLOTS,
                'contract_alignment' => 'calendar_year',
                'managed_client_template' => 'client_growth',
                'reporting_year_unlock_price_aed' => self::REPORTING_YEAR_UNLOCK_PRICE_AED,
                'demo_full_access' => true,
            ],
            'features' => ['consultant_agency', 'managed_clients', 'full_demo', 'qa'],
        ];
    }

    /**
     * Depth capacity plan — entitlements mirror the paired client_* package.
     * slot_limit on each subscription row is set at activation (request qty).
     *
     * @return array<string, mixed>
     */
    private static function depthPack(string $consultantCode, string $clientCode, int $sortOrder): array
    {
        $client = PlanEntitlementDefaults::forPlanCode($clientCode) ?? [];
        $priceAnnual = (float) ($client['price_annual'] ?? 0);
        $name = 'Consultant — ' . ($client['plan_name'] ?? $clientCode);

        return [
            'plan_code' => $consultantCode,
            'plan_name' => $name,
            'description' => 'Managed-client capacity at '
                . ($client['plan_name'] ?? $clientCode)
                . ' depth (mirrors `'
                . $clientCode
                . '`). One subscription row per purchase with its own slot count and expiry.',
            'plan_category' => 'consultant_agency',
            'price_annual' => $priceAnnual,
            'price_per_slot_aed' => $priceAnnual,
            'consultant_slot_count' => 1,
            'currency' => $client['currency'] ?? 'AED',
            'sort_order' => $sortOrder,
            'billing_cycle' => 'annual',
            'is_active' => true,
            'limits' => [
                'users' => self::CONSULTANT_ORG_USER_LIMIT,
                'consultant_slots' => 1,
                'locations' => -1,
                'documents' => -1,
            ],
            'entitlements' => array_merge(
                PlanEntitlementDefaults::entitlementsForPlanCode($clientCode) ?? [],
                [
                    'channel' => 'consultant_agency_pack',
                    'consultant_slot_count' => 1,
                    'contract_alignment' => 'calendar_year',
                    'mirrors_client_plan' => $clientCode,
                    'managed_client_template' => $clientCode,
                    'reporting_year_unlock_price_aed' => self::REPORTING_YEAR_UNLOCK_PRICE_AED,
                ]
            ),
            'features' => ['consultant_agency', 'managed_clients', 'depth_package', $consultantCode],
        ];
    }

    /**
     * @deprecated Legacy single paid Consultant Plan
     *
     * @return array<string, mixed>
     */
    private static function entityPack(): array
    {
        return [
            'plan_code' => self::ENTITY_PLAN_CODE,
            'plan_name' => 'Consultant Plan (legacy)',
            'description' => 'Retired — use consultant_scope_* / consultant_enterprise multi-row capacity.',
            'plan_category' => 'consultant_agency',
            'price_annual' => 1399,
            'price_per_slot_aed' => 1399,
            'consultant_slot_count' => self::ENTITY_PLAN_BASE_SLOTS,
            'currency' => 'AED',
            'sort_order' => 90,
            'billing_cycle' => 'annual',
            'is_active' => false,
            'limits' => [
                'users' => self::CONSULTANT_ORG_USER_LIMIT,
                'consultant_slots' => self::ENTITY_PLAN_BASE_SLOTS,
                'locations' => -1,
                'documents' => -1,
            ],
            'entitlements' => [
                'channel' => 'consultant_agency_pack',
                'consultant_slot_count' => self::ENTITY_PLAN_BASE_SLOTS,
                'contract_alignment' => 'calendar_year',
                'managed_client_template' => 'client_scope_basic',
                'reporting_year_unlock_price_aed' => self::REPORTING_YEAR_UNLOCK_PRICE_AED,
            ],
            'features' => ['consultant_agency', 'managed_clients', 'legacy'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function pack(
        int $slots,
        int $pricePerSlot,
        int $priceAnnual,
        int $sortOrder,
        string $name,
        string $description,
    ): array {
        return [
            'plan_code' => "consultant_{$slots}",
            'plan_name' => $name,
            'description' => $description,
            'plan_category' => 'consultant_agency',
            'price_annual' => $priceAnnual,
            'price_per_slot_aed' => $pricePerSlot,
            'consultant_slot_count' => $slots,
            'currency' => 'AED',
            'sort_order' => 100 + $sortOrder,
            'billing_cycle' => 'annual',
            'is_active' => false,
            'limits' => [
                'users' => self::CONSULTANT_ORG_USER_LIMIT,
                'consultant_slots' => $slots,
                'locations' => -1,
                'documents' => -1,
            ],
            'entitlements' => [
                'channel' => 'consultant_agency_pack',
                'consultant_slot_count' => $slots,
                'contract_alignment' => 'calendar_year',
                'managed_client_template' => 'client_scope_basic',
                'extra_slot_price_aed' => self::EXTRA_SLOT_PRICE_AED,
                'reporting_year_unlock_price_aed' => self::REPORTING_YEAR_UNLOCK_PRICE_AED,
            ],
            'features' => ['consultant_agency', 'managed_clients', 'legacy'],
        ];
    }
}
