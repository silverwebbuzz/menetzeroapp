<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7 — seat move audit trail on engagements.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('consultant_client_engagements')) {
            return;
        }

        if (!Schema::hasColumn('consultant_client_engagements', 'metadata')) {
            Schema::table('consultant_client_engagements', function (Blueprint $table) {
                $table->json('metadata')->nullable()->after('display_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('consultant_client_engagements')
            && Schema::hasColumn('consultant_client_engagements', 'metadata')
        ) {
            Schema::table('consultant_client_engagements', function (Blueprint $table) {
                $table->dropColumn('metadata');
            });
        }
    }
};
