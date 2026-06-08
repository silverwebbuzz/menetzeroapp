<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rows of the public plan comparison table (admin-managed).
 *
 * Each cell value uses: 'yes' => ✓, 'no'/'' => —, anything else => verbatim text.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_feature_rows', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->boolean('coming_soon')->default(false);
            $table->string('value_starter')->nullable();
            $table->string('value_growth')->nullable();
            $table->string('value_enterprise')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_feature_rows');
    }
};
