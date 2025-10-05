<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Measurement extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'period_start',
        'period_end',
        'frequency',
        'status',
        'fiscal_year',
        'fiscal_year_start_month',
        'created_by',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Get the location that owns this measurement
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the user who created this measurement
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all measurement data for this measurement
     */
    public function measurementData(): HasMany
    {
        return $this->hasMany(MeasurementData::class);
    }

    /**
     * Get audit trail entries for this measurement
     */
    public function auditTrail(): HasMany
    {
        return $this->hasMany(MeasurementAuditTrail::class);
    }

    /**
     * Get emission sources through measurement data
     */
    public function emissionSources(): HasManyThrough
    {
        return $this->hasManyThrough(
            EmissionSourceMaster::class,
            MeasurementData::class,
            'measurement_id',
            'id',
            'id',
            'emission_source_id'
        );
    }

    /**
     * Scope a query to only include measurements of a given status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include measurements for a given location
     */
    public function scopeByLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * Scope a query to only include measurements for a given fiscal year
     */
    public function scopeByFiscalYear($query, $fiscalYear)
    {
        return $query->where('fiscal_year', $fiscalYear);
    }

    /**
     * Get total CO2e for this measurement
     */
    public function getTotalCo2eAttribute()
    {
        return $this->measurementData()->sum('calculated_co2e');
    }

    /**
     * Get CO2e by scope
     */
    public function getCo2eByScope($scope)
    {
        return $this->measurementData()
            ->where('scope', $scope)
            ->sum('calculated_co2e');
    }

    /**
     * Check if measurement can be edited
     */
    public function canBeEdited()
    {
        return in_array($this->status, ['draft', 'not_verified']);
    }

    /**
     * Check if measurement can be submitted
     */
    public function canBeSubmitted()
    {
        return $this->status === 'draft' && $this->measurementData()->count() > 0;
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayAttribute()
    {
        return match($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'under_review' => 'Under Review',
            'not_verified' => 'Not Verified',
            'verified' => 'Verified',
            default => 'Unknown'
        };
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'draft' => 'gray',
            'submitted' => 'blue',
            'under_review' => 'yellow',
            'not_verified' => 'red',
            'verified' => 'green',
            default => 'gray'
        };
    }
}
