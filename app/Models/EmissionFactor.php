<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmissionFactor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'subcategory',
        'unit',
        'co2_factor',
        'ch4_factor',
        'n2o_factor',
        'total_gwp',
        'source',
        'source_url',
        'year',
        'region',
        'description',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'co2_factor' => 'decimal:6',
        'ch4_factor' => 'decimal:6',
        'n2o_factor' => 'decimal:6',
        'total_gwp' => 'decimal:6',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Scope a query to only include active emission factors.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include factors for a specific category.
     */
    public function scopeForCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to only include factors for a specific subcategory.
     */
    public function scopeForSubcategory($query, $subcategory)
    {
        return $query->where('subcategory', $subcategory);
    }

    /**
     * Scope a query to only include factors for a specific unit.
     */
    public function scopeForUnit($query, $unit)
    {
        return $query->where('unit', $unit);
    }

    /**
     * Scope a query to only include factors for a specific region.
     */
    public function scopeForRegion($query, $region)
    {
        return $query->where('region', $region);
    }

    /**
     * Get the most recent emission factor for a given category and unit.
     */
    public function scopeLatestForCategoryAndUnit($query, $category, $unit)
    {
        return $query->where('category', $category)
                    ->where('unit', $unit)
                    ->where('is_active', true)
                    ->orderBy('year', 'desc')
                    ->orderBy('id', 'desc');
    }
}
