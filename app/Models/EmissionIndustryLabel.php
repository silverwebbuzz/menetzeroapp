<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmissionIndustryLabel extends Model
{
    use HasFactory;

    protected $table = 'emission_industry_labels';

    protected $fillable = [
        'emission_source_id',
        'industry_category_id',
        'match_level',
        'also_match_children',
        'unit_type',
        'user_friendly_name',
        'user_friendly_description',
        'common_equipment',
        'typical_units',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'also_match_children' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function emissionSource()
    {
        return $this->belongsTo(EmissionSourceMaster::class, 'emission_source_id');
    }

    public function industryCategory()
    {
        return $this->belongsTo(MasterIndustryCategory::class, 'industry_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

