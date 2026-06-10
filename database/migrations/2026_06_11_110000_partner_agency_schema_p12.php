<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partner Agency P12 — schema for managed clients, subscriptions, engagements, add-ons.
 *
 * @see documentation/PARTNER_AGENCY_PLAN_V1.md §8
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('companies') && !Schema::hasColumn('companies', 'partner_id')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->foreignId('partner_id')
                    ->nullable()
                    ->after('is_direct_client')
                    ->constrained('companies')
                    ->nullOnDelete();
                $table->index('partner_id');
            });
        }

        if (!Schema::hasTable('partner_subscriptions')) {
            Schema::create('partner_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('partner_company_id')->constrained('companies')->cascadeOnDelete();
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

                $table->index(['partner_company_id', 'status'], 'ps_partner_status_idx');
                $table->index(['partner_company_id', 'contract_year'], 'ps_partner_year_idx');
            });
        }

        if (!Schema::hasTable('partner_client_engagements')) {
            Schema::create('partner_client_engagements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('partner_company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('managed_company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('partner_subscription_id')->constrained('partner_subscriptions')->cascadeOnDelete();
                $table->unsignedSmallInteger('primary_reporting_year');
                $table->enum('status', ['active', 'archived', 'transferred'])->default('active');
                $table->timestamp('archived_at')->nullable();
                $table->foreignId('previous_engagement_id')
                    ->nullable()
                    ->constrained('partner_client_engagements')
                    ->nullOnDelete();
                $table->string('display_name')->nullable();
                $table->timestamps();

                $table->index(['partner_company_id', 'status'], 'pce_partner_status_idx');
                $table->index(['managed_company_id', 'partner_company_id'], 'pce_managed_partner_idx');
            });
        }

        if (!Schema::hasTable('partner_subscription_addons')) {
            Schema::create('partner_subscription_addons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('partner_subscription_id')->constrained('partner_subscriptions')->cascadeOnDelete();
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

                $table->index('partner_subscription_id', 'psa_subscription_idx');
            });
        }

        if (Schema::hasTable('consultants') && !Schema::hasColumn('consultants', 'partner_company_id')) {
            Schema::table('consultants', function (Blueprint $table) {
                $table->foreignId('partner_company_id')
                    ->nullable()
                    ->after('is_active')
                    ->constrained('companies')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('consultants') && Schema::hasColumn('consultants', 'partner_company_id')) {
            Schema::table('consultants', function (Blueprint $table) {
                $table->dropConstrainedForeignId('partner_company_id');
            });
        }

        Schema::dropIfExists('partner_subscription_addons');
        Schema::dropIfExists('partner_client_engagements');
        Schema::dropIfExists('partner_subscriptions');

        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'partner_id')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropConstrainedForeignId('partner_id');
            });
        }
    }
};
