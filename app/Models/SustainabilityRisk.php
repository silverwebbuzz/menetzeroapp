<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SustainabilityRisk extends Model
{
    public const HORIZONS = ['short' => 'Short (0–1y)', 'medium' => 'Medium (1–5y)', 'long' => 'Long (5y+)'];
    public const LIKELIHOODS = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'];

    protected $fillable = [
        'company_id', 'fiscal_year', 'name', 'topic', 'time_horizon',
        'description', 'financial_impact', 'likelihood', 'mitigation', 'owner', 'status',
    ];

    protected $casts = ['fiscal_year' => 'integer'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function topicLabel(): string
    {
        $topics = config('disclosure.ifrs_s1.material_topics', []);

        return $topics[$this->topic]['label'] ?? $this->topic;
    }
}
