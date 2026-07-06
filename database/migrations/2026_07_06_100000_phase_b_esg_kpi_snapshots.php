<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esg_kpi_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('fiscal_year');
            $table->string('category', 32);
            $table->string('metric_key', 64);
            $table->decimal('value', 20, 4)->nullable();
            $table->string('unit', 32)->nullable();
            $table->string('source', 16)->default('manual');
            $table->timestamps();

            $table->unique(['company_id', 'fiscal_year', 'metric_key'], 'esg_kpi_company_year_metric_unique');
            $table->index(['company_id', 'fiscal_year', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esg_kpi_snapshots');
    }
};
