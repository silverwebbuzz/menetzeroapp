<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmissionFactor extends Model
{
    use HasFactory;

    protected $fillable = [
        'emission_source_id',
        'factor_value',
        'unit',
        'calculation_method',
        'region',
        'valid_from',
        'valid_to',
        'is_active',
        'description',
        'calculation_formula',
    ];

    protected $casts = [
        'factor_value' => 'decimal:6',
        'calculation_formula' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the emission source that owns this factor
     */
    public function emissionSource(): BelongsTo
    {
        return $this->belongsTo(EmissionSourceMaster::class, 'emission_source_id');
    }

    /**
     * Scope a query to only include active factors
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include factors for a given scope
     * Note: Scope is now retrieved via the emission source relationship
     */
    public function scopeByScope($query, $scope)
    {
        return $query->whereHas('emissionSource', function ($q) use ($scope) {
            $q->where('scope', $scope);
        });
    }

    /**
     * Scope a query to only include factors for a given region
     */
    public function scopeByRegion($query, $region)
    {
        return $query->where('region', $region);
    }

    /**
     * Scope a query to only include factors valid for a given year
     */
    public function scopeValidForYear($query, $year)
    {
        return $query->where(function ($q) use ($year) {
            $q->whereNull('valid_from')
              ->orWhere('valid_from', '<=', $year);
        })->where(function ($q) use ($year) {
            $q->whereNull('valid_to')
              ->orWhere('valid_to', '>=', $year);
        });
    }

    /**
     * Get formatted factor value with unit
     */
    public function getFormattedFactorAttribute()
    {
        return number_format($this->factor_value, 6) . ' ' . $this->unit;
    }

    /**
     * Check if factor is valid for current year
     */
    public function isValidForCurrentYear()
    {
        $currentYear = date('Y');
        return $this->isValidForYear($currentYear);
    }

    /**
     * Check if factor is valid for given year
     */
    public function isValidForYear($year)
    {
        $validFrom = $this->valid_from ? $this->valid_from <= $year : true;
        $validTo = $this->valid_to ? $this->valid_to >= $year : true;
        
        return $validFrom && $validTo;
    }

    /**
     * Get the best factor for a given emission source and region
     * Note: Scope is now retrieved via the emission source relationship
     */
    public static function getBestFactor($emissionSourceId, $region = 'UAE', $year = null)
    {
        if ($year === null) {
            $year = date('Y');
        }

        return self::where('emission_source_id', $emissionSourceId)
            ->where('region', $region)
            ->where('is_active', true)
            ->validForYear($year)
            ->orderBy('valid_from', 'desc')
            ->first();
    }

    /**
     * Calculate CO2e using this factor
     */
    public function calculateCo2e($quantity)
    {
        return $quantity * $this->factor_value;
    }
}