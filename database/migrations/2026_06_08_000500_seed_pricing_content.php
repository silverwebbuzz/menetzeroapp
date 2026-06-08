<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\PlanFeatureRow;
use App\Models\Scope3Addon;

/**
 * Seed the admin-managed pricing content (comparison rows + Scope 3 add-ons)
 * with the initial values. Safe to run once; skips if data already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (PlanFeatureRow::count() === 0) {
            foreach ($this->featureRows() as $i => $row) {
                PlanFeatureRow::create([
                    'label' => $row[0],
                    'coming_soon' => $row[1],
                    'value_starter' => $this->cell($row[2]),
                    'value_growth' => $this->cell($row[3]),
                    'value_enterprise' => $this->cell($row[4]),
                    'sort_order' => $i + 1,
                    'is_active' => true,
                ]);
            }
        }

        if (Scope3Addon::count() === 0) {
            foreach ($this->addons() as $i => $addon) {
                Scope3Addon::create([
                    'name' => $addon['name'],
                    'price_display' => $addon['price'],
                    'items' => $addon['items'],
                    'sort_order' => $i + 1,
                    'is_active' => true,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Leave admin-edited content in place on rollback.
    }

    /** Convert a bool/string cell to the stored string form. */
    private function cell($value): string
    {
        if ($value === true) {
            return 'yes';
        }
        if ($value === false) {
            return 'no';
        }
        return (string) $value;
    }

    /** [label, coming_soon, starter, growth, enterprise] */
    private function featureRows(): array
    {
        return [
            ['Users', false, 'Up to 10', 'Up to 25', 'Unlimited'],
            ['Locations / Branches', false, '2', '10', 'Unlimited'],
            ['Scope 1 Calculation', false, true, true, true],
            ['Scope 2 Calculation', false, true, true, true],
            ['Electricity Consumption Tracking', false, true, true, true],
            ['Fuel Consumption Tracking', false, true, true, true],
            ['Emission Factor Library', false, true, true, true],
            ['Dashboard', false, true, true, true],
            ['Annual Carbon Report PDF', false, '1', 'Unlimited', 'Unlimited'],
            ['Historical Data', false, '2 Years', '5 Years', 'Unlimited'],
            ['Data Import (Excel / CSV)', false, true, true, true],
            ['AI Categorization', true, 'Basic', 'Advanced', 'Advanced'],
            ['AI Data Quality Check', true, false, true, true],
            ['Branch-wise Reporting', true, false, true, true],
            ['Department-wise Reporting', true, false, true, true],
            ['Reduction Recommendations', true, false, true, true],
            ['API Access', true, false, 'Limited', 'Full'],
            ['White Label Reports', true, false, false, true],
            ['Dedicated Account Manager', false, false, false, true],
            ['Email Support', false, true, 'Priority', 'Priority'],
            ['Training Sessions', false, '1', '3', 'Unlimited'],
        ];
    }

    private function addons(): array
    {
        return [
            [
                'name' => 'Scope 3 Lite (Spend Based)',
                'price' => 'AED 10,000 – 15,000 / year',
                'items' => [
                    ['label' => 'Purchased Goods & Services', 'soon' => false],
                    ['label' => 'Business Travel', 'soon' => false],
                    ['label' => 'Employee Commuting', 'soon' => false],
                    ['label' => 'Waste', 'soon' => false],
                    ['label' => 'Basic Scope 3 Report', 'soon' => false],
                ],
            ],
            [
                'name' => 'Scope 3 Standard',
                'price' => 'AED 20,000 – 40,000 / year',
                'items' => [
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
                'price' => 'AED 50,000 – 100,000+ / year',
                'items' => [
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
};
