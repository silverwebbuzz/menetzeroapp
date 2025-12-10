<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageTracking extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'resource_type',
        'resource_id',
        'action',
        'quantity',
        'period',
        'period_start',
        'metadata',
    ];

    protected $casts = [
        'period_start' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Get the company.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope for specific resource type.
     */
    public function scopeResourceType($query, $type)
    {
        return $query->where('resource_type', $type);
    }

    /**
     * Scope for specific period.
     */
    public function scopePeriod($query, $period, $periodStart)
    {
        return $query->where('period', $period)
            ->where('period_start', $periodStart);
    }
}

