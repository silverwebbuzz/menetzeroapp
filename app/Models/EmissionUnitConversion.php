<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmissionUnitConversion extends Model
{
    use HasFactory;

    protected $table = 'emission_unit_conversions';

    protected $fillable = [
        'from_unit',
        'to_unit',
        'conversion_factor',
        'fuel_type',
        'region',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:6',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

