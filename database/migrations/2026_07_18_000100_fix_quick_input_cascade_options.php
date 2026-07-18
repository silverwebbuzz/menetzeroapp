<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Quick Input dropdowns were empty / incomplete because:
 * 1) UAE 2026 factor cleanup deactivated DEFRA rows that still powered form cascades
 * 2) Vehicle form field dependencies pointed at non-existent fields (vehicle_size)
 * 3) Static form options listed categories/types that no longer had active factors
 * 4) Fuel-amount vehicle mode used fuel names that no longer exist as active factors
 *
 * This migration restores cascade-usable factors (region retagged), aligns form options
 * with active factors, and fixes vehicle field dependencies.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('emission_factors') || !Schema::hasTable('emission_source_form_fields')) {
            return;
        }

        $now = now();

        // ------------------------------------------------------------------
        // 1) Restore cascade coverage: reactivate DEFRA rows with correct region
        //    UAE-specific IPCC/UAE factors remain preferred via is_default/priority.
        // ------------------------------------------------------------------
        DB::table('emission_factors')
            ->whereIn('emission_source_id', [53, 54])
            ->where('source_standard', 'DEFRA')
            ->where('is_active', 0)
            ->update([
                'is_active' => 1,
                'is_default' => 0,
                'region' => 'UK',
                'updated_at' => $now,
            ]);

        // Natural gas: keep UAE IPCC defaults; reactivate DEFRA as UK for unit variety
        DB::table('emission_factors')
            ->where('emission_source_id', 52)
            ->where('source_standard', 'DEFRA')
            ->where('is_active', 0)
            ->update([
                'is_active' => 1,
                'is_default' => 0,
                'region' => 'UK',
                'updated_at' => $now,
            ]);

        // Heat/steam/cooling: reactivate USEPA as Global fallback if UAE rows missing a type
        DB::table('emission_factors')
            ->where('emission_source_id', 58)
            ->where('source_standard', 'USEPA')
            ->where('is_active', 0)
            ->update([
                'is_active' => 1,
                'is_default' => 0,
                'region' => 'Global',
                'updated_at' => $now,
            ]);

        // Ensure fuel-amount vehicle factors exist under names the form can select
        $this->ensureVehicleFuelAmountFactors($now);

        // Ensure common UAE car distance factors exist
        $this->ensureUaeCarDistanceFactors($now);

        // ------------------------------------------------------------------
        // 2) Fix vehicle form field dependencies / ownership visibility
        // ------------------------------------------------------------------
        $vehicleSourceId = DB::table('emission_sources_master')
            ->where('quick_input_slug', 'vehicle')
            ->value('id');

        if ($vehicleSourceId) {
            DB::table('emission_source_form_fields')
                ->where('emission_source_id', $vehicleSourceId)
                ->where('field_name', 'vehicle_ownership_type')
                ->update([
                    'depends_on_field' => null,
                    'updated_at' => $now,
                ]);

            DB::table('emission_source_form_fields')
                ->where('emission_source_id', $vehicleSourceId)
                ->where('field_name', 'vehicle_category')
                ->update([
                    'depends_on_field' => null,
                    'updated_at' => $now,
                ]);

            DB::table('emission_source_form_fields')
                ->where('emission_source_id', $vehicleSourceId)
                ->where('field_name', 'vehicle_type')
                ->update([
                    'depends_on_field' => 'vehicle_category',
                    'updated_at' => $now,
                ]);

            // Bug: depended on non-existent "vehicle_size"
            DB::table('emission_source_form_fields')
                ->where('emission_source_id', $vehicleSourceId)
                ->where('field_name', 'vehicle_fuel_type')
                ->update([
                    'depends_on_field' => 'vehicle_type',
                    'updated_at' => $now,
                ]);

            DB::table('emission_source_form_fields')
                ->where('emission_source_id', $vehicleSourceId)
                ->where('field_name', 'unit_of_measure')
                ->update([
                    'depends_on_field' => 'vehicle_fuel_type',
                    'updated_at' => $now,
                ]);

            DB::table('emission_source_form_fields')
                ->where('emission_source_id', $vehicleSourceId)
                ->where('field_name', 'distance')
                ->update([
                    'depends_on_field' => 'unit_of_measure',
                    'updated_at' => $now,
                ]);

            $this->syncVehicleFormOptions((int) $vehicleSourceId, $now);
        }

        // ------------------------------------------------------------------
        // 3) Sync fuel / process / heat / natural-gas options from active factors
        // ------------------------------------------------------------------
        $this->syncFuelCategoryOptions($now);
        $this->syncProcessTypeOptions($now);
        $this->syncHeatEnergyTypeOptions($now);
        $this->syncNaturalGasUnits($now);
        $this->syncRefrigerantOptions($now);
    }

    public function down(): void
    {
        // Non-destructive: leave restored factors and corrected dependencies in place.
    }

    private function ensureVehicleFuelAmountFactors($now): void
    {
        $vehicleSourceId = DB::table('emission_sources_master')
            ->where('quick_input_slug', 'vehicle')
            ->value('id');

        if (!$vehicleSourceId) {
            return;
        }

        $rows = [
            [
                'fuel_type' => 'Diesel (average biofuel blend)',
                'factor_value' => 2.680000,
                'co2_factor' => 2.628180,
                'ch4_factor' => 0.000290,
                'n2o_factor' => 0.033080,
                'total_co2e_factor' => 2.662567,
                'description' => 'Diesel vehicle — fuel-amount entry (litres).',
            ],
            [
                'fuel_type' => 'Petrol (average biofuel blend)',
                'factor_value' => 2.390000,
                'co2_factor' => 2.325670,
                'ch4_factor' => 0.008200,
                'n2o_factor' => 0.005970,
                'total_co2e_factor' => 2.340547,
                'description' => 'Petrol vehicle — fuel-amount entry (litres).',
            ],
            [
                'fuel_type' => 'Diesel (100% mineral diesel)',
                'factor_value' => 2.680000,
                'co2_factor' => 2.628180,
                'ch4_factor' => 0.000290,
                'n2o_factor' => 0.033080,
                'total_co2e_factor' => 2.662567,
                'description' => 'Diesel vehicle — fuel-amount entry (litres).',
            ],
            [
                'fuel_type' => 'Petrol (100% mineral petrol)',
                'factor_value' => 2.390000,
                'co2_factor' => 2.325670,
                'ch4_factor' => 0.008200,
                'n2o_factor' => 0.005970,
                'total_co2e_factor' => 2.340547,
                'description' => 'Petrol vehicle — fuel-amount entry (litres).',
            ],
        ];

        $nextId = max(10100, ((int) DB::table('emission_factors')->max('id')) + 1);

        foreach ($rows as $row) {
            $exists = DB::table('emission_factors')
                ->where('emission_source_id', $vehicleSourceId)
                ->where('fuel_type', $row['fuel_type'])
                ->where('unit', 'litres')
                ->where('is_active', 1)
                ->exists();

            if ($exists) {
                continue;
            }

            // Prefer reactivating a matching inactive row
            $updated = DB::table('emission_factors')
                ->where('emission_source_id', $vehicleSourceId)
                ->where('fuel_type', $row['fuel_type'])
                ->where('unit', 'litres')
                ->update([
                    'is_active' => 1,
                    'is_default' => 0,
                    'region' => 'UAE',
                    'factor_value' => $row['factor_value'],
                    'total_co2e_factor' => $row['total_co2e_factor'],
                    'updated_at' => $now,
                ]);

            if ($updated) {
                continue;
            }

            DB::table('emission_factors')->insert([
                'id' => $nextId++,
                'emission_source_id' => $vehicleSourceId,
                'factor_value' => $row['factor_value'],
                'unit' => 'litres',
                'calculation_method' => 'Fuel-based (Tier 1)',
                'region' => 'UAE',
                'valid_from' => '2024',
                'valid_to' => null,
                'is_active' => 1,
                'description' => $row['description'],
                'calculation_formula' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'fuel_type' => $row['fuel_type'],
                'fuel_category' => 'LDV',
                'vehicle_category' => null,
                'vehicle_type' => null,
                'vehicle_size' => null,
                'co2_factor' => $row['co2_factor'],
                'ch4_factor' => $row['ch4_factor'],
                'n2o_factor' => $row['n2o_factor'],
                'total_co2e_factor' => $row['total_co2e_factor'],
                'source_standard' => 'IPCC',
                'source_reference' => 'UAE / IPCC fuel factors for vehicle fuel-amount mode',
                'gwp_version' => 'AR6',
                'is_default' => 0,
                'priority' => 90,
            ]);
        }
    }

    private function ensureUaeCarDistanceFactors($now): void
    {
        $vehicleSourceId = DB::table('emission_sources_master')
            ->where('quick_input_slug', 'vehicle')
            ->value('id');

        if (!$vehicleSourceId) {
            return;
        }

        $matrix = [
            ['Average car', 'Petrol', 0.207900],
            ['Average car', 'Diesel', 0.165330],
            ['Small car', 'Petrol', 0.170000],
            ['Small car', 'Diesel', 0.140000],
            ['Medium car', 'Petrol', 0.207900],
            ['Medium car', 'Diesel', 0.165330],
            ['Large car', 'Petrol', 0.260000],
            ['Large car', 'Diesel', 0.210000],
        ];

        $nextId = max(10200, ((int) DB::table('emission_factors')->max('id')) + 1);

        foreach ($matrix as [$vehicleType, $fuelType, $factor]) {
            $exists = DB::table('emission_factors')
                ->where('emission_source_id', $vehicleSourceId)
                ->where('vehicle_category', 'Cars (by size)')
                ->where('vehicle_type', $vehicleType)
                ->where('fuel_type', $fuelType)
                ->where('unit', 'km')
                ->where('is_active', 1)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('emission_factors')->insert([
                'id' => $nextId++,
                'emission_source_id' => $vehicleSourceId,
                'factor_value' => $factor,
                'unit' => 'km',
                'calculation_method' => 'Distance-based (Tier 1)',
                'region' => 'UAE',
                'valid_from' => '2024',
                'valid_to' => null,
                'is_active' => 1,
                'description' => "{$fuelType} {$vehicleType} — UAE distance factor.",
                'calculation_formula' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'fuel_type' => $fuelType,
                'fuel_category' => null,
                'vehicle_category' => 'Cars (by size)',
                'vehicle_type' => $vehicleType,
                'vehicle_size' => null,
                'co2_factor' => null,
                'ch4_factor' => null,
                'n2o_factor' => null,
                'total_co2e_factor' => $factor,
                'source_standard' => 'IPCC',
                'source_reference' => 'Emirates Nature-WWF UAE GHG Guide 2020 / IPCC-aligned car averages',
                'gwp_version' => 'AR6',
                'is_default' => $vehicleType === 'Average car' ? 1 : 0,
                'priority' => 100,
            ]);
        }
    }

    private function syncVehicleFormOptions(int $vehicleSourceId, $now): void
    {
        $categories = DB::table('emission_factors')
            ->where('emission_source_id', $vehicleSourceId)
            ->where('is_active', 1)
            ->whereNotNull('vehicle_category')
            ->where('vehicle_category', '!=', '')
            ->distinct()
            ->orderBy('vehicle_category')
            ->pluck('vehicle_category')
            ->values()
            ->all();

        if (!empty($categories)) {
            $this->updateFieldOptions($vehicleSourceId, 'vehicle_category', $categories, $now);
        }

        // Seed vehicle_type with Cars sizes as a sensible default list;
        // runtime JS still loads the category-specific set from the API.
        $carTypes = DB::table('emission_factors')
            ->where('emission_source_id', $vehicleSourceId)
            ->where('is_active', 1)
            ->where('vehicle_category', 'Cars (by size)')
            ->whereNotNull('vehicle_type')
            ->distinct()
            ->orderBy('vehicle_type')
            ->pluck('vehicle_type')
            ->values()
            ->all();

        if (!empty($carTypes)) {
            $this->updateFieldOptions($vehicleSourceId, 'vehicle_type', $carTypes, $now);
        }

        $fuels = DB::table('emission_factors')
            ->where('emission_source_id', $vehicleSourceId)
            ->where('is_active', 1)
            ->whereNotNull('fuel_type')
            ->where('fuel_type', '!=', '')
            ->whereNotNull('vehicle_category')
            ->distinct()
            ->orderBy('fuel_type')
            ->pluck('fuel_type')
            ->values()
            ->all();

        if (!empty($fuels)) {
            $this->updateFieldOptions($vehicleSourceId, 'vehicle_fuel_type', $fuels, $now);
        }

        $units = DB::table('emission_factors')
            ->where('emission_source_id', $vehicleSourceId)
            ->where('is_active', 1)
            ->whereNotNull('unit')
            ->whereIn('unit', ['km', 'miles', 'litres', 'tonnes'])
            ->distinct()
            ->orderBy('unit')
            ->pluck('unit')
            ->values()
            ->all();

        if (!empty($units)) {
            $this->updateFieldOptions($vehicleSourceId, 'unit_of_measure', $units, $now);
        }
    }

    private function syncFuelCategoryOptions($now): void
    {
        $sourceId = DB::table('emission_sources_master')
            ->where('quick_input_slug', 'fuel')
            ->value('id');

        if (!$sourceId) {
            return;
        }

        $categories = DB::table('emission_factors')
            ->where('emission_source_id', $sourceId)
            ->where('is_active', 1)
            ->whereNotNull('fuel_category')
            ->where('fuel_category', '!=', '')
            ->distinct()
            ->orderBy('fuel_category')
            ->pluck('fuel_category')
            ->values()
            ->all();

        if (!empty($categories)) {
            $this->updateFieldOptions((int) $sourceId, 'fuel_category', $categories, $now);
        }
    }

    private function syncProcessTypeOptions($now): void
    {
        $sourceId = DB::table('emission_sources_master')
            ->where('quick_input_slug', 'process')
            ->value('id');

        if (!$sourceId) {
            return;
        }

        $types = DB::table('emission_factors')
            ->where('emission_source_id', $sourceId)
            ->where('is_active', 1)
            ->whereNotNull('fuel_type')
            ->where('fuel_type', '!=', '')
            ->distinct()
            ->orderBy('fuel_type')
            ->pluck('fuel_type')
            ->values()
            ->all();

        if (!empty($types)) {
            $this->updateFieldOptions((int) $sourceId, 'process_type', $types, $now);
        }
    }

    private function syncHeatEnergyTypeOptions($now): void
    {
        $sourceId = DB::table('emission_sources_master')
            ->where('quick_input_slug', 'heat-steam-cooling')
            ->value('id');

        if (!$sourceId) {
            return;
        }

        $types = DB::table('emission_factors')
            ->where('emission_source_id', $sourceId)
            ->where('is_active', 1)
            ->whereNotNull('fuel_type')
            ->where('fuel_type', '!=', '')
            ->distinct()
            ->orderBy('fuel_type')
            ->pluck('fuel_type')
            ->values()
            ->all();

        if (!empty($types)) {
            $labels = [
                'Heat' => 'Heat (District Heating)',
                'Steam' => 'Steam',
                'Cooling' => 'Cooling (District Cooling)',
            ];
            $options = array_map(
                fn ($value) => ['value' => $value, 'label' => $labels[$value] ?? $value],
                $types
            );

            DB::table('emission_source_form_fields')
                ->where('emission_source_id', $sourceId)
                ->where('field_name', 'energy_type')
                ->update([
                    'field_options' => json_encode(array_values($options)),
                    'updated_at' => $now,
                ]);
        }

        $units = DB::table('emission_factors')
            ->where('emission_source_id', $sourceId)
            ->where('is_active', 1)
            ->whereNotNull('unit')
            ->distinct()
            ->orderBy('unit')
            ->pluck('unit')
            ->values()
            ->all();

        if (!empty($units)) {
            $this->updateFieldOptions((int) $sourceId, 'unit_of_measure', $units, $now);
        }
    }

    private function syncNaturalGasUnits($now): void
    {
        $sourceId = DB::table('emission_sources_master')
            ->where('quick_input_slug', 'natural-gas')
            ->value('id');

        if (!$sourceId) {
            return;
        }

        $units = DB::table('emission_factors')
            ->where('emission_source_id', $sourceId)
            ->where('is_active', 1)
            ->whereNotNull('unit')
            ->distinct()
            ->orderBy('unit')
            ->pluck('unit')
            ->values()
            ->all();

        if (!empty($units)) {
            $this->updateFieldOptions((int) $sourceId, 'unit_of_measure', $units, $now);
        }
    }

    private function syncRefrigerantOptions($now): void
    {
        $sourceId = DB::table('emission_sources_master')
            ->where('quick_input_slug', 'refrigerants')
            ->value('id');

        if (!$sourceId) {
            return;
        }

        $types = DB::table('emission_factors')
            ->where('emission_source_id', $sourceId)
            ->where('is_active', 1)
            ->whereNotNull('fuel_type')
            ->where('fuel_type', '!=', '')
            ->distinct()
            ->orderBy('fuel_type')
            ->pluck('fuel_type')
            ->values()
            ->all();

        if (!empty($types)) {
            $this->updateFieldOptions((int) $sourceId, 'refrigerant_type', $types, $now);
        }
    }

    private function updateFieldOptions(int $sourceId, string $fieldName, array $values, $now): void
    {
        $options = array_map(
            fn ($value) => ['value' => $value, 'label' => $value],
            $values
        );

        DB::table('emission_source_form_fields')
            ->where('emission_source_id', $sourceId)
            ->where('field_name', $fieldName)
            ->update([
                'field_options' => json_encode(array_values($options)),
                'updated_at' => $now,
            ]);
    }
};
