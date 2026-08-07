<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remove measurement_data rows written against deprecated emission sources, then
 * recalculate the affected measurement totals.
 *
 * Background
 * ----------
 * Migration 2026_06_08_001300 retired source ID 1 ("Onsite Wastewater Treatment")
 * by setting is_active = 0 / is_quick_input = 0 and clearing its subcategory —
 * Scope 3 wastewater is covered by ID 36 instead.
 *
 * ConsultantFullDemoSeeder then selected Scope 3 sources with
 * `where('scope', 'Scope 3')->limit(6)` and no is_quick_input / is_active filter,
 * so ID 1 (lowest ID) was picked up and seeded across the demo companies. Because
 * no emission factor exists for it, the seeder's `?: 1500` fallback fabricated a
 * flat 1500 kg CO2e per row with emission_factor_id = NULL.
 *
 * Why that matters: measurements.scope_3_co2e sums every Scope 3 row, but the
 * report's category breakdown (GhgReportService::buildScope3Categories) groups by
 * emission_sources_master.subcategory and the coverage matrix maps by
 * quick_input_slug. Both are NULL on ID 1, so those emissions counted toward the
 * headline Scope 3 figure while being absent from all 15 GHG Protocol categories —
 * the headline no longer reconciled with the breakdown beneath it.
 *
 * The seeder query has been fixed alongside this migration; this cleans the rows
 * already written to the database.
 *
 * Scope of deletion — only rows that are ALL of:
 *   - attached to an inactive, non-quick-input emission source, AND
 *   - carrying no emission_factor_id (no auditable calculation behind them), AND
 *   - carrying a non-zero calculated_co2e (they actually distort a total)
 *
 * The conditions are deliberately narrow. Any genuine entry created through Quick
 * Input carries an emission_factor_id and is left untouched.
 *
 * The third condition matters: measurement 2 (a real company, not demo data) holds
 * four legacy key/value rows on deprecated source 8 — field_name = quantity /
 * supplier / tariff_type / billing_period — with NULL quantity and NULL co2e. They
 * contribute nothing to any total, but the measurement's cached total_co2e (248.888)
 * predates them and has no surviving rows behind it. Deleting them would recalculate
 * that real company's stored total to zero, so they are left in place; they are inert
 * and the report's Uncategorised bucket does not pick them up (co2e is NULL).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('measurement_data') || !Schema::hasTable('emission_sources_master')) {
            return;
        }

        $deprecatedSourceIds = DB::table('emission_sources_master')
            ->where('is_active', 0)
            ->where('is_quick_input', 0)
            ->pluck('id');

        if ($deprecatedSourceIds->isEmpty()) {
            return;
        }

        $doomed = DB::table('measurement_data')
            ->whereIn('emission_source_id', $deprecatedSourceIds)
            ->whereNull('emission_factor_id')
            ->where('calculated_co2e', '>', 0)
            ->get(['id', 'measurement_id']);

        if ($doomed->isEmpty()) {
            return;
        }

        $affectedMeasurementIds = $doomed->pluck('measurement_id')->unique()->filter()->values();

        DB::table('measurement_data')->whereIn('id', $doomed->pluck('id'))->delete();

        $this->recalculateMeasurementTotals($affectedMeasurementIds);
    }

    /**
     * Recompute cached scope totals from the surviving measurement_data rows.
     *
     * Mirrors MeasurementService::updateMeasurementTotals, but written as plain
     * queries so the migration stays correct even if that service later changes.
     *
     * @param  \Illuminate\Support\Collection<int, mixed>  $measurementIds
     */
    private function recalculateMeasurementTotals($measurementIds): void
    {
        if (!Schema::hasTable('measurements')) {
            return;
        }

        $columns = [
            'total_co2e' => Schema::hasColumn('measurements', 'total_co2e'),
            'scope_1_co2e' => Schema::hasColumn('measurements', 'scope_1_co2e'),
            'scope_2_co2e' => Schema::hasColumn('measurements', 'scope_2_co2e'),
            'scope_3_co2e' => Schema::hasColumn('measurements', 'scope_3_co2e'),
        ];

        if (!in_array(true, $columns, true)) {
            return;
        }

        foreach ($measurementIds->chunk(200) as $chunk) {
            $totals = DB::table('measurement_data')
                ->selectRaw('measurement_id')
                ->selectRaw('COALESCE(SUM(calculated_co2e), 0) as total_co2e')
                ->selectRaw("COALESCE(SUM(CASE WHEN scope = 'Scope 1' THEN calculated_co2e ELSE 0 END), 0) as scope_1_co2e")
                ->selectRaw("COALESCE(SUM(CASE WHEN scope = 'Scope 2' THEN calculated_co2e ELSE 0 END), 0) as scope_2_co2e")
                ->selectRaw("COALESCE(SUM(CASE WHEN scope = 'Scope 3' THEN calculated_co2e ELSE 0 END), 0) as scope_3_co2e")
                ->whereIn('measurement_id', $chunk)
                ->groupBy('measurement_id')
                ->get()
                ->keyBy('measurement_id');

            foreach ($chunk as $measurementId) {
                // A measurement whose rows were all deleted has no aggregate row — reset it to zero.
                $row = $totals->get($measurementId);

                $update = [];
                if ($columns['total_co2e']) {
                    $update['total_co2e'] = $row->total_co2e ?? 0;
                }
                if ($columns['scope_1_co2e']) {
                    $update['scope_1_co2e'] = $row->scope_1_co2e ?? 0;
                }
                if ($columns['scope_2_co2e']) {
                    $update['scope_2_co2e'] = $row->scope_2_co2e ?? 0;
                }
                if ($columns['scope_3_co2e']) {
                    $update['scope_3_co2e'] = $row->scope_3_co2e ?? 0;
                }

                if (Schema::hasColumn('measurements', 'co2e_calculated_at')) {
                    $update['co2e_calculated_at'] = now();
                }

                DB::table('measurements')->where('id', $measurementId)->update($update);
            }
        }
    }

    public function down(): void
    {
        // Deleted rows were fabricated demo entries with no emission factor behind
        // them — there is nothing meaningful to restore. Re-running the demo seeder
        // regenerates demo data against valid sources.
    }
};
