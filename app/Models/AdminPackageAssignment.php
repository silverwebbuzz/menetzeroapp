<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Database record of an admin-approved package assignment (client plan or
 * consultant agency pack) granted at no charge.
 */
class AdminPackageAssignment extends Model
{
    protected $fillable = [
        'admin_id',
        'company_id',
        'consultant_id',
        'subscription_plan_id',
        'target_type',
        'contract_year',
        'duration_months',
        'note',
        'status',
        'client_subscription_id',
        'consultant_subscription_id',
        'metadata',
    ];

    protected $casts = [
        'contract_year' => 'integer',
        'duration_months' => 'integer',
        'metadata' => 'array',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function clientSubscription(): BelongsTo
    {
        return $this->belongsTo(ClientSubscription::class, 'client_subscription_id');
    }

    public function consultantSubscription(): BelongsTo
    {
        return $this->belongsTo(ConsultantSubscription::class, 'consultant_subscription_id');
    }

    public function isConsultantTarget(): bool
    {
        return $this->target_type === 'consultant';
    }
}
