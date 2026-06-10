<?php

namespace App\Data;

/**
 * Partner / Agency pack definitions (PARTNER_AGENCY_PLAN_V1.md §4).
 *
 * Wholesale pricing — shown only inside the partner hub, not public /pricing.
 * Managed clients receive Growth-equivalent entitlements per active engagement (PRY).
 */
class PartnerPlanMatrix
{
    public const PLAN_CODES = [
        'partner_5',
        'partner_10',
        'partner_25',
        'partner_50',
    ];

    public const ENTERPRISE_CODE = 'partner_enterprise';

    /** AED per extra slot (pro-rata to contract 31 Dec). */
    public const EXTRA_SLOT_PRICE_AED = 1299;

    /** AED to unlock a new PRY for an existing managed client mid-contract. */
    public const REPORTING_YEAR_UNLOCK_PRICE_AED = 999;

    /** Max users on the partner organisation (not per managed client). */
    public const PARTNER_ORG_USER_LIMIT = 10;

    /** Managed client entitlement template — mirrors direct Growth. */
    public const MANAGED_CLIENT_TEMPLATE = 'client_growth';

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function packDefinitions(): array
    {
        return [
            'partner_5' => self::pack(5, 1299, 6495, 1, 'Partner 5', 'Solo consultant — up to 5 managed clients'),
            'partner_10' => self::pack(10, 999, 9990, 2, 'Partner 10', 'Small practice — up to 10 managed clients'),
            'partner_25' => self::pack(25, 899, 22475, 3, 'Partner 25', 'Growing agency — up to 25 managed clients'),
            'partner_50' => self::pack(50, 799, 39950, 4, 'Partner 50', 'Large agency — up to 50 managed clients'),
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
        return (int) (self::forPlanCode($planCode)['partner_slot_count'] ?? 0);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function selectablePacks(): array
    {
        return array_values(self::packDefinitions());
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
            'channel' => 'partner_managed',
            'consultant_directory' => 'none',
            'pry_export_only' => true,
        ]);
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
            'plan_code' => "partner_{$slots}",
            'plan_name' => $name,
            'description' => $description,
            'plan_category' => 'partner',
            'price_annual' => $priceAnnual,
            'price_per_slot_aed' => $pricePerSlot,
            'partner_slot_count' => $slots,
            'currency' => 'AED',
            'sort_order' => 10 + $sortOrder,
            'billing_cycle' => 'annual',
            'is_active' => true,
            'limits' => [
                'users' => self::PARTNER_ORG_USER_LIMIT,
                'partner_slots' => $slots,
                'locations' => -1,
                'documents' => -1,
            ],
            'entitlements' => [
                'channel' => 'partner_pack',
                'partner_slot_count' => $slots,
                'contract_alignment' => 'calendar_year',
                'managed_client_template' => self::MANAGED_CLIENT_TEMPLATE,
                'extra_slot_price_aed' => self::EXTRA_SLOT_PRICE_AED,
                'reporting_year_unlock_price_aed' => self::REPORTING_YEAR_UNLOCK_PRICE_AED,
            ],
            'features' => ['partner_agency', 'managed_clients'],
        ];
    }
}
