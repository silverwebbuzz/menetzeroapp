<?php

namespace App\Support;

use App\Models\User;
use App\Services\PlanEntitlementService;

/**
 * View-layer helper for Commercial Plan v1 UI gates (C3+).
 */
class PlanGate
{
    public function __construct(
        protected ?int $companyId,
        protected PlanEntitlementService $entitlements,
    ) {
    }

    public static function forUser(?User $user): self
    {
        $company = ($user && method_exists($user, 'getActiveCompany'))
            ? $user->getActiveCompany()
            : null;

        return new self(
            $company?->id,
            app(PlanEntitlementService::class),
        );
    }

    public static function forCompany(?int $companyId): self
    {
        return new self($companyId, app(PlanEntitlementService::class));
    }

    public function hasCompany(): bool
    {
        return $this->companyId !== null;
    }

    public function isScope3Locked(): bool
    {
        if (!$this->companyId) {
            return true;
        }

        return $this->entitlements->isScope3Locked($this->companyId);
    }

    public function canBulkImport(): bool
    {
        return $this->companyId && $this->entitlements->canBulkImport($this->companyId)['allowed'];
    }

    public function bulkImportMessage(): string
    {
        return $this->entitlements->canBulkImport($this->companyId ?? 0)['message']
            ?? 'Bulk import requires a paid plan.';
    }

    public function canBulkExport(): bool
    {
        return $this->companyId && $this->entitlements->canBulkExport($this->companyId)['allowed'];
    }

    public function bulkExportMessage(): string
    {
        return $this->entitlements->canBulkExport($this->companyId ?? 0)['message']
            ?? 'Bulk export requires a paid plan.';
    }

    public function canHelpGuide(): bool
    {
        return $this->companyId && $this->entitlements->canAccessHelpGuide($this->companyId)['allowed'];
    }

    public function helpGuideMessage(): string
    {
        return $this->entitlements->canAccessHelpGuide($this->companyId ?? 0)['message']
            ?? 'Full help guide requires Starter or above.';
    }

    public function canExport(string $exportCode, ?int $fiscalYear = null): bool
    {
        if (!$this->companyId) {
            return false;
        }

        return $this->entitlements->canExport($this->companyId, $exportCode, $fiscalYear)['allowed'];
    }

    public function exportMessage(string $exportCode): string
    {
        return $this->entitlements->canExport($this->companyId ?? 0, $exportCode)['message']
            ?? 'Upgrade to unlock this export.';
    }

    public function canDisclosureExport(?int $fiscalYear = null): bool
    {
        if (!$this->companyId) {
            return false;
        }

        $base = $this->entitlements->canExportDisclosures($this->companyId, $fiscalYear);
        if (!$base['allowed']) {
            return false;
        }

        return true;
    }

    public function disclosureExportMessage(): string
    {
        return $this->entitlements->canExportDisclosures($this->companyId ?? 0)['message']
            ?? 'IFRS and GRI downloads require Growth.';
    }

    public function canDisclosureExportType(string $exportCode, ?int $fiscalYear = null): bool
    {
        if (!$this->canDisclosureExport($fiscalYear)) {
            return false;
        }

        return $this->canExport($exportCode, $fiscalYear);
    }

    /** Starter-tier exports (GHG, MOCCAE, Excel, IEQT). */
    public function needsStarterForExports(): bool
    {
        if (!$this->companyId) {
            return true;
        }

        return !$this->canExport(PlanEntitlementService::EXPORT_GHG_PDF);
    }

    /** Growth-tier disclosure PDF exports. */
    public function needsGrowthForDisclosures(): bool
    {
        if (!$this->companyId) {
            return true;
        }

        return !$this->canDisclosureExport();
    }
}
