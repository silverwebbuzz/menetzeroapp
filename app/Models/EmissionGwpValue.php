<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmissionGwpValue extends Model
{
    use HasFactory;

    protected $table = 'emission_gwp_values';

    protected $fillable = [
        'gas_name',
        'gas_code',
        'gwp_version',
        'gwp_100_year',
        'gwp_20_year',
        'gwp_500_year',
        'notes',
        'is_kyoto_protocol',
        'is_active',
    ];

    protected $casts = [
        'gwp_100_year' => 'decimal:2',
        'gwp_20_year' => 'decimal:2',
        'gwp_500_year' => 'decimal:2',
        'is_kyoto_protocol' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByVersion($query, $version)
    {
        return $query->where('gwp_version', $version);
    }
}

