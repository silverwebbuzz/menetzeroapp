<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('measurement_data')) {
            return;
        }

        Schema::table('measurement_data', function (Blueprint $table) {
            if (!Schema::hasColumn('measurement_data', 'supporting_docs')) {
                $table->json('supporting_docs')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('measurement_data') || !Schema::hasColumn('measurement_data', 'supporting_docs')) {
            return;
        }

        Schema::table('measurement_data', function (Blueprint $table) {
            $table->dropColumn('supporting_docs');
        });
    }
};
