<?php

namespace App\Services;

use App\Models\Company;
use App\Models\SubscriptionPlan;
use App\Models\ClientSubscription;
use App\Models\PartnerSubscription;
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
     * Subscribe a partner to a plan.
     */
    public function subscribePartner($companyId, $planId, $data = [])
    {
        $plan = SubscriptionPlan::findOrFail($planId);
        
        if ($plan->plan_category !== 'partner') {
            throw new \Exception('Plan is not for partners');
        }

        // Cancel existing active subscription
        PartnerSubscription::where('company_id', $companyId)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        $startedAt = $data['started_at'] ?? now();
        $billingCycle = $data['billing_cycle'] ?? 'annual';
        
        $expiresAt = $billingCycle === 'annual' 
            ? Carbon::parse($startedAt)->addYear()
            : Carbon::parse($startedAt)->addMonth();

        return PartnerSubscription::create([
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
        if ($type === 'client') {
            return ClientSubscription::where('company_id', $companyId)
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->with('plan')
                ->first();
        } else {
            return PartnerSubscription::where('company_id', $companyId)
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->with('plan')
                ->first();
        }
    }

    /**
     * Check if feature is accessible for company.
     */
    public function checkFeatureAccess($companyId, $featureCode)
    {
        $company = Company::findOrFail($companyId);
        
        if ($company->isClient()) {
            $subscription = $this->getActiveSubscription($companyId, 'client');
        } else {
            $subscription = $this->getActiveSubscription($companyId, 'partner');
        }

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
        $company = Company::findOrFail($companyId);
        
        if ($company->isClient()) {
            $subscription = $this->getActiveSubscription($companyId, 'client');
        } else {
            $subscription = $this->getActiveSubscription($companyId, 'partner');
        }

        if (!$subscription || !$subscription->plan) {
            return [];
        }

        return $subscription->plan->limits ?? [];
    }

    /**
     * Check if partner can add more clients.
     */
    public function canAddMoreClients($partnerId)
    {
        $subscription = $this->getActiveSubscription($partnerId, 'partner');
        
        if (!$subscription || !$subscription->plan) {
            return false;
        }

        $limits = $subscription->plan->limits ?? [];
        $clientLimit = $limits['clients'] ?? 0;

        // -1 means unlimited
        if ($clientLimit === -1) {
            return true;
        }

        $currentCount = \App\Models\PartnerExternalClient::where('partner_company_id', $partnerId)
            ->where('status', 'active')
            ->count();

        return $currentCount < $clientLimit;
    }

    /**
     * Get client limit for partner.
     */
    public function getClientLimit($partnerId)
    {
        $subscription = $this->getActiveSubscription($partnerId, 'partner');
        
        if (!$subscription || !$subscription->plan) {
            return 0;
        }

        $limits = $subscription->plan->limits ?? [];
        return $limits['clients'] ?? 0;
    }

    /**
     * Get current client count for partner.
     */
    public function getClientCount($partnerId)
    {
        return \App\Models\PartnerExternalClient::where('partner_company_id', $partnerId)
            ->where('status', 'active')
            ->count();
    }
}

