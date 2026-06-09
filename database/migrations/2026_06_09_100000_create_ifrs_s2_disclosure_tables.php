<?php

use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_disclosures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('framework', 20)->default('ifrs_s2');
            $table->string('section', 50);
            $table->unsignedSmallInteger('fiscal_year');
            $table->json('content')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('last_edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'framework', 'section', 'fiscal_year']);
        });

        Schema::create('climate_risks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('fiscal_year');
            $table->string('name');
            $table->string('risk_type', 20);
            $table->string('time_horizon', 20);
            $table->text('description')->nullable();
            $table->text('financial_impact')->nullable();
            $table->string('likelihood', 20)->nullable();
            $table->text('mitigation')->nullable();
            $table->string('owner')->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamps();
        });

        Schema::create('climate_opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('fiscal_year');
            $table->string('name');
            $table->string('category', 50)->nullable();
            $table->text('description')->nullable();
            $table->text('potential_impact')->nullable();
            $table->text('actions')->nullable();
            $table->timestamps();
        });

        Schema::create('reduction_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('target_type', 20)->default('absolute');
            $table->string('scope_coverage', 30)->default('scope12');
            $table->unsignedSmallInteger('base_year')->nullable();
            $table->unsignedSmallInteger('target_year');
            $table->decimal('baseline_tco2e', 14, 4)->nullable();
            $table->decimal('target_tco2e', 14, 4)->nullable();
            $table->decimal('reduction_percent', 8, 2)->nullable();
            $table->boolean('sbti_aligned')->default(false);
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('transition_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reduction_target_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('action_type', 30)->nullable();
            $table->unsignedSmallInteger('planned_year')->nullable();
            $table->decimal('capex_aed', 14, 2)->nullable();
            $table->decimal('opex_aed', 14, 2)->nullable();
            $table->decimal('expected_reduction_tco2e', 14, 4)->nullable();
            $table->string('status', 20)->default('planned');
            $table->timestamps();
        });

        $this->enableIfrsS2OnPaidPlans();
    }

    public function down(): void
    {
        Schema::dropIfExists('transition_actions');
        Schema::dropIfExists('reduction_targets');
        Schema::dropIfExists('climate_opportunities');
        Schema::dropIfExists('climate_risks');
        Schema::dropIfExists('company_disclosures');
    }

    private function enableIfrsS2OnPaidPlans(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        foreach (['client_growth', 'client_enterprise'] as $code) {
            $plan = SubscriptionPlan::where('plan_code', $code)->first();
            if (!$plan) {
                continue;
            }
            $features = array_values(array_unique(array_merge($plan->features ?? [], ['ifrs_s2'])));
            $plan->update(['features' => $features]);
        }
    }
};
