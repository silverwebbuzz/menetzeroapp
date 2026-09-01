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
        $subscriptions = ConsultantSubscription::forConsultant($consultantCompanyId)
            ->with('plan')
            ->active()
            ->orderByDesc('expires_at')
            ->get();

        return $subscriptions->first(fn (ConsultantSubscription $sub) => !$sub->isFreeTrial())
            ?? $subscriptions->first();
    }

    /**
     * Auto-provision the one free trial slot for new consultants (no paid pack yet).
     */
    public function ensureFreeTrialSubscription(Company $consultantOrg): ?ConsultantSubscription
    {
        $this->assertConsultantOrg($consultantOrg);

        $active = $this->getActiveSubscription($consultantOrg->id);

        if ($active) {
            return $active;
        }

        if ($this->hasConsumedFreeTrial($consultantOrg->id) || $this->hasEverHadPaidPack($consultantOrg->id)) {
            return null;
        }

        $plan = $this->resolveFreeTrialPlan();

        if (!$plan) {
            return null;
        }

        $contractYear = (int) now()->year;

        return $this->activatePackSubscription($consultantOrg, $plan, [
            'contract_year' => $contractYear,
            'metadata' => ['provision_type' => 'free_trial'],
            'expires_at' => now()->addYears(5)->toDateString(),
        ]);
    }

    /**
     * Fetch the free-trial pack, repairing/creating it from the plan matrix when
     * the stored row is missing or has drifted (e.g. wrong plan_category from an
     * incomplete migration). Prevents consultant onboarding from 500-ing with
     * "Plan must be a consultant pack." when the DB seed is out of sync.
     */
    private function resolveFreeTrialPlan(): ?SubscriptionPlan
    {
        $definition = ConsultantAgencyPlanMatrix::forPlanCode(ConsultantAgencyPlanMatrix::FREE_TRIAL_CODE);

        if (!$definition) {
            return null;
        }

        $plan = SubscriptionPlan::where('plan_code', ConsultantAgencyPlanMatrix::FREE_TRIAL_CODE)->first();

        if ($plan && $plan->isConsultantAgencyPack()) {
            return $plan;
        }

        $priceAnnual = (float) $definition['price_annual'];

        return SubscriptionPlan::updateOrCreate(
            ['plan_code' => ConsultantAgencyPlanMatrix::FREE_TRIAL_CODE],
            [
                'plan_name' => $definition['plan_name'],
                'plan_category' => $definition['plan_category'],
                'description' => $definition['description'],
                'price_annual' => $priceAnnual,
                'price_inr' => PlanEntitlementDefaults::defaultPriceInr($priceAnnual),
                'currency' => $definition['currency'],
                'billing_cycle' => $definition['billing_cycle'],
                'is_active' => $definition['is_active'],
                'sort_order' => $definition['sort_order'],
                'limits' => $definition['limits'],
                'entitlements' => $definition['entitlements'],
                'features' => $definition['features'],
            ]
        );
    }

    public function hasConsumedFreeTrial(int $consultantCompanyId): bool
    {
        return ConsultantClientEngagement::query()
            ->whereHas('subscription', function ($query) use ($consultantCompanyId) {
                $query->where('consultant_company_id', $consultantCompanyId)
                    ->where(function ($inner) {
                        $inner->where('metadata->provision_type', 'free_trial')
                            ->orWhereHas('plan', fn ($plan) => $plan->where('plan_code', ConsultantAgencyPlanMatrix::FREE_TRIAL_CODE));
                    });
            })
            ->exists();
    }

    public function hasEverHadPaidPack(int $consultantCompanyId): bool
    {
        return ConsultantSubscription::forConsultant($consultantCompanyId)
            ->whereHas('plan', fn ($query) => $query->where('plan_code', '!=', ConsultantAgencyPlanMatrix::FREE_TRIAL_CODE))
            ->exists();
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
        if ($subscription) {
            return max(0, (int) $subscription->slot_limit - $this->activeSlotUsage($consultantCompanyId, $subscription));
        }

        return array_sum(array_column($this->availableCapacityBuckets($consultantCompanyId), 'remaining'));
    }

    public function canConsumeSlot(int $consultantCompanyId): bool
    {
        return $this->slotsRemaining($consultantCompanyId) > 0;
    }

    /**
     * Capacity per active subscription row (Free / Demo / each depth purchase).
     *
     * @return list<array{
     *   subscription_id: int,
     *   plan_code: string|null,
     *   plan_name: string,
     *   client_package_code: string|null,
     *   slot_limit: int,
     *   used: int,
     *   remaining: int,
     *   is_trial: bool,
     *   is_demo: bool,
     *   is_depth: bool,
     *   expires_at: string|null,
     *   contract_year: int|null
     * }>
     */
    public function capacityBuckets(int $consultantCompanyId): array
    {
        $subscriptions = ConsultantSubscription::forConsultant($consultantCompanyId)
            ->with('plan')
            ->active()
            ->orderBy('id')
            ->get();

        $buckets = [];

        foreach ($subscriptions as $subscription) {
            $planCode = $subscription->plan?->plan_code;
            $used = $this->activeSlotUsage($consultantCompanyId, $subscription);
            $limit = (int) $subscription->slot_limit;
            $clientCode = $planCode
                ? ConsultantAgencyPlanMatrix::clientDepthForConsultantPlan($planCode)
                : null;
            if (!$clientCode) {
                $meta = $subscription->metadata['managed_client_package_code']
                    ?? $subscription->metadata['client_package_code']
                    ?? null;
                $clientCode = is_string($meta) ? $meta : null;
            }

            $buckets[] = [
                'subscription_id' => (int) $subscription->id,
                'plan_code' => $planCode,
                'plan_name' => $subscription->plan?->plan_name ?? 'Capacity',
                'client_package_code' => $clientCode,
                'slot_limit' => $limit,
                'used' => $used,
                'remaining' => max(0, $limit - $used),
                'is_trial' => $subscription->isFreeTrial(),
                'is_demo' => $planCode === ConsultantAgencyPlanMatrix::DEMO_PACK_CODE,
                'is_depth' => $planCode ? ConsultantAgencyPlanMatrix::isDepthPlan($planCode) : false,
                'expires_at' => $subscription->expires_at?->toDateString(),
                'contract_year' => $subscription->contract_year,
            ];
        }

        return $buckets;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function availableCapacityBuckets(int $consultantCompanyId): array
    {
        return array_values(array_filter(
            $this->capacityBuckets($consultantCompanyId),
            fn (array $b) => $b['remaining'] > 0
        ));
    }

    public function findActiveSubscriptionForOrg(int $consultantCompanyId, int $subscriptionId): ?ConsultantSubscription
    {
        return ConsultantSubscription::forConsultant($consultantCompanyId)
            ->with('plan')
            ->active()
            ->where('id', $subscriptionId)
            ->first();
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
    /**
     * Quote a pack purchase.
     *
     * $slots is the number of managed-client slots being bought. It was
     * previously absent and the quote was always one slot's list price, so a
     * five-slot purchase was charged for one. Priced through
     * ConsultantAgencyPlanMatrix::extraSlotPriceAed(), which applies the
     * volume bands, rather than multiplying the entry rate -- otherwise buying
     * five singly would cost more than buying a block of five.
     */
    public function resolvePackPurchase(
        Company $consultantOrg,
        SubscriptionPlan $plan,
        ?int $contractYear = null,
        string $chargeCurrency = 'AED',
        int $slots = 1,
    ): array {
        $this->assertConsultantOrg($consultantOrg);

        if (!$plan->isConsultantAgencyPack()) {
            throw new RuntimeException('Selected plan is not a consultant pack.');
        }

        $slots = max(1, $slots);
        $contractYear ??= (int) now()->year;
        $chargeCurrency = strtoupper($chargeCurrency);

        // Banded AED total for the whole purchase. Falls back to the plan's own
        // price x slots for a pack with no bands (legacy, enterprise).
        $annualAed = ConsultantAgencyPlanMatrix::extraSlotPriceAed($plan->plan_code, $slots);
        if ($annualAed <= 0) {
            $annualAed = (float) $plan->price_annual * $slots;
        }

        $annualPrice = $chargeCurrency === 'INR'
            ? PlanEntitlementDefaults::defaultPriceInr($annualAed)
            : $annualAed;
        $isMidYear = !now()->startOfDay()->equalTo(Carbon::create($contractYear, 1, 1)->startOfDay());
        $chargeAmount = $isMidYear
            ? $this->proRataToContractYearEnd($annualPrice, $contractYear)
            : $annualPrice;

        $yearEnd = $this->contractYearEnd($contractYear);
        $daysRemaining = (int) now()->startOfDay()->diffInDays($yearEnd->copy()->startOfDay()) + 1;

        return [
            'type' => 'new_pack',
            'slots' => $slots,
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
                : throw new RuntimeException('Completed consultant transaction is missing subscription reference.');
        }

        $metadata = array_merge($transaction->metadata ?? [], $gatewayRefs);
        $planId = $metadata['plan_id'] ?? null;
        $contractYear = (int) ($metadata['contract_year'] ?? now()->year);

        if (!$planId) {
            throw new RuntimeException('consultant pack transaction is missing plan reference.');
        }

        $plan = SubscriptionPlan::findOrFail($planId);
        $consultantOrg = Company::findOrFail($transaction->company_id);

        $options = [
            'contract_year' => $contractYear,
            'starts_at' => now()->toDateString(),
            'payment_transaction_id' => $transaction->id,
            'metadata' => array_merge($gatewayRefs, [
                'provision_type' => $metadata['provision_type'] ?? 'paid',
            ]),
        ];

        // Slots bought at checkout. Without this the pack activates at the
        // plan's default capacity, so someone who paid for five slots would
        // receive one -- and the payment has already been taken by this point.
        if (!empty($metadata['slot_limit'])) {
            $options['slot_limit'] = (int) $metadata['slot_limit'];
        }

        $subscription = $this->activatePackSubscription($consultantOrg, $plan, $options);

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
     *
     * Options:
     * - replace_existing (default true): expire other active rows for same contract year (legacy pack upgrades).
     * - slot_limit: absolute capacity for this row (depth multi-package purchases).
     * - preserve_engagements (default true when replace_existing): move engagements to the new row.
     */
    public function activatePackSubscription(Company $consultantOrg, SubscriptionPlan $plan, array $options = []): ConsultantSubscription
    {
        $this->assertConsultantOrg($consultantOrg);

        if (!$plan->isConsultantAgencyPack()) {
            throw new RuntimeException('Plan must be a consultant pack.');
        }

        $contractYear = (int) ($options['contract_year'] ?? now()->year);
        $baseSlots = ConsultantAgencyPlanMatrix::slotCountForPlanCode($plan->plan_code);
        $replaceExisting = (bool) ($options['replace_existing'] ?? true);

        return DB::transaction(function () use ($consultantOrg, $plan, $options, $contractYear, $baseSlots, $replaceExisting) {
            $oldIds = [];
            $carriedSlotFloor = 0;

            if ($replaceExisting) {
                $oldSubscriptions = ConsultantSubscription::forConsultant($consultantOrg->id)
                    ->with('plan')
                    ->where('contract_year', $contractYear)
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->get();

                foreach ($oldSubscriptions as $old) {
                    // Never expire free / demo when replacing a paid pack in the same year.
                    if ($old->isFreeTrial() || $old->plan?->plan_code === ConsultantAgencyPlanMatrix::DEMO_PACK_CODE) {
                        continue;
                    }
                    $oldIds[] = $old->id;
                    $carriedSlotFloor = max($carriedSlotFloor, (int) $old->slot_limit);
                }

                if (!empty($oldIds)) {
                    ConsultantSubscription::whereIn('id', $oldIds)->update(['status' => 'expired']);
                }
            }

            if (array_key_exists('slot_limit', $options)) {
                $slotLimit = max(1, (int) $options['slot_limit']);
            } else {
                $slotLimit = max($baseSlots, $carriedSlotFloor > 0 ? $carriedSlotFloor : $baseSlots);
            }

            $newSubscription = ConsultantSubscription::create([
                'consultant_company_id' => $consultantOrg->id,
                'subscription_plan_id' => $plan->id,
                'contract_year' => $contractYear,
                'slot_limit' => $slotLimit,
                'starts_at' => $options['starts_at'] ?? now()->toDateString(),
                'expires_at' => $options['expires_at'] ?? $this->contractYearEnd($contractYear)->toDateString(),
                'status' => 'active',
                'payment_transaction_id' => $options['payment_transaction_id'] ?? null,
                'metadata' => $options['metadata'] ?? null,
            ]);

            $preserveEngagements = (bool) ($options['preserve_engagements'] ?? $replaceExisting);
            if ($preserveEngagements && !empty($oldIds)) {
                ConsultantClientEngagement::whereIn('consultant_subscription_id', $oldIds)
                    ->where('status', 'active')
                    ->update(['consultant_subscription_id' => $newSubscription->id]);
            }

            return $newSubscription;
        });
    }

    /**
     * Additive capacity row for a depth plan (Phase 3) — does not expire free or other depth rows.
     */
    public function grantDepthSubscription(
        Company $consultantOrg,
        string $consultantPlanCode,
        int $slotCount,
        int $contractYear,
        array $metadata = [],
        ?int $adminId = null,
    ): ConsultantSubscription {
        $plan = SubscriptionPlan::where('plan_code', $consultantPlanCode)
            ->where('plan_category', 'consultant_agency')
            ->firstOrFail();

        return $this->activatePackSubscription($consultantOrg, $plan, [
            'contract_year' => $contractYear,
            'slot_limit' => max(1, $slotCount),
            'replace_existing' => false,
            'preserve_engagements' => false,
            'metadata' => array_merge($metadata, array_filter([
                'provision_type' => 'depth_grant',
                'granted_by' => $adminId,
                'granted_at' => now()->toIso8601String(),
            ], fn ($value) => $value !== null)),
        ]);
    }

    /**
     * Admin grant — no payment required.
     *
     * @param  int  $slotCount  Absolute seats (depth/admin). 0 = plan matrix default.
     */
    public function grantPackSubscription(
        Company $consultantOrg,
        string $planCode,
        int $contractYear,
        array $metadata = [],
        ?int $adminId = null,
        int $slotCount = 0,
    ): ConsultantSubscription {
        if (ConsultantAgencyPlanMatrix::isDepthPlan($planCode)
            || $planCode === ConsultantAgencyPlanMatrix::ENTERPRISE_CODE
        ) {
            $slots = max(1, $slotCount > 0 ? $slotCount : 1);

            return $this->grantDepthSubscription(
                $consultantOrg,
                $planCode,
                $slots,
                $contractYear,
                $metadata,
                $adminId,
            );
        }

        $plan = SubscriptionPlan::where('plan_code', $planCode)->where('plan_category', 'consultant_agency')->firstOrFail();
        $limit = $slotCount > 0
            ? max(1, $slotCount)
            : ConsultantAgencyPlanMatrix::slotCountForPlanCode($planCode);

        return $this->activatePackSubscription($consultantOrg, $plan, [
            'contract_year' => $contractYear,
            'slot_limit' => $limit,
            'metadata' => array_merge($metadata, array_filter([
                'provision_type' => 'admin_grant',
                'granted_by' => $adminId,
                'granted_at' => now()->toIso8601String(),
            ], fn ($value) => $value !== null)),
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
        // Depth-aware: an ESG slot and a Carbon slot are different products,
        // and a flat rate would sell one of them at the wrong price.
        $annualTotal = ConsultantAgencyPlanMatrix::extraSlotPriceAed(
            $subscription->plan?->plan_code,
            $quantity
        );
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

    /**
     * Legacy checkout hook: more seats become a NEW capacity row (same plan / contract year).
     * Preferred path: Request clients → admin grantDepthSubscription.
     */
    public function addExtraSlots(ConsultantSubscription $subscription, int $quantity, ?PaymentTransaction $transaction = null): ConsultantSubscription
    {
        if ($quantity < 1) {
            throw new RuntimeException('Quantity must be at least 1.');
        }

        $subscription->loadMissing('plan', 'consultantCompany');
        $planCode = $subscription->plan?->plan_code;
        $org = $subscription->consultantCompany;

        if (!$org || !$planCode) {
            throw new RuntimeException('Subscription is missing organisation or plan.');
        }

        // Depth / enterprise: additive row (multi-package model).
        if (ConsultantAgencyPlanMatrix::isDepthPlan($planCode)
            || $planCode === ConsultantAgencyPlanMatrix::ENTERPRISE_CODE
        ) {
            $new = $this->grantDepthSubscription(
                $org,
                $planCode,
                $quantity,
                (int) $subscription->contract_year,
                [
                    'provision_type' => 'extra_slot_as_new_row',
                    'source_subscription_id' => $subscription->id,
                    'payment_transaction_id' => $transaction?->id,
                ],
            );

            ConsultantSubscriptionAddon::create([
                'consultant_subscription_id' => $new->id,
                'addon_type' => 'extra_slot',
                'quantity' => $quantity,
                'amount_aed' => ConsultantAgencyPlanMatrix::EXTRA_SLOT_PRICE_AED * $quantity,
                'payment_transaction_id' => $transaction?->id,
            ]);

            return $new;
        }

        // Free/demo/legacy packs: bump slot_limit on the same row.
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
     * @return array{used: int, limit: int, remaining: int, contract_year: int|null, expires_at: string|null, is_trial: bool, buckets: list<array<string, mixed>>}
     */
    public function slotSummary(int $consultantCompanyId, ?ConsultantSubscription $subscription = null): array
    {
        $buckets = $this->capacityBuckets($consultantCompanyId);
        $used = array_sum(array_column($buckets, 'used'));
        $limit = array_sum(array_column($buckets, 'slot_limit'));
        $remaining = array_sum(array_column($buckets, 'remaining'));

        $primaryBucket = collect($buckets)->first(fn (array $b) => !$b['is_trial'] && $b['remaining'] > 0)
            ?? collect($buckets)->first();

        $isTrialOnly = $buckets !== [] && collect($buckets)->every(fn (array $b) => $b['is_trial']);

        return [
            'used' => $used,
            'limit' => $limit,
            'remaining' => $remaining,
            'contract_year' => $primaryBucket['contract_year'] ?? $subscription?->contract_year,
            'expires_at' => $primaryBucket['expires_at'] ?? $subscription?->expires_at?->toDateString(),
            'is_trial' => $isTrialOnly || (($subscription?->isFreeTrial() ?? false) && $remaining <= ($buckets[0]['remaining'] ?? 0)),
            'buckets' => $buckets,
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

        $newLimit = ConsultantAgencyPlanMatrix::slotCountForPlanCode($plan->plan_code);
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
