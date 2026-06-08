<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Models\SubscriptionPlan;

/**
 * Resolves the display currency for the visitor and exposes the amount that
 * must be charged through the payment gateways (always INR).
 */
class CurrencyService
{
    public const SUPPORTED = ['AED', 'INR'];

    /**
     * The currency to show prices in: explicit user choice, then geo-detection
     * (Cloudflare country header), then the configured default.
     */
    public static function displayCurrency(): string
    {
        $session = session('display_currency');
        if (in_array($session, self::SUPPORTED, true)) {
            return $session;
        }

        if (SiteSetting::get('currency_auto_detect', '1') === '1') {
            $country = strtoupper((string) request()->header('CF-IPCountry'));
            if ($country === 'IN') {
                return 'INR';
            }
            if ($country === 'AE') {
                return 'AED';
            }
        }

        return strtoupper(SiteSetting::get('default_currency', 'AED'));
    }

    public static function setDisplayCurrency(string $code): void
    {
        $code = strtoupper($code);
        if (in_array($code, self::SUPPORTED, true)) {
            session(['display_currency' => $code]);
        }
    }

    /**
     * Display price for a plan, in the given (or auto-resolved) currency.
     *
     * @return array{currency:string, amount:float}
     */
    public static function displayPrice(SubscriptionPlan $plan, ?string $currency = null): array
    {
        $currency = $currency ?: self::displayCurrency();
        $amount = $currency === 'INR'
            ? (float) $plan->price_inr
            : (float) $plan->price_annual;

        return ['currency' => $currency, 'amount' => $amount];
    }

    /**
     * Amount sent to the payment gateway. Matches the visitor's display currency
     * so Cashfree/Razorpay checkout shows the same currency they picked on our
     * site (AED or INR). Settlement to your bank is still in INR per gateway
     * rules; AED orders use Cashfree International / Pay Native.
     *
     * @return array{currency:string, amount:float, display_currency:string}
     */
    public static function chargeAmount(SubscriptionPlan $plan, ?string $displayCurrency = null): array
    {
        $displayCurrency = strtoupper($displayCurrency ?: self::displayCurrency());

        if ($displayCurrency === 'AED') {
            return [
                'currency' => 'AED',
                'amount' => (float) $plan->price_annual,
                'display_currency' => 'AED',
            ];
        }

        return [
            'currency' => 'INR',
            'amount' => (float) $plan->price_inr,
            'display_currency' => 'INR',
        ];
    }

    /**
     * Human-friendly currency prefix.
     */
    public static function symbol(string $code): string
    {
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
