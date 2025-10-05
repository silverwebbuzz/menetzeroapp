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
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('frequency', ['monthly', 'quarterly', 'half_yearly', 'annually']);
            $table->enum('status', ['draft', 'submitted', 'under_review', 'not_verified', 'verified'])->default('draft');
            $table->integer('fiscal_year');
            $table->string('fiscal_year_start_month', 3); // JAN, FEB, etc.
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Store additional measurement metadata
            $table->timestamps();
            
            // Ensure unique measurement periods per location
            $table->unique(['location_id', 'period_start', 'period_end'], 'unique_measurement_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
};
