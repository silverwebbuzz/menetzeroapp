<?php

use App\Data\ConsultantAgencyPlanMatrix;
use App\Data\PlanEntitlementDefaults;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 8 — seed client_scope_* / ESG packages, consultant_entity,
 * consultant_managed_standard (limit template), refresh demo pack label,
 * deactivate legacy consultant_5/10/25/50 self-serve packs.
 *
 * Keeps consultant_1 (Demo / QA — 1 client full Growth) for admin testing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        $clientCodes = [
            'client_scope_basic',
            'client_scope_pro',
            'client_esg_starter',
            'client_esg_complete',
            'consultant_managed_standard',
        ];

        foreach ($clientCodes as $code) {
            $definition = PlanEntitlementDefaults::forPlanCode($code);
            if (!$definition) {
                continue;
            }

            $priceAnnual = (float) $definition['price_annual'];
            $isTemplate = $code === 'consultant_managed_standard';

            SubscriptionPlan::updateOrCreate(
                ['plan_code' => $code],
                [
                    'plan_name' => $definition['plan_name'],
                    'plan_category' => 'client',
                    'description' => $definition['description'],
                    'price_annual' => $priceAnnual,
                    'price_inr' => $priceAnnual > 0 ? PlanEntitlementDefaults::defaultPriceInr($priceAnnual) : 0,
                    'currency' => $definition['currency'],
                    'billing_cycle' => 'annual',
                    // Template is never sold; packages are active for admin assign.
                    'is_active' => !$isTemplate,
                    'sort_order' => $definition['sort_order'],
                    'limits' => $definition['limits'],
                    'entitlements' => $definition['entitlements'],
                    'features' => $definition['features'],
                ]
            );
        }

        // Ensure client_enterprise still matches defaults (already seeded historically).
        $enterprise = PlanEntitlementDefaults::forPlanCode('client_enterprise');
        if ($enterprise) {
            SubscriptionPlan::updateOrCreate(
                ['plan_code' => 'client_enterprise'],
                [
                    'plan_name' => $enterprise['plan_name'],
                    'plan_category' => 'client',
                    'description' => $enterprise['description'],
                    'price_annual' => $enterprise['price_annual'],
                    'price_inr' => PlanEntitlementDefaults::defaultPriceInr((float) $enterprise['price_annual']),
                    'currency' => $enterprise['currency'],
                    'billing_cycle' => 'annual',
                    'is_active' => true,
                    'sort_order' => $enterprise['sort_order'],
                    'limits' => $enterprise['limits'],
                    'entitlements' => $enterprise['entitlements'],
                    'features' => $enterprise['features'],
                ]
            );
        }

        // Consultant Plan + refresh demo/legacy rows from matrix.
        foreach (ConsultantAgencyPlanMatrix::packDefinitions() as $code => $definition) {
            $priceAnnual = (float) $definition['price_annual'];

            SubscriptionPlan::updateOrCreate(
                ['plan_code' => $code],
                [
                    'plan_name' => $definition['plan_name'],
                    'plan_category' => $definition['plan_category'],
                    'description' => $definition['description'],
                    'price_annual' => $priceAnnual,
                    'price_inr' => $priceAnnual > 0 ? PlanEntitlementDefaults::defaultPriceInr($priceAnnual) : 0,
                    'currency' => $definition['currency'],
                    'billing_cycle' => $definition['billing_cycle'],
                    'is_active' => (bool) $definition['is_active'],
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

        SubscriptionPlan::whereIn('plan_code', [
            'client_scope_basic',
            'client_scope_pro',
            'client_esg_starter',
            'client_esg_complete',
            'consultant_managed_standard',
            'consultant_entity',
        ])->delete();
    }
};
