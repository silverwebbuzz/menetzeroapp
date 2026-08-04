<?php

use App\Data\ConsultantAgencyPlanMatrix;
use App\Data\PlanEntitlementDefaults;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CONSULTANT_MULTI_PACKAGE_PLAN.md — Phase 2
 *
 * 1) Rename consultant_trial → consultant_free (merge FKs if both exist)
 * 2) Wipe Silver Webbuzz Sustainability Practice full demo org (+ consultant_50)
 * 3) Remap client_growth → client_scope_pro; deactivate growth
 * 4) Deactivate unused legacy consultant packs when FK-free
 */
return new class extends Migration
{
    private const DEMO_ORG_NAME = 'Silver Webbuzz Sustainability Practice';

    private const DEMO_CONSULTANT_EMAIL = 'demo.full@menetzero.com';

    public function up(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        $this->renameTrialToFree();
        $this->wipeFullDemoOrg();
        $this->remapClientGrowthToScopePro();
        $this->deactivateLegacyConsultantPlans();
        $this->refreshFreePlanRow();
    }

    public function down(): void
    {
        // Irreversible data wipe + merge; no automatic restore.
    }

    private function renameTrialToFree(): void
    {
        $trial = SubscriptionPlan::where('plan_code', 'consultant_trial')->first();
        $free = SubscriptionPlan::where('plan_code', 'consultant_free')->first();

        if (!$trial && !$free) {
            $definition = ConsultantAgencyPlanMatrix::forPlanCode('consultant_free');
            if ($definition) {
                $this->upsertPlanFromDefinition($definition, true);
            }

            return;
        }

        if ($trial && !$free) {
            $trial->update([
                'plan_code' => 'consultant_free',
                'plan_name' => 'Free trial',
                'description' => 'One managed client on Free rules (mirrors client_free). Always retained when paid depth rows are added.',
                'is_active' => true,
                'sort_order' => 4,
            ]);

            return;
        }

        if ($trial && $free) {
            $this->repointPlanId((int) $trial->id, (int) $free->id);
            $trial->delete();
        }

        if ($free) {
            $free->update([
                'plan_name' => 'Free trial',
                'is_active' => true,
                'description' => 'One managed client on Free rules (mirrors client_free). Always retained when paid depth rows are added.',
            ]);
        }
    }

    private function repointPlanId(int $fromId, int $toId): void
    {
        $tables = [
            'consultant_subscriptions' => 'subscription_plan_id',
            'client_subscriptions' => 'subscription_plan_id',
            'admin_package_assignments' => 'subscription_plan_id',
            'subscription_coupons' => 'subscription_plan_id',
        ];

        foreach ($tables as $table => $column) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }
            DB::table($table)->where($column, $fromId)->update([$column => $toId]);
        }
    }

    private function wipeFullDemoOrg(): void
    {
        if (!Schema::hasTable('companies')) {
            return;
        }

        $orgIds = collect();

        $byName = DB::table('companies')
            ->where('name', self::DEMO_ORG_NAME)
            ->pluck('id');
        $orgIds = $orgIds->merge($byName);

        if (Schema::hasTable('consultants')) {
            $consultantAgencyIds = DB::table('consultants')
                ->where('email', self::DEMO_CONSULTANT_EMAIL)
                ->whereNotNull('agency_company_id')
                ->pluck('agency_company_id');
            $orgIds = $orgIds->merge($consultantAgencyIds);

            $byCompanyName = DB::table('consultants')
                ->where('company_name', self::DEMO_ORG_NAME)
                ->whereNotNull('agency_company_id')
                ->pluck('agency_company_id');
            $orgIds = $orgIds->merge($byCompanyName);
        }

        if (Schema::hasTable('consultant_subscriptions') && Schema::hasTable('subscription_plans')) {
            $pack50Id = DB::table('subscription_plans')
                ->where('plan_code', 'consultant_50')
                ->value('id');
            if ($pack50Id) {
                $fromPack = DB::table('consultant_subscriptions')
                    ->where('subscription_plan_id', $pack50Id)
                    ->pluck('consultant_company_id');
                $orgIds = $orgIds->merge($fromPack);
            }
        }

        $orgIds = $orgIds->filter()->unique()->values();

        foreach ($orgIds as $orgId) {
            $this->wipeConsultantOrganisation((int) $orgId);
        }

        if (Schema::hasTable('consultants')) {
            DB::table('consultants')->where('email', self::DEMO_CONSULTANT_EMAIL)->delete();
            DB::table('consultants')->where('company_name', self::DEMO_ORG_NAME)->delete();
        }

        if (Schema::hasTable('users')) {
            $userIds = DB::table('users')->where('email', self::DEMO_CONSULTANT_EMAIL)->pluck('id');
            foreach ($userIds as $userId) {
                $this->detachUser((int) $userId);
            }
        }
    }

    private function wipeConsultantOrganisation(int $orgId): void
    {
        $managedIds = collect();

        if (Schema::hasTable('consultant_client_engagements')) {
            $managedIds = DB::table('consultant_client_engagements')
                ->where('consultant_company_id', $orgId)
                ->pluck('managed_company_id')
                ->filter()
                ->unique();
        }

        $managedIds = $managedIds->merge(
            DB::table('companies')->where('consultant_id', $orgId)->pluck('id')
        )->filter()->unique()->values();

        $subscriptionIds = collect();
        if (Schema::hasTable('consultant_subscriptions')) {
            $subscriptionIds = DB::table('consultant_subscriptions')
                ->where('consultant_company_id', $orgId)
                ->pluck('id');
        }

        if (Schema::hasTable('consultant_subscription_addons') && $subscriptionIds->isNotEmpty()) {
            DB::table('consultant_subscription_addons')
                ->whereIn('consultant_subscription_id', $subscriptionIds)
                ->delete();
        }

        if (Schema::hasTable('consultant_client_engagements')) {
            DB::table('consultant_client_engagements')
                ->where('consultant_company_id', $orgId)
                ->delete();
        }

        if (Schema::hasTable('consultant_subscriptions')) {
            DB::table('consultant_subscriptions')
                ->where('consultant_company_id', $orgId)
                ->delete();
        }

        if (Schema::hasTable('admin_package_assignments')) {
            DB::table('admin_package_assignments')->where('company_id', $orgId)->delete();
            if ($managedIds->isNotEmpty()) {
                DB::table('admin_package_assignments')->whereIn('company_id', $managedIds)->delete();
            }
        }

        if (Schema::hasTable('consultant_entity_requests')) {
            DB::table('consultant_entity_requests')->where('consultant_company_id', $orgId)->delete();
        }

        if (Schema::hasTable('consultants') && Schema::hasColumn('consultants', 'agency_company_id')) {
            DB::table('consultants')->where('agency_company_id', $orgId)->update(['agency_company_id' => null]);
        }

        foreach ($managedIds as $managedId) {
            $this->wipeCompanyData((int) $managedId);
            DB::table('companies')->where('id', $managedId)->delete();
        }

        $this->wipeCompanyData($orgId);
        DB::table('companies')->where('id', $orgId)->delete();
    }

    /**
     * Best-effort delete of company-scoped rows (demo data). Skips missing tables.
     */
    private function wipeCompanyData(int $companyId): void
    {
        $locationIds = collect();
        if (Schema::hasTable('locations') && Schema::hasColumn('locations', 'company_id')) {
            $locationIds = DB::table('locations')->where('company_id', $companyId)->pluck('id');
        }

        if ($locationIds->isNotEmpty() && Schema::hasTable('measurements')) {
            $measurementIds = DB::table('measurements')->whereIn('location_id', $locationIds)->pluck('id');

            foreach ([
                'measurement_data',
                'measurement_audit_trails',
                'energy_data',
                'industrial_data',
                'transport_data',
                'waste_data',
            ] as $child) {
                if ($measurementIds->isNotEmpty() && Schema::hasTable($child) && Schema::hasColumn($child, 'measurement_id')) {
                    DB::table($child)->whereIn('measurement_id', $measurementIds)->delete();
                }
            }

            if (Schema::hasTable('location_emission_boundaries')) {
                DB::table('location_emission_boundaries')->whereIn('location_id', $locationIds)->delete();
            }

            DB::table('measurements')->whereIn('location_id', $locationIds)->delete();
            DB::table('locations')->where('company_id', $companyId)->delete();
        }

        if (Schema::hasTable('facilities') && Schema::hasColumn('facilities', 'company_id')) {
            DB::table('facilities')->where('company_id', $companyId)->delete();
        }

        foreach ([
            'client_subscriptions',
            'company_reporting_settings',
            'climate_risks',
            'climate_opportunities',
            'reduction_targets',
            'material_sustainability_topics',
            'esg_kpi_snapshots',
            'esg_sustainability_targets',
            'stakeholder_engagements',
            'sustainability_risks',
            'transition_actions',
            'supply_chain_suppliers',
            'hris_kpi_import_logs',
            'company_package_requests',
        ] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'company_id')) {
                DB::table($table)->where('company_id', $companyId)->delete();
            }
        }

        // Disclosure / framework tables that commonly store company_id.
        foreach ([
            'ifrs_s1_disclosures',
            'ifrs_s2_disclosures',
            'gri_disclosures',
            'uae_esg_disclosures',
            'sasb_disclosures',
            'disclosure_answers',
            'company_disclosures',
        ] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'company_id')) {
                DB::table($table)->where('company_id', $companyId)->delete();
            }
        }

        if (Schema::hasTable('user_company_roles')) {
            DB::table('user_company_roles')->where('company_id', $companyId)->delete();
        }

        if (Schema::hasTable('user_active_contexts') && Schema::hasColumn('user_active_contexts', 'active_company_id')) {
            DB::table('user_active_contexts')->where('active_company_id', $companyId)->delete();
        }
    }

    private function detachUser(int $userId): void
    {
        if (Schema::hasTable('user_company_roles')) {
            DB::table('user_company_roles')->where('user_id', $userId)->delete();
        }
        if (Schema::hasTable('user_active_contexts')) {
            DB::table('user_active_contexts')->where('user_id', $userId)->delete();
        }
        if (Schema::hasTable('users')) {
            DB::table('users')->where('id', $userId)->delete();
        }
    }

    private function remapClientGrowthToScopePro(): void
    {
        $growth = SubscriptionPlan::where('plan_code', 'client_growth')->first();
        $pro = SubscriptionPlan::where('plan_code', 'client_scope_pro')->first();

        if (!$growth) {
            return;
        }

        if (!$pro) {
            $definition = PlanEntitlementDefaults::forPlanCode('client_scope_pro');
            if ($definition) {
                $pro = SubscriptionPlan::updateOrCreate(
                    ['plan_code' => 'client_scope_pro'],
                    [
                        'plan_name' => $definition['plan_name'],
                        'plan_category' => 'client',
                        'description' => $definition['description'],
                        'price_annual' => $definition['price_annual'],
                        'price_inr' => PlanEntitlementDefaults::defaultPriceInr((float) $definition['price_annual']),
                        'currency' => $definition['currency'],
                        'billing_cycle' => 'annual',
                        'is_active' => true,
                        'sort_order' => $definition['sort_order'],
                        'limits' => $definition['limits'],
                        'entitlements' => $definition['entitlements'],
                        'features' => $definition['features'],
                    ]
                );
            }
        }

        if ($pro) {
            $this->repointPlanId((int) $growth->id, (int) $pro->id);
        }

        $growth->update([
            'is_active' => false,
            'plan_name' => 'Growth (legacy)',
            'description' => 'Legacy — remapped subscribers to client_scope_pro (Phase 2).',
        ]);
    }

    private function deactivateLegacyConsultantPlans(): void
    {
        $legacy = [
            'consultant_entity',
            'consultant_managed_standard',
            'consultant_5',
            'consultant_10',
            'consultant_25',
            'consultant_50',
            'consultant_trial', // should already be gone
        ];

        foreach ($legacy as $code) {
            $plan = SubscriptionPlan::where('plan_code', $code)->first();
            if (!$plan) {
                continue;
            }

            $inUse = false;
            if (Schema::hasTable('consultant_subscriptions')) {
                $inUse = DB::table('consultant_subscriptions')
                    ->where('subscription_plan_id', $plan->id)
                    ->exists();
            }
            if (!$inUse && Schema::hasTable('client_subscriptions')) {
                $inUse = DB::table('client_subscriptions')
                    ->where('subscription_plan_id', $plan->id)
                    ->exists();
            }

            $plan->update(['is_active' => false]);

            // Safe hard-delete only when nothing points at the plan.
            if (!$inUse && Schema::hasTable('admin_package_assignments')) {
                $inUse = DB::table('admin_package_assignments')
                    ->where('subscription_plan_id', $plan->id)
                    ->exists();
            }
            if (!$inUse && Schema::hasTable('subscription_coupons')) {
                $inUse = DB::table('subscription_coupons')
                    ->where('subscription_plan_id', $plan->id)
                    ->exists();
            }

            if (!$inUse) {
                try {
                    $plan->delete();
                } catch (\Throwable) {
                    // keep deactivated row if FK elsewhere
                }
            }
        }
    }

    private function refreshFreePlanRow(): void
    {
        $definition = ConsultantAgencyPlanMatrix::forPlanCode('consultant_free');
        if ($definition) {
            $this->upsertPlanFromDefinition($definition, true);
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function upsertPlanFromDefinition(array $definition, bool $active): void
    {
        $priceAnnual = (float) $definition['price_annual'];
        SubscriptionPlan::updateOrCreate(
            ['plan_code' => $definition['plan_code']],
            [
                'plan_name' => $definition['plan_name'],
                'plan_category' => $definition['plan_category'],
                'description' => $definition['description'],
                'price_annual' => $priceAnnual,
                'price_inr' => $priceAnnual > 0 ? PlanEntitlementDefaults::defaultPriceInr($priceAnnual) : 0,
                'currency' => $definition['currency'],
                'billing_cycle' => $definition['billing_cycle'],
                'is_active' => $active,
                'sort_order' => $definition['sort_order'],
                'limits' => $definition['limits'],
                'entitlements' => $definition['entitlements'],
                'features' => $definition['features'],
            ]
        );
    }
};
