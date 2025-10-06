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
        'staff_count',
        'staff_work_from_home',
        'work_from_home_percentage',
        'total_co2e',
        'scope_1_co2e',
        'scope_2_co2e',
        'scope_3_co2e',
        'co2e_calculated_at',
        'emission_source_co2e',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'metadata' => 'array',
        'staff_work_from_home' => 'boolean',
        'work_from_home_percentage' => 'decimal:2',
        'total_co2e' => 'decimal:6',
        'scope_1_co2e' => 'decimal:6',
        'scope_2_co2e' => 'decimal:6',
        'scope_3_co2e' => 'decimal:6',
        'co2e_calculated_at' => 'datetime',
        'emission_source_co2e' => 'array',
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
     * Get total CO2e for this measurement (calculated directly)
     */
    public function getTotalCo2eAttribute()
    {
        $totalCo2e = 0;
        
        // Get all measurement data grouped by emission source
        $measurementData = $this->measurementData()
            ->with('emissionSource')
            ->get()
            ->groupBy('emission_source_id');
        
        foreach ($measurementData as $emissionSourceId => $data) {
            // Get quantity from the data
            $quantityData = $data->where('field_name', 'quantity')->first();
            if ($quantityData) {
                $quantity = (float) $quantityData->field_value;
                
                // Get emission factor for this source
                $emissionFactor = \App\Models\EmissionFactor::getBestFactor($emissionSourceId, 'UAE', $this->fiscal_year);
                if ($emissionFactor) {
                    $co2e = $quantity * $emissionFactor->factor_value;
                    $totalCo2e += round($co2e, 6);
                }
            }
        }
        
        return round($totalCo2e, 6);
    }

    /**
     * Get CO2e by scope (calculated directly)
     */
    public function getCo2eByScope($scope)
    {
        $scopeCo2e = 0;
        
        // Get all measurement data grouped by emission source
        $measurementData = $this->measurementData()
            ->with('emissionSource')
            ->get()
            ->groupBy('emission_source_id');
        
        foreach ($measurementData as $emissionSourceId => $data) {
            // Get quantity from the data
            $quantityData = $data->where('field_name', 'quantity')->first();
            if ($quantityData) {
                $quantity = (float) $quantityData->field_value;
                
                // Get emission factor for this source
                $emissionFactor = \App\Models\EmissionFactor::getBestFactor($emissionSourceId, 'UAE', $this->fiscal_year);
                if ($emissionFactor) {
                    $co2e = $quantity * $emissionFactor->factor_value;
                    
                    // Get emission source scope
                    $emissionSource = $data->first()->emissionSource;
                    if ($emissionSource && $emissionSource->scope === $scope) {
                        $scopeCo2e += round($co2e, 6);
                    }
                }
            }
        }
        
        return round($scopeCo2e, 6);
    }

    /**
     * Get CO2e for a specific emission source (calculated directly)
     */
    public function getSourceCo2e($emissionSourceId)
    {
        // Get measurement data for this specific source
        $data = $this->measurementData()
            ->where('emission_source_id', $emissionSourceId)
            ->get()
            ->keyBy('field_name');
        
        // Get quantity from the data
        $quantityData = $data->where('field_name', 'quantity')->first();
        if ($quantityData) {
            $quantity = (float) $quantityData->field_value;
            
            // Get emission factor for this source
            $emissionFactor = \App\Models\EmissionFactor::getBestFactor($emissionSourceId, 'UAE', $this->fiscal_year);
            if ($emissionFactor) {
                $co2e = $quantity * $emissionFactor->factor_value;
                return round($co2e, 6);
            }
        }
        
        return 0;
    }

    /**
     * Calculate and save CO2e totals to database fields
     */
    public function calculateAndSaveCo2e()
    {
        $totalCo2e = 0;
        $scope1Co2e = 0;
        $scope2Co2e = 0;
        $scope3Co2e = 0;
        $emissionSourceCo2e = [];

        // Get all measurement data for this measurement
        $measurementData = $this->measurementData()
            ->with('emissionSource')
            ->get()
            ->groupBy('emission_source_id');

        foreach ($measurementData as $sourceId => $sourceData) {
            $source = $sourceData->first()->emissionSource;
            if (!$source) continue;

            // Get quantity and emission factor
            $quantityData = $sourceData->where('field_name', 'quantity')->first();
            if (!$quantityData) continue;

            $quantity = (float) $quantityData->field_value;
            $emissionFactor = \App\Models\EmissionFactor::getBestFactor($sourceId, 'UAE', $this->fiscal_year);
            
            if ($emissionFactor) {
                $co2e = $quantity * $emissionFactor->emission_factor;
                $totalCo2e += $co2e;
                $emissionSourceCo2e[$sourceId] = round($co2e, 6);

                // Add to scope totals
                switch ($source->scope) {
                    case 'Scope 1':
                        $scope1Co2e += $co2e;
                        break;
                    case 'Scope 2':
                        $scope2Co2e += $co2e;
                        break;
                    case 'Scope 3':
                        $scope3Co2e += $co2e;
                        break;
                }
            }
        }

        // Update the measurement with calculated values
        $this->update([
            'total_co2e' => round($totalCo2e, 6),
            'scope_1_co2e' => round($scope1Co2e, 6),
            'scope_2_co2e' => round($scope2Co2e, 6),
            'scope_3_co2e' => round($scope3Co2e, 6),
            'emission_source_co2e' => $emissionSourceCo2e,
            'co2e_calculated_at' => now(),
        ]);
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
