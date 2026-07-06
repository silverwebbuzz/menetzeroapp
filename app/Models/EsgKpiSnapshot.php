<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EsgKpiSnapshot extends Model
{
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_AUTO = 'auto';
    public const SOURCE_HRIS = 'hris';

    protected $fillable = [
        'company_id',
        'fiscal_year',
        'category',
        'metric_key',
        'value',
        'unit',
        'source',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'value' => 'decimal:4',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
