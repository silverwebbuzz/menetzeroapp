<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'company_type')) {
                $table->enum('company_type', ['client', 'partner'])->default('client')->after('is_active');
            }
            if (!Schema::hasColumn('companies', 'is_direct_client')) {
                $table->boolean('is_direct_client')->default(true)->after('company_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'is_direct_client')) $table->dropColumn('is_direct_client');
            if (Schema::hasColumn('companies', 'company_type')) $table->dropColumn('company_type');
        });
    }
};
