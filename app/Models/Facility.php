<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'name', 'location', 'type',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function energyData()
    {
        return $this->hasMany(EnergyData::class);
    }

    public function transportData()
    {
        return $this->hasMany(TransportData::class);
    }

    public function industrialData()
    {
        return $this->hasMany(IndustrialData::class);
    }

    public function wasteData()
    {
        return $this->hasMany(WasteData::class);
    }

    public function agricultureData()
    {
        return $this->hasMany(AgricultureData::class);
    }
}


