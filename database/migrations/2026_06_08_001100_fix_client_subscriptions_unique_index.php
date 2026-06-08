<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replace UNIQUE(company_id, status) with a partial unique that only enforces
 * one active subscription per company. The old index blocked upgrades because
 * only one "cancelled" row was allowed per company.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('client_subscriptions')) {
            return;
        }

        try {
            Schema::table('client_subscriptions', function (Blueprint $table) {
                $table->dropUnique('company_active_subscription');
            });
        } catch (\Throwable $e) {
            // Index may already be removed on some environments.
        }

        if (!Schema::hasColumn('client_subscriptions', 'active_company_key')) {
            DB::statement(
                "ALTER TABLE client_subscriptions
                 ADD COLUMN active_company_key BIGINT UNSIGNED
                 AS (IF(status = 'active', company_id, NULL)) STORED"
            );
            DB::statement(
                'CREATE UNIQUE INDEX company_one_active_subscription ON client_subscriptions (active_company_key)'
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('client_subscriptions')) {
            return;
        }

        try {
            DB::statement('DROP INDEX company_one_active_subscription ON client_subscriptions');
            Schema::table('client_subscriptions', function (Blueprint $table) {
                $table->dropColumn('active_company_key');
            });
            Schema::table('client_subscriptions', function (Blueprint $table) {
                $table->unique(['company_id', 'status'], 'company_active_subscription');
            });
        } catch (\Throwable $e) {
            //
        }
    }
};
