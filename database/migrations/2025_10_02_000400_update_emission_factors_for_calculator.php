<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop existing emission_factors table if it exists
        Schema::dropIfExists('emission_factors');
        
        // Create new emission_factors table with proper structure
        Schema::create('emission_factors', function (Blueprint $table) {
            $table->id();
            $table->string('activity_type'); // e.g., 'diesel_fuel', 'electricity', 'waste_landfill'
            $table->string('unit'); // e.g., 'litres', 'kWh', 'tonnes', 'm3', 'km'
            $table->decimal('factor_value', 10, 4); // CO2e per unit
            $table->enum('scope', ['Scope1', 'Scope2', 'Scope3']);
            $table->string('source')->nullable(); // e.g., 'MOCCAE', 'IPCC', 'EPA'
            $table->integer('year')->default(2024);
            $table->string('region')->default('UAE');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['activity_type', 'unit', 'scope']);
            $table->index(['scope', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emission_factors');
    }
};
