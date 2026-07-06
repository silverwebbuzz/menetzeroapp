<?php

namespace App\Data;

/**
 * Single source of truth for the public-facing subscription pricing/comparison UI.
 *
 * NOTE: This is presentation data only. Enforced limits (users, locations,
 * scope3_records_per_form, etc.) live in the `subscription_plans.limits` JSON
 * column and are checked by SubscriptionService. Keep the two in sync.
 *
 * Cell values in the feature matrix can be:
 *   - true  => rendered as a ✓
 *   - false => rendered as a — (not included)
 *   - string => rendered verbatim (e.g. "Up to 10", "2 Years", "Limited")
 *
 * A row marked `coming_soon => true` is NOT yet built in the product; it is
 * shown with a "Coming soon" badge so the roadmap is transparent.
 */
class SubscriptionPlanMatrix
{
    /**
     * Paid tiers shown in the comparison table (in column order).
     * Keyed by the matching subscription_plans.plan_code.
     */
    public static function plans(): array
    {
        return [
            'client_starter' => [
                'name' => 'Starter',
                'tagline' => 'MOCCAE-ready inventory & IEQT',
                'price_display' => 'AED 1,499',
                'price_sub' => '/ year',
                'is_custom' => false,
                'selectable' => true,
                'highlight' => false,
            ],
            'client_growth' => [
                'name' => 'Growth',
                'tagline' => 'IFRS & GRI report downloads',
                'price_display' => 'AED 2,499',
                'price_sub' => '/ year',
                'is_custom' => false,
                'selectable' => true,
                'highlight' => true,
            ],
            'client_enterprise' => [
                'name' => 'Enterprise',
                'tagline' => 'For large / multi-site organisations',
                'price_display' => 'Custom',
                'price_sub' => 'AED 20,000+ / year',
                'is_custom' => true,
                'selectable' => false,
                'highlight' => false,
            ],
        ];
    }

    /**
     * Column keys in render order (must match keys used in each row's `cells`).
     */
    public static function columns(): array
    {
        return ['client_starter', 'client_growth', 'client_enterprise'];
    }

    /**
     * The feature comparison rows. Reads admin-managed rows from the DB and
     * falls back to the built-in defaults if the table is empty/missing.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function featureRows(): array
    {
        try {
            $rows = \App\Models\PlanFeatureRow::where('is_active', true)
                ->orderBy('sort_order')->orderBy('id')->get();
            if ($rows->isNotEmpty()) {
                return $rows->map->toMatrixRow()->all();
            }
        } catch (\Throwable $e) {
            // Table not migrated yet — use defaults below.
        }

        return self::defaultFeatureRows();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function defaultFeatureRows(): array
    {
        return [
            ['label' => 'Users', 'coming_soon' => false, 'cells' => [
                'client_starter' => 'Up to 5', 'client_growth' => 'Up to 10', 'client_enterprise' => 'Unlimited',
            ]],
            ['label' => 'Locations / Branches', 'coming_soon' => false, 'cells' => [
                'client_starter' => '3', 'client_growth' => '10', 'client_enterprise' => 'Unlimited',
            ]],
            ['label' => 'Scope 1 Calculation', 'coming_soon' => false, 'cells' => [
                'client_starter' => true, 'client_growth' => true, 'client_enterprise' => true,
            ]],
            ['label' => 'Scope 2 Calculation', 'coming_soon' => false, 'cells' => [
                'client_starter' => true, 'client_growth' => true, 'client_enterprise' => true,
            ]],
            ['label' => 'Electricity Consumption Tracking', 'coming_soon' => false, 'cells' => [
                'client_starter' => true, 'client_growth' => true, 'client_enterprise' => true,
            ]],
            ['label' => 'Fuel Consumption Tracking', 'coming_soon' => false, 'cells' => [
                'client_starter' => true, 'client_growth' => true, 'client_enterprise' => true,
            ]],
            ['label' => 'Emission Factor Library', 'coming_soon' => false, 'cells' => [
                'client_starter' => true, 'client_growth' => true, 'client_enterprise' => true,
            ]],
            ['label' => 'Dashboard', 'coming_soon' => false, 'cells' => [
                'client_starter' => true, 'client_growth' => true, 'client_enterprise' => true,
            ]],
            ['label' => 'Annual Carbon Report PDF', 'coming_soon' => false, 'cells' => [
                'client_starter' => '1', 'client_growth' => 'Unlimited', 'client_enterprise' => 'Unlimited',
            ]],
            ['label' => 'Historical Data', 'coming_soon' => false, 'cells' => [
                'client_starter' => '2 Years', 'client_growth' => '5 Years', 'client_enterprise' => 'Unlimited',
            ]],
            ['label' => 'Data Import (Excel / CSV)', 'coming_soon' => false, 'cells' => [
                'client_starter' => true, 'client_growth' => true, 'client_enterprise' => true,
            ]],
            ['label' => 'AI Categorization', 'coming_soon' => true, 'cells' => [
                'client_starter' => 'Basic', 'client_growth' => 'Advanced', 'client_enterprise' => 'Advanced',
            ]],
            ['label' => 'AI Data Quality Check', 'coming_soon' => true, 'cells' => [
                'client_starter' => false, 'client_growth' => true, 'client_enterprise' => true,
            ]],
            ['label' => 'Branch-wise Reporting', 'coming_soon' => true, 'cells' => [
                'client_starter' => false, 'client_growth' => true, 'client_enterprise' => true,
            ]],
            ['label' => 'Department-wise Reporting', 'coming_soon' => true, 'cells' => [
                'client_starter' => false, 'client_growth' => true, 'client_enterprise' => true,
            ]],
            ['label' => 'Reduction Recommendations', 'coming_soon' => true, 'cells' => [
                'client_starter' => false, 'client_growth' => true, 'client_enterprise' => true,
            ]],
            ['label' => 'API Access', 'coming_soon' => true, 'cells' => [
                'client_starter' => false, 'client_growth' => 'Limited', 'client_enterprise' => 'Full',
            ]],
            ['label' => 'White Label Reports', 'coming_soon' => false, 'cells' => [
                'client_starter' => false, 'client_growth' => false, 'client_enterprise' => true,
            ]],
            ['label' => 'Dedicated Account Manager', 'coming_soon' => false, 'cells' => [
                'client_starter' => false, 'client_growth' => false, 'client_enterprise' => true,
            ]],
            ['label' => 'Email Support', 'coming_soon' => false, 'cells' => [
                'client_starter' => true, 'client_growth' => 'Priority', 'client_enterprise' => 'Priority',
            ]],
            ['label' => 'Training Sessions', 'coming_soon' => false, 'cells' => [
                'client_starter' => '1', 'client_growth' => '3', 'client_enterprise' => 'Unlimited',
            ]],
        ];
    }

    /**
     * Scope 3 is sold as a separate add-on, not bundled into standard plans.
     *
     * The core Scope 3 engine (15 GHG Protocol categories, calculation and the
     * Scope 3 report) is built, so "Lite" items are live. Advanced supplier /
     * AI / data-quality features are still on the roadmap (`soon => true`).
     *
     * Each `includes` item: ['label' => string, 'soon' => bool].
     *
     * Reads admin-managed add-ons from the DB and falls back to the built-in
     * defaults if the table is empty/missing.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function scope3AddOns(): array
    {
        try {
            $addons = \App\Models\Scope3Addon::where('is_active', true)
                ->orderBy('sort_order')->orderBy('id')->get();
            if ($addons->isNotEmpty()) {
                return $addons->map->toMatrixAddon()->all();
            }
        } catch (\Throwable $e) {
            // Table not migrated yet — use defaults below.
        }

        return self::defaultScope3AddOns();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function defaultScope3AddOns(): array
    {
        return [
            [
                'name' => 'Scope 3 Lite (Spend Based)',
                'price_display' => 'AED 10,000 – 15,000 / year',
                'includes' => [
                    ['label' => 'Purchased Goods & Services', 'soon' => false],
                    ['label' => 'Business Travel', 'soon' => false],
                    ['label' => 'Employee Commuting', 'soon' => false],
                    ['label' => 'Waste', 'soon' => false],
                    ['label' => 'Basic Scope 3 Report', 'soon' => false],
                ],
            ],
            [
                'name' => 'Scope 3 Standard',
                'price_display' => 'AED 20,000 – 40,000 / year',
                'includes' => [
                    ['label' => 'Everything in Lite', 'soon' => false],
                    ['label' => 'Supplier Mapping', 'soon' => true],
                    ['label' => 'Missing Data Analysis', 'soon' => true],
                    ['label' => 'Data Quality Scoring', 'soon' => true],
                    ['label' => 'AI Recommendations', 'soon' => true],
                    ['label' => 'Annual Review', 'soon' => true],
                ],
            ],
            [
                'name' => 'Scope 3 Advanced — Supplier Engagement',
                'price_display' => 'AED 50,000 – 100,000+ / year',
                'includes' => [
                    ['label' => 'Supplier Portal', 'soon' => true],
                    ['label' => 'Supplier Questionnaires', 'soon' => true],
                    ['label' => 'Activity-Based Calculations', 'soon' => true],
                    ['label' => 'Multi-country Suppliers', 'soon' => true],
                    ['label' => 'Audit Support', 'soon' => true],
                    ['label' => 'ESG Consulting Support', 'soon' => true],
                ],
            ],
        ];
    }

}
