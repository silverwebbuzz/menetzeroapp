<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReductionTarget extends Model
{
    public const SCOPE_COVERAGE = [
        'scope1' => 'Scope 1 only',
        'scope2' => 'Scope 2 only',
        'scope12' => 'Scope 1 & 2',
        'scope3' => 'Scope 3 only',
        'scope123' => 'Scope 1, 2 & 3',
    ];

    protected $fillable = [
        'company_id', 'name', 'target_type', 'scope_coverage',
        'base_year', 'target_year', 'baseline_tco2e', 'target_tco2e',
        'reduction_percent', 'sbti_aligned', 'status',
    ];

    protected $casts = [
        'base_year' => 'integer',
        'target_year' => 'integer',
        'baseline_tco2e' => 'decimal:4',
        'target_tco2e' => 'decimal:4',
        'reduction_percent' => 'decimal:2',
        'sbti_aligned' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function transitionActions(): HasMany
    {
        return $this->hasMany(TransitionAction::class);
    }
}
