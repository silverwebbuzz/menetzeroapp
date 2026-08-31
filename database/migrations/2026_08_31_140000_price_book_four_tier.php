<?php

use App\Models\CommercialPriceBookEntry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Price-book entries for the four-tier catalogue (section 65).
 *
 * CommercialPriceBook reads this table FIRST and only falls back to its
 * constants when a code is absent, so without these rows an admin raising a
 * quote for Carbon or ESG would get no list price and the quote would silently
 * become "custom".
 *
 * Retired package rows are kept and marked, not deleted: a quote raised before
 * the catalogue changed, or a renewal for a grandfathered subscriber, still
 * resolves its original price here.
 *
 * Adds the per-slot consultant rates. The legacy consultant_rate_le_10 /
 * _gt_10 rows priced by headcount alone, which cannot express that a Carbon
 * slot and an ESG slot are different products.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('commercial_price_book_entries')) {
            return;
        }

        $rows = [
            [
                'code' => 'client_carbon',
                'category' => 'company_package',
                'label' => 'Carbon',
                'amount_aed' => 3000,
                'is_custom' => false,
                'notes' => 'Full Scope 1-3 inventory · MOCCAE / IEQT / GHG / Excel · 5 sites',
                'sort_order' => 10,
            ],
            [
                'code' => 'client_esg',
                'category' => 'company_package',
                'label' => 'ESG',
                'amount_aed' => 6500,
                'is_custom' => false,
                'notes' => 'Carbon plus IFRS S1 & S2, GRI, SASB, UAE ESG · 5 sites',
                'sort_order' => 20,
            ],
            [
                'code' => 'consultant_slot_carbon',
                'category' => 'consultant_rate',
                'label' => 'Consultant slot — Carbon',
                'amount_aed' => 2000,
                'is_custom' => false,
                'notes' => 'Per managed client / year excl. VAT. Min 5 slots. Client list AED 3,000.',
                'sort_order' => 5,
                'meta' => ['min_slots' => 5, 'single' => 1900, 'block5' => 1800],
            ],
            [
                'code' => 'consultant_slot_esg',
                'category' => 'consultant_rate',
                'label' => 'Consultant slot — ESG',
                'amount_aed' => 4000,
                'is_custom' => false,
                'notes' => 'Per managed client / year excl. VAT. Min 5 slots. Client list AED 6,500.',
                'sort_order' => 6,
                'meta' => ['min_slots' => 5, 'single' => 3800, 'block5' => 3600],
            ],
        ];

        foreach ($rows as $row) {
            CommercialPriceBookEntry::updateOrCreate(['code' => $row['code']], $row);
        }

        $this->markRetired();
    }

    public function down(): void
    {
        if (!Schema::hasTable('commercial_price_book_entries')) {
            return;
        }

        CommercialPriceBookEntry::whereIn('code', [
            'client_carbon',
            'client_esg',
            'consultant_slot_carbon',
            'consultant_slot_esg',
        ])->delete();
    }

    /**
     * Label the superseded packages so an admin picking from the quote list
     * can tell which are still sold. Amounts are left alone -- they still
     * price in-flight quotes and grandfathered renewals.
     */
    private function markRetired(): void
    {
        $retired = [
            'client_scope_basic',
            'client_scope_pro',
            'client_esg_starter',
            'client_esg_complete',
            'consultant_rate_le_10',
            'consultant_rate_gt_10',
        ];

        foreach ($retired as $code) {
            $entry = CommercialPriceBookEntry::where('code', $code)->first();
            if (!$entry) {
                continue;
            }

            if (str_contains((string) $entry->notes, 'RETIRED')) {
                continue;
            }

            $entry->update([
                'notes' => 'RETIRED — existing quotes and renewals only. ' . $entry->notes,
                'sort_order' => ($entry->sort_order ?? 0) + 100,
            ]);
        }
    }
};
