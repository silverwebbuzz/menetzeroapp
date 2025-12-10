<?php

namespace App\Services;

use App\Models\Company;
use App\Models\SubscriptionPlan;
use App\Models\ClientSubscription;
use Carbon\Carbon;

class SubscriptionService
{
    /**
     * Subscribe a client to a plan.
     */
    public function subscribeClient($companyId, $planId, $data = [])
    {
        $plan = SubscriptionPlan::findOrFail($planId);
        
        if ($plan->plan_category !== 'client') {
            throw new \Exception('Plan is not for clients');
        }

        // Cancel existing active subscription
        ClientSubscription::where('company_id', $companyId)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        $startedAt = $data['started_at'] ?? now();
        $billingCycle = $data['billing_cycle'] ?? 'annual';
        
        $expiresAt = $billingCycle === 'annual' 
            ? Carbon::parse($startedAt)->addYear()
            : Carbon::parse($startedAt)->addMonth();

        return ClientSubscription::create([
            'company_id' => $companyId,
            'subscription_plan_id' => $planId,
            'status' => 'active',
            'billing_cycle' => $billingCycle,
            'started_at' => $startedAt,
            'expires_at' => $expiresAt,
            'auto_renew' => $data['auto_renew'] ?? true,
            'payment_method' => $data['payment_method'] ?? null,
            'stripe_subscription_id' => $data['stripe_subscription_id'] ?? null,
            'stripe_customer_id' => $data['stripe_customer_id'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    /**
     * Get active subscription for a company.
     */
    public function getActiveSubscription($companyId, $type = 'client')
    {
        return ClientSubscription::where('company_id', $companyId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->with('plan')
            ->first();
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
        $subscription = $this->getActiveSubscription($companyId, 'client');

        if (!$subscription || !$subscription->plan) {
            return [];
        }

        return $subscription->plan->limits ?? [];
    }
}

