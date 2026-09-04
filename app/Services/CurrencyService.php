<?php

namespace App\Services;

use App\Models\SubscriptionPlan;

/**
 * Currency for display and for charging. AED only.
 *
 * MENetZero sells to UAE companies for UAE compliance, and the seller is an
 * Indian entity exporting services. Taking INR would make the sale look like a
 * domestic Indian supply rather than a zero-rated export, so INR was removed
 * from checkout entirely -- not merely hidden. The methods below are kept
 * (rather than deleted) because call sites across billing still ask for a
 * currency; they now always answer AED.
 */
class CurrencyService
{
    public const SUPPORTED = ['AED'];

    public const DEFAULT = 'AED';

    /**
     * Always AED. Geo-detection and the visitor's session choice were dropped
     * with INR: with a single supported currency there is nothing to detect
     * and nothing to switch to.
     */
    public static function displayCurrency(): string
    {
        return self::DEFAULT;
    }

    /**
     * No-op. Kept so the currency-switch route and any bookmarked link do not
     * fatal; an unsupported code was already ignored before.
     */
    public static function setDisplayCurrency(string $code): void
    {
        // Intentionally empty: AED is the only supported currency.
    }

    /**
     * Display price for a plan, in the given (or auto-resolved) currency.
     *
     * @return array{currency:string, amount:float}
     */
    public static function displayPrice(SubscriptionPlan $plan, ?string $currency = null): array
    {
        return ['currency' => self::DEFAULT, 'amount' => (float) $plan->price_annual];
    }

    /**
     * Amount sent to the payment gateway. Always AED, which requires
     * International Payments to be active on the Razorpay account. If that is
     * ever switched off the order now FAILS rather than silently re-pricing
     * into INR -- see the removed fallbacks in SubscriptionController and
     * ConsultantAgencyPaymentService. A visible error is recoverable; an
     * unintended INR charge changes the tax character of the sale.
     *
     * @return array{currency:string, amount:float, display_currency:string}
     */
    public static function chargeAmount(SubscriptionPlan $plan, ?string $displayCurrency = null): array
    {
        return [
            'currency' => self::DEFAULT,
            'amount' => (float) $plan->price_annual,
            'display_currency' => self::DEFAULT,
        ];
    }

    /**
     * Human-friendly currency prefix.
     */
    public static function symbol(string $code): string
    {
        // Historical transactions and invoices may still carry INR, so this
        // formats whatever it is given rather than assuming AED.
        return strtoupper($code) === 'INR' ? '₹' : 'AED';
    }

    /**
     * Format an amount with its currency prefix (no decimals).
     */
    public static function format($amount, string $code): string
    {
        return self::symbol($code) . ' ' . number_format((float) $amount, 0);
    }
}
