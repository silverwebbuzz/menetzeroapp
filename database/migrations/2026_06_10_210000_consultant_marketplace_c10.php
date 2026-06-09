<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultant_orders', function (Blueprint $table) {
            $table->foreignId('payment_transaction_id')->nullable()->after('intro_request_id')
                ->constrained('client_payment_transactions')->nullOnDelete();
            $table->timestamp('delivered_at')->nullable()->after('payment_reference');
            $table->timestamp('completed_at')->nullable()->after('delivered_at');
        });
    }

    public function down(): void
    {
        Schema::table('consultant_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_transaction_id');
            $table->dropColumn(['delivered_at', 'completed_at']);
        });
    }
};
