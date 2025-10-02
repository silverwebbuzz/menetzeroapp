<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emission_sources', function (Blueprint $table) {
            $table->id();
            
            // Company Information
            $table->string('company_name');
            $table->string('sector');
            $table->string('location');
            $table->integer('reporting_year');
            
            // Scope 1 (Direct Emissions)
            $table->decimal('diesel_litres', 12, 2)->nullable();
            $table->decimal('petrol_litres', 12, 2)->nullable();
            $table->decimal('natural_gas_m3', 12, 2)->nullable();
            $table->decimal('refrigerant_kg', 12, 2)->nullable();
            $table->decimal('other_emissions', 12, 2)->nullable();
            
            // Scope 2 (Purchased Energy)
            $table->decimal('electricity_kwh', 12, 2)->nullable();
            $table->decimal('district_cooling_kwh', 12, 2)->nullable();
            
            // Scope 3 (Other Indirect)
            $table->decimal('business_travel_flights_km', 12, 2)->nullable();
            $table->decimal('car_hire_km', 12, 2)->nullable();
            $table->decimal('waste_tonnes', 12, 2)->nullable();
            $table->decimal('water_m3', 12, 2)->nullable();
            $table->decimal('purchased_goods', 12, 2)->nullable();
            
            // Supporting Evidence Files
            $table->json('uploaded_files')->nullable();
            
            // Calculated totals
            $table->decimal('scope1_total', 12, 2)->nullable();
            $table->decimal('scope2_total', 12, 2)->nullable();
            $table->decimal('scope3_total', 12, 2)->nullable();
            $table->decimal('grand_total', 12, 2)->nullable();
            
            // Form status
            $table->enum('status', ['draft', 'submitted', 'reviewed'])->default('draft');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emission_sources');
    }
};
