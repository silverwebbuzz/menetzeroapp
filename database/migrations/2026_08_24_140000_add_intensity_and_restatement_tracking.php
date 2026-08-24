<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GHG Protocol Chapter 5 support: intensity denominators and structural-change
 * tracking.
 *
 * Absolute emissions alone misrepresent a company whose boundary grew — three
 * branches emitting less each than one branch did still shows as an increase.
 * Intensity (tCO2e per unit) normalises for that; the structural-change log
 * records WHY a boundary moved so a base-year restatement can be justified.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_reporting_settings')) {
            Schema::table('company_reporting_settings', function (Blueprint $table) {
                // Denominator for intensity, captured per reporting year.
                if (!Schema::hasColumn('company_reporting_settings', 'intensity_denominator_type')) {
                    $table->string('intensity_denominator_type', 30)->nullable()->after('scope3_category_policy');
                }
                if (!Schema::hasColumn('company_reporting_settings', 'intensity_denominator_value')) {
                    $table->decimal('intensity_denominator_value', 18, 4)->nullable()->after('intensity_denominator_type');
                }
                if (!Schema::hasColumn('company_reporting_settings', 'intensity_denominator_unit')) {
                    $table->string('intensity_denominator_unit', 40)->nullable()->after('intensity_denominator_value');
                }
                // Cumulative change (% of base-year emissions) that triggers a
                // base-year recalculation. 5% is the common convention.
                if (!Schema::hasColumn('company_reporting_settings', 'recalculation_threshold_percent')) {
                    $table->decimal('recalculation_threshold_percent', 5, 2)->nullable()->default(5)->after('recalculation_policy');
                }
            });
        }

        // Structural changes to the organisational boundary — acquisitions,
        // divestments, new sites, methodology changes. Drives the "not
        // like-for-like" warning and any base-year restatement.
        if (!Schema::hasTable('structural_changes')) {
            Schema::create('structural_changes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedSmallInteger('fiscal_year');
                // acquisition | divestment | new_build | closure | outsourcing
                // | insourcing | methodology | error_correction
                $table->string('change_type', 30);
                $table->string('title');
                $table->text('description')->nullable();
                // Organic growth (new_build) does NOT trigger recalculation under
                // GHG Protocol; transfers of existing activity do.
                $table->boolean('triggers_recalculation')->default(false);
                $table->decimal('emissions_impact_tco2e', 14, 4)->nullable();
                $table->timestamps();

                $table->index(['company_id', 'fiscal_year']);
            });
        }

        // Base-year restatements — mandatory disclosure under GHG Protocol and
        // IFRS S2: what changed, from what to what, and why.
        if (!Schema::hasTable('base_year_restatements')) {
            Schema::create('base_year_restatements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('base_year');
                $table->decimal('previous_baseline_tco2e', 14, 4)->nullable();
                $table->decimal('restated_baseline_tco2e', 14, 4);
                $table->text('reason');
                $table->foreignId('restated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['company_id', 'base_year']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('base_year_restatements');
        Schema::dropIfExists('structural_changes');

        if (Schema::hasTable('company_reporting_settings')) {
            Schema::table('company_reporting_settings', function (Blueprint $table) {
                foreach ([
                    'intensity_denominator_type',
                    'intensity_denominator_value',
                    'intensity_denominator_unit',
                    'recalculation_threshold_percent',
                ] as $column) {
                    if (Schema::hasColumn('company_reporting_settings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
