<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->string('vehicle_type');
            $table->string('fuel_type');
            $table->decimal('distance_travelled', 12, 2)->nullable();
            $table->decimal('fuel_consumed', 12, 2)->nullable();
            $table->string('unit');
            $table->date('date');
            $table->string('uploaded_file')->nullable();
            $table->decimal('co2e', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_data');
    }
};


