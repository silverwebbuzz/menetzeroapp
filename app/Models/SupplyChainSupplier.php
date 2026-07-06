<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyChainSupplier extends Model
{
    public const CATEGORIES = [
        'goods' => 'Purchased goods & services',
        'services' => 'Services',
        'capital' => 'Capital goods',
    ];

    public const SCREENING = [
        'not_screened' => 'Not screened',
        'in_progress' => 'In progress',
        'passed' => 'Passed',
        'failed' => 'Failed / excluded',
    ];

    protected $fillable = [
        'company_id',
        'fiscal_year',
        'supplier_name',
        'category',
        'spend_aed',
        'country',
        'scope3_category',
        'screening_status',
        'human_rights_assessed',
        'environmental_assessed',
        'notes',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'spend_aed' => 'decimal:2',
        'scope3_category' => 'integer',
        'human_rights_assessed' => 'boolean',
        'environmental_assessed' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
