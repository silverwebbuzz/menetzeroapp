<?php

use App\Data\ConsultantAgencyPlanMatrix;
use App\Data\PlanEntitlementDefaults;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Four-tier catalogue: Free, Carbon, ESG, Enterprise — on both sides.
 *
 * Replaces eight client packages and seven consultant packs, most of which
 * differed only by a location cap. The real product boundary is between GHG
 * accounting (inventory + MOCCAE/IEQT) and ESG disclosure (IFRS, GRI, SASB,
 * UAE ESG), so Carbon and ESG split on exactly that line.
 *
 * GRANDFATHERING: no legacy plan row is deleted and no subscriber is moved.
 * Old rows are only marked is_active = false, which removes them from
 * checkout while every existing subscription keeps its plan, its entitlements
 * and its price. Deleting a plan somebody is paying for would strip their
 * access at the next entitlement lookup. Same treatment consultant_trial
 * received in Phase 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        $this->upsertClientPlans();
        $this->upsertConsultantPacks();
        $this->deactivateSuperseded();
    }

    public function down(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        // Reactivate the superseded catalogue and retire the new tiers. The
        // new rows are deactivated rather than deleted for the same reason
        // the old ones were: somebody may already have subscribed.
        foreach (array_merge(
            PlanEntitlementDefaults::LEGACY_PLAN_CODES,
            ConsultantAgencyPlanMatrix::LEGACY_DEPTH_CODES
        ) as $code) {
            SubscriptionPlan::where('plan_code', $code)->update(['is_active' => true]);
        }

        foreach (['client_carbon', 'client_esg', 'consultant_carbon', 'consultant_esg'] as $code) {
            SubscriptionPlan::where('plan_code', $code)->update(['is_active' => false]);
        }
    }

    private function upsertClientPlans(): void
    {
        foreach (['client_carbon', 'client_esg'] as $code) {
            $definition = PlanEntitlementDefaults::forPlanCode($code);
            if (!$definition) {
                continue;
            }

            $priceAnnual = (float) $definition['price_annual'];

            SubscriptionPlan::updateOrCreate(
                ['plan_code' => $code],
                [
                    'plan_name' => $definition['plan_name'],
                    'plan_category' => 'client',
                    'description' => $definition['description'],
                    'price_annual' => $priceAnnual,
                    'price_inr' => PlanEntitlementDefaults::defaultPriceInr($priceAnnual),
                    'currency' => $definition['currency'],
                    'billing_cycle' => 'annual',
                    'is_active' => true,
                    'sort_order' => $definition['sort_order'],
                    'limits' => $definition['limits'],
                    'entitlements' => $definition['entitlements'],
                    'features' => $definition['features'],
                ]
            );
        }
    }

    private function upsertConsultantPacks(): void
    {
        foreach (['consultant_carbon', 'consultant_esg'] as $code) {
            $pack = ConsultantAgencyPlanMatrix::forPlanCode($code);
            if (!$pack) {
                continue;
            }

            $priceAnnual = (float) ($pack['price_annual'] ?? 0);

            SubscriptionPlan::updateOrCreate(
                ['plan_code' => $code],
                [
                    'plan_name' => $pack['plan_name'],
                    'plan_category' => $pack['plan_category'] ?? 'consultant_agency',
                    'description' => $pack['description'] ?? '',
                    'price_annual' => $priceAnnual,
                    'price_inr' => $priceAnnual > 0
                        ? PlanEntitlementDefaults::defaultPriceInr($priceAnnual)
                        : 0,
                    'currency' => $pack['currency'] ?? 'AED',
                    'billing_cycle' => $pack['billing_cycle'] ?? 'annual',
                    'is_active' => true,
                    'sort_order' => $pack['sort_order'] ?? 10,
                    'limits' => $pack['limits'] ?? [],
                    'entitlements' => $pack['entitlements'] ?? [],
                    'features' => $pack['features'] ?? [],
                ]
            );
        }
    }

    /**
     * Hide the superseded catalogue from checkout. Rows and subscriptions are
     * left intact.
     */
    private function deactivateSuperseded(): void
    {
        $codes = array_merge(
            PlanEntitlementDefaults::LEGACY_PLAN_CODES,
            ConsultantAgencyPlanMatrix::LEGACY_DEPTH_CODES
        );

        foreach ($codes as $code) {
            $plan = SubscriptionPlan::where('plan_code', $code)->first();
            if (!$plan) {
                continue;
            }

            $plan->update([
                'is_active' => false,
                'description' => rtrim((string) $plan->description, ' .')
                    . ' — superseded by the Carbon / ESG tiers; existing subscribers keep this plan.',
            ]);
        }
    }
};
