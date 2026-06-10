<?php

use App\Data\PartnerPlanMatrix;
use App\Data\PlanEntitlementDefaults;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Partner Agency P11 — seed partner_5/10/25/50 subscription plans.
 *
 * @see documentation/PARTNER_AGENCY_PLAN_V1.md
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        // Was ENUM('client') only — widen so partner_* packs can be seeded.
        if (Schema::hasColumn('subscription_plans', 'plan_category')) {
            DB::statement(
                "ALTER TABLE `subscription_plans` MODIFY `plan_category` VARCHAR(20) NOT NULL DEFAULT 'client'"
            );
        }

        foreach (PartnerPlanMatrix::packDefinitions() as $code => $definition) {
            $priceAnnual = (float) $definition['price_annual'];
            $priceInr = PlanEntitlementDefaults::defaultPriceInr($priceAnnual);

            SubscriptionPlan::updateOrCreate(
                ['plan_code' => $code],
                [
                    'plan_name' => $definition['plan_name'],
                    'plan_category' => $definition['plan_category'],
                    'description' => $definition['description'],
                    'price_annual' => $priceAnnual,
                    'price_inr' => $priceInr,
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
    }

    public function down(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        SubscriptionPlan::whereIn('plan_code', PartnerPlanMatrix::PLAN_CODES)->delete();

        if (Schema::hasColumn('subscription_plans', 'plan_category')) {
            DB::statement(
                "ALTER TABLE `subscription_plans` MODIFY `plan_category` ENUM('client') NOT NULL DEFAULT 'client'"
            );
        }
    }
};
