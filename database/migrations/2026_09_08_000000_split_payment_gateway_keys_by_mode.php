<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Keep test and live gateway credentials side by side.
 *
 * There was one set of key_id / key_secret / webhook_secret columns, so the
 * Mode dropdown was only a label: switching between Test and Live meant
 * retyping the whole credential set, and switching back meant typing the other
 * set again. Admins were pasting keys on every switch, which is both tedious
 * and a good way to put live keys into a sandbox account by mistake.
 *
 * Both sets are now stored, and `mode` simply selects which pair is used.
 * PaymentGateway exposes key_id / key_secret / webhook_secret as accessors over
 * the active pair, so every existing reader -- PaymentService, the checkout
 * views, the webhook controller -- keeps working unchanged.
 *
 * BACKFILL: the existing values belong to whichever mode the row is currently
 * in, so they are copied into that side and the originals left in place until
 * the follow-up migration drops them. Nothing is lost if this is rolled back.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->text('test_key_id')->nullable()->after('mode');
            $table->text('test_key_secret')->nullable()->after('test_key_id');
            $table->text('test_webhook_secret')->nullable()->after('test_key_secret');
            $table->text('live_key_id')->nullable()->after('test_webhook_secret');
            $table->text('live_key_secret')->nullable()->after('live_key_id');
            $table->text('live_webhook_secret')->nullable()->after('live_key_secret');
        });

        // Raw column copy: key_secret and webhook_secret are Laravel-encrypted
        // casts, and moving the ciphertext verbatim keeps it decryptable. Going
        // through the model would decrypt and re-encrypt for no reason.
        DB::statement("
            UPDATE payment_gateways
               SET test_key_id = CASE WHEN mode = 'live' THEN NULL ELSE key_id END,
                   test_key_secret = CASE WHEN mode = 'live' THEN NULL ELSE key_secret END,
                   test_webhook_secret = CASE WHEN mode = 'live' THEN NULL ELSE webhook_secret END,
                   live_key_id = CASE WHEN mode = 'live' THEN key_id ELSE NULL END,
                   live_key_secret = CASE WHEN mode = 'live' THEN key_secret ELSE NULL END,
                   live_webhook_secret = CASE WHEN mode = 'live' THEN webhook_secret ELSE NULL END
        ");
    }

    public function down(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropColumn([
                'test_key_id',
                'test_key_secret',
                'test_webhook_secret',
                'live_key_id',
                'live_key_secret',
                'live_webhook_secret',
            ]);
        });
    }
};
