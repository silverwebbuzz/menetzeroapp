<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'feature_code',
        'is_enabled',
        'enabled_at',
        'metadata',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'enabled_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the company.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope for enabled features.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }
}

