<?php

use App\Data\ConsultantAgencyPlanMatrix;
use App\Data\PlanEntitlementDefaults;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * One free managed-client slot per consultant org (not shown on public pricing).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        $definition = ConsultantAgencyPlanMatrix::forPlanCode(ConsultantAgencyPlanMatrix::FREE_TRIAL_CODE);

        if (!$definition) {
            return;
        }

        SubscriptionPlan::updateOrCreate(
            ['plan_code' => ConsultantAgencyPlanMatrix::FREE_TRIAL_CODE],
            [
                'plan_name' => $definition['plan_name'],
                'plan_category' => $definition['plan_category'],
                'description' => $definition['description'],
                'price_annual' => $definition['price_annual'],
                'price_inr' => 0,
                'currency' => $definition['currency'],
                'billing_cycle' => $definition['billing_cycle'],
                'is_active' => $definition['is_active'],
                'sort_order' => $definition['sort_order'],
                'limits' => $definition['limits'],
                'entitlements' => $definition['entitlements'],
                'features' => $definition['features'],
            ]
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        SubscriptionPlan::where('plan_code', ConsultantAgencyPlanMatrix::FREE_TRIAL_CODE)->delete();
    }
};
