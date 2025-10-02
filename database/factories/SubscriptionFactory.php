<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        $planTypes = ['Free', 'Standard', 'Premium'];
        $statuses = ['active', 'cancelled', 'trialing'];
        
        $planType = $this->faker->randomElement($planTypes);
        $status = $this->faker->randomElement($statuses);

        $startedAt = $this->faker->dateTimeBetween('-2 years', '-1 month');
        
        // Calculate expiry based on plan type and status
        $expiryMonths = [
            'Free' => 1,
            'Standard' => 12,
            'Premium' => 12
        ];

        $expiresAt = $this->faker->dateTimeBetween(
            $startedAt,
            $startedAt->modify('+' . $expiryMonths[$planType] . ' months')
        );

        // Only generate Stripe customer ID for paid plans
        $stripeCustomerId = null;
        if (in_array($planType, ['Standard', 'Premium']) && $status === 'active') {
            $stripeCustomerId = 'cus_' . $this->faker->regexify('[A-Za-z0-9]{14}');
        }

        return [
            'company_id' => Company::factory(),
            'plan_type' => $planType,
            'status' => $status,
            'stripe_customer_id' => $stripeCustomerId,
            'started_at' => $startedAt,
            'expires_at' => $expiresAt,
        ];
    }
}

