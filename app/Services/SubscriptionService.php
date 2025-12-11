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
                return \App\Models\DocumentUpload::where('company_id', $companyId)
                    ->count();
                    
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

