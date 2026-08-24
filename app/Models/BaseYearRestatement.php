<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A restated base year. GHG Protocol and IFRS S2 both require disclosing the
 * previous figure, the restated figure, and the reason — this is the record.
 */
class BaseYearRestatement extends Model
{
    protected $fillable = [
        'company_id',
        'base_year',
        'previous_baseline_tco2e',
        'restated_baseline_tco2e',
        'reason',
        'restated_by',
    ];

    protected $casts = [
        'base_year' => 'integer',
        'previous_baseline_tco2e' => 'decimal:4',
        'restated_baseline_tco2e' => 'decimal:4',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function restatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restated_by');
    }
}
