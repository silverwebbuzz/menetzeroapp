<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StakeholderEngagement extends Model
{
    public const FREQUENCIES = [
        'ongoing' => 'Ongoing',
        'quarterly' => 'Quarterly',
        'biannual' => 'Bi-annually',
        'annual' => 'Annually',
        'ad_hoc' => 'Ad hoc',
    ];

    protected $fillable = [
        'company_id',
        'fiscal_year',
        'stakeholder_group',
        'engagement_method',
        'frequency',
        'topics_discussed',
        'outcomes',
        'last_engaged_at',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'last_engaged_at' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
