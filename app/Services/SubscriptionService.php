<?php

namespace App\Services;

use App\Models\Company;
use App\Models\SubscriptionPlan;
use App\Models\ClientSubscription;
use App\Models\PaymentTransaction;
use Carbon\Carbon;

class SubscriptionService
{
    /**
     * Activate a subscription from a paid transaction. Idempotent: if the
     * transaction is already completed it just returns the existing
     * subscription. Used by both the checkout return handlers and webhooks.
     */
    public function completeTransaction(PaymentTransaction $transaction, array $gatewayRefs = [])
    {
        if ($transaction->status === 'completed') {
            return ClientSubscription::find($transaction->subscription_id);
        }

        $metadata = array_merge($transaction->metadata ?? [], $gatewayRefs);
        $planId = $metadata['plan_id'] ?? null;

        if (!$planId) {
            throw new \RuntimeException('Transaction is missing the plan reference.');
        }

        $subscription = $this->subscribeClient($transaction->company_id, $planId, [
            'billing_cycle' => 'annual',
            'payment_method' => $transaction->payment_method,
            'auto_renew' => (bool) ($metadata['auto_renew'] ?? false),
            'preserve_expiry' => (bool) ($metadata['preserve_expiry'] ?? false),
            'metadata' => array_merge($gatewayRefs, array_filter([
                'change_type' => $metadata['change_type'] ?? null,
                'from_plan_id' => $metadata['from_plan_id'] ?? null,
            ])),
        ]);

        $transaction->update([
            'status' => 'completed',
            'subscription_id' => $subscription->id,
            'paid_at' => now(),
            'metadata' => $metadata,
        ]);

        if (!empty($metadata['coupon_id'])) {
            $coupon = \App\Models\SubscriptionCoupon::find($metadata['coupon_id']);
            if ($coupon) {
                app(\App\Services\CouponService::class)->recordRedemption(
                    $coupon,
                    $transaction->company_id,
                    (float) ($metadata['discount_applied'] ?? 0),
                    $transaction->currency,
                    $subscription,
                    $transaction
                );
            }
        }

        return $subscription;
    }

    /**
     * Compare the target plan with the company's active subscription.
     *
     * @return array{
     *   type: string,
     *   requires_payment: bool,
     *   charge_amount: float,
     *   charge_currency: string,
     *   preserve_expiry: bool,
     *   message: string,
     *   days_remaining: int,
     *   credit_amount: float
     * }
     */
    public function resolvePlanChange(?ClientSubscription $current, SubscriptionPlan $target, string $chargeCurrency = 'INR'): array
    {
        $chargeCurrency = strtoupper($chargeCurrency);

        if (!$current || !$current->plan) {
            $amount = $this->planPriceInCurrency($target, $chargeCurrency);

            return [
                'type' => 'new',
                'requires_payment' => $amount > 0,
                'charge_amount' => $amount,
                'charge_currency' => $chargeCurrency,
                'preserve_expiry' => false,
                'message' => 'Start a new annual subscription.',
                'days_remaining' => 0,
                'credit_amount' => 0,
            ];
        }

        if ((int) $current->subscription_plan_id === (int) $target->id) {
            return [
                'type' => 'same',
                'requires_payment' => false,
                'charge_amount' => 0,
                'charge_currency' => $chargeCurrency,
                'preserve_expiry' => true,
                'message' => 'This is already your current plan.',
                'days_remaining' => max(0, (int) now()->diffInDays($current->expires_at, false)),
                'credit_amount' => 0,
            ];
        }

        $currentPlan = $current->plan;
        $currentTier = (int) $currentPlan->sort_order;
        $targetTier = (int) $target->sort_order;
        $daysRemaining = max(0, (int) now()->diffInDays($current->expires_at, false));

        // Downgrade (or move to Free): keep current plan until expiry, switch at renewal.
        if ($targetTier < $currentTier || (float) $target->price_annual <= 0) {
            return [
                'type' => (float) $target->price_annual <= 0 ? 'downgrade_to_free' : 'downgrade',
                'requires_payment' => false,
                'charge_amount' => 0,
                'charge_currency' => $chargeCurrency,
                'preserve_expiry' => true,
                'message' => "Your {$currentPlan->plan_name} plan stays active until "
                    . $current->expires_at->format('F d, Y')
                    . ". {$target->plan_name} will apply when you renew — no refund for unused time.",
                'days_remaining' => $daysRemaining,
                'credit_amount' => 0,
            ];
        }

        // Upgrade or lateral move to higher/equal paid tier.
        $currentPrice = $this->planPriceInCurrency($currentPlan, $chargeCurrency);
        $targetPrice = $this->planPriceInCurrency($target, $chargeCurrency);

        if ($currentPrice <= 0) {
            // Free → paid: full annual price, new 1-year term.
            return [
                'type' => 'upgrade',
                'requires_payment' => $targetPrice > 0,
                'charge_amount' => $targetPrice,
                'charge_currency' => $chargeCurrency,
                'preserve_expiry' => false,
                'message' => 'Pay the annual price to activate your new plan.',
                'days_remaining' => $daysRemaining,
                'credit_amount' => 0,
            ];
        }

        // Paid → higher paid: credit unused time toward a FULL year of the new plan.
        // Prevents "upgrade for 2 months during compliance, pay almost nothing" abuse.
        $totalDays = max(1, (int) $current->started_at->diffInDays($current->expires_at));
        $ratio = min(1, $daysRemaining / $totalDays);
        $unusedCredit = round($currentPrice * $ratio, 2);
        $upgradeCharge = max(0, round($targetPrice - $unusedCredit, 2));

        $creditLabel = \App\Services\CurrencyService::format($unusedCredit, $chargeCurrency);
        $chargeLabel = \App\Services\CurrencyService::format($upgradeCharge, $chargeCurrency);

        return [
            'type' => 'upgrade',
            'requires_payment' => $upgradeCharge > 0,
            'charge_amount' => $upgradeCharge,
            'charge_currency' => $chargeCurrency,
            'preserve_expiry' => false,
            'credit_amount' => $unusedCredit,
            'message' => $upgradeCharge > 0
                ? "Your unused {$currentPlan->plan_name} time ({$daysRemaining} days, credit {$creditLabel}) "
                    . "is applied toward a full year of {$target->plan_name}. "
                    . "You pay {$chargeLabel} today and your new 1-year term starts now."
                : 'Your plan will be upgraded immediately at no extra charge.',
            'days_remaining' => $daysRemaining,
        ];
    }

    /**
     * Warnings when downgrading would leave the company over the target plan limits.
     *
     * @return list<string>
     */
    public function getDowngradeWarnings($companyId, SubscriptionPlan $targetPlan): array
    {
        $warnings = [];
        $limits = $targetPlan->limits ?? [];
        $labels = [
            'locations' => 'locations / branches',
            'users' => 'users',
        ];

        foreach ($labels as $resource => $label) {
            $limit = $limits[$resource] ?? null;

            if ($limit === null || (int) $limit === -1) {
                continue;
            }

            $used = $this->getCurrentUsage($companyId, $resource);

            if ($used > (int) $limit) {
                $warnings[] = "You currently have {$used} {$label}, but {$targetPlan->plan_name} allows {$limit}. "
                    . "Please reduce usage before your renewal date or the downgrade cannot take effect.";
            }
        }

        return $warnings;
    }

    /**
     * Schedule a downgrade to take effect at the current term end (no refund).
     */
    public function scheduleDowngrade(ClientSubscription $subscription, SubscriptionPlan $targetPlan): ClientSubscription
    {
        $metadata = $subscription->metadata ?? [];
        $metadata['renewal_plan_id'] = $targetPlan->id;
        $metadata['renewal_plan_name'] = $targetPlan->plan_name;
        $metadata['renewal_scheduled_at'] = $subscription->expires_at->toIso8601String();

        $subscription->update(['metadata' => $metadata]);

        return $subscription->fresh();
    }

    /**
     * Clear a previously scheduled downgrade (e.g. customer upgrades instead).
     */
    public function clearScheduledDowngrade(ClientSubscription $subscription): void
    {
        $metadata = $subscription->metadata ?? [];
        unset($metadata['renewal_plan_id'], $metadata['renewal_plan_name'], $metadata['renewal_scheduled_at']);
        $subscription->update(['metadata' => $metadata]);
    }

    public function getScheduledRenewalPlan(ClientSubscription $subscription): ?SubscriptionPlan
    {
        $planId = $subscription->metadata['renewal_plan_id'] ?? null;

        return $planId ? SubscriptionPlan::find($planId) : null;
    }

    /**
     * True when the customer paid through a gateway (not admin grant or coupon).
     */
    public function isPaidSubscription(ClientSubscription $subscription): bool
    {
        if ($this->isComplimentary($subscription)) {
            return false;
        }

        return $subscription->plan && (float) $subscription->plan->price_annual > 0;
    }

    /**
     * Admin-granted or 100%-off campaign access — full plan features, no billing.
     */
    public function isComplimentary(ClientSubscription $subscription): bool
    {
        $type = $subscription->metadata['provision_type'] ?? null;

        return in_array($type, ['admin_comp', 'coupon'], true);
    }

    public function getProvisionLabel(ClientSubscription $subscription): ?string
    {
        $type = $subscription->metadata['provision_type'] ?? null;

        if ($type === 'admin_comp') {
            $note = $subscription->metadata['provision_note'] ?? null;

            return $note
                ? 'Complimentary — ' . $note
                : 'Complimentary — provided by MenetZero';
        }

        if ($type === 'coupon') {
            $code = $subscription->metadata['coupon_code'] ?? 'Campaign';

            return 'Campaign offer (' . $code . ')';
        }

        return null;
    }

    /**
     * Grant a paid-tier plan to a company at no charge (admin special cases).
     */
    public function grantComplimentary(
        int $companyId,
        int $planId,
        $expiresAt,
        ?string $note,
        ?int $adminUserId = null
    ): ClientSubscription {
        return $this->subscribeClient($companyId, $planId, [
            'billing_cycle' => 'annual',
            'payment_method' => 'admin_comp',
            'auto_renew' => false,
            'started_at' => now(),
            'expires_at' => $expiresAt,
            'metadata' => [
                'provision_type' => 'admin_comp',
                'provision_note' => $note,
                'granted_by' => $adminUserId,
                'granted_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Activate via a 100%-off coupon (no payment gateway).
     */
    public function activateWithCoupon(
        int $companyId,
        int $planId,
        array $couponMeta,
        bool $preserveExpiry = false
    ): ClientSubscription {
        return $this->subscribeClient($companyId, $planId, [
            'billing_cycle' => 'annual',
            'payment_method' => 'coupon',
            'auto_renew' => false,
            'preserve_expiry' => $preserveExpiry,
            'metadata' => array_merge($couponMeta, [
                'provision_type' => 'coupon',
            ]),
        ]);
    }

    public function isCancellationScheduled(ClientSubscription $subscription): bool
    {
        return !empty($subscription->metadata['cancel_at_period_end']);
    }

    /**
     * Request cancellation at the end of the paid term. Access continues until
     * expires_at; the plan will not renew.
     */
    public function scheduleCancellation(ClientSubscription $subscription): ClientSubscription
    {
        $metadata = $subscription->metadata ?? [];
        $metadata['cancel_at_period_end'] = true;
        $metadata['cancel_requested_at'] = now()->toIso8601String();
        $metadata['cancel_effective_at'] = $subscription->expires_at->toIso8601String();
        unset($metadata['renewal_plan_id'], $metadata['renewal_plan_name'], $metadata['renewal_scheduled_at']);

        $subscription->update([
            'auto_renew' => false,
            'metadata' => $metadata,
        ]);

        return $subscription->fresh();
    }

    /**
     * Undo a scheduled end-of-term cancellation and re-enable renewal reminders.
     */
    public function resumeSubscription(ClientSubscription $subscription): ClientSubscription
    {
        $metadata = $subscription->metadata ?? [];
        unset($metadata['cancel_at_period_end'], $metadata['cancel_requested_at'], $metadata['cancel_effective_at']);

        $subscription->update([
            'auto_renew' => true,
            'metadata' => $metadata,
        ]);

        return $subscription->fresh();
    }

    /**
     * Subscribe a client to a plan.
     */
    public function subscribeClient($companyId, $planId, $data = [])
    {
        $plan = SubscriptionPlan::findOrFail($planId);

        if ($plan->plan_category !== 'client') {
            throw new \Exception('Plan is not for clients');
        }

        $existing = ClientSubscription::where('company_id', $companyId)
            ->where('status', 'active')
            ->first();

        $preserveExpiry = (bool) ($data['preserve_expiry'] ?? false);

        if (!empty($data['expires_at'])) {
            $startedAt = isset($data['started_at']) ? Carbon::parse($data['started_at']) : now();
            $expiresAt = Carbon::parse($data['expires_at']);
        } elseif ($preserveExpiry && $existing) {
            $startedAt = $existing->started_at;
            $expiresAt = $existing->expires_at;
        } else {
            $startedAt = $data['started_at'] ?? now();
            $billingCycle = $data['billing_cycle'] ?? 'annual';
            $expiresAt = $billingCycle === 'annual'
                ? Carbon::parse($startedAt)->addYear()
                : Carbon::parse($startedAt)->addMonth();
        }

        $billingCycle = $data['billing_cycle'] ?? 'annual';
        $metadata = array_merge(
            $existing?->metadata ?? [],
            is_array($data['metadata'] ?? null) ? $data['metadata'] : []
        );
        // Upgrading clears any scheduled downgrade/cancellation and starts a fresh term.
        unset(
            $metadata['renewal_plan_id'],
            $metadata['renewal_plan_name'],
            $metadata['renewal_scheduled_at'],
            $metadata['cancel_at_period_end'],
            $metadata['cancel_requested_at'],
            $metadata['cancel_effective_at']
        );

        if ($existing && !$preserveExpiry) {
            $metadata['upgraded_at'] = now()->toIso8601String();
            $metadata['upgraded_from_plan_id'] = $existing->subscription_plan_id;
        }

        $payload = [
            'subscription_plan_id' => $planId,
            'status' => 'active',
            'billing_cycle' => $billingCycle,
            'started_at' => $startedAt,
            'expires_at' => $expiresAt,
            'auto_renew' => $data['auto_renew'] ?? true,
            'payment_method' => $data['payment_method'] ?? null,
            'stripe_subscription_id' => $data['stripe_subscription_id'] ?? null,
            'stripe_customer_id' => $data['stripe_customer_id'] ?? null,
            'metadata' => $metadata,
        ];

        if ($existing) {
            $existing->update($payload);

            return $existing->fresh();
        }

        return ClientSubscription::create(array_merge($payload, ['company_id' => $companyId]));
    }

    private function planPriceInCurrency(SubscriptionPlan $plan, string $currency): float
    {
        return strtoupper($currency) === 'AED'
            ? (float) $plan->price_annual
            : (float) $plan->price_inr;
    }

    /**
     * Change subscription status while respecting the (company_id, status) unique
     * index: remove any other row that already holds the target status.
     */
    public function transitionSubscriptionStatus(ClientSubscription $subscription, string $newStatus): void
    {
        ClientSubscription::where('company_id', $subscription->company_id)
            ->where('status', $newStatus)
            ->where('id', '!=', $subscription->id)
            ->delete();

        $subscription->update(['status' => $newStatus]);
    }

    /**
     * Get active subscription for a company.
     */
    public function getActiveSubscription($companyId, $type = 'client')
    {
        $company = Company::find($companyId);

        if ($company?->isManagedClient()) {
            return null;
        }

        return ClientSubscription::where('company_id', $companyId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->with('plan')
            ->first();
    }

    /**
     * Disclosure form access — all plans with disclosures.access (Commercial Plan v1).
     */
    public function canAccessIfrsS2(int $companyId): array
    {
        return app(PlanEntitlementService::class)->canAccessDisclosures($companyId);
    }

    public function canAccessIfrsS1(int $companyId): array
    {
        return app(PlanEntitlementService::class)->canAccessDisclosures($companyId);
    }

    public function canAccessGri(int $companyId): array
    {
        return app(PlanEntitlementService::class)->canAccessDisclosures($companyId);
    }

    public function canAccessDisclosures(int $companyId): array
    {
        return app(PlanEntitlementService::class)->canAccessDisclosures($companyId);
    }

    /**
     * Check if feature is accessible for company.
     */
    public function checkFeatureAccess($companyId, $featureCode)
    {
        $subscription = $this->getActiveSubscription($companyId, 'client');

        if (!$subscription || !$subscription->plan) {
            return false;
        }

        $features = $subscription->plan->features ?? [];
        return in_array($featureCode, $features);
    }

    /**
     * Get plan limits for company.
     */
    public function getPlanLimits($companyId)
    {
        $company = Company::find($companyId);

        if ($company?->isManagedClient()) {
            $growth = SubscriptionPlan::where('plan_code', 'client_growth')->first();

            return $growth?->limits ?? [];
        }

        if ($company?->isConsultantOrg()) {
            $consultantSub = app(\App\Services\ConsultantAgencySubscriptionService::class)
                ->getActiveSubscription($companyId);

            if (!$consultantSub?->plan) {
                return ['users' => 1];
            }

            return $consultantSub->plan->limits ?? [];
        }

        $subscription = $this->getActiveSubscription($companyId, 'client');

        if (!$subscription || !$subscription->plan) {
            return [];
        }

        return $subscription->plan->limits ?? [];
    }

    /**
     * Check if company can perform an action based on plan limits.
     */
    public function canPerformAction($companyId, $resourceType, $quantity = 1)
    {
        $limits = $this->getPlanLimits($companyId);
        
        if (empty($limits)) {
            // No limits defined, allow action
            return ['allowed' => true, 'message' => null];
        }

        $limit = $limits[$resourceType] ?? null;
        
        if ($limit === null) {
            // No limit for this resource type, allow action
            return ['allowed' => true, 'message' => null];
        }

        if ($limit === -1) {
            // Unlimited
            return ['allowed' => true, 'message' => null];
        }

        // Get current usage
        $currentUsage = $this->getCurrentUsage($companyId, $resourceType);
        
        if (($currentUsage + $quantity) > $limit) {
            $remaining = max(0, $limit - $currentUsage);
            return [
                'allowed' => false,
                'message' => "You have reached your plan limit for {$resourceType}. Your plan allows {$limit} {$resourceType}, and you currently have {$currentUsage}. " . ($remaining > 0 ? "You can add {$remaining} more." : "Please upgrade your plan to add more.")
            ];
        }

        return ['allowed' => true, 'message' => null];
    }

    /**
     * Records per Scope 3 form. 0 = locked, 1 = preview, -1 = unlimited.
     *
     * @return int
     */
    public function getScope3FormRecordLimit($companyId)
    {
        return app(PlanEntitlementService::class)->getScope3RecordsPerFormLimit($companyId);
    }

    /**
     * Count how many records the company already has for a Scope 3 form.
     */
    public function getScope3FormRecordCount($companyId, $emissionSourceId)
    {
        return \App\Models\MeasurementData::where('emission_source_id', $emissionSourceId)
            ->whereHas('measurement.location', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->count();
    }

    /**
     * Determine whether the company can add another record to a Scope 3 form.
     */
    public function canAddScope3Record($companyId, $emissionSourceId)
    {
        $scope3Access = app(PlanEntitlementService::class)->canAccessScope3($companyId);
        if (!$scope3Access['allowed']) {
            return [
                'allowed' => false,
                'limit' => 0,
                'used' => 0,
                'message' => $scope3Access['message'],
            ];
        }

        $limit = $this->getScope3FormRecordLimit($companyId);

        if ($limit === -1) {
            return ['allowed' => true, 'message' => null, 'limit' => -1, 'used' => 0];
        }

        if ($limit === 0) {
            return [
                'allowed' => false,
                'limit' => 0,
                'used' => 0,
                'message' => $scope3Access['message'] ?? 'Scope 3 is not available on your plan.',
            ];
        }

        $used = $this->getScope3FormRecordCount($companyId, $emissionSourceId);

        if (($used + 1) > $limit) {
            return [
                'allowed' => false,
                'limit' => $limit,
                'used' => $used,
                'message' => "Your plan allows {$limit} entry per Scope 3 category. "
                    . 'Upgrade to Enterprise for full Scope 3 reporting.',
            ];
        }

        return ['allowed' => true, 'message' => null, 'limit' => $limit, 'used' => $used];
    }

    /**
     * Get current usage for a resource type.
     */
    public function getCurrentUsage($companyId, $resourceType)
    {
        switch ($resourceType) {
            case 'users':
                // Count all unique users for the company
                // Get all user IDs (direct company users)
                $directUserIds = \App\Models\User::where('company_id', $companyId)->pluck('id')->toArray();
                
                // Get all user IDs from user_company_roles
                $accessUserIds = [];
                try {
                    $accessUserIds = \App\Models\UserCompanyRole::where('company_id', $companyId)
                        ->where('is_active', true)
                        ->pluck('user_id')
                        ->unique()
                        ->toArray();
                } catch (\Exception $e) {
                    // Table doesn't exist
                }
                
                // Combine and get unique count
                $allUserIds = array_unique(array_merge($directUserIds, $accessUserIds));
                $userCount = count($allUserIds);
                
                // Count pending invitations as they will become users
                try {
                    $pendingInvitations = \App\Models\CompanyInvitation::where('company_id', $companyId)
                        ->where('status', 'pending')
                        ->where('expires_at', '>', now())
                        ->count();
                } catch (\Exception $e) {
                    $pendingInvitations = 0;
                }
                
                return $userCount + $pendingInvitations;
                
            case 'locations':
                return \App\Models\Location::where('company_id', $companyId)
                    ->where('is_active', true)
                    ->count();
                    
            case 'documents':
                return 0;
                    
            default:
                return 0;
        }
    }

    /**
     * Get remaining quota for a resource type.
     */
    public function getRemainingQuota($companyId, $resourceType)
    {
        $limits = $this->getPlanLimits($companyId);
        $limit = $limits[$resourceType] ?? null;
        
        if ($limit === null || $limit === -1) {
            return -1; // Unlimited or no limit
        }
        
        $currentUsage = $this->getCurrentUsage($companyId, $resourceType);
        return max(0, $limit - $currentUsage);
    }
}

