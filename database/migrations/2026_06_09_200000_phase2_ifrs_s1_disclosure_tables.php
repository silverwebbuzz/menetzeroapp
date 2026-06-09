<?php

use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sustainability_risks')) {
            Schema::create('sustainability_risks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('fiscal_year');
                $table->string('name');
                $table->string('topic', 50);
                $table->string('time_horizon', 20);
                $table->text('description')->nullable();
                $table->text('financial_impact')->nullable();
                $table->string('likelihood', 20)->nullable();
                $table->text('mitigation')->nullable();
                $table->string('owner')->nullable();
                $table->string('status', 20)->default('open');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('material_sustainability_topics')) {
            Schema::create('material_sustainability_topics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('fiscal_year');
                $table->string('topic_key', 50);
                $table->boolean('is_material')->default(false);
                $table->text('rationale')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'fiscal_year', 'topic_key'], 'mat_sust_co_yr_topic_uq');
            });
        }

        $this->enableIfrsS1OnPaidPlans();
    }

    public function down(): void
    {
        Schema::dropIfExists('material_sustainability_topics');
        Schema::dropIfExists('sustainability_risks');
    }

    private function enableIfrsS1OnPaidPlans(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        foreach (['client_growth', 'client_enterprise'] as $code) {
            $plan = SubscriptionPlan::where('plan_code', $code)->first();
            if (!$plan) {
                continue;
            }
            $features = array_values(array_unique(array_merge($plan->features ?? [], ['ifrs_s1'])));
            $plan->update(['features' => $features]);
        }
    }
};
