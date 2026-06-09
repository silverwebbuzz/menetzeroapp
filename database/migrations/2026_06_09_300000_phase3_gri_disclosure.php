<?php

use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->enableGriOnPaidPlans();
    }

    public function down(): void
    {
        // Feature flag only — no tables to drop (GRI uses company_disclosures).
    }

    private function enableGriOnPaidPlans(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        foreach (['client_growth', 'client_enterprise'] as $code) {
            $plan = SubscriptionPlan::where('plan_code', $code)->first();
            if (!$plan) {
                continue;
            }
            $features = array_values(array_unique(array_merge($plan->features ?? [], ['gri'])));
            $plan->update(['features' => $features]);
        }
    }
};
