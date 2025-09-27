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
        Schema::create('carbon_emissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('scope', ['scope_1', 'scope_2', 'scope_3']);
            $table->string('category'); // e.g., 'fuel_consumption', 'electricity', 'business_travel'
            $table->string('subcategory')->nullable(); // e.g., 'gasoline', 'diesel', 'natural_gas'
            $table->string('activity_name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 4);
            $table->string('unit'); // e.g., 'liters', 'kWh', 'miles', 'kg'
            $table->decimal('emission_factor', 10, 6)->nullable(); // kg CO2e per unit
            $table->decimal('total_emissions', 15, 4); // kg CO2e
            $table->date('activity_date');
            $table->string('data_source')->nullable(); // e.g., 'meter_reading', 'invoice', 'estimate'
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Additional data like vehicle type, fuel type, etc.
            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'scope', 'activity_date']);
            $table->index(['company_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carbon_emissions');
    }
};
