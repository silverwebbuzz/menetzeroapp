<?php

namespace Database\Seeders;

use App\Data\ConsultantAgencyPlanMatrix;
use App\Data\PlanEntitlementDefaults;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Rebuilds subscription_plans from the canonical definitions.
 *
 * Both data classes already hold every plan -- PlanEntitlementDefaults for the
 * client side, ConsultantAgencyPlanMatrix for agency packs -- so this seeder
 * iterates them rather than restating prices and entitlements a second time.
 * A hardcoded copy here would be one more place for the catalogue to drift.
 *
 * ACTIVE (self-serve): the four-tier catalogue on each side.
 *   client_free · client_carbon · client_esg · client_enterprise
 *   consultant_free · consultant_carbon · consultant_esg · consultant_enterprise
 *
 * INACTIVE but seeded: every retired code, plus the admin-only demo pack and
 * the managed-client limit template. These rows must EXIST even though nobody
 * can buy them -- an existing subscription points at one by id, and
 * PlanEntitlementService resolves entitlements through that row. A missing row
 * strips a paying subscriber's access at the next lookup. is_active = false is
 * what removes them from checkout.
 *
 * Idempotent: updateOrCreate keyed on plan_code, so it is safe to re-run and
 * safe to run after truncating the table.
 *
 *   php artisan db:seed --class=SubscriptionPlanSeeder
 */
class SubscriptionPlanSeeder extends Seeder
{
    /** Codes that remain purchasable. Everything else is seeded inactive. */
    private const ACTIVE_CODES = [
        'client_free',
        'client_carbon',
        'client_esg',
        'client_enterprise',
        'consultant_free',
        'consultant_carbon',
        'consultant_esg',
        'consultant_enterprise',
    ];

    public function run(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            $this->command?->error('subscription_plans table not found — run migrations first.');

            return;
        }

        $clientCount = $this->seedClientPlans();
        $consultantCount = $this->seedConsultantPacks();

        $active = SubscriptionPlan::where('is_active', true)->count();
        $total = SubscriptionPlan::count();

        $this->command?->info("Seeded {$clientCount} client plans and {$consultantCount} consultant packs.");
        $this->command?->info("{$total} rows total, {$active} active.");
    }

    private function seedClientPlans(): int
    {
        $count = 0;

        foreach (PlanEntitlementDefaults::definitions() as $code => $definition) {
            $priceAnnual = (float) ($definition['price_annual'] ?? 0);

            // consultant_managed_standard is a limit template, not a sellable
            // plan — it is looked up for managed-client entitlements only.
            $category = str_starts_with($code, 'consultant_')
                ? 'consultant_agency'
                : 'client';

            SubscriptionPlan::updateOrCreate(
                ['plan_code' => $code],
                [
                    'plan_name' => $definition['plan_name'],
                    'plan_category' => $category,
                    'description' => $this->describe($code, $definition['description'] ?? ''),
                    'price_annual' => $priceAnnual,
                    'price_inr' => $priceAnnual > 0
                        ? PlanEntitlementDefaults::defaultPriceInr($priceAnnual)
                        : 0,
                    'currency' => $definition['currency'] ?? 'AED',
                    'billing_cycle' => 'annual',
                    'is_active' => in_array($code, self::ACTIVE_CODES, true),
                    'sort_order' => $definition['sort_order'] ?? 99,
                    'limits' => $definition['limits'] ?? [],
                    'entitlements' => $definition['entitlements'] ?? [],
                    'features' => $definition['features'] ?? [],
                ]
            );

            $count++;
        }

        return $count;
    }

    private function seedConsultantPacks(): int
    {
        $count = 0;

        foreach (ConsultantAgencyPlanMatrix::packDefinitions() as $code => $pack) {
            $priceAnnual = (float) ($pack['price_annual'] ?? 0);

            SubscriptionPlan::updateOrCreate(
                ['plan_code' => $code],
                [
                    'plan_name' => $pack['plan_name'] ?? $code,
                    'plan_category' => $pack['plan_category'] ?? 'consultant_agency',
                    'description' => $this->describe($code, $pack['description'] ?? ''),
                    'price_annual' => $priceAnnual,
                    'price_inr' => $priceAnnual > 0
                        ? PlanEntitlementDefaults::defaultPriceInr($priceAnnual)
                        : 0,
                    'currency' => $pack['currency'] ?? 'AED',
                    'billing_cycle' => $pack['billing_cycle'] ?? 'annual',
                    'is_active' => in_array($code, self::ACTIVE_CODES, true),
                    'sort_order' => $pack['sort_order'] ?? 99,
                    'limits' => $pack['limits'] ?? [],
                    'entitlements' => $pack['entitlements'] ?? [],
                    'features' => $pack['features'] ?? [],
                ]
            );

            $count++;
        }

        return $count;
    }

    /**
     * Marks a retired plan in its own description, so an admin looking at the
     * table can tell a superseded row from a live one without cross-checking
     * is_active against the code list.
     */
    private function describe(string $code, string $description): string
    {
        if (in_array($code, self::ACTIVE_CODES, true)) {
            return $description;
        }

        if (str_contains($description, 'superseded') || str_contains($description, 'legacy')) {
            return $description;
        }

        return rtrim($description, ' .')
            . ' — superseded by the Carbon / ESG tiers; existing subscribers keep this plan.';
    }
}
