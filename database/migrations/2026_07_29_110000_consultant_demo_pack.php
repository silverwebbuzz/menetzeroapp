<?php

use App\Data\ConsultantAgencyPlanMatrix;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * consultant_1 ("Consultant 1") — free complimentary demo pack that grants ONE
 * managed client with full Growth access. Admin-assigned only (kept inactive so
 * it never appears on the consultant self-serve packs page or public pricing).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        $definition = ConsultantAgencyPlanMatrix::forPlanCode(ConsultantAgencyPlanMatrix::DEMO_PACK_CODE);

        if (!$definition) {
            return;
        }

        SubscriptionPlan::updateOrCreate(
            ['plan_code' => ConsultantAgencyPlanMatrix::DEMO_PACK_CODE],
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

        SubscriptionPlan::where('plan_code', ConsultantAgencyPlanMatrix::DEMO_PACK_CODE)->delete();
    }
};
