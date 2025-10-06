<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmissionSourceFormField extends Model
{
    use HasFactory;

    protected $fillable = [
        'emission_source_id',
        'field_name',
        'field_type',
        'field_label',
        'field_placeholder',
        'field_options',
        'is_required',
        'field_order',
        'validation_rules',
        'help_text'
    ];

    protected $casts = [
        'field_options' => 'array',
        'validation_rules' => 'array',
        'is_required' => 'boolean'
    ];

    /**
     * Get the emission source that owns this form field
     */
    public function emissionSource()
    {
        return $this->belongsTo(EmissionSourceMaster::class, 'emission_source_id');
    }

    /**
     * Get form fields for a specific emission source, ordered by field_order
     */
    public static function getFieldsForSource($emissionSourceId)
    {
        return self::where('emission_source_id', $emissionSourceId)
                   ->orderBy('field_order')
                   ->get();
    }
}
