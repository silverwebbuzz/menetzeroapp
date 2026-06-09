<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remove legacy / mis-scoped sources from Scope 1 & 2 boundary pickers.
 * Quick Input uses IDs 52–58; IDs 1–10 are obsolete duplicates or wrong scope.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('emission_sources_master')) {
            return;
        }

        // ID 1: wastewater sent off-site is Scope 3 (Cat 5). ID 36 already covers this.
        DB::table('emission_sources_master')->where('id', 1)->update([
            'scope' => 'Scope 3',
            'category' => '3.5 Waste generated in operations',
            'description' => 'Wastewater treatment (off-site / municipal). Use Scope 3 Wastewater (ID 36). On-site owned treatment plants only belong in Scope 1 process emissions.',
            'is_active' => 0,
            'updated_at' => now(),
        ]);

        // ID 7: self-generated electricity from owned combustion is Scope 1, not purchased Scope 2.
        DB::table('emission_sources_master')->where('id', 7)->update([
            'scope' => 'Scope 1',
            'description' => 'Deprecated: report owned-generator fuel under Scope 1 Natural Gas / Fuel quick-input forms.',
            'is_active' => 0,
            'updated_at' => now(),
        ]);

        // Legacy boundary-only duplicates of quick-input sources 52–58.
        DB::table('emission_sources_master')
            ->whereIn('id', [2, 3, 4, 5, 6, 8, 9, 10])
            ->update([
                'is_active' => 0,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('emission_sources_master')) {
            return;
        }

        DB::table('emission_sources_master')
            ->whereIn('id', range(1, 10))
            ->update(['is_active' => 1, 'updated_at' => now()]);

        DB::table('emission_sources_master')->where('id', 1)->update(['scope' => 'Scope 1']);
        DB::table('emission_sources_master')->where('id', 7)->update(['scope' => 'Scope 2']);
    }
};
