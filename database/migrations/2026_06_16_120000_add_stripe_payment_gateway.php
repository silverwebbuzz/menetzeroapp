<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('payment_gateways')
            ->where('gateway', 'stripe')
            ->exists();

        if (!$exists) {
            DB::table('payment_gateways')->insert([
                'gateway' => 'stripe',
                'label' => 'Stripe',
                'is_enabled' => false,
                'mode' => 'test',
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('payment_gateways')
            ->where('gateway', 'stripe')
            ->delete();
    }
};

