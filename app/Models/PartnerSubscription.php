<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerSubscription extends Model
{
    protected $fillable = [
        'partner_company_id',
        'subscription_plan_id',
        'contract_year',
        'slot_limit',
        'extra_slots_purchased',
        'starts_at',
        'expires_at',
        'status',
        'payment_transaction_id',
        'metadata',
    ];

    protected $casts = [
        'contract_year' => 'integer',
        'slot_limit' => 'integer',
        'extra_slots_purchased' => 'integer',
        'starts_at' => 'date',
        'expires_at' => 'date',
        'metadata' => 'array',
    ];

    public function partnerCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'partner_company_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    public function engagements(): HasMany
    {
        return $this->hasMany(PartnerClientEngagement::class);
    }

    public function addons(): HasMany
    {
        return $this->hasMany(PartnerSubscriptionAddon::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at->endOfDay()->isFuture();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('expires_at', '>=', now()->toDateString());
    }

    public function scopeForPartner($query, int $partnerCompanyId)
    {
        return $query->where('partner_company_id', $partnerCompanyId);
    }
}
