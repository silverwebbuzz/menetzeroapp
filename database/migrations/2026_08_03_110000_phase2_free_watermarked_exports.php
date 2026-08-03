<?php

use App\Data\PlanEntitlementDefaults;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 — Free watermarked trial exports (GHG, MOCCAE, Excel, IEQT).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subscription_plans')) {
            return;
        }

        $free = PlanEntitlementDefaults::forPlanCode('client_free');
        if (! $free) {
            return;
        }

        SubscriptionPlan::query()
            ->where('plan_code', 'client_free')
            ->update([
                'description' => $free['description'],
                'limits' => $free['limits'],
                'entitlements' => $free['entitlements'],
                'features' => $free['features'],
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('subscription_plans')) {
            return;
        }

        // Roll back to Phase 1 Free (S3 preview, no trial downloads).
        SubscriptionPlan::query()
            ->where('plan_code', 'client_free')
            ->update([
                'description' => 'Scope 1 & 2 full, Scope 3 (1 entry per category), disclosure previews. Request a package for clean exports.',
                'limits' => [
                    'locations' => 1,
                    'users' => 2,
                    'documents' => 10,
                    'scope3_records_per_form' => 1,
                    'annual_report_pdf' => 0,
                    'historical_years' => 1,
                ],
                'entitlements' => [
                    'scope3_mode' => 'preview_per_category',
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
