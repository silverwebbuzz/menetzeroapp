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
 * Consultant capacity renewal (Phase 8 multi-row board).
 *
 * Leavers → archive (history / read-only only).
 * Continuers → new engagement on chosen capacity + archive prior engagement.
 * Legacy single-pack payment helpers retained for old checkout metadata.
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
        return $this->subscriptionsNeedingRenewal($consultantCompanyId)->isNotEmpty()
            || $this->boardEngagements($consultantCompanyId)->isNotEmpty();
    }

    /**
     * Active capacity rows inside the renewal window.
     *
     * @return Collection<int, ConsultantSubscription>
     */
    public function subscriptionsNeedingRenewal(int $consultantCompanyId): Collection
    {
        return ConsultantSubscription::forConsultant($consultantCompanyId)
            ->with('plan')
            ->active()
            ->orderBy('expires_at')
            ->get()
            ->filter(fn (ConsultantSubscription $s) => $this->subscriptionNeedsRenewalAttention($s))
            ->values();
    }

    /**
     * Active clients on capacity that is expiring / in renew window (or already expired).
     *
     * @return Collection<int, ConsultantClientEngagement>
     */
    public function boardEngagements(int $consultantCompanyId): Collection
    {
        return ConsultantClientEngagement::query()
            ->with(['managedCompany', 'subscription.plan'])
            ->forConsultant($consultantCompanyId)
            ->active()
            ->orderBy('display_name')
            ->orderBy('id')
            ->get()
            ->filter(function (ConsultantClientEngagement $e) {
                $sub = $e->subscription;
                if (!$sub) {
                    return true;
                }
                if (!$sub->isActive()) {
                    return true;
                }

                return $this->subscriptionNeedsRenewalAttention($sub);
            })
            ->values();
    }

    /**
     * Spare seats a renewing engagement may continue onto (same or higher tier).
     *
     * @return list<array<string, mixed>>
     */
    public function continueTargetsFor(ConsultantClientEngagement $engagement): array
    {
        $engagement->loadMissing('subscription.plan');
        $fromCode = $engagement->subscription?->plan?->plan_code;
        if (!$fromCode) {
            return [];
        }

        $buckets = $this->subscriptions->availableCapacityBuckets((int) $engagement->consultant_company_id);
        $options = [];

        foreach ($buckets as $bucket) {
            $toId = (int) $bucket['subscription_id'];
            if ($toId === (int) $engagement->consultant_subscription_id) {
                // Same expiring row — not a real continue target
                continue;
            }
            $toCode = (string) ($bucket['plan_code'] ?? '');
            if ($toCode === '') {
                continue;
            }
            if (ConsultantAgencyPlanMatrix::isDowngrade($fromCode, $toCode)) {
                continue;
            }
            if (!ConsultantAgencyPlanMatrix::isStrictUpgrade($fromCode, $toCode)
                && !ConsultantAgencyPlanMatrix::isSameDepthTier($fromCode, $toCode)
            ) {
                continue;
            }

            $options[] = array_merge($bucket, [
                'move_kind' => ConsultantAgencyPlanMatrix::isStrictUpgrade($fromCode, $toCode)
                    ? 'upgrade'
                    : 'same_plan_renew',
            ]);
        }

        return $options;
    }

    /**
     * @param  list<array{
     *   engagement_id: int,
     *   action: string,
     *   target_subscription_id?: int|null,
     *   primary_reporting_year?: int|null
     * }>  $decisions
     * @return array{continued: int, left: int}
     */
    public function applyBoardDecisions(Company $consultantOrg, array $decisions, ?int $actorUserId = null): array
    {
        if (!$consultantOrg->isConsultantOrg()) {
            throw new RuntimeException('Company must be a consultant organisation.');
        }

        $board = $this->boardEngagements($consultantOrg->id)->keyBy('id');
        if ($board->isEmpty()) {
            throw new RuntimeException('No clients are due for renewal right now.');
        }

        $normalized = [];
        foreach ($decisions as $row) {
            $id = (int) ($row['engagement_id'] ?? 0);
            if (!$board->has($id)) {
                throw new RuntimeException('One or more clients are not on the renew board.');
            }
            $action = (string) ($row['action'] ?? '');
            if (!in_array($action, ['leave', 'continue'], true)) {
                throw new RuntimeException('Each client must be Leave (history) or Continue.');
            }
            $normalized[] = [
                'engagement_id' => $id,
                'action' => $action,
                'target_subscription_id' => isset($row['target_subscription_id']) ? (int) $row['target_subscription_id'] : null,
                'primary_reporting_year' => isset($row['primary_reporting_year']) ? (int) $row['primary_reporting_year'] : null,
            ];
        }

        $boardIds = $board->keys()->map(fn ($id) => (int) $id)->sort()->values()->all();
        $decisionIds = collect($normalized)->pluck('engagement_id')->sort()->values()->all();
        if ($boardIds !== $decisionIds) {
            throw new RuntimeException('Decide Leave or Continue for every client on the renew board.');
        }

        return DB::transaction(function () use ($consultantOrg, $normalized, $board, $actorUserId) {
            $continued = 0;
            $left = 0;
            $seatUsage = []; // target_sub_id => reserved count in this batch

            foreach ($normalized as $row) {
                /** @var ConsultantClientEngagement $engagement */
                $engagement = ConsultantClientEngagement::query()
                    ->with('subscription.plan')
                    ->where('id', $row['engagement_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!$engagement->isActive()) {
                    continue;
                }

                if ($row['action'] === 'leave') {
                    $history = $engagement->metadata['seat_moves'] ?? [];
                    $history[] = [
                        'at' => now()->toIso8601String(),
                        'by_user_id' => $actorUserId,
                        'kind' => 'renew_leave',
                        'from_subscription_id' => (int) $engagement->consultant_subscription_id,
                    ];
                    $engagement->update([
                        'metadata' => array_merge($engagement->metadata ?? [], [
                            'seat_moves' => $history,
                            'renew_outcome' => 'left_history',
                        ]),
                    ]);
                    $this->subscriptions->archiveEngagement($engagement);
                    $left++;
                    continue;
                }

                $targetId = (int) ($row['target_subscription_id'] ?? 0);
                $allowed = collect($this->continueTargetsFor($engagement))
                    ->pluck('subscription_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if ($targetId < 1 || !in_array($targetId, $allowed, true)) {
                    $label = $engagement->display_name ?: $engagement->managedCompany?->name ?: '#'.$engagement->id;
                    throw new RuntimeException(
                        "Pick a same-or-higher package seat for {$label}, or mark them Leave (history only)."
                    );
                }

                $target = ConsultantSubscription::forConsultant($consultantOrg->id)
                    ->with('plan')
                    ->active()
                    ->lockForUpdate()
                    ->where('id', $targetId)
                    ->first();

                if (!$target) {
                    throw new RuntimeException('A selected continue package is no longer active.');
                }

                $used = ConsultantClientEngagement::query()
                    ->where('consultant_subscription_id', $target->id)
                    ->active()
                    ->count();
                $reserved = $seatUsage[$targetId] ?? 0;
                if (($used + $reserved) >= (int) $target->slot_limit) {
                    throw new RuntimeException(
                        "Not enough places on {$target->plan?->plan_name}. Request more clients or leave more as history."
                    );
                }

                $pry = (int) ($row['primary_reporting_year']
                    ?: ((int) $engagement->primary_reporting_year + 1));
                if ($pry < 2000 || $pry > 2100) {
                    throw new RuntimeException('Invalid reporting year for a continuing client.');
                }

                $new = ConsultantClientEngagement::create([
                    'consultant_company_id' => $engagement->consultant_company_id,
                    'managed_company_id' => $engagement->managed_company_id,
                    'consultant_subscription_id' => $target->id,
                    'primary_reporting_year' => $pry,
                    'status' => 'active',
                    'display_name' => $engagement->display_name,
                    'previous_engagement_id' => $engagement->id,
                    'metadata' => [
                        'renew_outcome' => 'continued',
                        'seat_moves' => [[
                            'at' => now()->toIso8601String(),
                            'by_user_id' => $actorUserId,
                            'kind' => 'renew_continue',
                            'from_subscription_id' => (int) $engagement->consultant_subscription_id,
                            'to_subscription_id' => (int) $target->id,
                            'from_plan_code' => $engagement->subscription?->plan?->plan_code,
                            'to_plan_code' => $target->plan?->plan_code,
                        ]],
                    ],
                ]);

                $engagement->update([
                    'metadata' => array_merge($engagement->metadata ?? [], [
                        'renew_outcome' => 'continued_to_'.$new->id,
                    ]),
                ]);
                $this->subscriptions->archiveEngagement($engagement);
                $seatUsage[$targetId] = $reserved + 1;
                $continued++;
                unset($new);
            }

            // Expire emptied renew-window rows that have no active engagements left.
            foreach ($this->subscriptionsNeedingRenewal($consultantOrg->id) as $sub) {
                $stillUsed = ConsultantClientEngagement::query()
                    ->where('consultant_subscription_id', $sub->id)
                    ->active()
                    ->exists();
                if (!$stillUsed && $sub->isActive()) {
                    $sub->update(['status' => 'expired']);
                }
            }

            return ['continued' => $continued, 'left' => $left];
        });
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
