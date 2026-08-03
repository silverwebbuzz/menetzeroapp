<?php

namespace App\Data;

use App\Models\CommercialPriceBookEntry;

/**
 * Admin-facing list prices for quotes (not shown publicly).
 * Defaults from §5 / §6.2; live values from commercial_price_book_entries (Phase 7).
 * Amounts excl. 5% VAT.
 */
class CommercialPriceBook
{
    /** Fallback defaults if DB empty / pre-migration. */
    public const COMPANY_LIST_AED = [
        'client_scope_basic' => 2500,
        'client_scope_pro' => 4999,
        'client_esg_starter' => 18000,
        'client_esg_complete' => 36000,
        'client_enterprise' => null,
    ];

    public const CONSULTANT_RATE_LE_10 = 1399;
    public const CONSULTANT_RATE_GT_10 = 1199;

    public const COMPANY_LIVE_PLAN_MAP = [
        'client_scope_basic' => 'client_starter',
        'client_scope_pro' => 'client_growth',
        'client_esg_starter' => 'client_growth',
        'client_esg_complete' => 'client_growth',
        'client_enterprise' => 'client_enterprise',
    ];

    public static function companyAmountAed(string $packageCode): ?float
    {
        $entry = self::safeFind($packageCode);
        if ($entry) {
            if ($entry->is_custom || $entry->amount_aed === null) {
                return null;
            }

            return (float) $entry->amount_aed;
        }

        $fallback = self::COMPANY_LIST_AED[$packageCode] ?? null;

        return $fallback === null ? null : (float) $fallback;
    }

    public static function consultantRateAed(int $entityCount): float
    {
        $entityCount = max(1, $entityCount);
        $code = $entityCount > 10 ? 'consultant_rate_gt_10' : 'consultant_rate_le_10';
        $entry = self::safeFind($code);

        if ($entry && $entry->amount_aed !== null) {
            return (float) $entry->amount_aed;
        }

        return $entityCount > 10
            ? (float) self::CONSULTANT_RATE_GT_10
            : (float) self::CONSULTANT_RATE_LE_10;
    }

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
        $amount = self::companyAmountAed($packageCode);
        $live = self::COMPANY_LIVE_PLAN_MAP[$packageCode] ?? null;
        $entry = self::safeFind($packageCode);
        $label = $entry?->label ?? CompanyPackageOptions::label($packageCode);
        $custom = $amount === null;

        if ($custom) {
            return [
                'amount_aed' => null,
                'custom' => true,
                'label' => $label,
                'live_plan_code' => $live,
                'breakdown' => $entry?->notes
                    ?: 'Enterprise / custom — set quote manually after sales discussion.',
                'band' => 'custom',
            ];
        }

        return [
            'amount_aed' => $amount,
            'custom' => false,
            'label' => $label,
            'live_plan_code' => $live,
            'breakdown' => "{$label} list AED " . number_format($amount, 0) . " / year excl. VAT (price book). Activates as live plan `{$live}`.",
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

        $rate = self::consultantRateAed($entityCount);
        $total = $rate * $entityCount;
        $band = $entityCount > 10
            ? '>10 @ ' . number_format($rate, 0)
            : '≤10 @ ' . number_format($rate, 0);

        return [
            'amount_aed' => (float) $total,
            'custom' => false,
            'rate_aed' => (float) $rate,
            'entity_count' => $entityCount,
            'breakdown' => "{$entityCount} × AED " . number_format($rate, 0) . ' = AED ' . number_format($total, 0) . " / year excl. VAT ({$band}). Preferential ≥10 onboarded is sales-only.",
            'band' => $band,
            'suggested_pack_code' => self::nearestAgencyPackCode($entityCount),
        ];
    }

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

    protected static function safeFind(string $code): ?CommercialPriceBookEntry
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('commercial_price_book_entries')) {
                return null;
            }

            return CommercialPriceBookEntry::findByCode($code);
        } catch (\Throwable) {
            return null;
        }
    }
}
