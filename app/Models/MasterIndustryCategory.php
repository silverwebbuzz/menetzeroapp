<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterIndustryCategory extends Model
{
    use HasFactory;

    protected $table = 'master_industry_categories';

    protected $fillable = [
        'name',
        'parent_id',
        'level',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get parent category
     */
    public function parent()
    {
        return $this->belongsTo(MasterIndustryCategory::class, 'parent_id');
    }

    /**
     * Get child categories
     */
    public function children()
    {
        return $this->hasMany(MasterIndustryCategory::class, 'parent_id')->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Get all sectors (Level 1)
     */
    public static function getSectors()
    {
        return static::where('level', 1)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get industries (Level 2) for a specific sector
     */
    public static function getIndustriesBySector($sectorId)
    {
        return static::where('parent_id', $sectorId)
            ->where('level', 2)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get subcategories (Level 3) for a specific industry
     */
    public static function getSubcategoriesByIndustry($industryId)
    {
        return static::where('parent_id', $industryId)
            ->where('level', 3)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Find category by name and level
     */
    public static function findByNameAndLevel($name, $level, $parentId = null)
    {
        $query = static::where('name', $name)
            ->where('level', $level)
            ->where('is_active', true);
        
        if ($parentId !== null) {
            $query->where('parent_id', $parentId);
        }
        
        return $query->first();
    }
}

