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
 * ACTIVE (self-serve): the client catalogue now opens at Essential.
 *   client_essential · client_carbon · client_esg · client_enterprise
 *   consultant_free · consultant_carbon · consultant_esg · consultant_enterprise
 *
 * INACTIVE but seeded: client_free and the admin-only demo pack (consultant_1).
 * Both must EXIST while being unbuyable. client_free is the entitlement floor --
 * companies mid-signup, companies whose subscription lapsed and anything else
 * without a subscription row resolve to it, so it stays seeded at price 0.
 * consultant_1 is looked up by plan_code on the admin screens.
 *
 * NOT SEEDED: every retired plan. They were deleted from the table on purpose,
 * so SEEDED_CODES is an allowlist and this seeder skips everything outside it --
 * re-running it will not resurrect them. Their definitions remain in PHP because
 * forPlanCode() still resolves them for historical lookups and for the
 * consultant-managed entitlement templates.
 *
 * Because of that, this seeder assumes nobody is subscribed to a retired plan.
 * That held when they were removed (all six had zero subscribers). Before
 * reviving one, add its code to SEEDED_CODES so the row exists again -- an
 * active subscription pointing at a missing plan row resolves to no
 * entitlements at the next lookup.
 *
 * Idempotent: updateOrCreate keyed on plan_code, so it is safe to re-run and
 * safe to run after truncating the table.
 *
 *   php artisan db:seed --class=SubscriptionPlanSeeder
 */
class SubscriptionPlanSeeder extends Seeder
{
    /**
     * The only rows this seeder creates. Anything not listed here is skipped,
     * even though the data classes still define it.
     *
     * Retired plans were deleted from the table deliberately, and an earlier
     * version of this seeder iterated every definition -- so each run brought
     * all of them back. The definitions stay in PHP because forPlanCode() must
     * still resolve them for historical lookups and for the consultant-managed
     * entitlement templates; they simply no longer earn a database row.
     *
     * Adding a plan back to the catalogue means adding its code here, and to
     * ACTIVE_CODES if it should also be purchasable.
     */
    private const SEEDED_CODES = [
        // Client — the floor, then the live ladder.
        'client_free',
        'client_essential',
        'client_carbon',
        'client_esg',
        'client_enterprise',
        // Consultant — live packs, plus the admin-only demo pack.
        'consultant_free',
        'consultant_1',
        'consultant_carbon',
        'consultant_esg',
        'consultant_enterprise',
    ];

    /** Codes that remain purchasable. Everything else is seeded inactive. */
    private const ACTIVE_CODES = [
        'client_essential',
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
            if (! in_array($code, self::SEEDED_CODES, true)) {
                continue;
            }

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
            if (! in_array($code, self::SEEDED_CODES, true)) {
                continue;
            }

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
