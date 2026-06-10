<?php

use App\Data\ConsultantAgencyPlanMatrix;
use App\Data\PlanEntitlementDefaults;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consultant agency packs — consultant_5/10/25/50 subscription plans.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        if (Schema::hasColumn('subscription_plans', 'plan_category')) {
            DB::statement(
                "ALTER TABLE `subscription_plans` MODIFY `plan_category` VARCHAR(20) NOT NULL DEFAULT 'client'"
            );
        }

        $legacyCodes = [
            'consultant_5' => 'partner_5',
            'consultant_10' => 'partner_10',
            'consultant_25' => 'partner_25',
            'consultant_50' => 'partner_50',
        ];

        foreach (ConsultantAgencyPlanMatrix::packDefinitions() as $code => $definition) {
            $priceAnnual = (float) $definition['price_annual'];
            $priceInr = PlanEntitlementDefaults::defaultPriceInr($priceAnnual);

            $payload = [
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
            ];

            $legacyCode = $legacyCodes[$code] ?? null;
            $legacyPlan = $legacyCode
                ? SubscriptionPlan::where('plan_code', $legacyCode)->first()
                : null;

            if ($legacyPlan) {
                $legacyPlan->update(array_merge($payload, ['plan_code' => $code]));
                continue;
            }

            SubscriptionPlan::updateOrCreate(['plan_code' => $code], $payload);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        SubscriptionPlan::whereIn('plan_code', ConsultantAgencyPlanMatrix::PLAN_CODES)->delete();
    }
};
