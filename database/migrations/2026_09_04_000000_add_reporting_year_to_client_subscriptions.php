<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pin each subscription to the ONE reporting year it was bought for.
 *
 * Entitlement used to be inferred from the term's dates:
 *
 *     $fy >= started_at->year && $fy <= expires_at->year
 *
 * A 12-month term straddles two calendar years unless it starts exactly on
 * 1 January, so that test unlocked TWO reporting years for one year's money.
 * Buy ESG on 1 March 2026 and FY2026 and FY2027 both opened up. It also made
 * a late upgrade lucrative: fill a year on Carbon, upgrade in the last days of
 * the term, and the fresh 12-month term back-dated ESG over data already
 * entered while also unlocking the next year.
 *
 * A term is a duration; a reporting year is a label. Date arithmetic cannot
 * turn one into the other, so the year is now stored explicitly at purchase.
 *
 * BACKFILL: existing rows take started_at's year -- the year the customer was
 * working in when they subscribed. This is deliberately the NARROWER reading
 * of an ambiguous historical grant, so it can take away a second year somebody
 * had access to. See the report the accompanying command prints before you run
 * this in production.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_subscriptions', function (Blueprint $table) {
            $table->unsignedSmallInteger('reporting_year')
                ->nullable()
                ->after('expires_at')
                ->comment('The single fiscal year this term entitles. Null = fall back to started_at year.');
        });

        // Backfill in SQL: this runs over live billing rows and must not depend
        // on model state or app config.
        DB::statement('UPDATE client_subscriptions SET reporting_year = YEAR(started_at) WHERE reporting_year IS NULL');
    }

    public function down(): void
    {
        Schema::table('client_subscriptions', function (Blueprint $table) {
            $table->dropColumn('reporting_year');
        });
    }
};
