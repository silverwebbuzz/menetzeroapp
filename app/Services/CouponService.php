<?php

namespace App\Services;

use App\Models\SubscriptionCoupon;
use App\Models\SubscriptionCouponRedemption;
use App\Models\SubscriptionPlan;
use App\Models\ClientSubscription;
use App\Models\PaymentTransaction;

class CouponService
{
    /**
     * Validate a coupon for a company + plan + charge amount.
     *
     * @return array{coupon: SubscriptionCoupon, discount: float, final_amount: float, is_free: bool}
     */
    public function validateForCheckout(
        string $code,
        int $companyId,
        SubscriptionPlan $plan,
        float $chargeAmount,
        string $currency
    ): array {
        $coupon = SubscriptionCoupon::where('code', SubscriptionCoupon::normalizeCode($code))->first();

        if (!$coupon) {
            throw new \RuntimeException('Invalid coupon code.');
        }

        if (!$coupon->is_active) {
            throw new \RuntimeException('This coupon is no longer active.');
        }

        if (!$coupon->isWithinWindow()) {
            throw new \RuntimeException('This coupon has expired or is not yet valid.');
        }

        if (!$coupon->hasUsesRemaining()) {
            throw new \RuntimeException('This coupon has reached its usage limit.');
        }

        if ($coupon->subscription_plan_id && (int) $coupon->subscription_plan_id !== (int) $plan->id) {
            throw new \RuntimeException('This coupon does not apply to the selected plan.');
        }

        if ($this->companyAlreadyRedeemed($coupon, $companyId)) {
            throw new \RuntimeException('This coupon has already been used by your company.');
        }

        $discount = $this->calculateDiscount($coupon, $chargeAmount, $currency);
        $final = max(0, round($chargeAmount - $discount, 2));

        return [
            'coupon' => $coupon,
            'discount' => $discount,
            'final_amount' => $final,
            'is_free' => $coupon->type === 'free' || $final <= 0,
        ];
    }

    public function calculateDiscount(SubscriptionCoupon $coupon, float $amount, string $currency): float
    {
        if ($coupon->type === 'free') {
            return $amount;
        }

        if ($coupon->type === 'percent') {
            $pct = (float) $coupon->discount_percent;

            return round($amount * ($pct / 100), 2);
        }

        $fixed = strtoupper($currency) === 'AED'
            ? (float) $coupon->discount_amount_aed
            : (float) $coupon->discount_amount_inr;

        return min($amount, round($fixed, 2));
    }

    public function recordRedemption(
        SubscriptionCoupon $coupon,
        int $companyId,
        float $discountApplied,
        string $currency,
        ?ClientSubscription $subscription = null,
        ?PaymentTransaction $transaction = null
    ): SubscriptionCouponRedemption {
        $coupon->increment('used_count');

        return SubscriptionCouponRedemption::create([
            'coupon_id' => $coupon->id,
            'company_id' => $companyId,
            'subscription_id' => $subscription?->id,
            'transaction_id' => $transaction?->id,
            'discount_applied' => $discountApplied,
            'currency' => strtoupper($currency),
            'redeemed_at' => now(),
        ]);
    }

    private function companyAlreadyRedeemed(SubscriptionCoupon $coupon, int $companyId): bool
    {
        return SubscriptionCouponRedemption::where('coupon_id', $coupon->id)
            ->where('company_id', $companyId)
            ->exists();
    }
}
