<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeasurementData extends Model
{
    use HasFactory;

    protected $fillable = [
        'measurement_id',
        'emission_source_id',
        'field_name',
        'field_value',
        'quantity',
        'unit',
        'calculated_co2e',
        'scope',
        'calculation_method',
        'supporting_docs',
        'is_offset',
        'notes',
        'additional_data',
        'entry_date',
        'fuel_type',
        'vehicle_type',
        'gas_type',
        'co2_emissions',
        'ch4_emissions',
        'n2o_emissions',
        'emission_factor_id',
        'gwp_version_used',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'calculated_co2e' => 'decimal:4',
        'co2_emissions' => 'decimal:4',
        'ch4_emissions' => 'decimal:4',
        'n2o_emissions' => 'decimal:4',
        'is_offset' => 'boolean',
        'supporting_docs' => 'array',
        'additional_data' => 'array',
        'entry_date' => 'date',
    ];

    /**
     * Get the measurement that owns this data
     */
    public function measurement()
    {
        return $this->belongsTo(Measurement::class);
    }

    /**
     * Get the emission source for this data
     */
    public function emissionSource()
    {
        return $this->belongsTo(EmissionSourceMaster::class, 'emission_source_id');
    }

    /**
     * Get all data for a specific measurement and emission source
     */
    public static function getDataForSource($measurementId, $emissionSourceId)
    {
        return self::where('measurement_id', $measurementId)
                   ->where('emission_source_id', $emissionSourceId)
                   ->get()
                   ->keyBy('field_name');
    }

    /**
     * Save or update data for a specific measurement and emission source
     */
    public static function saveDataForSource($measurementId, $emissionSourceId, $data, $userId = null)
    {
        foreach ($data as $fieldName => $fieldValue) {
            self::updateOrCreate(
                [
                    'measurement_id' => $measurementId,
                    'emission_source_id' => $emissionSourceId,
                    'field_name' => $fieldName
                ],
                [
                    'field_value' => $fieldValue,
                    'updated_by' => $userId
                ]
            );
        }
    }
}