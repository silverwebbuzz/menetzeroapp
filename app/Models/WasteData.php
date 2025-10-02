<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WasteData extends Model
{
    use HasFactory;

    protected $table = 'waste_data';

    protected $fillable = [
        'facility_id', 'waste_type', 'quantity', 'unit', 'disposal_method', 'date', 'uploaded_file', 'co2e',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}


