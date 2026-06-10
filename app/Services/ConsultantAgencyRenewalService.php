<?php

namespace App\Services;

use App\Data\ConsultantAgencyPlanMatrix;
use App\Models\Company;
use App\Models\ConsultantClientEngagement;
use App\Models\ConsultantSubscription;
use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * P20 — Partner contract renewal: select clients + PRY for the next calendar year.
 */
class ConsultantAgencyRenewalService
{
    public function __construct(
        protected ConsultantAgencySubscriptionService $subscriptions,
    ) {
    }

    public function renewalWindowDays(): int
    {
        return 45;
    }

    public function getRenewableSubscription(int $consultantCompanyId): ?ConsultantSubscription
    {
        $active = $this->subscriptions->getActiveSubscription($consultantCompanyId);

        if (!$active || !$this->subscriptionNeedsRenewalAttention($active)) {
            return null;
        }

        return $active;
    }

    public function subscriptionNeedsRenewalAttention(ConsultantSubscription $subscription): bool
    {
        if ($subscription->status !== 'active') {
            return false;
        }

        $expires = $subscription->expires_at->copy()->endOfDay();
        $windowStart = $expires->copy()->subDays($this->renewalWindowDays())->startOfDay();

        return now()->greaterThanOrEqualTo($windowStart);
    }

    public function hasNextYearSubscription(int $consultantCompanyId, int $nextContractYear): bool
    {
        return ConsultantSubscription::forConsultant($consultantCompanyId)
            ->where('contract_year', $nextContractYear)
            ->where('status', 'active')
            ->where('expires_at', '>=', now()->toDateString())
            ->exists();
    }

    public function needsRenewalFlow(int $consultantCompanyId): bool
    {
        $current = $this->subscriptions->getActiveSubscription($consultantCompanyId);

        if (!$current || !$this->subscriptionNeedsRenewalAttention($current)) {
            return false;
        }

        return !$this->hasNextYearSubscription($consultantCompanyId, (int) $current->contract_year + 1);
    }

    /**
     * @return Collection<int, ConsultantClientEngagement>
     */
    public function expiringEngagements(ConsultantSubscription $subscription): Collection
    {
        return ConsultantClientEngagement::query()
            ->with('managedCompany')
            ->where('consultant_subscription_id', $subscription->id)
            ->active()
            ->orderBy('display_name')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  list<array{engagement_id: int, primary_reporting_year: int}>  $carried
     */
    public function validateCarryForward(
        Company $consultantOrg,
        ConsultantSubscription $fromSubscription,
        SubscriptionPlan $targetPlan,
        array $carried,
    ): array {
        $slotLimit = ConsultantAgencyPlanMatrix::slotCountForPlanCode($targetPlan->plan_code);

        if (count($carried) > $slotLimit) {
            throw new RuntimeException(
                "You selected " . count($carried) . " clients but {$targetPlan->plan_name} allows {$slotLimit} slots."
            );
        }

        $expiringIds = $this->expiringEngagements($fromSubscription)->pluck('id')->all();
        $normalized = [];

        foreach ($carried as $row) {
            $engagementId = (int) ($row['engagement_id'] ?? 0);
            $pry = (int) ($row['primary_reporting_year'] ?? 0);

            if (!in_array($engagementId, $expiringIds, true)) {
                throw new RuntimeException('One or more selected clients are not on the expiring contract.');
            }

            if ($pry < 2000 || $pry > 2100) {
                throw new RuntimeException('Invalid reporting year for a carried client.');
            }

            $normalized[] = [
                'engagement_id' => $engagementId,
                'primary_reporting_year' => $pry,
            ];
        }

        return $normalized;
    }

    /**
     * @param  list<array{engagement_id: int, primary_reporting_year: int}>  $carried
     * @return array{
     *   type: string,
     *   requires_payment: bool,
     *   charge_amount: float,
     *   charge_currency: string,
     *   contract_year: int,
     *   pro_rata: bool,
     *   message: string
     * }
     */
    public function resolveRenewalPurchase(
        Company $consultantOrg,
        SubscriptionPlan $plan,
        ConsultantSubscription $fromSubscription,
        array $carried,
        string $chargeCurrency = 'AED',
    ): array {
        if (!$consultantOrg->isConsultantOrg()) {
            throw new RuntimeException('Company must be a consultant organisation.');
        }

        $nextYear = (int) $fromSubscription->contract_year + 1;
        $this->validateCarryForward($consultantOrg, $fromSubscription, $plan, $carried);

        if ($this->hasNextYearSubscription($consultantOrg->id, $nextYear)) {
            throw new RuntimeException("You already have an active pack for {$nextYear}.");
        }

        return $this->subscriptions->resolvePackPurchase($consultantOrg, $plan, $nextYear, $chargeCurrency);
    }

    /**
     * @param  list<array{engagement_id: int, primary_reporting_year: int}>  $carried
     */
    public function completeRenewalTransaction(
        PaymentTransaction $transaction,
        array $gatewayRefs = [],
    ): ConsultantSubscription {
        if ($transaction->status === 'completed') {
            $existingId = $transaction->metadata['consultant_subscription_id'] ?? null;

            return $existingId
                ? ConsultantSubscription::findOrFail($existingId)
                : throw new RuntimeException('Completed renewal transaction is missing subscription reference.');
        }

        $metadata = array_merge($transaction->metadata ?? [], $gatewayRefs);
        $planId = $metadata['plan_id'] ?? null;
        $fromSubscriptionId = $metadata['previous_subscription_id'] ?? null;
        $carried = $metadata['carried_engagements'] ?? [];
        $contractYear = (int) ($metadata['contract_year'] ?? 0);

        if (!$planId || !$fromSubscriptionId || $contractYear < 2000) {
            throw new RuntimeException('Renewal transaction is missing required metadata.');
        }

        $plan = SubscriptionPlan::findOrFail($planId);
        $consultantOrg = Company::findOrFail($transaction->company_id);
        $fromSubscription = ConsultantSubscription::findOrFail($fromSubscriptionId);

        $carried = $this->validateCarryForward($consultantOrg, $fromSubscription, $plan, $carried);

        return DB::transaction(function () use ($transaction, $metadata, $gatewayRefs, $plan, $consultantOrg, $fromSubscription, $carried, $contractYear) {
            $newSubscription = $this->subscriptions->activatePackSubscription($consultantOrg, $plan, [
                'contract_year' => $contractYear,
                'starts_at' => Carbon::create($contractYear, 1, 1)->toDateString(),
                'payment_transaction_id' => $transaction->id,
                'metadata' => ['provision_type' => 'renewal'],
            ]);

            $this->applyCarryForward($fromSubscription, $newSubscription, $carried);

            $transaction->update([
                'status' => 'completed',
                'paid_at' => now(),
                'metadata' => array_merge($metadata, $gatewayRefs, [
                    'consultant_subscription_id' => $newSubscription->id,
                ]),
            ]);

            return $newSubscription;
        });
    }

    /**
     * Carry selected clients into the new contract year; archive the rest.
     *
     * @param  list<array{engagement_id: int, primary_reporting_year: int}>  $carried
     */
    public function applyCarryForward(
        ConsultantSubscription $fromSubscription,
        ConsultantSubscription $toSubscription,
        array $carried,
    ): void {
        $carriedById = collect($carried)->keyBy('engagement_id');
        $expiring = $this->expiringEngagements($fromSubscription);

        foreach ($expiring as $engagement) {
            $selection = $carriedById->get($engagement->id);

            if ($selection) {
                ConsultantClientEngagement::create([
                    'consultant_company_id' => $engagement->consultant_company_id,
                    'managed_company_id' => $engagement->managed_company_id,
                    'consultant_subscription_id' => $toSubscription->id,
                    'primary_reporting_year' => (int) $selection['primary_reporting_year'],
                    'status' => 'active',
                    'display_name' => $engagement->display_name,
                    'previous_engagement_id' => $engagement->id,
                ]);
            }

            $this->subscriptions->archiveEngagement($engagement);
        }

        if ($fromSubscription->isActive()) {
            $fromSubscription->update(['status' => 'expired']);
        }
    }
}
