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
                'tagline' => 'For MSMEs getting started',
                'price_display' => 'AED 3,650',
                'price_sub' => '≈ USD 990 / year',
                'is_custom' => false,
                'selectable' => true,
                'highlight' => false,
            ],
            'client_growth' => [
                'name' => 'Growth',
                'tagline' => 'For expanding businesses',
                'price_display' => 'AED 9,150',
                'price_sub' => '≈ USD 2,490 / year',
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
     * The feature comparison rows.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function featureRows(): array
    {
        return [
            ['label' => 'Users', 'coming_soon' => false, 'cells' => [
                'client_starter' => 'Up to 10', 'client_growth' => 'Up to 25', 'client_enterprise' => 'Unlimited',
            ]],
            ['label' => 'Locations / Branches', 'coming_soon' => false, 'cells' => [
                'client_starter' => '2', 'client_growth' => '10', 'client_enterprise' => 'Unlimited',
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
            ['label' => 'White Label Reports', 'coming_soon' => true, 'cells' => [
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
     * Shown as "Coming soon" until purchasable add-on billing is built.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function scope3AddOns(): array
    {
        return [
            [
                'name' => 'Scope 3 Lite (Spend Based)',
                'price_display' => 'AED 10,000 – 15,000 / year',
                'includes' => [
                    'Purchased Goods & Services',
                    'Business Travel',
                    'Employee Commuting',
                    'Waste',
                    'Basic Scope 3 Report',
                ],
            ],
            [
                'name' => 'Scope 3 Standard',
                'price_display' => 'AED 20,000 – 40,000 / year',
                'includes' => [
                    'Everything in Lite',
                    'Supplier Mapping',
                    'Missing Data Analysis',
                    'Data Quality Scoring',
                    'AI Recommendations',
                    'Annual Review',
                ],
            ],
            [
                'name' => 'Scope 3 Advanced — Supplier Engagement',
                'price_display' => 'AED 50,000 – 100,000+ / year',
                'includes' => [
                    'Supplier Portal',
                    'Supplier Questionnaires',
                    'Activity-Based Calculations',
                    'Multi-country Suppliers',
                    'Audit Support',
                    'ESG Consulting Support',
                ],
            ],
        ];
    }

    /**
     * Bottom-of-page summary of headline packages / services.
     *
     * @return array<int, array<string, string>>
     */
    public static function packages(): array
    {
        return [
            ['package' => 'Scope 1 & 2 SaaS', 'price' => 'USD 990 / year'],
            ['package' => 'Setup Fee', 'price' => 'USD 500 (one-time)'],
            ['package' => 'Scope 3 Add-On', 'price' => 'AED 15,000+'],
            ['package' => 'Full Carbon Reporting Service', 'price' => 'AED 25,000 – 75,000 / year'],
        ];
    }
}
