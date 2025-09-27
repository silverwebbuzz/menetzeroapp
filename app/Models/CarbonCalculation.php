<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CarbonCalculation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'scope',
        'year',
        'quarter',
        'month',
        'total_emissions',
        'emissions_per_employee',
        'emissions_per_revenue',
        'breakdown',
        'trends',
        'reduction_target',
        'reduction_achieved',
        'reduction_percentage',
        'is_verified',
        'calculated_by',
        'calculated_at',
    ];

    protected $casts = [
        'total_emissions' => 'decimal:4',
        'emissions_per_employee' => 'decimal:4',
        'emissions_per_revenue' => 'decimal:4',
        'breakdown' => 'array',
        'trends' => 'array',
        'reduction_target' => 'decimal:4',
        'reduction_achieved' => 'decimal:4',
        'reduction_percentage' => 'decimal:2',
        'is_verified' => 'boolean',
        'calculated_at' => 'datetime',
    ];

    /**
     * Get the company that owns the calculation.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user that performed the calculation.
     */
    public function calculator()
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    /**
     * Scope a query to only include calculations for a specific scope.
     */
    public function scopeForScope($query, $scope)
    {
        return $query->where('scope', $scope);
    }

    /**
     * Scope a query to only include calculations for a specific year.
     */
    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope a query to only include calculations for a specific quarter.
     */
    public function scopeForQuarter($query, $year, $quarter)
    {
        return $query->where('year', $year)->where('quarter', $quarter);
    }

    /**
     * Scope a query to only include calculations for a specific month.
     */
    public function scopeForMonth($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    /**
     * Scope a query to only include verified calculations.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }
}
