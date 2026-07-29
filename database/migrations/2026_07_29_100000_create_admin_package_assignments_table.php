<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail of admin-approved package assignments.
 *
 * Every time an admin assigns a package/plan to a company (a client company or a
 * consultant agency organisation) at no charge, a row is recorded here capturing
 * WHO approved it, WHAT plan, for WHOM, and the resulting subscription — so the
 * approval and its context always live in the database.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admin_package_assignments')) {
            return;
        }

        Schema::create('admin_package_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable()->comment('Admin who approved the assignment');
            $table->unsignedBigInteger('company_id')->comment('Target org: client company or consultant agency org');
            $table->unsignedBigInteger('consultant_id')->nullable()->comment('Set when assigned from the consultant record');
            $table->unsignedBigInteger('subscription_plan_id');
            $table->string('target_type', 20)->comment('client | consultant');
            $table->unsignedSmallInteger('contract_year')->nullable()->comment('Consultant packs (calendar contract year)');
            $table->unsignedSmallInteger('duration_months')->nullable()->comment('Client complimentary grants');
            $table->text('note')->nullable()->comment('Reason / approval note');
            $table->string('status', 20)->default('approved');
            $table->unsignedBigInteger('client_subscription_id')->nullable();
            $table->unsignedBigInteger('consultant_subscription_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'target_type']);
            $table->index('admin_id');
            $table->index('consultant_id');
        });

        // Add foreign keys defensively (only when the referenced tables exist).
        Schema::table('admin_package_assignments', function (Blueprint $table) {
            if (Schema::hasTable('admins')) {
                $table->foreign('admin_id')->references('id')->on('admins')->nullOnDelete();
            }
            if (Schema::hasTable('companies')) {
                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            }
            if (Schema::hasTable('consultants')) {
                $table->foreign('consultant_id')->references('id')->on('consultants')->nullOnDelete();
            }
            if (Schema::hasTable('subscription_plans')) {
                $table->foreign('subscription_plan_id')->references('id')->on('subscription_plans')->cascadeOnDelete();
            }
            if (Schema::hasTable('client_subscriptions')) {
                $table->foreign('client_subscription_id')->references('id')->on('client_subscriptions')->nullOnDelete();
            }
            if (Schema::hasTable('consultant_subscriptions')) {
                $table->foreign('consultant_subscription_id')->references('id')->on('consultant_subscriptions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_package_assignments');
    }
};
