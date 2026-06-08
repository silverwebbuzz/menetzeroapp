<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stores admin-managed payment gateway credentials (Razorpay, Cashfree).
 * Secret values are encrypted at the model layer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('gateway')->unique();        // razorpay | cashfree
            $table->string('label');
            $table->boolean('is_enabled')->default(false);
            $table->string('mode')->default('test');     // test | live
            $table->text('key_id')->nullable();          // public/app id (not encrypted)
            $table->text('key_secret')->nullable();      // encrypted
            $table->text('webhook_secret')->nullable();  // encrypted
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('payment_gateways')->insert([
            [
                'gateway' => 'razorpay',
                'label' => 'Razorpay',
                'is_enabled' => false,
                'mode' => 'test',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'gateway' => 'cashfree',
                'label' => 'Cashfree',
                'is_enabled' => false,
                'mode' => 'test',
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
