<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerExternalClientLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_external_client_id',
        'name',
        'address',
        'city',
        'country',
        'location_type',
        'staff_count',
        'staff_work_from_home',
        'work_from_home_percentage',
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
        'work_from_home_percentage' => 'decimal:2',
    ];

    /**
     * Get the external client that owns this location.
     */
    public function externalClient()
    {
        return $this->belongsTo(PartnerExternalClient::class, 'partner_external_client_id');
    }

    /**
     * Get measurements for this location.
     */
    public function measurements()
    {
        return $this->hasMany(PartnerExternalClientMeasurement::class, 'partner_external_client_location_id');
    }

    /**
     * Get emission boundaries for this location.
     */
    public function emissionBoundaries()
    {
        return $this->hasMany(PartnerExternalClientEmissionBoundary::class, 'partner_external_client_location_id');
    }

    /**
     * Scope for active locations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

