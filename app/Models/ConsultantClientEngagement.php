<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultantClientEngagement extends Model
{
    protected $table = 'consultant_client_engagements';

    protected $fillable = [
        'consultant_company_id',
        'managed_company_id',
        'consultant_subscription_id',
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

    public function consultantCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'consultant_company_id');
    }

    public function managedCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'managed_company_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(ConsultantSubscription::class, 'consultant_subscription_id');
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

    public function scopeForConsultant($query, int $consultantCompanyId)
    {
        return $query->where('consultant_company_id', $consultantCompanyId);
    }
}
