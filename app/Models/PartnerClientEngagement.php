<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerClientEngagement extends Model
{
    protected $fillable = [
        'partner_company_id',
        'managed_company_id',
        'partner_subscription_id',
        'primary_reporting_year',
        'status',
        'archived_at',
        'previous_engagement_id',
        'display_name',
    ];

    protected $casts = [
        'primary_reporting_year' => 'integer',
        'archived_at' => 'datetime',
    ];

    public function partnerCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'partner_company_id');
    }

    public function managedCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'managed_company_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(PartnerSubscription::class, 'partner_subscription_id');
    }

    public function previousEngagement(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_engagement_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForPartner($query, int $partnerCompanyId)
    {
        return $query->where('partner_company_id', $partnerCompanyId);
    }
}
