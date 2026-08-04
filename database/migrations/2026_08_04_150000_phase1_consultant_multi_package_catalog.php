<?php

use App\Data\ConsultantAgencyPlanMatrix;
use App\Data\PlanEntitlementDefaults;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * CONSULTANT_MULTI_PACKAGE_PLAN.md — Phase 1
 * Seed consultant_free + consultant_scope_* / enterprise depth plans;
 * refresh demo; deactivate legacy packs (entity, 5/10/25/50, managed_standard).
 * Does NOT rename consultant_trial → consultant_free (Phase 2) or delete demo data.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        $seedCodes = [
            'consultant_free',
            'consultant_1',
            'consultant_scope_basic',
            'consultant_scope_pro',
            'consultant_esg_starter',
            'consultant_esg_complete',
            'consultant_enterprise',
            // Keep trial in sync until Phase 2 rename
            'consultant_trial',
        ];

        foreach ($seedCodes as $code) {
            $definition = ConsultantAgencyPlanMatrix::forPlanCode($code);
            if (!$definition) {
                continue;
            }

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

        // Deactivate legacy sellable / pack rows (keep rows for FK history until Phase 2).
        SubscriptionPlan::whereIn('plan_code', [
            'consultant_entity',
            'consultant_managed_standard',
            'consultant_5',
            'consultant_10',
            'consultant_25',
            'consultant_50',
        ])->update(['is_active' => false]);

        // Ensure legacy rows exist as inactive so admin list is complete if missing.
        foreach (['consultant_entity', 'consultant_5', 'consultant_10', 'consultant_25', 'consultant_50'] as $legacy) {
            $definition = ConsultantAgencyPlanMatrix::forPlanCode($legacy);
            if (!$definition) {
                continue;
            }
            $priceAnnual = (float) $definition['price_annual'];
            SubscriptionPlan::updateOrCreate(
                ['plan_code' => $legacy],
                [
                    'plan_name' => $definition['plan_name'],
                    'plan_category' => $definition['plan_category'],
                    'description' => $definition['description'],
                    'price_annual' => $priceAnnual,
                    'price_inr' => $priceAnnual > 0 ? PlanEntitlementDefaults::defaultPriceInr($priceAnnual) : 0,
                    'currency' => $definition['currency'],
                    'billing_cycle' => $definition['billing_cycle'],
                    'is_active' => false,
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
            'consultant_free',
            'consultant_scope_basic',
            'consultant_scope_pro',
            'consultant_esg_starter',
            'consultant_esg_complete',
            'consultant_enterprise',
        ])->delete();
    }
};
