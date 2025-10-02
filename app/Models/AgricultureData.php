<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgricultureData extends Model
{
    use HasFactory;

    protected $table = 'agriculture_data';

    protected $fillable = [
        'facility_id', 'livestock_type', 'feed_type', 'manure_mgmt', 'headcount', 'date', 'co2e',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}


