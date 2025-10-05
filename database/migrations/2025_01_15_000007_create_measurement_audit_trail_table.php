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
        Schema::create('measurement_audit_trail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('measurement_id')->constrained()->onDelete('cascade');
            $table->string('action', 50); // 'created', 'updated', 'status_changed', 'data_added', 'data_updated', 'deleted'
            $table->json('old_values')->nullable(); // Previous values
            $table->json('new_values')->nullable(); // New values
            $table->foreignId('changed_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('changed_at');
            $table->text('reason')->nullable(); // Reason for change
            $table->string('ip_address', 45)->nullable(); // IP address of the change
            $table->string('user_agent', 500)->nullable(); // Browser/device info
            $table->timestamps();
            
            // Index for performance
            $table->index(['measurement_id', 'action']);
            $table->index(['changed_by', 'changed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurement_audit_trail');
    }
};
