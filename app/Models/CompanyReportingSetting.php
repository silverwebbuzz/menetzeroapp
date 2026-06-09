<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyReportingSetting extends Model
{
    public const BOUNDARIES = [
        'operational_control' => 'Operational control',
        'equity_share' => 'Equity share',
        'financial_control' => 'Financial control',
    ];

    public const SCOPE3_CATEGORIES = [
        1 => 'Cat 1 – Purchased goods & services',
        2 => 'Cat 2 – Capital goods',
        3 => 'Cat 3 – Fuel & energy related',
        4 => 'Cat 4 – Upstream transport',
        5 => 'Cat 5 – Waste in operations',
        6 => 'Cat 6 – Business travel',
        7 => 'Cat 7 – Employee commuting',
        8 => 'Cat 8 – Upstream leased assets',
        9 => 'Cat 9 – Downstream transport',
        10 => 'Cat 10 – Processing of sold products',
        11 => 'Cat 11 – Use of sold products',
        12 => 'Cat 12 – End-of-life of sold products',
        13 => 'Cat 13 – Downstream leased assets',
        14 => 'Cat 14 – Franchises',
        15 => 'Cat 15 – Investments',
    ];

    protected $fillable = [
        'company_id',
        'fiscal_year',
        'organisational_boundary',
        'consolidation_approach',
        'base_year',
        'base_year_rationale',
        'recalculation_policy',
        'gwp_version',
        'scope3_category_policy',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'base_year' => 'integer',
        'scope3_category_policy' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function defaultScope3Policy(): array
    {
        return collect(self::SCOPE3_CATEGORIES)->map(fn ($label, $cat) => [
            'category' => (int) $cat,
            'label' => $label,
            'included' => in_array($cat, [6, 7], true),
            'reason' => in_array($cat, [6, 7], true) ? null : 'Not yet material / no data',
        ])->values()->all();
    }
}
