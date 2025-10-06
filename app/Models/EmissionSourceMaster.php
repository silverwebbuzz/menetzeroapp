<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmissionSourceMaster extends Model
{
    use HasFactory;

    protected $table = 'emission_sources_master';

    protected $fillable = [
        'name',
        'description',
        'scope',
        'category',
        'subcategory',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all location emission boundaries for this source
     */
    public function locationBoundaries()
    {
        return $this->hasMany(LocationEmissionBoundary::class, 'emission_source_id');
    }

    /**
     * Get all form fields for this emission source
     */
    public function formFields()
    {
        return $this->hasMany(EmissionSourceFormField::class, 'emission_source_id');
    }

    /**
     * Scope query to filter by scope
     */
    public function scopeByScope($query, $scope)
    {
        return $query->where('scope', $scope);
    }

    /**
     * Scope query to filter by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope query to filter by type (upstream/downstream)
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}
