<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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
        'supplier_emission_factor',
        'scope2_method',
        'is_biogenic',
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
        'is_biogenic' => 'boolean',
        'supplier_emission_factor' => 'decimal:6',
        'supporting_docs' => 'array',
        'additional_data' => 'array',
        'entry_date' => 'date',
    ];

    /**
     * Warn when an entry is written against a retired emission source.
     *
     * Deprecated sources (is_active = 0 / is_quick_input = 0) have no subcategory, so
     * their emissions count toward the cached scope totals while being absent from the
     * GHG Protocol category breakdown — the report stops reconciling. Quick Input can't
     * reach these sources, but seeders, imports, and scripts write here directly.
     *
     * Logged rather than blocked: refusing the write could break a legitimate historical
     * backfill, and silently dropping emissions data is worse than recording it loudly.
     */
    protected static function booted(): void
    {
        static::creating(function (self $entry) {
            if (!$entry->emission_source_id) {
                return;
            }

            $source = EmissionSourceMaster::find($entry->emission_source_id);

            if (!$source) {
                Log::warning('measurement_data written with unknown emission source', [
                    'emission_source_id' => $entry->emission_source_id,
                    'measurement_id' => $entry->measurement_id,
                ]);

                return;
            }

            if (!$source->is_active || !$source->is_quick_input) {
                Log::warning('measurement_data written against a deprecated emission source', [
                    'emission_source_id' => $source->id,
                    'emission_source_name' => $source->name,
                    'scope' => $source->scope,
                    'is_active' => (bool) $source->is_active,
                    'is_quick_input' => (bool) $source->is_quick_input,
                    'has_subcategory' => filled($source->subcategory),
                    'measurement_id' => $entry->measurement_id,
                ]);
            }
        });
    }

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
     * Get the emission factor used for this data
     */
    public function emissionFactor()
    {
        return $this->belongsTo(EmissionFactor::class, 'emission_factor_id');
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