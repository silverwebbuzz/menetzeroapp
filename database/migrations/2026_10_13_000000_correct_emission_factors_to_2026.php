<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bring the emission reference data up to the current official values, and
 * fix the rows that were wrong regardless of year.
 *
 * Checked on 2026-10-13 against the published source files (the values and the
 * row-by-row references live in database/data/emission_factors_2026.php):
 *
 *   1. UK DESNZ factors. The Scope 3 rows were the 2024 edition and the fuel
 *      rows mostly 2025, all labelled 2024. 447 rows move to the 2026 edition.
 *      Flights alone fall 16-42% (a 2025 methodology change we never picked up).
 *
 *   2. Waste (sources 66, 73). The numbers were real DEFRA values attached to
 *      the wrong treatment: "landfill" carried the combustion figure (6.41
 *      against ~520 kg CO2e/tonne), "combustion" carried the composting figure.
 *      Fixed as part of (1), but called out because it under-reported
 *      landfilled waste by roughly two orders of magnitude.
 *
 *   3. Refrigerants (source 55) and emission_gwp_values. Rows were labelled AR6
 *      while most held AR5 values (SF6 23,500 for 24,300; HFC-125 3,170 for
 *      3,740). All now hold the IPCC AR6 100-year GWP.
 *
 *   4. Grid electricity. Five Scope 3 sources used 0.424 kg/kWh while Scope 2
 *      defaulted to 0.3582, so the same kWh gave two answers depending on
 *      which form it was entered in. They now share the Scope 2 figure.
 *
 * GWP BASIS, because it is easy to get wrong: DESNZ publishes its factors as
 * finished kg CO2e built on AR5 GWPs, and they cannot be re-based. Those rows
 * are therefore marked gwp_version = AR5, which is what they are. Direct-gas
 * rows (refrigerants) are AR6. A row's gwp_version says what the number IS; it
 * is not a setting.
 *
 * LABELS ARE NOT CHANGED, with one exception. fuel_type / vehicle_type are the
 * values the Quick Input selects post back, so renaming them would break factor
 * lookup. DESNZ renamed some categories in 2026 (e.g. "All rigids" is now
 * "Average non-refrigerated rigids"); the new official name is recorded in each
 * row's source_reference instead. The exception is the typo "HFC-43-I0mee"
 * (capital I for 1), fixed in both the factor and the form-field option list.
 *
 * NOT CHANGED, and still unverified against an official source: the UAE grid
 * figures themselves, the spend-based (AED) factors, the PCAF investment
 * factors, the floor-area intensity, district cooling 0.549 kg/kWh, and the six
 * per-vehicle-km bus rows. See their source_reference before relying on them.
 *
 * Safe to run on a database whose ids differ from the dump this was built
 * from: every update is keyed on id AND emission_source_id (or gas_code), so a
 * row that is not the one expected is left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        $data = $this->data();
        $now = now();

        DB::transaction(function () use ($data, $now) {
            foreach ($data['desnz'] as [$id, $sourceId, $total, $co2, $ch4, $n2o, $reference]) {
                DB::table('emission_factors')
                    ->where('id', $id)
                    ->where('emission_source_id', $sourceId)
                    ->update([
                        'factor_value' => $total,
                        'total_co2e_factor' => $total,
                        'co2_factor' => $co2,
                        'ch4_factor' => $ch4,
                        'n2o_factor' => $n2o,
                        'valid_from' => 2026,
                        'gwp_version' => 'AR5',
                        'source_standard' => 'DEFRA',
                        'source_reference' => $reference,
                        // Free-text fields quote the edition year.
                        'calculation_method' => DB::raw("REPLACE(REPLACE(calculation_method, '2024', '2026'), '2025', '2026')"),
                        'description' => DB::raw("REPLACE(description, 'DEFRA 2024', 'DESNZ 2026')"),
                        'updated_at' => $now,
                    ]);
            }

            foreach ($data['refrigerants'] as [$id, $gwp, $reference, $version]) {
                DB::table('emission_factors')
                    ->where('id', $id)
                    ->where('emission_source_id', 55)
                    ->update([
                        'factor_value' => $gwp,
                        'total_co2e_factor' => $gwp,
                        'gwp_version' => $version,
                        'source_reference' => $reference,
                        'updated_at' => $now,
                    ]);
            }

            foreach ($data['gwp'] as [$id, $gasCode, $gwp]) {
                DB::table('emission_gwp_values')
                    ->where('id', $id)
                    ->where('gas_code', $gasCode)
                    ->where('gwp_version', 'AR6')
                    ->update([
                        'gwp_100_year' => $gwp,
                        'notes' => 'IPCC AR6 WG1 Table 7.SM.7, 100-year GWP, as listed in GHG Protocol "Global Warming Potential Values" (August 2024).',
                        'updated_at' => $now,
                    ]);
            }

            // PFC-318 is the cyclic c-C4F8; the row was captioned C4F10, which
            // is a different gas (PFC-31-10) with a different GWP.
            DB::table('emission_gwp_values')
                ->where('gas_code', 'PFC318')
                ->where('gwp_version', 'AR6')
                ->update(['gas_name' => 'c-C₄F₈ (PFC-318)']);

            foreach ($data['electricity'] as [$id, $factor, $reference]) {
                DB::table('emission_factors')
                    ->where('id', $id)
                    ->where('unit', 'kWh')
                    ->update([
                        'factor_value' => $factor,
                        'total_co2e_factor' => $factor,
                        'source_reference' => $reference,
                        // The T&D row spells the grid figure out in its method.
                        'calculation_method' => DB::raw("REPLACE(calculation_method, '0.424', '0.3582')"),
                        'updated_at' => $now,
                    ]);
            }

            // These three carry an IEA reference but were tagged MOCCAE, which
            // the report prints as the methodology. The ministry did not
            // publish this figure; 'UAE' is the closest value the enum allows.
            DB::table('emission_factors')
                ->whereIn('id', [10003, 10004, 10005])
                ->where('emission_source_id', 57)
                ->where('source_standard', 'MOCCAE')
                ->update(['source_standard' => 'UAE', 'updated_at' => $now]);

            $this->renameRefrigerant('HFC-43-I0mee', 'HFC-43-10mee');
        });
    }

    public function down(): void
    {
        $data = $this->data();

        DB::transaction(function () use ($data) {
            foreach ($data['desnz'] as [$id, $sourceId, , , , , , $old]) {
                [$factorValue, $total, $co2, $ch4, $n2o, $validFrom, $gwpVersion, $reference] = $old;

                DB::table('emission_factors')
                    ->where('id', $id)
                    ->where('emission_source_id', $sourceId)
                    ->update([
                        'factor_value' => $factorValue,
                        'total_co2e_factor' => $total,
                        'co2_factor' => $co2,
                        'ch4_factor' => $ch4,
                        'n2o_factor' => $n2o,
                        'valid_from' => $validFrom,
                        'gwp_version' => $gwpVersion,
                        'source_reference' => $reference,
                        // up() folds both 2024 and 2025 into 2026, so the
                        // edition year in these two free-text fields is not
                        // recoverable; every row said 2024 before.
                        'calculation_method' => DB::raw("REPLACE(calculation_method, '2026', '2024')"),
                        'description' => DB::raw("REPLACE(description, 'DESNZ 2026', 'DEFRA 2024')"),
                    ]);
            }

            foreach ($data['refrigerants'] as [$id, , , , $old]) {
                [$factorValue, $total, $reference, $gwpVersion] = $old;

                DB::table('emission_factors')
                    ->where('id', $id)
                    ->where('emission_source_id', 55)
                    ->update([
                        'factor_value' => $factorValue,
                        'total_co2e_factor' => $total,
                        'source_reference' => $reference,
                        'gwp_version' => $gwpVersion,
                    ]);
            }

            foreach ($data['gwp'] as [$id, $gasCode, , $old]) {
                DB::table('emission_gwp_values')
                    ->where('id', $id)
                    ->where('gas_code', $gasCode)
                    ->update(['gwp_100_year' => $old, 'notes' => null]);
            }

            DB::table('emission_gwp_values')
                ->where('gas_code', 'PFC318')
                ->where('gwp_version', 'AR6')
                ->update(['gas_name' => 'C₄F₁₀ (PFC-318)']);

            foreach ($data['electricity'] as [$id, , , $old]) {
                [$factorValue, $total, $reference] = $old;

                DB::table('emission_factors')
                    ->where('id', $id)
                    ->where('unit', 'kWh')
                    ->update([
                        'factor_value' => $factorValue,
                        'total_co2e_factor' => $total,
                        'source_reference' => $reference,
                        'calculation_method' => DB::raw("REPLACE(calculation_method, '0.3582', '0.424')"),
                    ]);
            }

            DB::table('emission_factors')
                ->whereIn('id', [10003, 10004, 10005])
                ->where('emission_source_id', 57)
                ->where('source_standard', 'UAE')
                ->update(['source_standard' => 'MOCCAE']);

            $this->renameRefrigerant('HFC-43-10mee', 'HFC-43-I0mee');
        });
    }

    /**
     * The refrigerant name is both the factor's fuel_type and an option value
     * in the Quick Input select, so the two must change together or the form
     * posts a name no factor matches.
     */
    private function renameRefrigerant(string $from, string $to): void
    {
        DB::table('emission_factors')
            ->where('emission_source_id', 55)
            ->where('fuel_type', $from)
            ->update(['fuel_type' => $to]);

        DB::table('emission_source_form_fields')
            ->where('emission_source_id', 55)
            ->where('field_options', 'like', '%'.$from.'%')
            ->update(['field_options' => DB::raw('REPLACE(field_options, '.DB::getPdo()->quote($from).', '.DB::getPdo()->quote($to).')')]);
    }

    private function data(): array
    {
        return require database_path('data/emission_factors_2026.php');
    }
};
