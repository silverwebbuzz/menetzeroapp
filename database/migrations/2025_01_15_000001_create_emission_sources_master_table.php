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
        Schema::create('emission_sources_master', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->enum('scope', ['Scope 1', 'Scope 2', 'Scope 3']);
            $table->string('category')->nullable(); // For Scope 3 categories like "3.1 Purchased goods and services"
            $table->string('subcategory')->nullable(); // For Scope 3 subcategories
            $table->string('type')->nullable(); // 'upstream' or 'downstream' for Scope 3
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emission_sources_master');
    }
};
