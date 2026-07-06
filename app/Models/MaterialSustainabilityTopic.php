<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialSustainabilityTopic extends Model
{
    public const MATERIALITY_LEVELS = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ];

    protected $fillable = [
        'company_id', 'fiscal_year', 'topic_key', 'is_material', 'rationale',
        'impact_materiality', 'financial_materiality',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'is_material' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function label(): string
    {
        $topics = config('disclosure.ifrs_s1.material_topics', []);

        return $topics[$this->topic_key]['label'] ?? $this->topic_key;
    }
}
