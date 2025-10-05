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
        Schema::create('measurement_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('measurement_id')->constrained()->onDelete('cascade');
            $table->foreignId('emission_source_id')->constrained('emission_sources_master')->onDelete('cascade');
            $table->decimal('quantity', 15, 4); // Support large numbers with 4 decimal places
            $table->string('unit', 50); // e.g., 'kWh', 'liters', 'kg', 'AED'
            $table->decimal('calculated_co2e', 15, 4); // Calculated CO2 equivalent
            $table->enum('scope', ['Scope 1', 'Scope 2', 'Scope 3']);
            $table->string('calculation_method', 100)->nullable(); // Method used for calculation
            $table->json('supporting_docs')->nullable(); // Store file paths/names
            $table->boolean('is_offset')->default(false); // Whether this emission is offset
            $table->text('notes')->nullable();
            $table->json('additional_data')->nullable(); // Store any additional form data
            $table->timestamps();
            
            // Ensure unique emission source per measurement
            $table->unique(['measurement_id', 'emission_source_id'], 'unique_measurement_source');
            
            // Index for performance
            $table->index(['measurement_id', 'scope']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurement_data');
    }
};
