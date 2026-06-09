<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('client_payment_transactions') || !Schema::hasColumn('client_payment_transactions', 'transaction_type')) {
            return;
        }

        // Was ENUM('subscription') only — widen so C10 consultant_pack checkout can insert.
        DB::statement(
            "ALTER TABLE `client_payment_transactions` MODIFY `transaction_type` VARCHAR(50) NOT NULL DEFAULT 'subscription'"
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('client_payment_transactions')) {
            return;
        }

        DB::statement(
            "ALTER TABLE `client_payment_transactions` MODIFY `transaction_type` ENUM('subscription') NOT NULL DEFAULT 'subscription'"
        );
    }
};
