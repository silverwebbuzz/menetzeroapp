<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransportData extends Model
{
    use HasFactory;

    protected $table = 'transport_data';

    protected $fillable = [
        'facility_id', 'vehicle_type', 'fuel_type', 'distance_travelled', 'fuel_consumed', 'unit', 'date', 'uploaded_file', 'co2e',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
