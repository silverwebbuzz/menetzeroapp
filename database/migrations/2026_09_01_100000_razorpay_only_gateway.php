<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Razorpay becomes the only payment gateway.
 *
 * Cashfree and Stripe checkout code is removed; their rows are DISABLED rather
 * than deleted. PaymentTransaction.payment_method stores the gateway name, and
 * a receipt or refund lookup resolves the row by that name -- deleting it would
 * orphan any historical payment. `client_payment_transactions` is currently
 * empty and all three FK holders are at zero, so nothing is affected today,
 * but the row costs nothing and a delete cannot be undone.
 *
 * Razorpay itself is NOT enabled here. It needs key_id / key_secret set in
 * admin first, and PaymentGateway::checkoutAvailable() gates checkout on a
 * configured gateway. Enabling an unconfigured row would surface a broken
 * payment button.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payment_gateways')) {
            return;
        }

        DB::table('payment_gateways')
            ->whereIn('gateway', ['cashfree', 'stripe'])
            ->update([
                'is_enabled' => false,
                'label' => DB::raw("CONCAT(label, ' (retired)')"),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('payment_gateways')) {
            return;
        }

        // Re-enabling is deliberately NOT automatic: the checkout code for both
        // is gone, so a re-enabled row would offer a payment path that cannot
        // complete. Only the label is restored.
        DB::table('payment_gateways')
            ->whereIn('gateway', ['cashfree', 'stripe'])
            ->update([
                'label' => DB::raw("REPLACE(label, ' (retired)', '')"),
                'updated_at' => now(),
            ]);
    }
};
