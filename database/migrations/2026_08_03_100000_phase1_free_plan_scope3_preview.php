<?php

use App\Data\ConsultantAgencyPlanMatrix;
use App\Data\PlanEntitlementDefaults;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Free product rules (PRICING_AND_PLAN_MAJOR_CHANGES.md):
 * Scope 1 & 2 full, Scope 3 all categories with 1 entry each, 2 users.
 * Sync consultant_trial description/slot metadata from matrix.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subscription_plans')) {
            return;
        }

        $free = PlanEntitlementDefaults::forPlanCode('client_free');
        if ($free) {
            SubscriptionPlan::query()
                ->where('plan_code', 'client_free')
                ->update([
                    'plan_name' => $free['plan_name'],
                    'description' => $free['description'],
                    'limits' => $free['limits'],
                    'entitlements' => $free['entitlements'],
                    'features' => $free['features'],
                ]);
        }

        $trial = ConsultantAgencyPlanMatrix::forPlanCode(ConsultantAgencyPlanMatrix::FREE_TRIAL_CODE);
        if ($trial) {
            SubscriptionPlan::query()
                ->where('plan_code', ConsultantAgencyPlanMatrix::FREE_TRIAL_CODE)
                ->update([
                    'description' => $trial['description'],
                    'limits' => $trial['limits'],
                    'entitlements' => $trial['entitlements'],
                    'features' => $trial['features'],
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('subscription_plans')) {
            return;
        }

        // Restore prior Free shape (Scope 3 locked, 1 user) if rolling back Phase 1 only.
        SubscriptionPlan::query()
            ->where('plan_code', 'client_free')
            ->update([
                'description' => 'Try Scope 1 & 2 and all disclosure forms — upgrade to export reports.',
                'limits' => [
                    'locations' => 1,
                    'users' => 1,
                    'documents' => 10,
                    'scope3_records_per_form' => 0,
                    'annual_report_pdf' => 0,
                    'historical_years' => 1,
                ],
                'entitlements' => [
                    'scope3_mode' => 'locked',
                    'bulk_import' => false,
                    'bulk_export' => false,
                    'help_level' => 'basic',
                    'disclosures' => ['access' => true, 'export' => false],
                    'exports' => [],
                    'consultant_directory' => 'teaser',
                    'export_regen' => 'none',
                ],
            ]);
    }
};
