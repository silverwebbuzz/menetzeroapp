<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Scope 3 add-on tiers shown on the pricing page (admin-managed).
 *
 * `items` is a JSON list of [{ "label": string, "soon": bool }].
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scope3_addons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('price_display')->nullable();
            $table->json('items')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scope3_addons');
    }
};
