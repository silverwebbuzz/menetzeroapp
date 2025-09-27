<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('emission_factors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category'); // e.g., 'fuel', 'electricity', 'transportation'
            $table->string('subcategory')->nullable(); // e.g., 'gasoline', 'diesel', 'natural_gas'
            $table->string('unit'); // e.g., 'liters', 'kWh', 'miles', 'kg'
            $table->decimal('co2_factor', 10, 6); // kg CO2e per unit
            $table->decimal('ch4_factor', 10, 6)->default(0); // kg CH4 per unit
            $table->decimal('n2o_factor', 10, 6)->default(0); // kg N2O per unit
            $table->decimal('total_gwp', 10, 6); // Total Global Warming Potential
            $table->string('source'); // e.g., 'EPA', 'IPCC', 'DEFRA'
            $table->string('source_url')->nullable();
            $table->integer('year'); // Year of the emission factor
            $table->string('region')->nullable(); // e.g., 'US', 'EU', 'Global'
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // Additional metadata
            $table->timestamps();
            
            $table->index(['category', 'subcategory']);
            $table->index(['unit', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emission_factors');
    }
};
