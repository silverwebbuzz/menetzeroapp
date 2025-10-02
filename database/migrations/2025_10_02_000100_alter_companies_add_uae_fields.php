<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'emirate')) {
                $table->enum('emirate', ['Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman', 'Umm Al Quwain', 'Fujairah', 'Ras Al Khaimah'])->nullable()->after('name');
            }
            if (!Schema::hasColumn('companies', 'sector')) {
                $table->string('sector')->nullable()->after('emirate');
            }
            if (!Schema::hasColumn('companies', 'license_no')) {
                $table->string('license_no')->nullable()->after('sector');
            }
            if (!Schema::hasColumn('companies', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('license_no');
            }
            if (!Schema::hasColumn('companies', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'emirate')) $table->dropColumn('emirate');
            if (Schema::hasColumn('companies', 'sector')) $table->dropColumn('sector');
            if (Schema::hasColumn('companies', 'license_no')) $table->dropColumn('license_no');
            if (Schema::hasColumn('companies', 'contact_person')) $table->dropColumn('contact_person');
            // keep phone if used elsewhere
        });
    }
};


