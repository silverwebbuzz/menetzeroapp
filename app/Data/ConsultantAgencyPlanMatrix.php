<?php

namespace App\Data;

/**
 * Consultant agency pack definitions — consultant_5/10/25/50.
 *
 * Wholesale pricing — shown only inside the consultant portal, not public /pricing.
 * Managed clients receive Growth-equivalent entitlements per active engagement (PRY).
 */
class ConsultantAgencyPlanMatrix
{
    public const PLAN_CODES = [
        'consultant_trial',
        'consultant_5',
        'consultant_10',
        'consultant_25',
        'consultant_50',
    ];

    /** One free managed client per consultant org (data entry only — client_free entitlements). */
    public const FREE_TRIAL_CODE = 'consultant_trial';

    public const FREE_TRIAL_SLOTS = 1;

    public const ENTERPRISE_CODE = 'consultant_enterprise';

    /** AED per extra slot (pro-rata to contract 31 Dec). */
    public const EXTRA_SLOT_PRICE_AED = 1299;

    /** AED to unlock a new PRY for an existing managed client mid-contract. */
    public const REPORTING_YEAR_UNLOCK_PRICE_AED = 999;

    /** Max users on the consultant organisation (not per managed client). */
    public const CONSULTANT_ORG_USER_LIMIT = 10;

    /** Managed client entitlement template — mirrors direct Growth. */
    public const MANAGED_CLIENT_TEMPLATE = 'client_growth';

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function packDefinitions(): array
    {
        return [
            'consultant_trial' => self::trialPack(),
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
        return array_values(array_filter(
            self::packDefinitions(),
            fn (array $pack) => ($pack['plan_code'] ?? '') !== self::FREE_TRIAL_CODE,
        ));
    }

    /**
     * Entitlements applied to each active managed client engagement (Growth + channel flags).
     *
     * @return array<string, mixed>
     */
    public static function managedClientEntitlements(): array
    {
        $growth = PlanEntitlementDefaults::entitlementsForPlanCode('client_growth') ?? [];

        return array_merge($growth, [
            'channel' => 'consultant_managed',
            'consultant_directory' => 'none',
            'pry_export_only' => true,
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
            'description' => 'One managed client — data entry only, no exports. Upgrade to an agency pack for full Growth workspaces.',
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
