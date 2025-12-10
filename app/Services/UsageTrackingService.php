<?php

namespace App\Services;

use App\Models\UsageTracking;
use Carbon\Carbon;

class UsageTrackingService
{
    /**
     * Track usage for a company.
     */
    public function trackUsage($companyId, $resourceType, $action, $quantity = 1, $resourceId = null, $metadata = null)
    {
        $period = 'monthly';
        $periodStart = Carbon::now()->startOfMonth()->format('Y-m-d');

        return UsageTracking::create([
            'company_id' => $companyId,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'action' => $action,
            'quantity' => $quantity,
            'period' => $period,
            'period_start' => $periodStart,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get usage for company.
     */
    public function getUsage($companyId, $period = 'monthly', $periodStart = null)
    {
        if (!$periodStart) {
            $periodStart = Carbon::now()->startOfMonth()->format('Y-m-d');
        }

        return UsageTracking::where('company_id', $companyId)
            ->where('period', $period)
            ->where('period_start', $periodStart)
            ->get();
    }

    /**
     * Check if limit is reached.
     */
    public function checkLimit($companyId, $resourceType, $limit)
    {
        if ($limit === -1) {
            return false; // Unlimited
        }

        $periodStart = Carbon::now()->startOfMonth()->format('Y-m-d');
        
        $usage = UsageTracking::where('company_id', $companyId)
            ->where('resource_type', $resourceType)
            ->where('period', 'monthly')
            ->where('period_start', $periodStart)
            ->sum('quantity');

        return $usage >= $limit;
    }

    /**
     * Get remaining quota.
     */
    public function getRemainingQuota($companyId, $resourceType, $limit)
    {
        if ($limit === -1) {
            return -1; // Unlimited
        }

        $periodStart = Carbon::now()->startOfMonth()->format('Y-m-d');
        
        $usage = UsageTracking::where('company_id', $companyId)
            ->where('resource_type', $resourceType)
            ->where('period', 'monthly')
            ->where('period_start', $periodStart)
            ->sum('quantity');

        return max(0, $limit - $usage);
    }
}

