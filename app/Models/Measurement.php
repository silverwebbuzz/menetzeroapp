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
     * Get total CO2e for this measurement (cached)
     */
    public function getTotalCo2eAttribute()
    {
        return $this->total_co2e ?? 0;
    }

    /**
     * Get CO2e by scope (cached)
     */
    public function getCo2eByScope($scope)
    {
        return match($scope) {
            'Scope 1' => $this->scope_1_co2e ?? 0,
            'Scope 2' => $this->scope_2_co2e ?? 0,
            'Scope 3' => $this->scope_3_co2e ?? 0,
            default => 0
        };
    }

    /**
     * Calculate and cache CO2e values
     */
    public function calculateAndCacheCo2e()
    {
        \Log::info("Starting CO2e calculation for measurement ID: " . $this->id);
        
        $scope1Co2e = 0;
        $scope2Co2e = 0;
        $scope3Co2e = 0;
        $sourceCo2e = []; // Store individual source CO2e values
        
        \Log::info("Initial scope values - Scope 1: {$scope1Co2e}, Scope 2: {$scope2Co2e}, Scope 3: {$scope3Co2e}");
        
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
                    
                    // Round to 6 decimal places to match database precision
                    $co2e = round($co2e, 6);
                    
                    // Store individual source CO2e
                    $sourceCo2e[$emissionSourceId] = $co2e;
                    
                    // Get emission source scope
                    $emissionSource = $data->first()->emissionSource;
                    if ($emissionSource) {
                        \Log::info("Source ID {$emissionSourceId}: {$emissionSource->name} - Scope: {$emissionSource->scope} - CO2e: {$co2e}");
                        switch ($emissionSource->scope) {
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
            }
        }
        
        $totalCo2e = $scope1Co2e + $scope2Co2e + $scope3Co2e;
        
        // Round all totals to 6 decimal places to match database precision
        $scope1Co2e = round($scope1Co2e, 6);
        $scope2Co2e = round($scope2Co2e, 6);
        $scope3Co2e = round($scope3Co2e, 6);
        $totalCo2e = round($totalCo2e, 6);
        
        \Log::info("Final scope totals - Scope 1: {$scope1Co2e}, Scope 2: {$scope2Co2e}, Scope 3: {$scope3Co2e}, Total: {$totalCo2e}");
        
        // Update cached values using mass assignment
        \Log::info("Before saving measurement ID: " . $this->id . 
                  " - Total: " . $totalCo2e . 
                  ", Scope 1: " . $scope1Co2e . 
                  ", Scope 2: " . $scope2Co2e . 
                  ", Scope 3: " . $scope3Co2e . 
                  ", Sources: " . json_encode($sourceCo2e));
        
        $updateData = [
            'total_co2e' => $totalCo2e,
            'scope_1_co2e' => $scope1Co2e,
            'scope_2_co2e' => $scope2Co2e,
            'scope_3_co2e' => $scope3Co2e,
            'emission_source_co2e' => $sourceCo2e,
            'co2e_calculated_at' => now(),
        ];
        
        \Log::info("Update data: " . json_encode($updateData));
        
        $saved = $this->update($updateData);
        \Log::info("Update result: " . ($saved ? 'SUCCESS' : 'FAILED'));
        
        // Reload the model to ensure we're getting the fresh data from the database
        $this->refresh();

        \Log::info("After saving and refreshing measurement ID: " . $this->id . 
                  " - Total: " . $this->total_co2e . 
                  ", Scope 1: " . $this->scope_1_co2e . 
                  ", Scope 2: " . $this->scope_2_co2e . 
                  ", Scope 3: " . $this->scope_3_co2e . 
                  ", Sources: " . json_encode($this->emission_source_co2e));
        
        \Log::info("CO2e calculation completed for measurement ID: " . $this->id . 
                  " - Total: " . $totalCo2e . 
                  ", Scope 1: " . $scope1Co2e . 
                  ", Sources: " . json_encode($sourceCo2e));
        
        return [
            'total' => $totalCo2e,
            'scope_1' => $scope1Co2e,
            'scope_2' => $scope2Co2e,
            'scope_3' => $scope3Co2e,
            'sources' => $sourceCo2e,
        ];
    }

    /**
     * Get CO2e for a specific emission source (cached)
     */
    public function getSourceCo2e($emissionSourceId)
    {
        $sourceCo2e = $this->emission_source_co2e ?? [];
        return $sourceCo2e[$emissionSourceId] ?? 0;
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
