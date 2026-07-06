<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EsgSustainabilityTarget extends Model
{
    public const CATEGORIES = [
        'water' => 'Water',
        'waste' => 'Waste',
        'energy' => 'Energy',
        'diversity' => 'Diversity & inclusion',
        'social' => 'Social',
        'governance' => 'Governance',
        'other' => 'Other',
    ];

    public const STATUSES = [
        'active' => 'Active',
        'draft' => 'Draft',
        'achieved' => 'Achieved',
        'retired' => 'Retired',
    ];

    protected $fillable = [
        'company_id',
        'name',
        'target_category',
        'metric_label',
        'baseline_value',
        'target_value',
        'unit',
        'base_year',
        'target_year',
        'status',
        'notes',
    ];

    protected $casts = [
        'baseline_value' => 'decimal:4',
        'target_value' => 'decimal:4',
        'base_year' => 'integer',
        'target_year' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
