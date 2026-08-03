<?php

namespace App\Data;

/**
 * Admin-facing list prices for quotes (not shown publicly).
 * Company: §5 xlsx. Consultant: §6.2 intro bands. Excl. 5% VAT.
 */
class CommercialPriceBook
{
    public const COMPANY_LIST_AED = [
        'client_scope_basic' => 2500,
        'client_scope_pro' => 4999,
        'client_esg_starter' => 18000,
        'client_esg_complete' => 36000,
        'client_enterprise' => null, // custom
    ];

    public const CONSULTANT_RATE_LE_10 = 1399;
    public const CONSULTANT_RATE_GT_10 = 1199;

    /**
     * Map marketing package codes → live subscription_plans.plan_code until Phase 8.
     */
    public const COMPANY_LIVE_PLAN_MAP = [
        'client_scope_basic' => 'client_starter',
        'client_scope_pro' => 'client_growth',
        'client_esg_starter' => 'client_growth',
        'client_esg_complete' => 'client_growth',
        'client_enterprise' => 'client_enterprise',
    ];

    /**
     * @return array{
     *   amount_aed: float|null,
     *   custom: bool,
     *   label: string,
     *   live_plan_code: string|null,
     *   breakdown: string,
     *   band: string|null
     * }
     */
    public static function suggestCompanyQuote(string $packageCode): array
    {
        $amount = self::COMPANY_LIST_AED[$packageCode] ?? null;
        $live = self::COMPANY_LIVE_PLAN_MAP[$packageCode] ?? null;
        $label = CompanyPackageOptions::label($packageCode);

        if ($amount === null) {
            return [
                'amount_aed' => null,
                'custom' => true,
                'label' => $label,
                'live_plan_code' => $live,
                'breakdown' => 'Enterprise / custom — set quote manually after sales discussion.',
                'band' => 'custom',
            ];
        }

        return [
            'amount_aed' => (float) $amount,
            'custom' => false,
            'label' => $label,
            'live_plan_code' => $live,
            'breakdown' => "{$label} list AED " . number_format($amount, 0) . " / year excl. VAT (xlsx §5). Activates as live plan `{$live}`.",
            'band' => 'list',
        ];
    }

    /**
     * @return array{
     *   amount_aed: float|null,
     *   custom: bool,
     *   rate_aed: float|null,
     *   entity_count: int,
     *   breakdown: string,
     *   band: string,
     *   suggested_pack_code: string|null
     * }
     */
    public static function suggestConsultantQuote(int $entityCount, bool $wantsEnterprise = false): array
    {
        $entityCount = max(1, $entityCount);

        if ($wantsEnterprise) {
            return [
                'amount_aed' => null,
                'custom' => true,
                'rate_aed' => null,
                'entity_count' => $entityCount,
                'breakdown' => 'Enterprise / white-label — set quote manually. Slot activation still uses nearest agency pack + extras.',
                'band' => 'enterprise',
                'suggested_pack_code' => self::nearestAgencyPackCode($entityCount),
            ];
        }

        $rate = $entityCount > 10 ? self::CONSULTANT_RATE_GT_10 : self::CONSULTANT_RATE_LE_10;
        $total = $rate * $entityCount;
        $band = $entityCount > 10 ? '>10 @ 1,199' : '≤10 @ 1,399';

        return [
            'amount_aed' => (float) $total,
            'custom' => false,
            'rate_aed' => (float) $rate,
            'entity_count' => $entityCount,
            'breakdown' => "{$entityCount} × AED " . number_format($rate, 0) . " = AED " . number_format($total, 0) . " / year excl. VAT ({$band}). Preferential ≥10 onboarded is sales-only.",
            'band' => $band,
            'suggested_pack_code' => self::nearestAgencyPackCode($entityCount),
        ];
    }

    /** Smallest legacy pack whose base slots ≥ count, else consultant_50. */
    public static function nearestAgencyPackCode(int $entityCount): string
    {
        foreach ([5, 10, 25, 50] as $n) {
            if ($entityCount <= $n) {
                return 'consultant_' . $n;
            }
        }

        return 'consultant_50';
    }

    public static function extraSlotsNeeded(int $entityCount, string $packCode): int
    {
        $base = (int) (ConsultantAgencyPlanMatrix::forPlanCode($packCode)['consultant_slot_count'] ?? 0);

        return max(0, $entityCount - $base);
    }
}
