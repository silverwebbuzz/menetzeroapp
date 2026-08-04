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
        'client_scope_basic' => 'client_scope_basic',
        'client_scope_pro' => 'client_scope_pro',
        'client_esg_starter' => 'client_esg_starter',
        'client_esg_complete' => 'client_esg_complete',
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
     * Consultant request with company-style package profile × client count.
     * Suggest = company list price × entities (xlsx / price book). Enterprise = custom.
     * Legacy rows without package_code fall back to §6 preferential per-client band.
     *
     * @return array{
     *   amount_aed: float|null,
     *   custom: bool,
     *   rate_aed: float|null,
     *   entity_count: int,
     *   package_code: string|null,
     *   breakdown: string,
     *   band: string,
     *   suggested_pack_code: string|null
     * }
     */
    public static function suggestConsultantQuote(
        int $entityCount,
        bool $wantsEnterprise = false,
        ?string $packageCode = null,
    ): array {
        return self::suggestConsultantLinesQuote([
            [
                'package_code' => $packageCode
                    ?? ($wantsEnterprise ? 'client_enterprise' : 'client_scope_basic'),
                'entity_count' => max(1, $entityCount),
            ],
        ]);
    }

    /**
     * Multi-line consultant quote (Phase 3) — Σ (list × qty) across depth lines.
     *
     * @param  list<array{package_code: string, entity_count: int}>  $lines
     * @return array{
     *   amount_aed: float|null,
     *   custom: bool,
     *   rate_aed: float|null,
     *   entity_count: int,
     *   package_code: string|null,
     *   breakdown: string,
     *   band: string,
     *   suggested_pack_code: string|null,
     *   suggested_activations: list<array{client_package_code: string, consultant_plan_code: string, entity_count: int}>,
     *   min10_tip?: bool,
     *   line_quotes: list<array<string, mixed>>
     * }
     */
    public static function suggestConsultantLinesQuote(array $lines): array
    {
        $normalized = [];
        foreach ($lines as $line) {
            $code = (string) ($line['package_code'] ?? '');
            $count = (int) ($line['entity_count'] ?? 0);
            if ($code === '' || $count < 1) {
                continue;
            }
            if (isset($normalized[$code])) {
                $normalized[$code] += $count;
            } else {
                $normalized[$code] = $count;
            }
        }

        if ($normalized === []) {
            return [
                'amount_aed' => null,
                'custom' => true,
                'rate_aed' => null,
                'entity_count' => 0,
                'package_code' => null,
                'breakdown' => 'No package lines on this request.',
                'band' => 'custom',
                'suggested_pack_code' => null,
                'suggested_activations' => [],
                'line_quotes' => [],
            ];
        }

        $totalClients = array_sum($normalized);
        $lineQuotes = [];
        $activations = [];
        $breakdownParts = [];
        $sum = 0.0;
        $anyCustom = false;
        $singleCode = count($normalized) === 1 ? array_key_first($normalized) : null;

        foreach ($normalized as $code => $count) {
            $unit = self::companyAmountAed($code);
            $label = CompanyPackageOptions::label($code);
            $consultantPlan = self::suggestedConsultantPlanCode($code);
            $activations[] = [
                'client_package_code' => $code,
                'consultant_plan_code' => $consultantPlan,
                'entity_count' => $count,
            ];

            if ($unit === null) {
                $anyCustom = true;
                $lineQuotes[] = [
                    'package_code' => $code,
                    'entity_count' => $count,
                    'amount_aed' => null,
                    'custom' => true,
                ];
                $breakdownParts[] = "{$count} × {$label} (custom) → `{$consultantPlan}`";
                continue;
            }

            $lineTotal = $unit * $count;
            $sum += $lineTotal;
            $lineQuotes[] = [
                'package_code' => $code,
                'entity_count' => $count,
                'amount_aed' => (float) $lineTotal,
                'rate_aed' => (float) $unit,
                'custom' => false,
            ];
            $breakdownParts[] = "{$count} × {$label} @ AED " . number_format($unit, 0)
                . ' = AED ' . number_format($lineTotal, 0)
                . " → `{$consultantPlan}`";
        }

        $preferential = $totalClients < 10
            ? ' Preferential ≥10 / 12 months is sales policy only.'
            : ' Count ≥10 — confirm preferential override offline if contracted.';

        $breakdown = implode(' · ', $breakdownParts)
            . ($anyCustom
                ? ' · Total custom — set quote manually.'
                : ' · Total AED ' . number_format($sum, 0) . ' / yr excl. VAT.')
            . $preferential
            . ' Activation creates one consultant_subscriptions row per line (own slot_limit + expiry).';

        return [
            'amount_aed' => $anyCustom ? null : (float) $sum,
            'custom' => $anyCustom,
            'rate_aed' => null,
            'entity_count' => $totalClients,
            'package_code' => $singleCode,
            'breakdown' => $breakdown,
            'band' => $anyCustom ? 'custom' : 'package×clients',
            'suggested_pack_code' => $activations[0]['consultant_plan_code'] ?? 'consultant_scope_basic',
            'suggested_activations' => $activations,
            'min10_tip' => $totalClients < 10,
            'line_quotes' => $lineQuotes,
        ];
    }

    /**
     * Map request company depth → consultant_* plan row to activate (multi-package Phase 1+).
     */
    public static function suggestedConsultantPlanCode(?string $clientPackageCode = null): string
    {
        if ($clientPackageCode) {
            $mapped = ConsultantAgencyPlanMatrix::consultantPlanForClientDepth($clientPackageCode);
            if ($mapped) {
                return $mapped;
            }
        }

        return 'consultant_scope_basic';
    }

    /**
     * @deprecated Prefer suggestedConsultantPlanCode($packageCode). Kept for older call sites.
     */
    public static function nearestAgencyPackCode(int $entityCount): string
    {
        return self::suggestedConsultantPlanCode('client_scope_basic');
    }

    public static function extraSlotsNeeded(int $entityCount, string $packCode): int
    {
        $base = ConsultantAgencyPlanMatrix::slotCountForPlanCode($packCode);

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
