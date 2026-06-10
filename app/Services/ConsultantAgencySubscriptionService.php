<?php

namespace App\Services;

use App\Data\ConsultantAgencyPlanMatrix;
use App\Data\PlanEntitlementDefaults;
use App\Models\Company;
use App\Models\ConsultantClientEngagement;
use App\Models\ConsultantSubscription;
use App\Models\ConsultantSubscriptionAddon;
use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ConsultantAgencySubscriptionService
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

    public function getActiveSubscription(int $consultantCompanyId): ?ConsultantSubscription
    {
        return ConsultantSubscription::forConsultant($consultantCompanyId)
            ->with('plan')
            ->active()
            ->orderByDesc('expires_at')
            ->first();
    }

    public function activeSlotUsage(int $consultantCompanyId, ?ConsultantSubscription $subscription = null): int
    {
        $subscription ??= $this->getActiveSubscription($consultantCompanyId);

        if (!$subscription) {
            return 0;
        }

        return ConsultantClientEngagement::query()
            ->where('consultant_subscription_id', $subscription->id)
            ->active()
            ->count();
    }

    public function slotsRemaining(int $consultantCompanyId, ?ConsultantSubscription $subscription = null): int
    {
        $subscription ??= $this->getActiveSubscription($consultantCompanyId);

        if (!$subscription) {
            return 0;
        }

        return max(0, (int) $subscription->slot_limit - $this->activeSlotUsage($consultantCompanyId, $subscription));
    }

    public function canConsumeSlot(int $consultantCompanyId): bool
    {
        return $this->slotsRemaining($consultantCompanyId) > 0;
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
        Company $consultantOrg,
        SubscriptionPlan $plan,
        ?int $contractYear = null,
        string $chargeCurrency = 'AED',
    ): array {
        $this->assertConsultantOrg($consultantOrg);

        if (!$plan->isConsultantAgencyPack()) {
            throw new RuntimeException('Selected plan is not a consultant pack.');
        }

        $contractYear ??= (int) now()->year;
        $chargeCurrency = strtoupper($chargeCurrency);
        $annualPrice = $chargeCurrency === 'INR'
            ? (float) ($plan->price_inr ?? PlanEntitlementDefaults::defaultPriceInr((float) $plan->price_annual))
            : (float) $plan->price_annual;
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
     * Activate a consultant pack from a completed payment (P19 wiring).
     */
    public function completePackTransaction(PaymentTransaction $transaction, array $gatewayRefs = []): ConsultantSubscription
    {
        if ($transaction->status === 'completed') {
            $existingId = $transaction->metadata['consultant_subscription_id'] ?? null;

            return $existingId
                ? ConsultantSubscription::findOrFail($existingId)
                : throw new RuntimeException('Completed partner transaction is missing subscription reference.');
        }

        $metadata = array_merge($transaction->metadata ?? [], $gatewayRefs);
        $planId = $metadata['plan_id'] ?? null;
        $contractYear = (int) ($metadata['contract_year'] ?? now()->year);

        if (!$planId) {
            throw new RuntimeException('consultant pack transaction is missing plan reference.');
        }

        $plan = SubscriptionPlan::findOrFail($planId);
        $consultantOrg = Company::findOrFail($transaction->company_id);

        $subscription = $this->activatePackSubscription($consultantOrg, $plan, [
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
                'consultant_subscription_id' => $subscription->id,
            ]),
        ]);

        return $subscription;
    }

    /**
     * Create or replace the active subscription for a contract year.
     */
    public function activatePackSubscription(Company $consultantOrg, SubscriptionPlan $plan, array $options = []): ConsultantSubscription
    {
        $this->assertConsultantOrg($consultantOrg);

        if (!$plan->isConsultantAgencyPack()) {
            throw new RuntimeException('Plan must be a consultant pack.');
        }

        $contractYear = (int) ($options['contract_year'] ?? now()->year);
        $baseSlots = ConsultantAgencyPlanMatrix::slotCountForPlanCode($plan->plan_code);
        $extraSlots = (int) ($options['extra_slots_purchased'] ?? 0);

        return DB::transaction(function () use ($consultantOrg, $plan, $options, $contractYear, $baseSlots, $extraSlots) {
            $oldSubscriptions = ConsultantSubscription::forConsultant($consultantOrg->id)
                ->where('contract_year', $contractYear)
                ->where('status', 'active')
                ->lockForUpdate()
                ->get();

            $carriedExtraSlots = $extraSlots;
            $oldIds = [];

            foreach ($oldSubscriptions as $old) {
                $oldIds[] = $old->id;

                if (!array_key_exists('extra_slots_purchased', $options)) {
                    $carriedExtraSlots += (int) $old->extra_slots_purchased;
                }
            }

            if (!empty($oldIds)) {
                ConsultantSubscription::whereIn('id', $oldIds)->update(['status' => 'expired']);
            }

            $newSubscription = ConsultantSubscription::create([
                'consultant_company_id' => $consultantOrg->id,
                'subscription_plan_id' => $plan->id,
                'contract_year' => $contractYear,
                'slot_limit' => $baseSlots + $carriedExtraSlots,
                'extra_slots_purchased' => $carriedExtraSlots,
                'starts_at' => $options['starts_at'] ?? now()->toDateString(),
                'expires_at' => $this->contractYearEnd($contractYear)->toDateString(),
                'status' => 'active',
                'payment_transaction_id' => $options['payment_transaction_id'] ?? null,
                'metadata' => $options['metadata'] ?? null,
            ]);

            if (!empty($oldIds)) {
                ConsultantClientEngagement::whereIn('consultant_subscription_id', $oldIds)
                    ->where('status', 'active')
                    ->update(['consultant_subscription_id' => $newSubscription->id]);
            }

            return $newSubscription;
        });
    }

    /**
     * Admin grant — no payment required.
     */
    public function grantPackSubscription(
        Company $consultantOrg,
        string $planCode,
        int $contractYear,
        array $metadata = [],
    ): ConsultantSubscription {
        $plan = SubscriptionPlan::where('plan_code', $planCode)->where('plan_category', 'consultant_agency')->firstOrFail();

        return $this->activatePackSubscription($consultantOrg, $plan, [
            'contract_year' => $contractYear,
            'metadata' => array_merge($metadata, ['provision_type' => 'admin_grant']),
        ]);
    }

    /**
     * @return array{
     *   type: string,
     *   requires_payment: bool,
     *   charge_amount: float,
     *   charge_currency: string,
     *   contract_year: int,
     *   pro_rata: bool,
     *   quantity: int,
     *   message: string
     * }
     */
    public function resolveExtraSlotPurchase(
        ConsultantSubscription $subscription,
        int $quantity,
        string $chargeCurrency = 'AED',
    ): array {
        if ($quantity < 1 || $quantity > 50) {
            throw new RuntimeException('Quantity must be between 1 and 50.');
        }

        if (!$subscription->isActive()) {
            throw new RuntimeException('No active agency pack for extra slots.');
        }

        $contractYear = (int) $subscription->contract_year;
        $chargeCurrency = strtoupper($chargeCurrency);
        $annualTotal = ConsultantAgencyPlanMatrix::EXTRA_SLOT_PRICE_AED * $quantity;
        $chargeAmount = $chargeCurrency === 'INR'
            ? PlanEntitlementDefaults::defaultPriceInr($annualTotal)
            : $annualTotal;
        $isMidYear = !now()->startOfDay()->equalTo(Carbon::create($contractYear, 1, 1)->startOfDay());
        $chargeAmount = $isMidYear
            ? $this->proRataToContractYearEnd($chargeAmount, $contractYear)
            : $chargeAmount;

        return [
            'type' => 'extra_slot',
            'requires_payment' => $chargeAmount > 0,
            'charge_amount' => round($chargeAmount, 2),
            'charge_currency' => $chargeCurrency,
            'contract_year' => $contractYear,
            'pro_rata' => $isMidYear,
            'quantity' => $quantity,
            'message' => "{$quantity} extra slot(s) through 31 Dec {$contractYear}.",
        ];
    }

    public function completeExtraSlotTransaction(PaymentTransaction $transaction, array $gatewayRefs = []): ConsultantSubscription
    {
        if ($transaction->status === 'completed') {
            $subscriptionId = $transaction->metadata['consultant_subscription_id'] ?? null;

            return $subscriptionId
                ? ConsultantSubscription::findOrFail($subscriptionId)
                : throw new RuntimeException('Completed extra slot transaction is missing subscription reference.');
        }

        $metadata = array_merge($transaction->metadata ?? [], $gatewayRefs);
        $subscriptionId = $metadata['consultant_subscription_id'] ?? null;
        $quantity = (int) ($metadata['quantity'] ?? 0);

        if (!$subscriptionId || $quantity < 1) {
            throw new RuntimeException('Extra slot transaction is missing subscription or quantity.');
        }

        $subscription = ConsultantSubscription::findOrFail($subscriptionId);

        return DB::transaction(function () use ($transaction, $metadata, $gatewayRefs, $subscription, $quantity) {
            $updated = $this->addExtraSlots($subscription, $quantity, $transaction);

            $transaction->update([
                'status' => 'completed',
                'paid_at' => now(),
                'metadata' => array_merge($metadata, $gatewayRefs, [
                    'consultant_subscription_id' => $updated->id,
                ]),
            ]);

            return $updated;
        });
    }

    /**
     * @return array{
     *   type: string,
     *   requires_payment: bool,
     *   charge_amount: float,
     *   charge_currency: string,
     *   contract_year: int,
     *   reporting_year: int,
     *   pro_rata: bool,
     *   message: string
     * }
     */
    public function resolveYearUnlockPurchase(
        ConsultantClientEngagement $engagement,
        int $reportingYear,
        string $chargeCurrency = 'AED',
    ): array {
        if (!$engagement->isActive()) {
            throw new RuntimeException('Client engagement must be active for a year unlock.');
        }

        $subscription = $engagement->subscription;

        if (!$subscription || !$subscription->isActive()) {
            throw new RuntimeException('Active agency pack required for reporting year unlock.');
        }

        $mode = app(ConsultantAgencyEntitlementService::class)->reportingYearMode($engagement, $reportingYear);

        if ($mode === ConsultantAgencyEntitlementService::MODE_PRY_FULL) {
            throw new RuntimeException("Reporting year {$reportingYear} already has full export access.");
        }

        if ($mode === ConsultantAgencyEntitlementService::MODE_READ_ONLY) {
            throw new RuntimeException("Reporting year {$reportingYear} is read-only — use renewal to carry this client forward.");
        }

        $contractYear = (int) $subscription->contract_year;
        $chargeCurrency = strtoupper($chargeCurrency);
        $annualPrice = ConsultantAgencyPlanMatrix::REPORTING_YEAR_UNLOCK_PRICE_AED;
        $chargeAmount = $chargeCurrency === 'INR'
            ? PlanEntitlementDefaults::defaultPriceInr($annualPrice)
            : $annualPrice;
        $isMidYear = !now()->startOfDay()->equalTo(Carbon::create($contractYear, 1, 1)->startOfDay());
        $chargeAmount = $isMidYear
            ? $this->proRataToContractYearEnd($chargeAmount, $contractYear)
            : $chargeAmount;

        return [
            'type' => 'reporting_year_unlock',
            'requires_payment' => $chargeAmount > 0,
            'charge_amount' => round($chargeAmount, 2),
            'charge_currency' => $chargeCurrency,
            'contract_year' => $contractYear,
            'reporting_year' => $reportingYear,
            'pro_rata' => $isMidYear,
            'message' => "Unlock full exports for {$reportingYear} on this client through 31 Dec {$contractYear}.",
        ];
    }

    public function addReportingYearUnlock(
        ConsultantClientEngagement $engagement,
        int $reportingYear,
        ?PaymentTransaction $transaction = null,
    ): ConsultantSubscriptionAddon {
        $subscription = $engagement->subscription;

        if (!$subscription) {
            throw new RuntimeException('Engagement has no linked subscription.');
        }

        if (ConsultantSubscriptionAddon::query()
            ->where('consultant_subscription_id', $subscription->id)
            ->where('addon_type', 'reporting_year_unlock')
            ->where('managed_company_id', $engagement->managed_company_id)
            ->where('reporting_year', $reportingYear)
            ->exists()) {
            throw new RuntimeException('This reporting year is already unlocked for this client.');
        }

        return ConsultantSubscriptionAddon::create([
            'consultant_subscription_id' => $subscription->id,
            'addon_type' => 'reporting_year_unlock',
            'quantity' => 1,
            'managed_company_id' => $engagement->managed_company_id,
            'reporting_year' => $reportingYear,
            'amount_aed' => ConsultantAgencyPlanMatrix::REPORTING_YEAR_UNLOCK_PRICE_AED,
            'payment_transaction_id' => $transaction?->id,
        ]);
    }

    public function completeYearUnlockTransaction(PaymentTransaction $transaction, array $gatewayRefs = []): ConsultantSubscriptionAddon
    {
        if ($transaction->status === 'completed') {
            $addonId = $transaction->metadata['consultant_addon_id'] ?? null;

            return $addonId
                ? ConsultantSubscriptionAddon::findOrFail($addonId)
                : throw new RuntimeException('Completed year unlock transaction is missing addon reference.');
        }

        $metadata = array_merge($transaction->metadata ?? [], $gatewayRefs);
        $engagementId = $metadata['engagement_id'] ?? null;
        $reportingYear = (int) ($metadata['reporting_year'] ?? 0);

        if (!$engagementId || $reportingYear < 2000) {
            throw new RuntimeException('Year unlock transaction is missing engagement or reporting year.');
        }

        $engagement = ConsultantClientEngagement::findOrFail($engagementId);

        return DB::transaction(function () use ($transaction, $metadata, $gatewayRefs, $engagement, $reportingYear) {
            $addon = $this->addReportingYearUnlock($engagement, $reportingYear, $transaction);

            $transaction->update([
                'status' => 'completed',
                'paid_at' => now(),
                'metadata' => array_merge($metadata, $gatewayRefs, [
                    'consultant_addon_id' => $addon->id,
                ]),
            ]);

            return $addon;
        });
    }

    public function addExtraSlots(ConsultantSubscription $subscription, int $quantity, ?PaymentTransaction $transaction = null): ConsultantSubscription
    {
        if ($quantity < 1) {
            throw new RuntimeException('Quantity must be at least 1.');
        }

        $subscription->increment('extra_slots_purchased', $quantity);
        $subscription->increment('slot_limit', $quantity);

        ConsultantSubscriptionAddon::create([
            'consultant_subscription_id' => $subscription->id,
            'addon_type' => 'extra_slot',
            'quantity' => $quantity,
            'amount_aed' => ConsultantAgencyPlanMatrix::EXTRA_SLOT_PRICE_AED * $quantity,
            'payment_transaction_id' => $transaction?->id,
        ]);

        return $subscription->fresh();
    }

    public function archiveEngagement(ConsultantClientEngagement $engagement): ConsultantClientEngagement
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
    public function slotSummary(int $consultantCompanyId, ?ConsultantSubscription $subscription = null): array
    {
        $subscription ??= $this->getActiveSubscription($consultantCompanyId);
        $used = $this->activeSlotUsage($consultantCompanyId, $subscription);

        return [
            'used' => $used,
            'limit' => $subscription?->slot_limit ?? 0,
            'remaining' => max(0, ($subscription?->slot_limit ?? 0) - $used),
            'contract_year' => $subscription?->contract_year,
            'expires_at' => $subscription?->expires_at?->toDateString(),
        ];
    }

    /**
     * @param  iterable<SubscriptionPlan>  $plans
     * @return array<int, array{charge_amount: float, pro_rata: bool}>
     */
    public function planQuotes(Company $consultantOrg, iterable $plans, ?int $contractYear = null): array
    {
        $contractYear ??= (int) now()->year;
        $quotes = [];

        foreach ($plans as $plan) {
            $quote = $this->resolvePackPurchase($consultantOrg, $plan, $contractYear);
            $quotes[$plan->id] = [
                'charge_amount' => $quote['charge_amount'],
                'pro_rata' => $quote['pro_rata'],
            ];
        }

        return $quotes;
    }

    public function validatePackChange(Company $consultantOrg, SubscriptionPlan $plan, ?ConsultantSubscription $current): void
    {
        if (!$current) {
            return;
        }

        $newBaseSlots = ConsultantAgencyPlanMatrix::slotCountForPlanCode($plan->plan_code);
        $newLimit = $newBaseSlots + (int) $current->extra_slots_purchased;
        $used = $this->activeSlotUsage($consultantOrg->id, $current);

        if ($used > $newLimit) {
            throw new RuntimeException(
                "You have {$used} active clients but {$plan->plan_name} allows {$newLimit} slots. "
                . 'Archive clients before downgrading your pack.'
            );
        }
    }

    private function assertConsultantOrg(Company $company): void
    {
        if ($company->company_type !== 'consultant') {
            throw new RuntimeException('Company must be a consultant organisation.');
        }
    }
}
