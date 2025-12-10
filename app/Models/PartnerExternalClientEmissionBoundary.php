<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerExternalClientEmissionBoundary extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_external_client_location_id',
        'scope',
        'selected_sources',
    ];

    protected $casts = [
        'selected_sources' => 'array',
    ];

    /**
     * Get the location that owns this emission boundary.
     */
    public function location()
    {
        return $this->belongsTo(PartnerExternalClientLocation::class, 'partner_external_client_location_id');
    }
}

