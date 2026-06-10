<?php

namespace App\Services;

use App\Data\PartnerPlanMatrix;
use App\Models\Company;
use App\Models\PartnerClientEngagement;
use App\Models\PartnerSubscription;
use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * P20 — Partner contract renewal: select clients + PRY for the next calendar year.
 */
class PartnerRenewalService
{
    public function __construct(
        protected PartnerSubscriptionService $subscriptions,
        protected PartnerManagedClientService $managedClients,
    ) {
    }

    public function renewalWindowDays(): int
    {
        return 45;
    }

    public function getRenewableSubscription(int $partnerCompanyId): ?PartnerSubscription
    {
        $active = $this->subscriptions->getActiveSubscription($partnerCompanyId);

        if ($active && $this->subscriptionNeedsRenewalAttention($active)) {
            return $active;
        }

        return PartnerSubscription::forPartner($partnerCompanyId)
            ->where('status', 'active')
            ->orderByDesc('expires_at')
            ->first();
    }

    public function subscriptionNeedsRenewalAttention(PartnerSubscription $subscription): bool
    {
        if ($subscription->status !== 'active') {
            return false;
        }

        $expires = $subscription->expires_at->copy()->endOfDay();
        $windowStart = $expires->copy()->subDays($this->renewalWindowDays())->startOfDay();

        return now()->greaterThanOrEqualTo($windowStart);
    }

    public function hasNextYearSubscription(int $partnerCompanyId, int $nextContractYear): bool
    {
        return PartnerSubscription::forPartner($partnerCompanyId)
            ->where('contract_year', $nextContractYear)
            ->where('status', 'active')
            ->where('expires_at', '>=', now()->toDateString())
            ->exists();
    }

    public function needsRenewalFlow(int $partnerCompanyId): bool
    {
        $current = $this->getRenewableSubscription($partnerCompanyId);

        if (!$current || !$this->subscriptionNeedsRenewalAttention($current)) {
            return false;
        }

        $nextYear = (int) $current->contract_year + 1;

        return !$this->hasNextYearSubscription($partnerCompanyId, $nextYear);
    }

    /**
     * @return Collection<int, PartnerClientEngagement>
     */
    public function expiringEngagements(PartnerSubscription $subscription): Collection
    {
        return PartnerClientEngagement::query()
            ->with('managedCompany')
            ->where('partner_subscription_id', $subscription->id)
            ->active()
            ->orderBy('display_name')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  list<array{engagement_id: int, primary_reporting_year: int}>  $carried
     */
    public function validateCarryForward(
        Company $partner,
        PartnerSubscription $fromSubscription,
        SubscriptionPlan $targetPlan,
        array $carried,
    ): array {
        $slotLimit = PartnerPlanMatrix::slotCountForPlanCode($targetPlan->plan_code);

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
        Company $partner,
        SubscriptionPlan $plan,
        PartnerSubscription $fromSubscription,
        array $carried,
        string $chargeCurrency = 'AED',
    ): array {
        if (!$partner->isPartner()) {
            throw new RuntimeException('Company must be a partner organisation.');
        }

        $nextYear = (int) $fromSubscription->contract_year + 1;
        $this->validateCarryForward($partner, $fromSubscription, $plan, $carried);

        if ($this->hasNextYearSubscription($partner->id, $nextYear)) {
            throw new RuntimeException("You already have an active pack for {$nextYear}.");
        }

        return $this->subscriptions->resolvePackPurchase($partner, $plan, $nextYear, $chargeCurrency);
    }

    /**
     * @param  list<array{engagement_id: int, primary_reporting_year: int}>  $carried
     */
    public function completeRenewalTransaction(
        PaymentTransaction $transaction,
        array $gatewayRefs = [],
    ): PartnerSubscription {
        if ($transaction->status === 'completed') {
            $existingId = $transaction->metadata['partner_subscription_id'] ?? null;

            return $existingId
                ? PartnerSubscription::findOrFail($existingId)
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
        $partner = Company::findOrFail($transaction->company_id);
        $fromSubscription = PartnerSubscription::findOrFail($fromSubscriptionId);

        $carried = $this->validateCarryForward($partner, $fromSubscription, $plan, $carried);

        return DB::transaction(function () use ($transaction, $metadata, $gatewayRefs, $plan, $partner, $fromSubscription, $carried, $contractYear) {
            $newSubscription = $this->subscriptions->activatePackSubscription($partner, $plan, [
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
                    'partner_subscription_id' => $newSubscription->id,
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
        PartnerSubscription $fromSubscription,
        PartnerSubscription $toSubscription,
        array $carried,
    ): void {
        $carriedById = collect($carried)->keyBy('engagement_id');
        $expiring = $this->expiringEngagements($fromSubscription);

        foreach ($expiring as $engagement) {
            $selection = $carriedById->get($engagement->id);

            if ($selection) {
                PartnerClientEngagement::create([
                    'partner_company_id' => $engagement->partner_company_id,
                    'managed_company_id' => $engagement->managed_company_id,
                    'partner_subscription_id' => $toSubscription->id,
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
