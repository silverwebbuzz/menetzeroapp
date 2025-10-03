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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Business location name
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('location_type')->nullable(); // e.g., 'Co-Working Desks', 'Office', 'Warehouse'
            $table->integer('staff_count')->nullable(); // Total number of staff (FTE)
            $table->boolean('staff_work_from_home')->default(false);
            $table->string('fiscal_year_start')->default('January'); // January, February, etc.
            $table->boolean('is_head_office')->default(false);
            $table->boolean('is_active')->default(true);
            
            // Building details
            $table->boolean('receives_utility_bills')->default(false);
            $table->boolean('pays_electricity_proportion')->default(false);
            $table->boolean('shared_building_services')->default(false);
            
            // Measurement period
            $table->integer('reporting_period')->nullable(); // e.g., 2025
            $table->enum('measurement_frequency', ['Annually', 'Half Yearly', 'Quarterly', 'Monthly'])->default('Annually');
            
            $table->timestamps();
            
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'is_head_office']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
