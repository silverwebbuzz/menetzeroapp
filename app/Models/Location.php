<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Services\MeasurementPeriodService;
use Illuminate\Support\Facades\Log;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'address',
        'city',
        'country',
        'location_type',
        'staff_count',
        'staff_work_from_home',
        'fiscal_year_start',
        'is_head_office',
        'is_active',
        'receives_utility_bills',
        'pays_electricity_proportion',
        'shared_building_services',
        'reporting_period',
        'measurement_frequency',
    ];

    protected $casts = [
        'staff_work_from_home' => 'boolean',
        'is_head_office' => 'boolean',
        'is_active' => 'boolean',
        'receives_utility_bills' => 'boolean',
        'pays_electricity_proportion' => 'boolean',
        'shared_building_services' => 'boolean',
    ];

    /**
     * Get the company that owns the location.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the emission boundaries for this location
     */
    public function emissionBoundaries()
    {
        return $this->hasMany(LocationEmissionBoundary::class);
    }

    /**
     * Get the measurements for this location
     */
    public function measurements()
    {
        return $this->hasMany(Measurement::class);
    }

    /**
     * Get the full address string.
     */
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->country
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Get the location type options.
     */
    public static function getLocationTypes()
    {
        return [
            'Co-Working Desks',
            'Office',
            'Warehouse',
            'Factory',
            'Retail Store',
            'Data Center',
            'Other'
        ];
    }

    /**
     * Get the fiscal year start options.
     */
    public static function getFiscalYearOptions()
    {
        return [
            'January', 'February', 'March', 'April',
            'May', 'June', 'July', 'August',
            'September', 'October', 'November', 'December'
        ];
    }

    /**
     * Get the measurement frequency options.
     */
    public static function getMeasurementFrequencyOptions()
    {
        return [
            'Annually',
            'Half Yearly',
            'Quarterly',
            'Monthly'
        ];
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // After creating a location, sync measurement periods
        static::created(function ($location) {
            \Log::info('Location created event triggered', [
                'location_id' => $location->id,
                'measurement_frequency' => $location->measurement_frequency,
                'fiscal_year_start' => $location->fiscal_year_start
            ]);
            
            if ($location->measurement_frequency && $location->fiscal_year_start) {
                $service = app(MeasurementPeriodService::class);
                $service->syncMeasurementPeriods($location, auth()->id());
            }
        });

        // After updating a location, check if measurement settings changed
        static::updated(function ($location) {
            $original = $location->getOriginal();
            $current = $location->getAttributes();
            
            // Check if measurement-related fields changed
            $measurementSettingsChanged = (
                ($original['measurement_frequency'] ?? null) !== ($current['measurement_frequency'] ?? null) ||
                ($original['fiscal_year_start'] ?? null) !== ($current['fiscal_year_start'] ?? null) ||
                ($original['reporting_period'] ?? null) !== ($current['reporting_period'] ?? null)
            );
            
            \Log::info('Location updated event triggered', [
                'location_id' => $location->id,
                'measurement_settings_changed' => $measurementSettingsChanged,
                'original_values' => [
                    'measurement_frequency' => $original['measurement_frequency'] ?? null,
                    'fiscal_year_start' => $original['fiscal_year_start'] ?? null,
                    'reporting_period' => $original['reporting_period'] ?? null
                ],
                'new_values' => [
                    'measurement_frequency' => $current['measurement_frequency'] ?? null,
                    'fiscal_year_start' => $current['fiscal_year_start'] ?? null,
                    'reporting_period' => $current['reporting_period'] ?? null
                ]
            ]);
            
            if ($measurementSettingsChanged) {
                \Log::info('Measurement settings changed, syncing periods...');
                try {
                    $service = app(MeasurementPeriodService::class);
                    $result = $service->syncMeasurementPeriods($location, auth()->id());
                    \Log::info('Measurement sync completed', $result);
                } catch (\Exception $e) {
                    \Log::error('Error syncing measurement periods: ' . $e->getMessage());
                }
            } else {
                \Log::info('No measurement settings changed, skipping sync');
            }
        });
    }
}
