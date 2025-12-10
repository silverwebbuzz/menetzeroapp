<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_code',
        'plan_name',
        'plan_category',
        'price_annual',
        'currency',
        'billing_cycle',
        'is_active',
        'sort_order',
        'description',
        'features',
        'limits',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price_annual' => 'decimal:2',
        'features' => 'array',
        'limits' => 'array',
    ];

    /**
     * Get client subscriptions for this plan.
     */
    public function clientSubscriptions()
    {
        return $this->hasMany(ClientSubscription::class, 'subscription_plan_id');
    }

    /**
     * Scope for active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for client plans.
     */
    public function scopeForClients($query)
    {
        return $query->where('plan_category', 'client');
    }
}

