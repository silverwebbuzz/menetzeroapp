<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CarbonEmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'scope',
        'category',
        'subcategory',
        'activity_name',
        'description',
        'quantity',
        'unit',
        'emission_factor',
        'total_emissions',
        'activity_date',
        'data_source',
        'notes',
        'metadata',
        'is_verified',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'emission_factor' => 'decimal:6',
        'total_emissions' => 'decimal:4',
        'activity_date' => 'date',
        'metadata' => 'array',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the company that owns the emission.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user that created the emission.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user that verified the emission.
     */
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope a query to only include emissions for a specific scope.
     */
    public function scopeForScope($query, $scope)
    {
        return $query->where('scope', $scope);
    }

    /**
     * Scope a query to only include emissions for a specific category.
     */
    public function scopeForCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to only include verified emissions.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope a query to only include emissions within a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('activity_date', [$startDate, $endDate]);
    }
}
