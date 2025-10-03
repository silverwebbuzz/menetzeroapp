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
        // Drop the old table
        Schema::dropIfExists('location_emission_boundaries');
        
        // Create the new optimized table
        Schema::create('location_emission_boundaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->enum('scope', ['Scope 1', 'Scope 2', 'Scope 3']);
            $table->json('selected_sources'); // Store array of emission source IDs
            $table->timestamps();
            
            // Ensure only one record per location per scope
            $table->unique(['location_id', 'scope'], 'loc_scope_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_emission_boundaries');
    }
};
