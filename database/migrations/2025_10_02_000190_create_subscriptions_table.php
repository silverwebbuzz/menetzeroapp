<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->enum('plan_type', ['Free', 'Standard', 'Premium']);
            $table->enum('status', ['active', 'cancelled', 'trialing']);
            $table->string('stripe_customer_id')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};


