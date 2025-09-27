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
        Schema::create('carbon_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->enum('scope', ['scope_1', 'scope_2', 'scope_3', 'total']);
            $table->integer('year');
            $table->integer('quarter')->nullable(); // 1-4 for quarterly calculations
            $table->integer('month')->nullable(); // 1-12 for monthly calculations
            $table->decimal('total_emissions', 15, 4); // kg CO2e
            $table->decimal('emissions_per_employee', 15, 4)->nullable(); // kg CO2e per employee
            $table->decimal('emissions_per_revenue', 15, 4)->nullable(); // kg CO2e per revenue unit
            $table->json('breakdown')->nullable(); // Detailed breakdown by category
            $table->json('trends')->nullable(); // Year-over-year comparison data
            $table->decimal('reduction_target', 15, 4)->nullable(); // Target reduction amount
            $table->decimal('reduction_achieved', 15, 4)->nullable(); // Actual reduction achieved
            $table->decimal('reduction_percentage', 5, 2)->nullable(); // Percentage reduction achieved
            $table->boolean('is_verified')->default(false);
            $table->foreignId('calculated_by')->constrained('users');
            $table->timestamp('calculated_at');
            $table->timestamps();
            
            $table->index(['company_id', 'scope', 'year']);
            $table->index(['company_id', 'year', 'quarter']);
            $table->index(['company_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carbon_calculations');
    }
};
