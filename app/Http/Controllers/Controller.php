<?php

namespace App\Http\Controllers;

use App\Services\PartnerEntitlementService;
use App\Services\PlanEntitlementService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Check if user has permission, abort if not.
     * Supports both old format (single string) and new format (module, action).
     */
    protected function requirePermission($permissionOrModule, $action = null, $alternativePermissions = [])
    {
        $user = Auth::user();
        $company = $user->getActiveCompany();
        $companyId = $company ? $company->id : null;

        // Super admin and company admin bypass permission checks
        if ($user->isAdmin() || ($companyId && $user->isCompanyAdmin($companyId))) {
            return;
        }

        // New format: module and action
        if ($action !== null) {
            if ($user->hasModulePermission($permissionOrModule, $action, $companyId)) {
                return;
            }
        } else {
            // Old format: single permission string
            if ($user->hasPermission($permissionOrModule, $companyId)) {
                return;
            }
            
            // Also try to parse as module.action format and check module/action
            // e.g., "measurements.view" -> module="measurements", action="view"
            if (str_contains($permissionOrModule, '.')) {
                $parts = explode('.', $permissionOrModule, 2);
                if (count($parts) === 2) {
                    $module = $parts[0];
                    $actionPart = $parts[1];
                    if ($user->hasModulePermission($module, $actionPart, $companyId)) {
                        return;
                    }
                }
            }
        }

        // Check alternative permissions if provided
        foreach ($alternativePermissions as $altPermission) {
            if (is_array($altPermission) && count($altPermission) === 2) {
                // New format: [module, action]
                if ($user->hasModulePermission($altPermission[0], $altPermission[1], $companyId)) {
                    return;
                }
            } else {
                // Old format: string
                if ($user->hasPermission($altPermission, $companyId)) {
                    return;
                }
                
                // Also try to parse as module.action format
                if (str_contains($altPermission, '.')) {
                    $parts = explode('.', $altPermission, 2);
                    if (count($parts) === 2) {
                        $module = $parts[0];
                        $actionPart = $parts[1];
                        if ($user->hasModulePermission($module, $actionPart, $companyId)) {
                            return;
                        }
                    }
                }
            }
        }

        abort(403, 'You do not have permission to perform this action.');
    }

    protected function planEntitlements(): PlanEntitlementService
    {
        return app(PlanEntitlementService::class);
    }

    protected function denyEntitlement(string $message): never
    {
        $user = Auth::user();
        $company = $user?->getActiveCompany();

        if ($company && app(PartnerEntitlementService::class)->isManagedClient($company->id)) {
            throw new HttpResponseException(
                back()->with('error', $message)
            );
        }

        throw new HttpResponseException(
            redirect()->route('subscriptions.upgrade')->with('error', $message)
        );
    }

    protected function requirePlanExport(int $companyId, string $exportCode, ?int $fiscalYear = null): void
    {
        $check = $this->planEntitlements()->canExport($companyId, $exportCode, $fiscalYear);
        if (!$check['allowed']) {
            $this->denyEntitlement($check['message']);
        }
    }

    protected function requireDisclosureExport(int $companyId, string $exportCode, ?int $fiscalYear = null): void
    {
        $disclosureCheck = $this->planEntitlements()->canExportDisclosures($companyId, $fiscalYear);
        if (!$disclosureCheck['allowed']) {
            $this->denyEntitlement($disclosureCheck['message']);
        }

        $this->requirePlanExport($companyId, $exportCode, $fiscalYear);
    }

    protected function requireBulkImport(int $companyId): void
    {
        $check = $this->planEntitlements()->canBulkImport($companyId);
        if (!$check['allowed']) {
            $this->denyEntitlement($check['message']);
        }
    }

    protected function requireBulkExport(int $companyId): void
    {
        $check = $this->planEntitlements()->canBulkExport($companyId);
        if (!$check['allowed']) {
            $this->denyEntitlement($check['message']);
        }
    }

    protected function requireHelpGuide(int $companyId): void
    {
        $check = $this->planEntitlements()->canAccessHelpGuide($companyId);
        if (!$check['allowed']) {
            $this->denyEntitlement($check['message']);
        }
    }

    protected function requireScope3Access(int $companyId): void
    {
        $check = $this->planEntitlements()->canAccessScope3($companyId);
        if (!$check['allowed']) {
            $this->denyEntitlement($check['message']);
        }
    }
}
