<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmissionFactorSelectionRule extends Model
{
    use HasFactory;

    protected $table = 'emission_factor_selection_rules';

    protected $fillable = [
        'emission_source_id',
        'rule_name',
        'priority',
        'conditions',
        'emission_factor_id',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];

    public function emissionSource()
    {
        return $this->belongsTo(EmissionSourceMaster::class, 'emission_source_id');
    }

    public function emissionFactor()
    {
        return $this->belongsTo(EmissionFactor::class, 'emission_factor_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }
}

