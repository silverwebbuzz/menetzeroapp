<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\SubscriptionPlan;

/**
 * Restructure client plans to the AED pricing model:
 *   - Free       : unchanged limits (signup/trial tier)
 *   - Starter    : AED 3,650  (~USD 990)  | 2 locations, 10 users, 2yr history
 *   - Growth     : AED 9,150  (~USD 2,490)| 10 locations, 25 users, 5yr history
 *   - Enterprise : Custom (AED 20,000+)   | unlimited (renamed from "Pro")
 *
 * Scope 3 stays an add-on (sold separately, "coming soon"), so
 * scope3_records_per_form is kept at 1 (preview) for every standard plan.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Free — keep as signup/trial tier, just make sure limit keys exist.
        $this->updatePlan('client_free', [
            'plan_name' => 'Free',
            'description' => 'Free signup — Scope 1 & 2 with 1 location and 2 users.',
            'price_annual' => 0,
            'currency' => 'AED',
            'sort_order' => 1,
            'limits' => [
                'locations' => 1, 'users' => 2, 'documents' => 10,
                'scope3_records_per_form' => 1, 'annual_report_pdf' => 1, 'historical_years' => 1,
            ],
        ]);

        // Starter
        $this->updatePlan('client_starter', [
            'plan_name' => 'Starter',
            'description' => 'For MSMEs getting started with Scope 1 & 2 reporting.',
            'price_annual' => 3650,
            'currency' => 'AED',
            'sort_order' => 2,
            'limits' => [
                'locations' => 2, 'users' => 10, 'documents' => 50,
                'scope3_records_per_form' => 1, 'annual_report_pdf' => 1, 'historical_years' => 2,
            ],
        ]);

        // Growth
        $this->updatePlan('client_growth', [
            'plan_name' => 'Growth',
            'description' => 'For expanding businesses needing more locations and users.',
            'price_annual' => 9150,
            'currency' => 'AED',
            'sort_order' => 3,
            'limits' => [
                'locations' => 10, 'users' => 25, 'documents' => 200,
                'scope3_records_per_form' => 1, 'annual_report_pdf' => -1, 'historical_years' => 5,
            ],
        ]);

        // Pro -> Enterprise (custom-priced; AED 20,000+)
        $pro = SubscriptionPlan::where('plan_code', 'client_pro')
            ->orWhere('plan_code', 'client_enterprise')
            ->first();
        if ($pro) {
            $pro->plan_code = 'client_enterprise';
            $pro->plan_name = 'Enterprise';
            $pro->description = 'For large / multi-site organisations. Custom pricing (AED 20,000+).';
            $pro->price_annual = 20000;
            $pro->currency = 'AED';
            $pro->sort_order = 4;
            $pro->limits = [
                'locations' => -1, 'users' => -1, 'documents' => -1,
                'scope3_records_per_form' => 1, 'annual_report_pdf' => -1, 'historical_years' => -1,
            ];
            $pro->save();
        }
    }

    public function down(): void
    {
        $enterprise = SubscriptionPlan::where('plan_code', 'client_enterprise')->first();
        if ($enterprise) {
            $enterprise->plan_code = 'client_pro';
            $enterprise->plan_name = 'Pro';
            $enterprise->description = 'Professional plan with all features';
            $enterprise->save();
        }
    }

    private function updatePlan(string $code, array $attributes): void
    {
        $plan = SubscriptionPlan::where('plan_code', $code)->first();
        if (!$plan) {
            return;
        }

        // Preserve any limit keys not explicitly set here.
        if (isset($attributes['limits'])) {
            $attributes['limits'] = array_merge($plan->limits ?? [], $attributes['limits']);
        }

        $plan->fill($attributes)->save();
    }
};
