<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrisKpiImportLog extends Model
{
    protected $fillable = [
        'company_id',
        'fiscal_year',
        'imported_by',
        'filename',
        'source_system',
        'rows_imported',
        'rows_skipped',
        'errors',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'rows_imported' => 'integer',
        'rows_skipped' => 'integer',
        'errors' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
