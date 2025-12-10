<?php

namespace App\Services;

use App\Models\Company;
use App\Models\FeatureFlag;
use App\Services\SubscriptionService;

class FeatureFlagService
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Enable a feature for a company.
     */
    public function enableFeature($companyId, $featureCode, $metadata = null)
    {
        // Check if feature is available in subscription
        if (!$this->subscriptionService->checkFeatureAccess($companyId, $featureCode)) {
            throw new \Exception("Feature '{$featureCode}' is not available in current subscription plan");
        }

        return FeatureFlag::updateOrCreate(
            [
                'company_id' => $companyId,
                'feature_code' => $featureCode,
            ],
            [
                'is_enabled' => true,
                'enabled_at' => now(),
                'metadata' => $metadata,
            ]
        );
    }

    /**
     * Disable a feature for a company.
     */
    public function disableFeature($companyId, $featureCode)
    {
        return FeatureFlag::where('company_id', $companyId)
            ->where('feature_code', $featureCode)
            ->update([
                'is_enabled' => false,
                'enabled_at' => null,
            ]);
    }

    /**
     * Check if feature is enabled for company.
     */
    public function isFeatureEnabled($companyId, $featureCode)
    {
        // First check subscription
        if (!$this->subscriptionService->checkFeatureAccess($companyId, $featureCode)) {
            return false;
        }

        // Then check feature flag
        $flag = FeatureFlag::where('company_id', $companyId)
            ->where('feature_code', $featureCode)
            ->first();

        return $flag && $flag->is_enabled;
    }

    /**
     * Get all enabled features for company.
     */
    public function getEnabledFeatures($companyId)
    {
        $company = Company::findOrFail($companyId);
        
        $subscription = $this->subscriptionService->getActiveSubscription($companyId, 'client');

        if (!$subscription || !$subscription->plan) {
            return [];
        }

        $availableFeatures = $subscription->plan->features ?? [];
        
        // Get enabled feature flags
        $enabledFlags = FeatureFlag::where('company_id', $companyId)
            ->where('is_enabled', true)
            ->pluck('feature_code')
            ->toArray();

        // Return intersection of available and enabled
        return array_intersect($availableFeatures, $enabledFlags);
    }
}

