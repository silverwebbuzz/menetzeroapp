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
        'field_type',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'field_value' => 'string'
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