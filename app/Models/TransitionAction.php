<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransitionAction extends Model
{
    protected $fillable = [
        'reduction_target_id', 'company_id', 'title', 'description',
        'action_type', 'planned_year', 'capex_aed', 'opex_aed',
        'expected_reduction_tco2e', 'status',
    ];

    protected $casts = [
        'planned_year' => 'integer',
        'capex_aed' => 'decimal:2',
        'opex_aed' => 'decimal:2',
        'expected_reduction_tco2e' => 'decimal:4',
    ];

    public function target(): BelongsTo
    {
        return $this->belongsTo(ReductionTarget::class, 'reduction_target_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
