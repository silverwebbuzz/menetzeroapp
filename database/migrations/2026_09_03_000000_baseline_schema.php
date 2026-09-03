<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Schema baseline — 2026-09-03.
 *
 * The 89 migrations that built the schema up to this point were squashed into
 * database/schema/mysql-schema.sql, taken from the live database. They are not
 * lost: they remain in git history at commit 9f3f9aa and earlier.
 *
 * Why the squash was needed: seven migrations had been deleted from the repo
 * while their rows stayed in the `migrations` table, so 29 tables (including
 * emission_factors, subscription_plans and user_company_roles) had no creating
 * migration at all. The schema could no longer be rebuilt from the repository.
 * The dump fixes that by being the single source of truth for everything that
 * existed on 2026-09-03.
 *
 * This migration itself does nothing. It exists so the `migrations` table has a
 * row marking the baseline, which is what tells Laravel that everything in the
 * schema file has already been applied.
 *
 * From here on, every schema change is a NEW migration dated after this one.
 * Never edit the schema file to make a change -- regenerate it, with
 * `php artisan schema:dump`, only when squashing again.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Intentionally empty: database/schema/mysql-schema.sql is the schema.
    }

    public function down(): void
    {
        // Not reversible. Restoring means reloading the schema file.
    }
};
