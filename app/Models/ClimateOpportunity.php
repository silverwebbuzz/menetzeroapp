<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClimateOpportunity extends Model
{
    protected $fillable = [
        'company_id', 'fiscal_year', 'name', 'category',
        'description', 'potential_impact', 'actions',
    ];

    protected $casts = ['fiscal_year' => 'integer'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
