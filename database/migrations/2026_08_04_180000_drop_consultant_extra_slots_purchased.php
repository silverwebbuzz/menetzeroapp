<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop legacy extra_slots_purchased — capacity is only slot_limit per subscription row.
 * More seats = new purchase row (multi-package), not a shadow counter.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('consultant_subscriptions')) {
            return;
        }

        if (Schema::hasColumn('consultant_subscriptions', 'extra_slots_purchased')) {
            Schema::table('consultant_subscriptions', function (Blueprint $table) {
                $table->dropColumn('extra_slots_purchased');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('consultant_subscriptions')) {
            return;
        }

        if (!Schema::hasColumn('consultant_subscriptions', 'extra_slots_purchased')) {
            Schema::table('consultant_subscriptions', function (Blueprint $table) {
                $table->unsignedInteger('extra_slots_purchased')->default(0)->after('slot_limit');
            });
        }
    }
};
