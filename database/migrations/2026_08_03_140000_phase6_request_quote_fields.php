<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_package_requests', function (Blueprint $table) {
            $table->decimal('quote_amount_aed', 12, 2)->nullable()->after('admin_notes');
            $table->text('quote_breakdown')->nullable()->after('quote_amount_aed');
            $table->unsignedSmallInteger('duration_months')->default(12)->after('quote_breakdown');
            $table->timestamp('quoted_at')->nullable()->after('duration_months');
            $table->timestamp('paid_at')->nullable()->after('quoted_at');
            $table->timestamp('activated_at')->nullable()->after('paid_at');
        });

        Schema::table('consultant_entity_requests', function (Blueprint $table) {
            $table->decimal('quote_amount_aed', 12, 2)->nullable()->after('admin_notes');
            $table->text('quote_breakdown')->nullable()->after('quote_amount_aed');
            $table->timestamp('quoted_at')->nullable()->after('quote_breakdown');
            $table->timestamp('paid_at')->nullable()->after('quoted_at');
            $table->timestamp('activated_at')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('company_package_requests', function (Blueprint $table) {
            $table->dropColumn([
                'quote_amount_aed',
                'quote_breakdown',
                'duration_months',
                'quoted_at',
                'paid_at',
                'activated_at',
            ]);
        });

        Schema::table('consultant_entity_requests', function (Blueprint $table) {
            $table->dropColumn([
                'quote_amount_aed',
                'quote_breakdown',
                'quoted_at',
                'paid_at',
                'activated_at',
            ]);
        });
    }
};
