<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationEmissionBoundary extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'emission_source_id',
        'is_selected',
    ];

    protected $casts = [
        'is_selected' => 'boolean',
    ];

    /**
     * Get the location that owns this boundary
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the emission source master data
     */
    public function emissionSource()
    {
        return $this->belongsTo(EmissionSourceMaster::class, 'emission_source_id');
    }
}
