<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultant_entity_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultant_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('entity_count');
            $table->boolean('needs_sites_over_5')->default(false);
            $table->boolean('wants_enterprise')->default(false);
            $table->text('message')->nullable();
            $table->string('status', 32)->default('new');
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('consultant_company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultant_entity_requests');
    }
};
