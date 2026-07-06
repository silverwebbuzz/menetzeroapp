<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('material_sustainability_topics')) {
            Schema::table('material_sustainability_topics', function (Blueprint $table) {
                if (!Schema::hasColumn('material_sustainability_topics', 'impact_materiality')) {
                    $table->string('impact_materiality', 20)->nullable()->after('rationale');
                }
                if (!Schema::hasColumn('material_sustainability_topics', 'financial_materiality')) {
                    $table->string('financial_materiality', 20)->nullable()->after('impact_materiality');
                }
            });
        }

        if (!Schema::hasTable('stakeholder_engagements')) {
            Schema::create('stakeholder_engagements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('fiscal_year');
                $table->string('stakeholder_group');
                $table->string('engagement_method', 100)->nullable();
                $table->string('frequency', 50)->nullable();
                $table->text('topics_discussed')->nullable();
                $table->text('outcomes')->nullable();
                $table->date('last_engaged_at')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'fiscal_year']);
            });
        }

        if (!Schema::hasTable('esg_sustainability_targets')) {
            Schema::create('esg_sustainability_targets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('target_category', 50);
                $table->string('metric_label')->nullable();
                $table->decimal('baseline_value', 20, 4)->nullable();
                $table->decimal('target_value', 20, 4)->nullable();
                $table->string('unit', 32)->nullable();
                $table->unsignedSmallInteger('base_year')->nullable();
                $table->unsignedSmallInteger('target_year');
                $table->string('status', 20)->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'target_category']);
            });
        }

        if (!Schema::hasTable('supply_chain_suppliers')) {
            Schema::create('supply_chain_suppliers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('fiscal_year');
                $table->string('supplier_name');
                $table->string('category', 50)->default('goods');
                $table->decimal('spend_aed', 16, 2)->nullable();
                $table->string('country', 100)->nullable();
                $table->unsignedTinyInteger('scope3_category')->default(1);
                $table->string('screening_status', 30)->default('not_screened');
                $table->boolean('human_rights_assessed')->default(false);
                $table->boolean('environmental_assessed')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'fiscal_year']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_chain_suppliers');
        Schema::dropIfExists('esg_sustainability_targets');
        Schema::dropIfExists('stakeholder_engagements');

        if (Schema::hasTable('material_sustainability_topics')) {
            Schema::table('material_sustainability_topics', function (Blueprint $table) {
                if (Schema::hasColumn('material_sustainability_topics', 'financial_materiality')) {
                    $table->dropColumn('financial_materiality');
                }
                if (Schema::hasColumn('material_sustainability_topics', 'impact_materiality')) {
                    $table->dropColumn('impact_materiality');
                }
            });
        }
    }
};
