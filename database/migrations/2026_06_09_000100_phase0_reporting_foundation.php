<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('company_reporting_settings')) {
            Schema::create('company_reporting_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('fiscal_year')->nullable();
                $table->string('organisational_boundary', 20)->default('operational_control');
                $table->string('consolidation_approach', 20)->default('operational_control');
                $table->unsignedSmallInteger('base_year')->nullable();
                $table->text('base_year_rationale')->nullable();
                $table->text('recalculation_policy')->nullable();
                $table->string('gwp_version', 10)->default('AR6');
                $table->json('scope3_category_policy')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'fiscal_year']);
            });
        }

        if (Schema::hasTable('measurement_data')) {
            Schema::table('measurement_data', function (Blueprint $table) {
                if (!Schema::hasColumn('measurement_data', 'scope2_method')) {
                    $table->string('scope2_method', 20)->nullable()->after('supplier_emission_factor');
                }
                if (!Schema::hasColumn('measurement_data', 'is_biogenic')) {
                    $table->boolean('is_biogenic')->default(false)->after('scope2_method');
                }
            });
        }

        $this->seedElectricityMarketBasedFields();
    }

    public function down(): void
    {
        if (Schema::hasTable('measurement_data')) {
            Schema::table('measurement_data', function (Blueprint $table) {
                if (Schema::hasColumn('measurement_data', 'is_biogenic')) {
                    $table->dropColumn('is_biogenic');
                }
                if (Schema::hasColumn('measurement_data', 'scope2_method')) {
                    $table->dropColumn('scope2_method');
                }
            });
        }

        Schema::dropIfExists('company_reporting_settings');
    }

    private function seedElectricityMarketBasedFields(): void
    {
        if (!Schema::hasTable('emission_sources_master') || !Schema::hasTable('emission_source_form_fields')) {
            return;
        }

        $sourceId = DB::table('emission_sources_master')
            ->where('quick_input_slug', 'electricity')
            ->where('scope', 'Scope 2')
            ->value('id');

        if (!$sourceId) {
            return;
        }

        $fields = [
            [
                'field_name' => 'scope2_method',
                'field_type' => 'select',
                'field_label' => 'Scope 2 reporting method',
                'field_options' => json_encode([
                    ['value' => 'location', 'label' => 'Location-based (grid average)'],
                    ['value' => 'market', 'label' => 'Market-based (supplier / RECs)'],
                ]),
                'is_required' => 0,
                'field_order' => 50,
                'help_text' => 'IFRS S2 expects location-based; add market-based when you have supplier data or renewable certificates.',
            ],
            [
                'field_name' => 'supplier_emission_factor',
                'field_type' => 'number',
                'field_label' => 'Supplier emission factor (kg CO₂e/kWh)',
                'field_placeholder' => 'e.g. 0.000 for 100% renewable',
                'is_required' => 0,
                'field_order' => 51,
                'help_text' => 'From your electricity supplier or REC documentation. Used for market-based Scope 2.',
            ],
            [
                'field_name' => 'renewable_percent',
                'field_type' => 'number',
                'field_label' => 'Renewable electricity (%)',
                'field_placeholder' => 'e.g. 100',
                'is_required' => 0,
                'field_order' => 52,
                'help_text' => 'Optional: if supplier factor unknown, we blend grid factor with renewable share.',
            ],
            [
                'field_name' => 'is_biogenic',
                'field_type' => 'checkbox',
                'field_label' => 'Biogenic CO₂ (report separately)',
                'is_required' => 0,
                'field_order' => 53,
                'help_text' => 'Check if this activity is biogenic carbon (IFRS S2 reports separately from fossil GHG).',
            ],
        ];

        // Production DB imports often use explicit IDs without AUTO_INCREMENT on this table.
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
