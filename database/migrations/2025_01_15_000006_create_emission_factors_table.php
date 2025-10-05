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
            $table->foreignId('emission_source_id')->constrained('emission_sources_master')->onDelete('cascade');
            $table->decimal('factor_value', 10, 6); // High precision for emission factors
            $table->string('unit', 50); // Unit for the factor (e.g., 'kg CO2e per kWh')
            $table->enum('scope', ['Scope 1', 'Scope 2', 'Scope 3']);
            $table->string('calculation_method', 100)->nullable(); // Method used (e.g., 'IPCC', 'EPA', 'DEFRA')
            $table->string('region', 50)->default('UAE'); // Regional specificity
            $table->year('valid_from')->nullable(); // Factor validity period
            $table->year('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->json('calculation_formula')->nullable(); // Store complex calculation formulas
            $table->timestamps();
            
            // Ensure unique active factors per source
            $table->unique(['emission_source_id', 'scope', 'region', 'is_active'], 'unique_active_factor');
            
            // Index for performance
            $table->index(['emission_source_id', 'scope', 'is_active']);
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
