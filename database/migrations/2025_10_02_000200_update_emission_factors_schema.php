<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing emission_factors table and recreate with new schema
        Schema::dropIfExists('emission_factors');
        
        Schema::create('emission_factors', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('subcategory');
            $table->decimal('factor_value', 10, 4);
            $table->string('unit');
            $table->string('source');
            $table->integer('year');
            $table->string('region')->default('UAE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emission_factors');
    }
};
