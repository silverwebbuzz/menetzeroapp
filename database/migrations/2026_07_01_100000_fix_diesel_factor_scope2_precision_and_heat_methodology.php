<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Client corrections (verified against official sources):
 * 1. Scope 1 stationary diesel (litres): 2.6800 kg CO2e/l — DEFRA mobile/stationary diesel
 *    & IPCC 2006 Tier 1 (widely adopted; DEFRA 2024 mineral diesel condensed = 2.6616,
 *    rounded 2.68 used for UAE Scope 1 fuel reporting).
 * 2. Scope 2 subtotal display uses 4 dp (code change in views).
 * 3. Heat/steam/cooling: DEWA SR2023 is grid electricity only (0.3979 tCO2e/MWh per
 *    DEWA Sustainability Report 2023) — not valid for purchased steam; add methodology override fields.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->correctDieselScope1Factors();
        $this->decoupleSteamFromDewaGridFactor();
        $this->seedHeatSteamCoolingMethodologyFields();
    }

    public function down(): void
    {
        // Form fields are left in place on rollback; factor values are not reverted.
    }

    private function correctDieselScope1Factors(): void
    {
        if (!Schema::hasTable('emission_factors')) {
            return;
        }

        $dieselTypes = [
            'Diesel (100% mineral diesel)',
            'Diesel (average biofuel blend)',
        ];

        DB::table('emission_factors')
            ->whereIn('fuel_type', $dieselTypes)
            ->where(function ($q) {
                $q->where('unit', 'litres')
                    ->orWhere('unit', 'liters')
                    ->orWhere('unit', 'like', '%litre%')
                    ->orWhere('unit', 'like', '%liter%');
            })
            ->update([
                'factor_value' => 2.6800,
                'total_co2e_factor' => 2.6800,
                'co2_factor' => 2.6800,
                'source_standard' => 'DEFRA',
                'source_reference' => 'UK Govt GHG Conversion Factors — diesel Scope 1 (2.68 kg CO2e/l; IPCC 2006 Tier 1 / DEFRA rounded)',
                'updated_at' => now(),
            ]);
    }

    /**
     * Steam/heat factors must not reference DEWA grid electricity (SR2023).
     * DEWA 0.3979 tCO2e/MWh applies to purchased grid electricity only.
     */
    private function decoupleSteamFromDewaGridFactor(): void
    {
        if (!Schema::hasTable('emission_factors') || !Schema::hasTable('emission_sources_master')) {
            return;
        }

        $heatSourceId = DB::table('emission_sources_master')
            ->where('quick_input_slug', 'heat-steam-cooling')
            ->value('id');

        if (!$heatSourceId) {
            return;
        }

        $steamHeatFactors = DB::table('emission_factors')
            ->where('emission_source_id', $heatSourceId)
            ->whereIn('fuel_type', ['Steam', 'Heat'])
            ->where(function ($q) {
                $q->where('source_reference', 'like', '%DEWA%')
                    ->orWhere('source_reference', 'like', '%SR2023%')
                    ->orWhere('source_reference', 'like', '%SR 2023%')
                    ->orWhere('description', 'like', '%DEWA%');
            })
            ->get(['id', 'unit']);

        foreach ($steamHeatFactors as $factor) {
            DB::table('emission_factors')->where('id', $factor->id)->update([
                'source_standard' => 'DEFRA',
                'source_reference' => 'DEFRA Heat & Steam — use supplier factor or custom methodology (DEWA grid factor is electricity-only)',
                'description' => 'Default factor — override with supplier-specific or published steam/heat factor via Quick Input methodology field.',
                'updated_at' => now(),
            ]);
        }
    }

    private function seedHeatSteamCoolingMethodologyFields(): void
    {
        if (!Schema::hasTable('emission_sources_master') || !Schema::hasTable('emission_source_form_fields')) {
            return;
        }

        $sourceId = DB::table('emission_sources_master')
            ->where('quick_input_slug', 'heat-steam-cooling')
            ->where('scope', 'Scope 2')
            ->value('id');

        if (!$sourceId) {
            return;
        }

        $fields = [
            [
                'field_name' => 'emission_factor_methodology',
                'field_type' => 'select',
                'field_label' => 'Emission factor methodology',
                'field_options' => json_encode([
                    ['value' => 'default', 'label' => 'System default (supplier / region factor)'],
                    ['value' => 'supplier', 'label' => 'Supplier-provided factor (from bill or EPD)'],
                    ['value' => 'custom', 'label' => 'Custom / other published factor'],
                    ['value' => 'dewa_grid', 'label' => 'DEWA SR2023 grid electricity (kWh only — not for steam)'],
                ]),
                'is_required' => 0,
                'field_order' => 50,
                'help_text' => 'DEWA Sustainability Report 2023 grid factor (0.3979 tCO2e/MWh) applies to purchased electricity, not steam. Choose supplier or custom for steam, heat, and district cooling when you have a better factor.',
            ],
            [
                'field_name' => 'supplier_emission_factor',
                'field_type' => 'number',
                'field_label' => 'Custom emission factor (kg CO₂e per unit)',
                'field_placeholder' => 'e.g. 0.170 for DEFRA district heat (kg CO2e/kWh)',
                'is_required' => 0,
                'field_order' => 51,
                'help_text' => 'Enter when methodology is Supplier or Custom. Must match your quantity unit (kWh or RT).',
            ],
            [
                'field_name' => 'methodology_reference',
                'field_type' => 'text',
                'field_label' => 'Methodology reference',
                'field_placeholder' => 'e.g. Empower EPD 2024, DEFRA Heat & Steam 2024',
                'is_required' => 0,
                'field_order' => 52,
                'help_text' => 'Document or standard used for your custom factor (shown on reports).',
            ],
        ];

        $nextId = ((int) DB::table('emission_source_form_fields')->max('id')) + 1;
        if ($nextId < 1) {
            $nextId = 1;
        }

        foreach ($fields as $field) {
            $exists = DB::table('emission_source_form_fields')
                ->where('emission_source_id', $sourceId)
                ->where('field_name', $field['field_name'])
                ->exists();

            if ($exists) {
                DB::table('emission_source_form_fields')
                    ->where('emission_source_id', $sourceId)
                    ->where('field_name', $field['field_name'])
                    ->update(array_merge($field, ['updated_at' => now()]));

                continue;
            }

            DB::table('emission_source_form_fields')->insert(array_merge($field, [
                'id' => $nextId++,
                'emission_source_id' => $sourceId,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
};
