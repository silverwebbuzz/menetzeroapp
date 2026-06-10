<?php

namespace App\Models;

use App\Data\ConsultantAgencyPlanMatrix;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsultantSubscription extends Model
{
    protected $table = 'consultant_subscriptions';

    protected $fillable = [
        'consultant_company_id',
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

    public function consultantCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'consultant_company_id');
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
        return $this->hasMany(ConsultantClientEngagement::class);
    }

    public function addons(): HasMany
    {
        return $this->hasMany(ConsultantSubscriptionAddon::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at->endOfDay()->isFuture();
    }

    public function isFreeTrial(): bool
    {
        if (($this->metadata['provision_type'] ?? null) === 'free_trial') {
            return true;
        }

        return $this->relationLoaded('plan')
            ? $this->plan?->plan_code === ConsultantAgencyPlanMatrix::FREE_TRIAL_CODE
            : $this->plan()->where('plan_code', ConsultantAgencyPlanMatrix::FREE_TRIAL_CODE)->exists();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('expires_at', '>=', now()->toDateString());
    }

    public function scopeForConsultant($query, int $consultantCompanyId)
    {
        return $query->where('consultant_company_id', $consultantCompanyId);
    }
}
