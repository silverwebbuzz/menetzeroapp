<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Login tracking for users, consultants and admins.
 *
 * Nothing recorded sign-ins before this: no last_login_at, no IP, no history.
 * Admin could not tell whether an account had been used since registration,
 * which is what makes a dormant free trial identifiable.
 *
 * Three columns rather than a logins table: the question is "when did they
 * last appear, and from where", which does not need unbounded history and its
 * own pruning job.
 */
return new class extends Migration
{
    /** All three authenticatables get the same treatment. */
    private const TABLES = ['users', 'consultants', 'admins'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                if (!Schema::hasColumn($table, 'last_login_at')) {
                    $t->timestamp('last_login_at')->nullable()->index();
                }
                if (!Schema::hasColumn($table, 'last_login_ip')) {
                    // 45 chars holds an IPv6 address in full.
                    $t->string('last_login_ip', 45)->nullable();
                }
                if (!Schema::hasColumn($table, 'login_count')) {
                    $t->unsignedInteger('login_count')->default(0);
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                foreach (['last_login_at', 'last_login_ip', 'login_count'] as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $t->dropColumn($column);
                    }
                }
            });
        }
    }
};
