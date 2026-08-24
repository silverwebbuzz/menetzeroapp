<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill emission_sources_master.type.
 *
 * The column was never populated — every row held NULL. The emission-boundaries
 * page groups Scope 3 into upstream/downstream by this column, so with no value
 * the entire Scope 3 tab rendered empty: 15 active sources existed in the
 * database and none appeared on the page.
 *
 * GHG Protocol splits the 15 categories at 8/9: categories 1-8 are upstream,
 * 9-15 downstream. The category number is parsed from `subcategory`, which
 * already carries values like "Cat 6 – Business Travel" (GhgReportService
 * relies on the same field). Scope 1 and 2 have no upstream/downstream concept,
 * so they are left NULL by design.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('emission_sources_master')
            || !Schema::hasColumn('emission_sources_master', 'type')) {
            return;
        }

        $sources = DB::table('emission_sources_master')
            ->select('id', 'subcategory', 'category', 'name')
            ->where('scope', 'Scope 3')
            ->get();

        foreach ($sources as $source) {
            $categoryNumber = $this->categoryNumber($source);

            if ($categoryNumber === null) {
                continue; // leave NULL — surfaces under "Other Scope 3"
            }

            DB::table('emission_sources_master')
                ->where('id', $source->id)
                ->update([
                    'type' => $categoryNumber <= 8 ? 'upstream' : 'downstream',
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('emission_sources_master')
            && Schema::hasColumn('emission_sources_master', 'type')) {
            DB::table('emission_sources_master')
                ->where('scope', 'Scope 3')
                ->update(['type' => null]);
        }
    }

    /**
     * Leading category number from "Cat 6 – Business Travel" / "Category 6 …".
     * Falls back to matching the source name against the known category names.
     */
    private function categoryNumber(object $source): ?int
    {
        foreach ([$source->subcategory ?? '', $source->category ?? ''] as $value) {
            if ($value === '') {
                continue;
            }

            if (preg_match('/(?:cat(?:egory)?\.?\s*)(\d{1,2})/i', $value, $m)) {
                $number = (int) $m[1];
                if ($number >= 1 && $number <= 15) {
                    return $number;
                }
            }
        }

        return $this->numberFromName($source->name ?? '');
    }

    /**
     * Distinctive keyword per GHG Protocol category, most specific first —
     * "upstream/downstream transport" must be tested before plain "transport".
     */
    private function numberFromName(string $name): ?int
    {
        $name = strtolower($name);

        $patterns = [
            9 => ['downstream transport', 'downstream transportation'],
            13 => ['downstream leased'],
            4 => ['upstream transport', 'upstream transportation'],
            8 => ['upstream leased'],
            1 => ['purchased goods'],
            2 => ['capital goods'],
            3 => ['fuel and energy', 'fuel & energy', 'fuel-and-energy'],
            5 => ['waste generated', 'waste in operations'],
            6 => ['business travel'],
            7 => ['employee commuting'],
            10 => ['processing of sold'],
            11 => ['use of sold'],
            12 => ['end-of-life', 'end of life'],
            14 => ['franchise'],
            15 => ['investment'],
        ];

        foreach ($patterns as $number => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($name, $needle)) {
                    return $number;
                }
            }
        }

        return null;
    }
};
