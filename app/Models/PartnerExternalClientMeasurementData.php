<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerExternalClientMeasurementData extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_external_client_measurement_id',
        'emission_source_id',
        'field_name',
        'field_value',
        'field_type',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the measurement that owns this data.
     */
    public function measurement()
    {
        return $this->belongsTo(PartnerExternalClientMeasurement::class, 'partner_external_client_measurement_id');
    }

    /**
     * Get the emission source.
     */
    public function emissionSource()
    {
        return $this->belongsTo(EmissionSourceMaster::class, 'emission_source_id');
    }

    /**
     * Get the user who created this data.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who updated this data.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

