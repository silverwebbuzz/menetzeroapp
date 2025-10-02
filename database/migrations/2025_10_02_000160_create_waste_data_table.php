<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->string('waste_type');
            $table->decimal('quantity', 12, 2);
            $table->string('unit');
            $table->enum('disposal_method', ['Landfill', 'Incineration', 'Recycling', 'Composting']);
            $table->date('date');
            $table->string('uploaded_file')->nullable();
            $table->decimal('co2e', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_data');
    }
};


