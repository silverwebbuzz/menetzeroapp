<?php

namespace App\Data;

/**
 * Consultant agency pack definitions — legacy self-serve pack sizes (consultant_5/10/25/50).
 *
 * COMMERCIAL NOTE (Aug 2026): Default paid consultant offer is now per-entity intro pricing
 * (AED 1,399 / 1,199) documented in documentation/PRICING_AND_PLAN_MAJOR_CHANGES.md §6.
 * Self-serve pack checkout is being hidden (Phase 3). Free trial (1 entity) remains.
 * Managed clients on Free trial use client_free entitlements. Paid entities use
 * **Standard** via managedClientEntitlements() (§6.3). Demo pack (consultant_1)
 * still uses Growth via demoManagedClientEntitlements(). Phase 8 may seed
 * `consultant_entity` plan codes and retire legacy pack rows.
 *
 * Wholesale pack rows below are retained for migration / demo until Phase 8 cleanup.
 */
class ConsultantAgencyPlanMatrix
{
    public const PLAN_CODES = [
        'consultant_trial',
        'consultant_1',
        'consultant_5',
        'consultant_10',
        'consultant_25',
        'consultant_50',
    ];

    /** One free managed client per consultant org (data entry only — client_free entitlements). */
    public const FREE_TRIAL_CODE = 'consultant_trial';

    public const FREE_TRIAL_SLOTS = 1;

    /**
     * Complimentary demo pack — one managed client with FULL Growth access.
     * Admin-assigned only (not shown on the consultant self-serve packs page).
     */
    public const DEMO_PACK_CODE = 'consultant_1';

    public const DEMO_PACK_SLOTS = 1;

    public const ENTERPRISE_CODE = 'consultant_enterprise';

    /** AED per extra slot (pro-rata to contract 31 Dec). */
    public const EXTRA_SLOT_PRICE_AED = 1299;

    /** AED to unlock a new PRY for an existing managed client mid-contract. */
    public const REPORTING_YEAR_UNLOCK_PRICE_AED = 999;

    /** Max users on the consultant organisation (not per managed client). */
    public const CONSULTANT_ORG_USER_LIMIT = 10;

    /** Closest seeded client plan for paid-entity limit lookups until Phase 8 `consultant_entity`. */
    public const MANAGED_CLIENT_TEMPLATE = 'client_starter';

    /** Standard default sites per paid managed client (§6.3). */
    public const STANDARD_SITES_PER_ENTITY = 5;

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function packDefinitions(): array
    {
        return [
            'consultant_trial' => self::trialPack(),
            'consultant_1' => self::demoPack(),
            'consultant_5' => self::pack(5, 1299, 6495, 1, 'Consultant 5', 'Solo consultant — up to 5 managed clients'),
            'consultant_10' => self::pack(10, 999, 9990, 2, 'Consultant 10', 'Small practice — up to 10 managed clients'),
            'consultant_25' => self::pack(25, 899, 22475, 3, 'Consultant 25', 'Growing agency — up to 25 managed clients'),
            'consultant_50' => self::pack(50, 799, 39950, 4, 'Consultant 50', 'Large agency — up to 50 managed clients'),
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
        if ($planCode === self::FREE_TRIAL_CODE) {
            return self::FREE_TRIAL_SLOTS;
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
        $hidden = [self::FREE_TRIAL_CODE, self::DEMO_PACK_CODE];

        return array_values(array_filter(
            self::packDefinitions(),
            fn (array $pack) => !in_array($pack['plan_code'] ?? '', $hidden, true),
        ));
    }

    /**
     * Entitlements applied to each paid managed client (Standard — §6.3).
     * Scope 1&2 exports (GHG/MOCCAE/Excel/IEQT), bulk import — not full ESG.
     * Site limit ≤5 is commercial; use client_starter for limit lookups until Phase 8
     * seeds consultant_entity (admin activation should set locations to 5).
     *
     * @return array<string, mixed>
     */
    public static function managedClientEntitlements(): array
    {
        return self::standardManagedClientEntitlements();
    }

    /**
     * @return array<string, mixed>
     */
    public static function standardManagedClientEntitlements(): array
    {
        $starter = PlanEntitlementDefaults::entitlementsForPlanCode('client_starter') ?? [];

        return array_merge($starter, [
            'channel' => 'consultant_managed',
            'consultant_directory' => 'none',
            'pry_export_only' => true,
            'package_profile' => 'standard',
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
        $growth = PlanEntitlementDefaults::entitlementsForPlanCode('client_growth') ?? [];

        return array_merge($growth, [
            'channel' => 'consultant_managed',
            'consultant_directory' => 'none',
            'pry_export_only' => true,
            'package_profile' => 'demo_growth',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function trialPack(): array
    {
        return [
            'plan_code' => self::FREE_TRIAL_CODE,
            'plan_name' => 'Free trial',
            'description' => 'One managed client on Free rules (Scope 1 & 2 full, Scope 3 one entry per category, watermarked GHG/Excel/IEQT trial downloads). Request slots for clean exports and more clients.',
            'plan_category' => 'consultant_agency',
            'price_annual' => 0,
            'price_per_slot_aed' => 0,
            'consultant_slot_count' => self::FREE_TRIAL_SLOTS,
            'currency' => 'AED',
            'sort_order' => 5,
            'billing_cycle' => 'annual',
            'is_active' => false,
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
            ],
            'features' => ['consultant_agency', 'managed_clients', 'free_trial'],
        ];
    }

    /**
     * Complimentary demo pack — one managed client with FULL Growth access (unlike
     * the trial, which is data-entry only). Not a free_trial provision, so managed
     * clients resolve to the full managedClientEntitlements() template. Assigned by
     * admins only; kept inactive so it never appears on the self-serve packs page.
     *
     * @return array<string, mixed>
     */
    private static function demoPack(): array
    {
        return [
            'plan_code' => self::DEMO_PACK_CODE,
            'plan_name' => 'Consultant 1',
            'description' => 'One managed client with full Growth access — complimentary demo pack for special consultants (admin-assigned).',
            'plan_category' => 'consultant_agency',
            'price_annual' => 0,
            'price_per_slot_aed' => 0,
            'consultant_slot_count' => self::DEMO_PACK_SLOTS,
            'currency' => 'AED',
            'sort_order' => 6,
            'billing_cycle' => 'annual',
            'is_active' => false,
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
                'managed_client_template' => self::MANAGED_CLIENT_TEMPLATE,
                'reporting_year_unlock_price_aed' => self::REPORTING_YEAR_UNLOCK_PRICE_AED,
            ],
            'features' => ['consultant_agency', 'managed_clients', 'full_demo'],
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
            'sort_order' => 10 + $sortOrder,
            'billing_cycle' => 'annual',
            'is_active' => true,
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
                'managed_client_template' => self::MANAGED_CLIENT_TEMPLATE,
                'extra_slot_price_aed' => self::EXTRA_SLOT_PRICE_AED,
                'reporting_year_unlock_price_aed' => self::REPORTING_YEAR_UNLOCK_PRICE_AED,
            ],
            'features' => ['consultant_agency', 'managed_clients'],
        ];
    }
}
