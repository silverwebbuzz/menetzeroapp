<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CONSULTANT_MULTI_PACKAGE_PLAN.md — Phase 3
 * Persist multi-line package × qty on consultant entity requests.
 * Backfill legacy single package_code + entity_count into lines JSON.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('consultant_entity_requests')) {
            return;
        }

        if (!Schema::hasColumn('consultant_entity_requests', 'lines')) {
            Schema::table('consultant_entity_requests', function (Blueprint $table) {
                $table->json('lines')->nullable()->after('entity_count');
            });
        }

        DB::table('consultant_entity_requests')
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $existing = $row->lines ?? null;
                    if (is_string($existing) && $existing !== '' && $existing !== 'null') {
                        $decoded = json_decode($existing, true);
                        if (is_array($decoded) && $decoded !== []) {
                            continue;
                        }
                    }

                    $code = $row->package_code
                        ?? ((int) ($row->wants_enterprise ?? 0) === 1 ? 'client_enterprise' : 'client_scope_basic');
                    $count = max(1, (int) ($row->entity_count ?? 1));

                    DB::table('consultant_entity_requests')->where('id', $row->id)->update([
                        'lines' => json_encode([
                            [
                                'package_code' => $code,
                                'entity_count' => $count,
                            ],
                        ]),
                    ]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('consultant_entity_requests') && Schema::hasColumn('consultant_entity_requests', 'lines')) {
            Schema::table('consultant_entity_requests', function (Blueprint $table) {
                $table->dropColumn('lines');
            });
        }
    }
};
