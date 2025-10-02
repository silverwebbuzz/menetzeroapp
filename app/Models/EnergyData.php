<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnergyData extends Model
{
    use HasFactory;

    protected $table = 'energy_data';

    protected $fillable = [
        'facility_id', 'source_type', 'consumption_value', 'unit', 'date', 'uploaded_file', 'co2e',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
