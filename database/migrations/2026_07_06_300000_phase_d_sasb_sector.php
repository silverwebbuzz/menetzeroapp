<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_reporting_settings') && !Schema::hasColumn('company_reporting_settings', 'sasb_sector')) {
            Schema::table('company_reporting_settings', function (Blueprint $table) {
                $table->string('sasb_sector', 32)->nullable()->after('scope3_category_policy');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('company_reporting_settings') && Schema::hasColumn('company_reporting_settings', 'sasb_sector')) {
            Schema::table('company_reporting_settings', function (Blueprint $table) {
                $table->dropColumn('sasb_sector');
            });
        }
    }
};
