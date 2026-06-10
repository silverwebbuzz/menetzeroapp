<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consultant agency schema — managed clients, subscriptions, engagements, add-ons.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('consultant_subscriptions')) {
            $this->ensureAgencyCompanyIdColumn();

            return;
        }

        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'company_type')) {
            DB::statement(
                "ALTER TABLE `companies` MODIFY `company_type` VARCHAR(20) NOT NULL DEFAULT 'client'"
            );
        }

        if (Schema::hasTable('companies') && !Schema::hasColumn('companies', 'consultant_id')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->foreignId('consultant_id')
                    ->nullable()
                    ->after('is_direct_client')
                    ->constrained('companies')
                    ->nullOnDelete();
                $table->index('consultant_id');
            });
        }

        Schema::create('consultant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultant_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->restrictOnDelete();
            $table->unsignedSmallInteger('contract_year');
            $table->unsignedInteger('slot_limit');
            $table->unsignedInteger('extra_slots_purchased')->default(0);
            $table->date('starts_at');
            $table->date('expires_at');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->foreignId('payment_transaction_id')
                ->nullable()
                ->constrained('client_payment_transactions')
                ->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['consultant_company_id', 'status'], 'cs_consultant_status_idx');
            $table->index(['consultant_company_id', 'contract_year'], 'cs_consultant_year_idx');
        });

        Schema::create('consultant_client_engagements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultant_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('managed_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('consultant_subscription_id')->constrained('consultant_subscriptions')->cascadeOnDelete();
            $table->unsignedSmallInteger('primary_reporting_year');
            $table->enum('status', ['active', 'archived', 'transferred'])->default('active');
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('previous_engagement_id')
                ->nullable()
                ->constrained('consultant_client_engagements')
                ->nullOnDelete();
            $table->string('display_name')->nullable();
            $table->timestamps();

            $table->index(['consultant_company_id', 'status'], 'cce_consultant_status_idx');
            $table->index(['managed_company_id', 'consultant_company_id'], 'cce_managed_consultant_idx');
            $table->index(['managed_company_id', 'status'], 'cce_managed_status_idx');
        });

        Schema::create('consultant_subscription_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultant_subscription_id')->constrained('consultant_subscriptions')->cascadeOnDelete();
            $table->enum('addon_type', ['extra_slot', 'reporting_year_unlock']);
            $table->unsignedInteger('quantity')->default(1);
            $table->foreignId('managed_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->unsignedSmallInteger('reporting_year')->nullable();
            $table->decimal('amount_aed', 10, 2);
            $table->foreignId('payment_transaction_id')
                ->nullable()
                ->constrained('client_payment_transactions')
                ->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('consultant_subscription_id', 'csa_subscription_idx');
            $table->index(
                ['consultant_subscription_id', 'addon_type', 'managed_company_id', 'reporting_year'],
                'csa_unlock_lookup_idx'
            );
        });

        $this->ensureAgencyCompanyIdColumn();
    }

    private function ensureAgencyCompanyIdColumn(): void
    {
        if (Schema::hasTable('consultants') && !Schema::hasColumn('consultants', 'agency_company_id')) {
            Schema::table('consultants', function (Blueprint $table) {
                $table->foreignId('agency_company_id')
                    ->nullable()
                    ->after('is_active')
                    ->constrained('companies')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('consultants') && Schema::hasColumn('consultants', 'agency_company_id')) {
            Schema::table('consultants', function (Blueprint $table) {
                $table->dropConstrainedForeignId('agency_company_id');
            });
        }

        Schema::dropIfExists('consultant_subscription_addons');
        Schema::dropIfExists('consultant_client_engagements');
        Schema::dropIfExists('consultant_subscriptions');

        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'consultant_id')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropConstrainedForeignId('consultant_id');
            });
        }
    }
};
