<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('industrial_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->string('process_type');
            $table->string('raw_material');
            $table->decimal('quantity', 12, 2);
            $table->string('unit');
            $table->date('date');
            $table->string('uploaded_file')->nullable();
            $table->decimal('co2e', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('industrial_data');
    }
};


