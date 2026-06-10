<?php

namespace App\Services;

use App\Data\PartnerPlanMatrix;
use App\Models\Company;
use App\Models\PartnerClientEngagement;
use App\Models\PartnerSubscription;
use App\Models\PartnerSubscriptionAddon;
use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PartnerSubscriptionService
{
    /**
     * Last day of the calendar contract year (31 Dec).
     */
    public function contractYearEnd(int $contractYear): Carbon
    {
        return Carbon::create($contractYear, 12, 31)->endOfDay();
    }

    /**
     * Pro-rata from a start date through 31 Dec of the contract year.
     */
    public function proRataToContractYearEnd(float $annualPrice, int $contractYear, ?Carbon $from = null): float
    {
        $from = ($from ?? now())->copy()->startOfDay();
        $yearStart = Carbon::create($contractYear, 1, 1)->startOfDay();
        $yearEnd = Carbon::create($contractYear, 12, 31)->startOfDay();

        if ($from->greaterThan($yearEnd)) {
            return 0.0;
        }

        $effectiveStart = $from->greaterThan($yearStart) ? $from : $yearStart;
        $daysRemaining = (int) $effectiveStart->diffInDays($yearEnd) + 1;
        $totalDays = $yearStart->isLeapYear() ? 366 : 365;

        return round($annualPrice * ($daysRemaining / $totalDays), 2);
    }

    public function getActiveSubscription(int $partnerCompanyId): ?PartnerSubscription
    {
        return PartnerSubscription::forPartner($partnerCompanyId)
            ->active()
            ->orderByDesc('expires_at')
            ->first();
    }

    public function activeSlotUsage(int $partnerCompanyId, ?PartnerSubscription $subscription = null): int
    {
        $subscription ??= $this->getActiveSubscription($partnerCompanyId);

        if (!$subscription) {
            return 0;
        }

        return PartnerClientEngagement::query()
            ->where('partner_subscription_id', $subscription->id)
            ->active()
            ->count();
    }

    public function slotsRemaining(int $partnerCompanyId, ?PartnerSubscription $subscription = null): int
    {
        $subscription ??= $this->getActiveSubscription($partnerCompanyId);

        if (!$subscription) {
            return 0;
        }

        return max(0, (int) $subscription->slot_limit - $this->activeSlotUsage($partnerCompanyId, $subscription));
    }

    public function canConsumeSlot(int $partnerCompanyId): bool
    {
        return $this->slotsRemaining($partnerCompanyId) > 0;
    }

    /**
     * @return array{
     *   type: string,
     *   requires_payment: bool,
     *   charge_amount: float,
     *   charge_currency: string,
     *   contract_year: int,
     *   pro_rata: bool,
     *   days_remaining: int,
     *   message: string
     * }
     */
    public function resolvePackPurchase(
        Company $partner,
        SubscriptionPlan $plan,
        ?int $contractYear = null,
        string $chargeCurrency = 'AED',
    ): array {
        $this->assertPartnerCompany($partner);

        if (!$plan->isPartnerPack()) {
            throw new RuntimeException('Selected plan is not a partner pack.');
        }

        $contractYear ??= (int) now()->year;
        $annualPrice = (float) $plan->price_annual;
        $isMidYear = !now()->startOfDay()->equalTo(Carbon::create($contractYear, 1, 1)->startOfDay());
        $chargeAmount = $isMidYear
            ? $this->proRataToContractYearEnd($annualPrice, $contractYear)
            : $annualPrice;

        $yearEnd = $this->contractYearEnd($contractYear);
        $daysRemaining = (int) now()->startOfDay()->diffInDays($yearEnd->copy()->startOfDay()) + 1;

        return [
            'type' => 'new_pack',
            'requires_payment' => $chargeAmount > 0,
            'charge_amount' => $chargeAmount,
            'charge_currency' => strtoupper($chargeCurrency),
            'contract_year' => $contractYear,
            'pro_rata' => $isMidYear,
            'days_remaining' => max(0, $daysRemaining),
            'message' => $isMidYear
                ? "Pro-rata pack price through 31 Dec {$contractYear}."
                : "Full annual pack price for calendar year {$contractYear}.",
        ];
    }

    /**
     * Activate a partner pack from a completed payment (P19 wiring).
     */
    public function completePackTransaction(PaymentTransaction $transaction, array $gatewayRefs = []): PartnerSubscription
    {
        if ($transaction->status === 'completed') {
            $existingId = $transaction->metadata['partner_subscription_id'] ?? null;

            return $existingId
                ? PartnerSubscription::findOrFail($existingId)
                : throw new RuntimeException('Completed partner transaction is missing subscription reference.');
        }

        $metadata = array_merge($transaction->metadata ?? [], $gatewayRefs);
        $planId = $metadata['plan_id'] ?? null;
        $contractYear = (int) ($metadata['contract_year'] ?? now()->year);

        if (!$planId) {
            throw new RuntimeException('Partner pack transaction is missing plan reference.');
        }

        $plan = SubscriptionPlan::findOrFail($planId);
        $partner = Company::findOrFail($transaction->company_id);

        $subscription = $this->activatePackSubscription($partner, $plan, [
            'contract_year' => $contractYear,
            'starts_at' => now()->toDateString(),
            'payment_transaction_id' => $transaction->id,
            'metadata' => array_merge($gatewayRefs, [
                'provision_type' => $metadata['provision_type'] ?? 'paid',
            ]),
        ]);

        $transaction->update([
            'status' => 'completed',
            'paid_at' => now(),
            'metadata' => array_merge($metadata, $gatewayRefs, [
                'partner_subscription_id' => $subscription->id,
            ]),
        ]);

        return $subscription;
    }

    /**
     * Create or replace the active subscription for a contract year.
     */
    public function activatePackSubscription(Company $partner, SubscriptionPlan $plan, array $options = []): PartnerSubscription
    {
        $this->assertPartnerCompany($partner);

        if (!$plan->isPartnerPack()) {
            throw new RuntimeException('Plan must be a partner pack.');
        }

        $contractYear = (int) ($options['contract_year'] ?? now()->year);
        $baseSlots = PartnerPlanMatrix::slotCountForPlanCode($plan->plan_code);
        $extraSlots = (int) ($options['extra_slots_purchased'] ?? 0);

        return DB::transaction(function () use ($partner, $plan, $options, $contractYear, $baseSlots, $extraSlots) {
            PartnerSubscription::forPartner($partner->id)
                ->where('contract_year', $contractYear)
                ->where('status', 'active')
                ->update(['status' => 'expired']);

            return PartnerSubscription::create([
                'partner_company_id' => $partner->id,
                'subscription_plan_id' => $plan->id,
                'contract_year' => $contractYear,
                'slot_limit' => $baseSlots + $extraSlots,
                'extra_slots_purchased' => $extraSlots,
                'starts_at' => $options['starts_at'] ?? now()->toDateString(),
                'expires_at' => $this->contractYearEnd($contractYear)->toDateString(),
                'status' => 'active',
                'payment_transaction_id' => $options['payment_transaction_id'] ?? null,
                'metadata' => $options['metadata'] ?? null,
            ]);
        });
    }

    /**
     * Admin grant — no payment required.
     */
    public function grantPackSubscription(
        Company $partner,
        string $planCode,
        int $contractYear,
        array $metadata = [],
    ): PartnerSubscription {
        $plan = SubscriptionPlan::where('plan_code', $planCode)->where('plan_category', 'partner')->firstOrFail();

        return $this->activatePackSubscription($partner, $plan, [
            'contract_year' => $contractYear,
            'metadata' => array_merge($metadata, ['provision_type' => 'admin_grant']),
        ]);
    }

    public function addExtraSlots(PartnerSubscription $subscription, int $quantity, ?PaymentTransaction $transaction = null): PartnerSubscription
    {
        if ($quantity < 1) {
            throw new RuntimeException('Quantity must be at least 1.');
        }

        $subscription->increment('extra_slots_purchased', $quantity);
        $subscription->increment('slot_limit', $quantity);

        PartnerSubscriptionAddon::create([
            'partner_subscription_id' => $subscription->id,
            'addon_type' => 'extra_slot',
            'quantity' => $quantity,
            'amount_aed' => PartnerPlanMatrix::EXTRA_SLOT_PRICE_AED * $quantity,
            'payment_transaction_id' => $transaction?->id,
        ]);

        return $subscription->fresh();
    }

    public function archiveEngagement(PartnerClientEngagement $engagement): PartnerClientEngagement
    {
        $engagement->update([
            'status' => 'archived',
            'archived_at' => now(),
        ]);

        return $engagement->fresh();
    }

    /**
     * @return array{used: int, limit: int, remaining: int, contract_year: int|null, expires_at: string|null}
     */
    public function slotSummary(int $partnerCompanyId): array
    {
        $subscription = $this->getActiveSubscription($partnerCompanyId);
        $used = $this->activeSlotUsage($partnerCompanyId, $subscription);

        return [
            'used' => $used,
            'limit' => $subscription?->slot_limit ?? 0,
            'remaining' => max(0, ($subscription?->slot_limit ?? 0) - $used),
            'contract_year' => $subscription?->contract_year,
            'expires_at' => $subscription?->expires_at?->toDateString(),
        ];
    }

    private function assertPartnerCompany(Company $company): void
    {
        if ($company->company_type !== 'partner') {
            throw new RuntimeException('Company must be a partner organisation.');
        }
    }
}
