<?php

namespace App\Services;

use App\Data\PlanEntitlementDefaults;
use App\Models\SubscriptionPlan;

/**
 * Admin UI helpers: structured entitlement forms (C7).
 */
class PlanEntitlementAdminService
{
    /**
     * @return array<string, string>
     */
    public static function scope3ModeOptions(): array
    {
        return [
            'locked' => 'Locked (Free)',
            'preview_per_category' => 'Preview — 1 entry per category',
            'full' => 'Full (unlimited)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function helpLevelOptions(): array
    {
        return [
            'basic' => 'Basic onboarding only',
            'full' => 'Full (S1&2 + bulk)',
            'full_disclosures' => 'Full + disclosure playbooks',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function consultantDirectoryOptions(): array
    {
        return [
            'teaser' => 'Teaser only',
            'partial' => 'Partial — request intro',
            'full' => 'Full connect',
            'priority' => 'Priority / dedicated',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function exportRegenOptions(): array
    {
        return [
            'none' => 'No downloads',
            'subscription_year_unlimited' => 'Unlimited regen within subscription year',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function exportOptions(): array
    {
        return [
            PlanEntitlementService::EXPORT_GHG_PDF => 'GHG Inventory PDF',
            PlanEntitlementService::EXPORT_MOCCAE_PDF => 'MOCCAE S1&2 PDF',
            PlanEntitlementService::EXPORT_EXCEL => 'Excel results',
            PlanEntitlementService::EXPORT_IEQT => 'IEQT export',
            PlanEntitlementService::EXPORT_IFRS_S2_PDF => 'IFRS S2 PDF',
            PlanEntitlementService::EXPORT_IFRS_S1_PDF => 'IFRS S1 PDF',
            PlanEntitlementService::EXPORT_GRI_PDF => 'GRI PDF',
            PlanEntitlementService::EXPORT_GRI_CONTENT_INDEX => 'GRI content index CSV',
            PlanEntitlementService::EXPORT_UAE_ESG_PDF => 'UAE ESG Report PDF',
            PlanEntitlementService::EXPORT_ESG_SCORECARD => 'ESG Scorecard Excel',
            PlanEntitlementService::EXPORT_SASB_INDEX => 'SASB index CSV',
            PlanEntitlementService::EXPORT_GRI_CONTENT_INDEX_EXTENDED => 'GRI enterprise content index (80+)',
            PlanEntitlementService::EXPORT_ESG_SCORECARD_ENTERPRISE => 'ESG scorecard enterprise (80+ KPIs)',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formValuesFromPlan(SubscriptionPlan $plan): array
    {
        $defaults = PlanEntitlementDefaults::entitlementsForPlanCode($plan->plan_code) ?? [];
        $entitlements = is_array($plan->entitlements) && $plan->entitlements !== []
            ? array_merge($defaults, $plan->entitlements)
            : $defaults;

        $limits = $plan->limits ?? [];
        $exports = $entitlements['exports'] ?? [];
        $allExports = in_array('*', $exports, true);

        return [
            'locations' => $limits['locations'] ?? 1,
            'users' => $limits['users'] ?? 1,
            'scope3_mode' => $entitlements['scope3_mode'] ?? 'locked',
            'bulk_import' => (bool) ($entitlements['bulk_import'] ?? false),
            'bulk_export' => (bool) ($entitlements['bulk_export'] ?? false),
            'help_level' => $entitlements['help_level'] ?? 'basic',
            'disclosures_access' => (bool) ($entitlements['disclosures']['access'] ?? true),
            'disclosures_export' => (bool) ($entitlements['disclosures']['export'] ?? false),
            'exports_all' => $allExports,
            'exports' => $allExports ? array_keys(self::exportOptions()) : $exports,
            'consultant_directory' => $entitlements['consultant_directory'] ?? 'teaser',
            'export_regen' => $entitlements['export_regen'] ?? 'none',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildEntitlementsFromRequest(array $input): array
    {
        $exportsAll = !empty($input['exports_all']);
        $selectedExports = array_values(array_filter((array) ($input['exports'] ?? [])));

        $exports = $exportsAll ? ['*'] : $selectedExports;

        return [
            'scope3_mode' => $input['scope3_mode'] ?? 'locked',
            'bulk_import' => !empty($input['bulk_import']),
            'bulk_export' => !empty($input['bulk_export']),
            'help_level' => $input['help_level'] ?? 'basic',
            'disclosures' => [
                'access' => !empty($input['disclosures_access']),
                'export' => !empty($input['disclosures_export']),
            ],
            'exports' => $exports,
            'consultant_directory' => $input['consultant_directory'] ?? 'teaser',
            'export_regen' => $input['export_regen'] ?? 'none',
        ];
    }

    /**
     * @return array<string, int>
     */
    public function buildLimitsFromRequest(array $input, array $existingLimits = []): array
    {
        $scope3Mode = $input['scope3_mode'] ?? 'locked';
        $exportRegen = $input['export_regen'] ?? 'none';
        $hasGhgExport = !empty($input['exports_all'])
            || in_array(PlanEntitlementService::EXPORT_GHG_PDF, (array) ($input['exports'] ?? []), true);

        $scope3Records = match ($scope3Mode) {
            'full' => -1,
            'preview_per_category' => 1,
            default => 0,
        };

        $annualReportPdf = ($exportRegen === 'subscription_year_unlimited' && $hasGhgExport) ? -1 : 0;

        return array_merge($existingLimits, [
            'locations' => (int) ($input['locations'] ?? 1),
            'users' => (int) ($input['users'] ?? 1),
            'scope3_records_per_form' => $scope3Records,
            'annual_report_pdf' => $annualReportPdf,
        ]);
    }

    /**
     * @return list<string>
     */
    public function buildFeaturesFromEntitlements(array $entitlements): array
    {
        $features = [];

        if (!empty($entitlements['disclosures']['access'])) {
            $features[] = 'disclosures_access';
        }

        $exports = $entitlements['exports'] ?? [];
        $all = in_array('*', $exports, true);

        if ($all || in_array(PlanEntitlementService::EXPORT_IFRS_S2_PDF, $exports, true)) {
            $features[] = 'ifrs_s2';
        }
        if ($all || in_array(PlanEntitlementService::EXPORT_IFRS_S1_PDF, $exports, true)) {
            $features[] = 'ifrs_s1';
        }
        if ($all || in_array(PlanEntitlementService::EXPORT_GRI_PDF, $exports, true)) {
            $features[] = 'gri';
        }

        return array_values(array_unique($features));
    }

    public function applyToPlan(SubscriptionPlan $plan, array $input): void
    {
        $entitlements = $this->buildEntitlementsFromRequest($input);
        $limits = $this->buildLimitsFromRequest($input, $plan->limits ?? []);
        $features = $this->buildFeaturesFromEntitlements($entitlements);

        $plan->update([
            'entitlements' => $entitlements,
            'limits' => $limits,
            'features' => $features,
        ]);
    }

    public function resetToDefaults(SubscriptionPlan $plan): void
    {
        $definition = PlanEntitlementDefaults::forPlanCode($plan->plan_code);
        if (!$definition) {
            return;
        }

        $plan->update([
            'entitlements' => $definition['entitlements'],
            'limits' => array_merge($plan->limits ?? [], $definition['limits']),
            'features' => $definition['features'],
        ]);
    }
}
