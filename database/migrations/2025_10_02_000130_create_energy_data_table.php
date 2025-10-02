<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('energy_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->enum('source_type', ['Electricity', 'Diesel', 'LPG', 'Gasoline', 'Other']);
            $table->decimal('consumption_value', 12, 2);
            $table->string('unit');
            $table->date('date');
            $table->string('uploaded_file')->nullable();
            $table->decimal('co2e', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('energy_data');
    }
};


