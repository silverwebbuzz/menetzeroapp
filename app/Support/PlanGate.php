<?php

namespace App\Support;

use App\Models\MeasurementData;
use App\Models\User;
use App\Services\ConsultantAgencyEntitlementService;
use App\Services\PlanEntitlementService;
use App\Services\SubscriptionService;

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

    public function companyId(): ?int
    {
        return $this->companyId;
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

    public function exportMessage(string $exportCode, ?int $fiscalYear = null): string
    {
        return $this->entitlements->canExport($this->companyId ?? 0, $exportCode, $fiscalYear)['message']
            ?? 'Upgrade to unlock this export.';
    }

    public function isManagedClient(): bool
    {
        if (!$this->companyId) {
            return false;
        }

        return app(ConsultantAgencyEntitlementService::class)->isManagedClient($this->companyId);
    }

    public function managedReportingYearMode(?int $fiscalYear = null): ?string
    {
        if (!$this->companyId || !$this->isManagedClient() || $fiscalYear === null) {
            return null;
        }

        return app(ConsultantAgencyEntitlementService::class)
            ->reportingYearModeForCompany($this->companyId, $fiscalYear);
    }

    public function managedPreviewBannerMessage(?int $fiscalYear = null): ?string
    {
        if (!$this->companyId) {
            return null;
        }

        return app(ConsultantAgencyEntitlementService::class)
            ->previewBannerMessage($this->companyId, $fiscalYear);
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

    public function disclosureExportMessage(?int $fiscalYear = null): string
    {
        return $this->entitlements->canExportDisclosures($this->companyId ?? 0, $fiscalYear)['message']
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

    /**
     * Usage meters for Plan & billing hub (C6).
     *
     * @return array<string, array{used: int, limit: int|null, label: string}>
     */
    public function usageMeters(): array
    {
        if (!$this->companyId) {
            return [];
        }

        $subscriptionService = app(SubscriptionService::class);
        $limits = $subscriptionService->getPlanLimits($this->companyId);

        return [
            'locations' => [
                'label' => 'Locations',
                'used' => $subscriptionService->getCurrentUsage($this->companyId, 'locations'),
                'limit' => $this->normalizeLimit($limits['locations'] ?? null),
            ],
            'users' => [
                'label' => 'Users',
                'used' => $subscriptionService->getCurrentUsage($this->companyId, 'users'),
                'limit' => $this->normalizeLimit($limits['users'] ?? null),
            ],
            'scope3_categories' => [
                'label' => 'Scope 3 categories',
                'used' => $this->scope3CategoriesUsed(),
                'limit' => $this->scope3CategoriesLimit(),
            ],
        ];
    }

    /**
     * @return list<array{label: string, allowed: bool, hint: string|null}>
     */
    public function dataEntitlementsList(): array
    {
        return [
            [
                'label' => 'Scope 1 & 2',
                'allowed' => true,
                'hint' => null,
            ],
            [
                'label' => 'Bulk import',
                'allowed' => $this->canBulkImport(),
                'hint' => $this->canBulkImport() ? null : 'Starter',
            ],
            [
                'label' => 'Scope 3',
                'allowed' => !$this->isScope3Locked(),
                'hint' => $this->scope3EntitlementHint(),
            ],
            [
                'label' => 'Disclosure forms',
                'allowed' => true,
                'hint' => $this->canDisclosureExport() ? null : 'Preview only',
            ],
            [
                'label' => 'Help guide',
                'allowed' => $this->canHelpGuide(),
                'hint' => $this->canHelpGuide() ? null : 'Basic only',
            ],
        ];
    }

    /**
     * @return list<array{label: string, allowed: bool, hint: string|null}>
     */
    public function downloadEntitlementsList(): array
    {
        $items = [
            ['label' => 'GHG PDF', 'code' => PlanEntitlementService::EXPORT_GHG_PDF],
            ['label' => 'MOCCAE PDF', 'code' => PlanEntitlementService::EXPORT_MOCCAE_PDF],
            ['label' => 'Excel', 'code' => PlanEntitlementService::EXPORT_EXCEL],
            ['label' => 'IEQT', 'code' => PlanEntitlementService::EXPORT_IEQT],
            ['label' => 'IFRS S1/S2 PDF', 'code' => PlanEntitlementService::EXPORT_IFRS_S2_PDF],
            ['label' => 'GRI PDF', 'code' => PlanEntitlementService::EXPORT_GRI_PDF],
        ];

        return array_map(function (array $item) {
            $allowed = $this->canExport($item['code']);
            $hint = null;
            if (!$allowed) {
                $hint = in_array($item['code'], [
                    PlanEntitlementService::EXPORT_IFRS_S2_PDF,
                    PlanEntitlementService::EXPORT_GRI_PDF,
                ], true) ? 'Growth' : 'Starter';
            }

            return [
                'label' => $item['label'],
                'allowed' => $allowed,
                'hint' => $hint,
            ];
        }, $items);
    }

    public function consultantDirectoryLabel(): string
    {
        if (!$this->companyId) {
            return 'Teaser';
        }

        $level = app(PlanEntitlementService::class)->consultantDirectoryLevel($this->companyId);

        return match ($level) {
            'partial' => 'Request intro',
            'full' => 'Full connect',
            'priority' => 'Priority partner',
            default => 'Teaser only',
        };
    }

    protected function scope3CategoriesUsed(): int
    {
        if (!$this->companyId) {
            return 0;
        }

        return (int) MeasurementData::query()
            ->where('scope', 'Scope 3')
            ->whereHas('measurement.location', fn ($q) => $q->where('company_id', $this->companyId))
            ->distinct()
            ->count('emission_source_id');
    }

    /** -1 = unlimited, 0 = locked, positive = cap. */
    protected function scope3CategoriesLimit(): int
    {
        if (!$this->companyId) {
            return 0;
        }

        $mode = app(PlanEntitlementService::class)->scope3Mode($this->companyId);

        return match ($mode) {
            'locked' => 0,
            'full' => -1,
            default => 15,
        };
    }

    protected function scope3EntitlementHint(): ?string
    {
        if ($this->isScope3Locked()) {
            return 'Unlock on Starter';
        }

        $limit = $this->scope3CategoriesLimit();

        return $limit === -1 ? null : '1 entry / category';
    }

    protected function normalizeLimit(mixed $limit): ?int
    {
        if ($limit === null) {
            return null;
        }

        $value = (int) $limit;

        return $value === -1 ? null : $value;
    }
}
