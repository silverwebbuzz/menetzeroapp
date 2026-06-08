<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\SubscriptionPlan;

return new class extends Migration
{
    /**
     * Make the Scope 3 "records per form" limit data-driven.
     *
     * Free plans (price 0 / "free" code) are limited to 1 record per Scope 3 form;
     * every paid client plan gets unlimited (-1). Existing limits are preserved.
     */
    public function up(): void
    {
        SubscriptionPlan::where('plan_category', 'client')->get()->each(function (SubscriptionPlan $plan) {
            $limits = $plan->limits ?? [];

            // Don't overwrite an explicitly configured value.
            if (array_key_exists('scope3_records_per_form', $limits) && $limits['scope3_records_per_form'] !== null) {
                return;
            }

            $isFreePlan = ((float) $plan->price_annual) <= 0
                || stripos((string) $plan->plan_code, 'free') !== false;

            $limits['scope3_records_per_form'] = $isFreePlan ? 1 : -1;

            $plan->limits = $limits;
            $plan->save();
        });
    }

    public function down(): void
    {
        SubscriptionPlan::where('plan_category', 'client')->get()->each(function (SubscriptionPlan $plan) {
            $limits = $plan->limits ?? [];
            unset($limits['scope3_records_per_form']);
            $plan->limits = $limits;
            $plan->save();
        });
    }
};
