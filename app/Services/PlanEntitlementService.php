<?php

namespace App\Services;

use App\Data\PlanEntitlementDefaults;
use App\Models\ClientSubscription;
use App\Models\SubscriptionPlan;

class PlanEntitlementService
{
    public const EXPORT_GHG_PDF = 'ghg_pdf';
    public const EXPORT_MOCCAE_PDF = 'moccae_pdf';
    public const EXPORT_EXCEL = 'excel';
    public const EXPORT_IEQT = 'ieqt';
    public const EXPORT_IFRS_S2_PDF = 'ifrs_s2_pdf';
    public const EXPORT_IFRS_S1_PDF = 'ifrs_s1_pdf';
    public const EXPORT_GRI_PDF = 'gri_pdf';
    public const EXPORT_GRI_CONTENT_INDEX = 'gri_content_index';
    public const EXPORT_UAE_ESG_PDF = 'uae_esg_pdf';
    public const EXPORT_ESG_SCORECARD = 'esg_scorecard';
    public const EXPORT_SASB_INDEX = 'sasb_index';
    public const EXPORT_GRI_CONTENT_INDEX_EXTENDED = 'gri_content_index_extended';
    public const EXPORT_ESG_SCORECARD_ENTERPRISE = 'esg_scorecard_enterprise';
    public const FEATURE_ASSURANCE_UPLOAD = 'assurance_upload';
    public const FEATURE_ENERGY_FROM_ACTIVITY = 'energy_from_activity';
    public const EXPORT_UAE_ESG_PDF_ENTERPRISE = 'uae_esg_pdf_enterprise';

    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected ConsultantAgencyEntitlementService $consultantOrgEntitlements,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function forCompany(int $companyId): array
    {
        if ($this->consultantOrgEntitlements->isManagedClient($companyId)) {
            return $this->consultantOrgEntitlements->entitlementsForManagedClient($companyId);
        }

        $subscription = $this->subscriptionService->getActiveSubscription($companyId);

        return $this->resolveEntitlements($subscription?->plan);
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveEntitlements(?SubscriptionPlan $plan): array
    {
        if (!$plan) {
            return PlanEntitlementDefaults::entitlementsForPlanCode('client_free') ?? [];
        }

        $entitlements = $plan->entitlements;
        if (!is_array($entitlements) || $entitlements === []) {
            $entitlements = PlanEntitlementDefaults::entitlementsForPlanCode($plan->plan_code);
        }

        return is_array($entitlements) ? $entitlements : [];
    }

    public function scope3Mode(int $companyId): string
    {
        return (string) ($this->forCompany($companyId)['scope3_mode'] ?? 'locked');
    }

    public function isScope3Locked(int $companyId): bool
    {
        return $this->scope3Mode($companyId) === 'locked';
    }

    /**
     * @return array{allowed: bool, message: string|null}
     */
    public function canAccessScope3(int $companyId): array
    {
        if ($this->isScope3Locked($companyId)) {
            return [
                'allowed' => false,
                'message' => 'Scope 3 is available on Starter and above. Upgrade to unlock value-chain emissions.',
            ];
        }

        return ['allowed' => true, 'message' => null];
    }

    /**
     * Per Scope 3 form (emission source). -1 = unlimited.
     */
    public function getScope3RecordsPerFormLimit(int $companyId): int
    {
        $mode = $this->scope3Mode($companyId);

        if ($mode === 'locked') {
            return 0;
        }

        if ($mode === 'full') {
            return -1;
        }

        $subscription = $this->subscriptionService->getActiveSubscription($companyId);
        $limits = $subscription?->plan?->limits ?? [];

        if (array_key_exists('scope3_records_per_form', $limits) && $limits['scope3_records_per_form'] !== null) {
            return (int) $limits['scope3_records_per_form'];
        }

        return 1;
    }

    /**
     * @return array{allowed: bool, message: string|null}
     */
    public function canAccessDisclosures(int $companyId): array
    {
        $entitlements = $this->forCompany($companyId);
        $access = $entitlements['disclosures']['access'] ?? false;

        if ($access) {
            return ['allowed' => true, 'message' => null];
        }

        if ($this->subscriptionService->checkFeatureAccess($companyId, 'disclosures_access')) {
            return ['allowed' => true, 'message' => null];
        }

        return [
            'allowed' => false,
            'message' => 'Disclosure workspaces require an active MenetZero plan.',
        ];
    }

    /**
     * @return array{allowed: bool, message: string|null}
     */
    public function canExportDisclosures(int $companyId, ?int $fiscalYear = null): array
    {
        $entitlements = $this->forCompany($companyId);
        $canExport = (bool) ($entitlements['disclosures']['export'] ?? false);

        if (!$canExport) {
            return [
                'allowed' => false,
                'message' => 'IFRS and GRI report downloads are available on the Growth plan (AED 2,499/year).',
            ];
        }

        if ($this->consultantOrgEntitlements->isManagedClient($companyId)) {
            return $this->consultantOrgEntitlements->canExportDisclosures($companyId, $fiscalYear);
        }

        $yearCheck = $this->checkExportRegenWindow($companyId, $fiscalYear);
        if (!$yearCheck['allowed']) {
            return $yearCheck;
        }

        return ['allowed' => true, 'message' => null];
    }

    /**
     * @return array{allowed: bool, message: string|null}
     */
    public function canExport(int $companyId, string $exportCode, ?int $fiscalYear = null): array
    {
        $entitlements = $this->forCompany($companyId);
        $exports = $entitlements['exports'] ?? [];

        if (!$this->exportIncluded($exports, $exportCode)) {
            return [
                'allowed' => false,
                'message' => $this->exportDeniedMessage($exportCode),
            ];
        }

        if ($this->consultantOrgEntitlements->isManagedClient($companyId)) {
            return $this->consultantOrgEntitlements->canExport($companyId, $exportCode, $fiscalYear);
        }

        return $this->checkExportRegenWindow($companyId, $fiscalYear);
    }

    /**
     * @return array{allowed: bool, message: string|null}
     */
    public function canBulkImport(int $companyId): array
    {
        if ($this->forCompany($companyId)['bulk_import'] ?? false) {
            return ['allowed' => true, 'message' => null];
        }

        return [
            'allowed' => false,
            'message' => 'Bulk CSV/XLS import is available on the Starter plan (AED 1,499/year) and above.',
        ];
    }

    /**
     * @return array{allowed: bool, message: string|null}
     */
    public function canBulkExport(int $companyId): array
    {
        if ($this->forCompany($companyId)['bulk_export'] ?? false) {
            return ['allowed' => true, 'message' => null];
        }

        return [
            'allowed' => false,
            'message' => 'Bulk data export is available on the Starter plan (AED 1,499/year) and above.',
        ];
    }

    /**
     * @return array{allowed: bool, message: string|null}
     */
    public function canAccessHelpGuide(int $companyId): array
    {
        $level = (string) ($this->forCompany($companyId)['help_level'] ?? 'basic');

        if (in_array($level, ['full', 'full_disclosures'], true)) {
            return ['allowed' => true, 'message' => null];
        }

        return [
            'allowed' => false,
            'message' => 'The full help guide is available on the Starter plan (AED 1,499/year) and above.',
        ];
    }

    public function consultantDirectoryLevel(int $companyId): string
    {
        return (string) ($this->forCompany($companyId)['consultant_directory'] ?? 'teaser');
    }

    /**
     * @return array{allowed: bool, message: string|null}
     */
    public function canWriteForReportingYear(int $companyId, int $reportingYear): array
    {
        return $this->consultantOrgEntitlements->canWriteForReportingYear($companyId, $reportingYear);
    }

    /**
     * @param  list<string>  $exports
     */
    protected function exportIncluded(array $exports, string $exportCode): bool
    {
        if (in_array('*', $exports, true)) {
            return true;
        }

        return in_array($exportCode, $exports, true);
    }

    /**
     * @return array{allowed: bool, message: string|null}
     */
    protected function checkExportRegenWindow(int $companyId, ?int $fiscalYear): array
    {
        $entitlements = $this->forCompany($companyId);
        $regen = (string) ($entitlements['export_regen'] ?? 'none');

        if ($regen === 'none') {
            return [
                'allowed' => false,
                'message' => 'Report downloads require a paid plan. Upgrade to Starter from AED 1,499/year.',
            ];
        }

        if ($regen !== 'subscription_year_unlimited' || $fiscalYear === null) {
            return ['allowed' => true, 'message' => null];
        }

        $subscription = $this->subscriptionService->getActiveSubscription($companyId);
        if (!$subscription) {
            return [
                'allowed' => false,
                'message' => 'Report downloads require an active subscription.',
            ];
        }

        if ($this->fiscalYearWithinSubscription($subscription, $fiscalYear)) {
            return ['allowed' => true, 'message' => null];
        }

        return [
            'allowed' => false,
            'message' => "Downloads for fiscal year {$fiscalYear} are included for your active subscription term only. "
                . 'Renew or upgrade to export this year.',
        ];
    }

    protected function fiscalYearWithinSubscription(ClientSubscription $subscription, int $fiscalYear): bool
    {
        $startYear = (int) $subscription->started_at->year;
        $endYear = (int) $subscription->expires_at->year;

        return $fiscalYear >= $startYear && $fiscalYear <= $endYear;
    }

    protected function exportDeniedMessage(string $exportCode): string
    {
        $growthOnly = [
            self::EXPORT_IFRS_S2_PDF,
            self::EXPORT_IFRS_S1_PDF,
            self::EXPORT_GRI_PDF,
            self::EXPORT_GRI_CONTENT_INDEX,
            self::EXPORT_UAE_ESG_PDF,
            self::EXPORT_ESG_SCORECARD,
            self::EXPORT_SASB_INDEX,
        ];

        if (in_array($exportCode, $growthOnly, true)) {
            return 'IFRS, GRI, and UAE ESG report downloads are available on the Growth plan (AED 2,499/year).';
        }

        if ($exportCode === self::EXPORT_GRI_CONTENT_INDEX_EXTENDED) {
            return 'The full GRI content index (80+ disclosures) is available on the Enterprise plan. Contact sales for access.';
        }

        if ($exportCode === self::EXPORT_ESG_SCORECARD_ENTERPRISE) {
            return 'The enterprise ESG scorecard (80+ KPIs) is available on the Enterprise plan. Contact sales for access.';
        }

        if ($exportCode === self::FEATURE_ASSURANCE_UPLOAD) {
            return 'Independent assurance PDF upload is available on the Enterprise plan. Contact sales for access.';
        }

        if ($exportCode === self::FEATURE_ENERGY_FROM_ACTIVITY) {
            return 'Auto energy (GJ) from Quick Input is available on the Enterprise plan. Contact sales for access.';
        }

        if ($exportCode === self::EXPORT_UAE_ESG_PDF_ENTERPRISE) {
            return 'The white-label UAE ESG Report PDF is available on the Enterprise plan. Contact sales for access.';
        }

        return 'This export is available on the Starter plan (AED 1,499/year) and above.';
    }
}
