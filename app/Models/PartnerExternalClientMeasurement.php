<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerExternalClientMeasurement extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_external_client_location_id',
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
        'staff_work_from_home' => 'boolean',
        'work_from_home_percentage' => 'decimal:2',
        'total_co2e' => 'decimal:6',
        'scope_1_co2e' => 'decimal:6',
        'scope_2_co2e' => 'decimal:6',
        'scope_3_co2e' => 'decimal:6',
        'co2e_calculated_at' => 'datetime',
        'metadata' => 'array',
        'emission_source_co2e' => 'array',
    ];

    /**
     * Get the location that owns this measurement.
     */
    public function location()
    {
        return $this->belongsTo(PartnerExternalClientLocation::class, 'partner_external_client_location_id');
    }

    /**
     * Get the user who created this measurement.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get measurement data entries.
     */
    public function measurementData()
    {
        return $this->hasMany(PartnerExternalClientMeasurementData::class, 'partner_external_client_measurement_id');
    }

    /**
     * Scope for specific status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}

