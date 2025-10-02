<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndustrialData extends Model
{
    use HasFactory;

    protected $table = 'industrial_data';

    protected $fillable = [
        'facility_id', 'process_type', 'raw_material', 'quantity', 'unit', 'date', 'uploaded_file', 'co2e',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}
