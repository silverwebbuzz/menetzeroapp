<?php

use App\Data\PlanEntitlementDefaults;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Commercial Plan v1 (C1): prices, limits, entitlements JSON.
 *
 * @see documentation/COMMERCIAL_PLAN_V1.md
 */
return new class extends Migration
{
    /** @var array<string, array<string, mixed>> Snapshot for down() */
    private array $previousByCode = [];

    public function up(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        if (!Schema::hasColumn('subscription_plans', 'entitlements')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->json('entitlements')->nullable()->after('limits');
            });
        }

        foreach (PlanEntitlementDefaults::definitions() as $code => $definition) {
            $plan = SubscriptionPlan::where('plan_code', $code)->first();
            if (!$plan) {
                continue;
            }

            $this->previousByCode[$code] = [
                'plan_name' => $plan->plan_name,
                'description' => $plan->description,
                'price_annual' => $plan->price_annual,
                'price_inr' => $plan->price_inr,
                'currency' => $plan->currency,
                'sort_order' => $plan->sort_order,
                'limits' => $plan->limits,
                'entitlements' => $plan->entitlements,
                'features' => $plan->features,
            ];

            $limits = array_merge($plan->limits ?? [], $definition['limits']);

            $priceAnnual = (float) $definition['price_annual'];
            $priceInr = $priceAnnual > 0
                ? PlanEntitlementDefaults::defaultPriceInr($priceAnnual)
                : 0;

            $plan->fill([
                'plan_name' => $definition['plan_name'],
                'description' => $definition['description'],
                'price_annual' => $priceAnnual,
                'price_inr' => $priceInr,
                'currency' => $definition['currency'],
                'sort_order' => $definition['sort_order'],
                'limits' => $limits,
                'entitlements' => $definition['entitlements'],
                'features' => $definition['features'],
            ]);
            $plan->save();
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        // Restore pre-C1 rows when this migration ran in the same deploy.
        $fallback = $this->legacyPlanSnapshot();

        foreach ($fallback as $code => $attributes) {
            $plan = SubscriptionPlan::where('plan_code', $code)->first();
            if (!$plan) {
                continue;
            }

            $plan->fill($attributes);
            $plan->save();
        }

        if (Schema::hasColumn('subscription_plans', 'entitlements')) {
            Schema::table('subscription_plans', function (Blueprint $table) {
                $table->dropColumn('entitlements');
            });
        }
    }

    /**
     * Pre–Commercial Plan v1 values (2026_06_08_000200 migration).
     *
     * @return array<string, array<string, mixed>>
     */
    private function legacyPlanSnapshot(): array
    {
        if ($this->previousByCode !== []) {
            return $this->previousByCode;
        }

        return [
            'client_free' => [
                'plan_name' => 'Free',
                'description' => 'Free signup — Scope 1 & 2 with 1 location and 2 users.',
                'price_annual' => 0,
                'price_inr' => 0,
                'currency' => 'AED',
                'sort_order' => 1,
                'limits' => [
                    'locations' => 1,
                    'users' => 2,
                    'documents' => 10,
                    'scope3_records_per_form' => 1,
                    'annual_report_pdf' => 1,
                    'historical_years' => 1,
                ],
                'entitlements' => null,
                'features' => [],
            ],
            'client_starter' => [
                'plan_name' => 'Starter',
                'description' => 'For MSMEs getting started with Scope 1 & 2 reporting.',
                'price_annual' => 3650,
                'price_inr' => 83950,
                'currency' => 'AED',
                'sort_order' => 2,
                'limits' => [
                    'locations' => 2,
                    'users' => 10,
                    'documents' => 50,
                    'scope3_records_per_form' => 1,
                    'annual_report_pdf' => 1,
                    'historical_years' => 2,
                ],
                'entitlements' => null,
                'features' => [],
            ],
            'client_growth' => [
                'plan_name' => 'Growth',
                'description' => 'For expanding businesses needing more locations and users.',
                'price_annual' => 9150,
                'price_inr' => 210450,
                'currency' => 'AED',
                'sort_order' => 3,
                'limits' => [
                    'locations' => 10,
                    'users' => 25,
                    'documents' => 200,
                    'scope3_records_per_form' => 1,
                    'annual_report_pdf' => -1,
                    'historical_years' => 5,
                ],
                'entitlements' => null,
                'features' => ['ifrs_s2', 'ifrs_s1', 'gri'],
            ],
            'client_enterprise' => [
                'plan_name' => 'Enterprise',
                'description' => 'For large / multi-site organisations. Custom pricing (AED 20,000+).',
                'price_annual' => 20000,
                'price_inr' => 460000,
                'currency' => 'AED',
                'sort_order' => 4,
                'limits' => [
                    'locations' => -1,
                    'users' => -1,
                    'documents' => -1,
                    'scope3_records_per_form' => 1,
                    'annual_report_pdf' => -1,
                    'historical_years' => -1,
                ],
                'entitlements' => null,
                'features' => ['ifrs_s2', 'ifrs_s1', 'gri'],
            ],
        ];
    }
};
