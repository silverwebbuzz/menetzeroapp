<?php

namespace App\Services;

use App\Data\PartnerPlanMatrix;
use App\Models\Company;
use App\Models\PartnerClientEngagement;
use App\Models\PartnerSubscriptionAddon;

/**
 * Entitlements for partner-managed client workspaces (PRY / preview / read-only).
 *
 * @see documentation/PARTNER_AGENCY_PLAN_V1.md §3.1, §7.3
 */
class PartnerEntitlementService
{
    public const MODE_PRY_FULL = 'pry_full';
    public const MODE_PREVIEW = 'preview';
    public const MODE_READ_ONLY = 'read_only';
    public const MODE_DENIED = 'denied';

    public function isManagedClient(int $companyId): bool
    {
        $company = Company::find($companyId);

        return $company !== null && $company->isManagedClient();
    }

    /**
     * @return array<string, mixed>
     */
    public function entitlementsForManagedClient(int $companyId): array
    {
        $engagement = $this->getActiveEngagement($companyId);

        if (!$engagement || !$engagement->isActive()) {
            return array_merge(PartnerPlanMatrix::managedClientEntitlements(), [
                'channel' => 'partner_managed',
                'engagement_status' => $engagement?->status ?? 'none',
            ]);
        }

        return array_merge(PartnerPlanMatrix::managedClientEntitlements(), [
            'engagement_status' => 'active',
            'primary_reporting_year' => $engagement->primary_reporting_year,
            'partner_company_id' => $engagement->partner_company_id,
        ]);
    }

    public function getActiveEngagement(int $managedCompanyId): ?PartnerClientEngagement
    {
        return PartnerClientEngagement::query()
            ->where('managed_company_id', $managedCompanyId)
            ->active()
            ->orderByDesc('id')
            ->first();
    }

    public function reportingYearMode(PartnerClientEngagement $engagement, int $reportingYear): string
    {
        if (!$engagement->isActive()) {
            return self::MODE_DENIED;
        }

        if ($this->hasYearUnlock($engagement, $reportingYear)) {
            return self::MODE_PRY_FULL;
        }

        $pry = (int) $engagement->primary_reporting_year;

        if ($reportingYear === $pry) {
            return self::MODE_PRY_FULL;
        }

        if ($reportingYear === $pry + 1) {
            return self::MODE_PREVIEW;
        }

        if ($reportingYear < $pry) {
            return self::MODE_READ_ONLY;
        }

        return self::MODE_PREVIEW;
    }

    public function reportingYearModeForCompany(int $companyId, int $reportingYear): ?string
    {
        if (!$this->isManagedClient($companyId)) {
            return null;
        }

        $engagement = $this->getActiveEngagement($companyId);

        if (!$engagement) {
            return self::MODE_DENIED;
        }

        return $this->reportingYearMode($engagement, $reportingYear);
    }

    /**
     * @return array{allowed: bool, message: string|null}
     */
    public function canWriteForReportingYear(int $companyId, int $reportingYear): array
    {
        if (!$this->isManagedClient($companyId)) {
            return ['allowed' => true, 'message' => null];
        }

        $engagement = $this->getActiveEngagement($companyId);

        if (!$engagement) {
            return [
                'allowed' => false,
                'message' => 'No active partner engagement for this client workspace.',
            ];
        }

        if (!$engagement->isActive()) {
            return [
                'allowed' => false,
                'message' => 'This client engagement is archived — data is read-only.',
            ];
        }

        $mode = $this->reportingYearMode($engagement, $reportingYear);

        if ($mode === self::MODE_READ_ONLY) {
            return [
                'allowed' => false,
                'message' => "Fiscal year {$reportingYear} is read-only for this managed client.",
            ];
        }

        if ($mode === self::MODE_DENIED) {
            return [
                'allowed' => false,
                'message' => 'This managed client workspace is not active on the partner contract.',
            ];
        }

        return ['allowed' => true, 'message' => null];
    }

    /**
     * @return array{allowed: bool, message: string|null}
     */
    public function canExport(int $companyId, string $exportCode, ?int $reportingYear = null): array
    {
        if (!$this->isManagedClient($companyId)) {
            return ['allowed' => true, 'message' => null];
        }

        $engagement = $this->getActiveEngagement($companyId);

        if (!$engagement || !$engagement->isActive()) {
            return [
                'allowed' => false,
                'message' => 'Exports require an active partner engagement for this client.',
            ];
        }

        if ($reportingYear === null) {
            $reportingYear = (int) $engagement->primary_reporting_year;
        }

        $mode = $this->reportingYearMode($engagement, $reportingYear);

        return match ($mode) {
            self::MODE_PRY_FULL => ['allowed' => true, 'message' => null],
            self::MODE_PREVIEW => [
                'allowed' => false,
                'message' => "Fiscal year {$reportingYear} is preview-only. "
                    . "Downloads are available for Primary Reporting Year {$engagement->primary_reporting_year} "
                    . 'or after a reporting year unlock / partner renewal.',
            ],
            self::MODE_READ_ONLY => [
                'allowed' => false,
                'message' => "Fiscal year {$reportingYear} is read-only for this managed client.",
            ],
            default => [
                'allowed' => false,
                'message' => 'Exports are not available for this reporting year on the partner contract.',
            ],
        };
    }

    /**
     * @return array{allowed: bool, message: string|null}
     */
    public function canExportDisclosures(int $companyId, ?int $reportingYear = null): array
    {
        return $this->canExport($companyId, PlanEntitlementService::EXPORT_IFRS_S2_PDF, $reportingYear);
    }

    public function previewBannerMessage(int $companyId, ?int $reportingYear = null): ?string
    {
        if (!$this->isManagedClient($companyId)) {
            return null;
        }

        $engagement = $this->getActiveEngagement($companyId);

        if (!$engagement) {
            return 'This managed client has no active partner engagement.';
        }

        $reportingYear ??= (int) $engagement->primary_reporting_year;
        $mode = $this->reportingYearMode($engagement, $reportingYear);

        return match ($mode) {
            self::MODE_PREVIEW => "Preview only for fiscal year {$reportingYear}. "
                . "Full IFRS/GRI and report downloads are enabled for PRY {$engagement->primary_reporting_year} only.",
            self::MODE_READ_ONLY => "Fiscal year {$reportingYear} is read-only for this managed client.",
            self::MODE_DENIED => 'This client workspace is not active on the current partner contract.',
            default => null,
        };
    }

    protected function hasYearUnlock(PartnerClientEngagement $engagement, int $reportingYear): bool
    {
        return PartnerSubscriptionAddon::query()
            ->where('partner_subscription_id', $engagement->partner_subscription_id)
            ->where('addon_type', 'reporting_year_unlock')
            ->where('managed_company_id', $engagement->managed_company_id)
            ->where('reporting_year', $reportingYear)
            ->exists();
    }
}
