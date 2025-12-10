<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'subscription_plan_id',
        'status',
        'billing_cycle',
        'started_at',
        'expires_at',
        'auto_renew',
        'payment_method',
        'stripe_subscription_id',
        'stripe_customer_id',
        'metadata',
    ];

    protected $casts = [
        'auto_renew' => 'boolean',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the company that owns this subscription.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the subscription plan.
     */
    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * Check if subscription is active.
     */
    public function isActive()
    {
        return $this->status === 'active' && $this->expires_at > now();
    }

    /**
     * Check if subscription is expired.
     */
    public function isExpired()
    {
        return $this->expires_at < now();
    }

    /**
     * Scope for active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('expires_at', '>', now());
    }
}

