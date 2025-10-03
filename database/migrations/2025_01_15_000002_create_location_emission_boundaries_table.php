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
        Schema::create('location_emission_boundaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->foreignId('emission_source_id')->constrained('emission_sources_master')->onDelete('cascade');
            $table->boolean('is_selected')->default(false);
            $table->timestamps();
            
            // Ensure unique combination of location and emission source
            $table->unique(['location_id', 'emission_source_id']);
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
