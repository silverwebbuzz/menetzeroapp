<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hris_kpi_import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('fiscal_year')->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('filename', 255)->nullable();
            $table->string('source_system', 64)->nullable();
            $table->unsignedInteger('rows_imported')->default(0);
            $table->unsignedInteger('rows_skipped')->default(0);
            $table->json('errors')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hris_kpi_import_logs');
    }
};
