<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmissionSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name', 'sector', 'location', 'reporting_year',
        'diesel_litres', 'petrol_litres', 'natural_gas_m3', 'refrigerant_kg', 'other_emissions',
        'electricity_kwh', 'district_cooling_kwh',
        'business_travel_flights_km', 'car_hire_km', 'waste_tonnes', 'water_m3', 'purchased_goods',
        'uploaded_files', 'scope1_total', 'scope2_total', 'scope3_total', 'grand_total', 'status'
    ];

    protected $casts = [
        'uploaded_files' => 'array',
        'diesel_litres' => 'decimal:2',
        'petrol_litres' => 'decimal:2',
        'natural_gas_m3' => 'decimal:2',
        'refrigerant_kg' => 'decimal:2',
        'other_emissions' => 'decimal:2',
        'electricity_kwh' => 'decimal:2',
        'district_cooling_kwh' => 'decimal:2',
        'business_travel_flights_km' => 'decimal:2',
        'car_hire_km' => 'decimal:2',
        'waste_tonnes' => 'decimal:2',
        'water_m3' => 'decimal:2',
        'purchased_goods' => 'decimal:2',
        'scope1_total' => 'decimal:2',
        'scope2_total' => 'decimal:2',
        'scope3_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    // Emission factors (kg CO2e per unit)
    const EMISSION_FACTORS = [
        'diesel_litres' => 2.68,
        'petrol_litres' => 2.31,
        'natural_gas_m3' => 0.202,
        'refrigerant_kg' => 1.0, // Placeholder
        'other_emissions' => 1.0, // Placeholder
        'electricity_kwh' => 0.424,
        'district_cooling_kwh' => 0.3,
        'business_travel_flights_km' => 0.285,
        'car_hire_km' => 0.2,
        'waste_tonnes' => 1.9,
        'water_m3' => 0.5,
        'purchased_goods' => 0.1,
    ];

    public function calculateScope1Total(): float
    {
        $total = 0;
        $total += ($this->diesel_litres ?? 0) * self::EMISSION_FACTORS['diesel_litres'];
        $total += ($this->petrol_litres ?? 0) * self::EMISSION_FACTORS['petrol_litres'];
        $total += ($this->natural_gas_m3 ?? 0) * self::EMISSION_FACTORS['natural_gas_m3'];
        $total += ($this->refrigerant_kg ?? 0) * self::EMISSION_FACTORS['refrigerant_kg'];
        $total += ($this->other_emissions ?? 0) * self::EMISSION_FACTORS['other_emissions'];
        
        return round($total, 2);
    }

    public function calculateScope2Total(): float
    {
        $total = 0;
        $total += ($this->electricity_kwh ?? 0) * self::EMISSION_FACTORS['electricity_kwh'];
        $total += ($this->district_cooling_kwh ?? 0) * self::EMISSION_FACTORS['district_cooling_kwh'];
        
        return round($total, 2);
    }

    public function calculateScope3Total(): float
    {
        $total = 0;
        $total += ($this->business_travel_flights_km ?? 0) * self::EMISSION_FACTORS['business_travel_flights_km'];
        $total += ($this->car_hire_km ?? 0) * self::EMISSION_FACTORS['car_hire_km'];
        $total += ($this->waste_tonnes ?? 0) * self::EMISSION_FACTORS['waste_tonnes'];
        $total += ($this->water_m3 ?? 0) * self::EMISSION_FACTORS['water_m3'];
        $total += ($this->purchased_goods ?? 0) * self::EMISSION_FACTORS['purchased_goods'];
        
        return round($total, 2);
    }

    public function calculateGrandTotal(): float
    {
        return $this->calculateScope1Total() + $this->calculateScope2Total() + $this->calculateScope3Total();
    }

    public function updateCalculatedTotals(): void
    {
        $this->scope1_total = $this->calculateScope1Total();
        $this->scope2_total = $this->calculateScope2Total();
        $this->scope3_total = $this->calculateScope3Total();
        $this->grand_total = $this->calculateGrandTotal();
        $this->save();
    }
}
