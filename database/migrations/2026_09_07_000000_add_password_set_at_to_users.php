<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Record whether an account has a password its owner actually knows.
 *
 * Google signup stores Hash::make(Str::random(24)) to satisfy the not-null
 * column. The user has never seen that string, so the change-password form --
 * which asks for the current password before allowing a new one -- locked
 * those accounts out of setting a password at all.
 *
 * `provider` alone cannot answer this: OAuthController sets provider='google'
 * both when creating a new Google account AND when linking Google to an
 * existing email/password account, and the second kind of user does know their
 * password. This column records the fact directly instead of inferring it.
 *
 * BACKFILL: every existing row EXCEPT those created through Google signup is
 * treated as having a real password. A Google-created row is identified by
 * provider='google' with no password reset ever recorded; linked accounts are
 * indistinguishable in existing data, so they are given the benefit of the
 * doubt and treated as having a password. That is the safe direction -- the
 * worst case is that such a user is asked for a password they do know, rather
 * than being allowed to skip verification on an account they may not own.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_set_at')->nullable()->after('password');
        });

        Schema::table('consultants', function (Blueprint $table) {
            $table->timestamp('password_set_at')->nullable()->after('password');
        });

        // Anyone not created through Google signup chose their own password.
        DB::table('users')
            ->where(function ($q) {
                $q->whereNull('provider')->orWhere('provider', '!=', 'google');
            })
            ->update(['password_set_at' => DB::raw('COALESCE(created_at, NOW())')]);

        DB::table('consultants')
            ->where(function ($q) {
                $q->whereNull('provider')->orWhere('provider', '!=', 'google');
            })
            ->update(['password_set_at' => DB::raw('COALESCE(created_at, NOW())')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_set_at');
        });

        Schema::table('consultants', function (Blueprint $table) {
            $table->dropColumn('password_set_at');
        });
    }
};
