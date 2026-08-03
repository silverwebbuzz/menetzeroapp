<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultant_entity_requests', function (Blueprint $table) {
            $table->string('package_code', 64)->nullable()->after('entity_count');
            $table->json('extras')->nullable()->after('needs_sites_over_5');
        });
    }

    public function down(): void
    {
        Schema::table('consultant_entity_requests', function (Blueprint $table) {
            $table->dropColumn(['package_code', 'extras']);
        });
    }
};
