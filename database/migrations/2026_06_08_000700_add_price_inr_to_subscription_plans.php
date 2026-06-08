<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\SubscriptionPlan;

/**
 * Adds an INR price to plans. Payment gateways (Razorpay/Cashfree) settle in
 * INR, so this is the amount actually charged. AED stays as the display price
 * for UAE visitors. The backfill uses an approximate AED->INR rate as a
 * starting value — review the INR prices in admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_plans', 'price_inr')) {
                $table->decimal('price_inr', 12, 2)->nullable()->after('price_annual');
            }
        });

        // Seed a sensible starting INR value (~AED * 23). Admin should review.
        SubscriptionPlan::query()->each(function (SubscriptionPlan $plan) {
            if ($plan->price_inr === null) {
                $plan->price_inr = round(((float) $plan->price_annual) * 23);
                $plan->save();
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_plans', 'price_inr')) {
                $table->dropColumn('price_inr');
            }
        });
    }
};
