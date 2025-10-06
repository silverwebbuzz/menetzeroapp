<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeasurementData extends Model
{
    use HasFactory;

    protected $fillable = [
        'measurement_id',
        'emission_source_id',
        'quantity',
        'unit',
        'calculated_co2e',
        'scope',
        'calculation_method',
        'supporting_docs',
        'is_offset',
        'notes',
        'additional_data',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'calculated_co2e' => 'decimal:4',
        'supporting_docs' => 'array',
        'additional_data' => 'array',
        'is_offset' => 'boolean',
    ];

    /**
     * Get the measurement that owns this data
     */
    public function measurement(): BelongsTo
    {
        return $this->belongsTo(Measurement::class);
    }

    /**
     * Get the emission source for this data
     */
    public function emissionSource(): BelongsTo
    {
        return $this->belongsTo(EmissionSourceMaster::class, 'emission_source_id');
    }

    /**
     * Get the emission factor for this data
     */
    public function emissionFactor(): BelongsTo
    {
        return $this->belongsTo(EmissionFactor::class, 'emission_source_id', 'emission_source_id')
            ->where('is_active', true);
    }

    /**
     * Calculate CO2e based on quantity and emission factor
     */
    public function calculateCo2e($factorValue = null)
    {
        if ($factorValue === null) {
            $factor = $this->emissionFactor;
            if (!$factor) {
                return 0;
            }
            $factorValue = $factor->factor_value;
        }

        $this->calculated_co2e = $this->quantity * $factorValue;
        return $this->calculated_co2e;
    }

    /**
     * Get formatted quantity with unit
     */
    public function getFormattedQuantityAttribute()
    {
        return number_format($this->quantity, 2) . ' ' . $this->unit;
    }

    /**
     * Get formatted CO2e
     */
    public function getFormattedCo2eAttribute()
    {
        return number_format($this->calculated_co2e, 2) . ' kg CO2e';
    }

    /**
     * Check if this data has supporting documents
     */
    public function hasSupportingDocs()
    {
        return !empty($this->supporting_docs);
    }

    /**
     * Get supporting documents count
     */
    public function getSupportingDocsCountAttribute()
    {
        return is_array($this->supporting_docs) ? count($this->supporting_docs) : 0;
    }

    /**
     * Scope a query to only include data of a given scope
     */
    public function scopeByScope($query, $scope)
    {
        return $query->where('scope', $scope);
    }

    /**
     * Scope a query to only include offset data
     */
    public function scopeOffset($query)
    {
        return $query->where('is_offset', true);
    }

    /**
     * Scope a query to only include non-offset data
     */
    public function scopeNonOffset($query)
    {
        return $query->where('is_offset', false);
    }
}
