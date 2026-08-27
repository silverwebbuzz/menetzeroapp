<?php

namespace App\Support;

use App\Models\User;

/**
 * Permission gates for the company-portal sidebar.
 *
 * SINGLE SOURCE OF TRUTH, shared by BOTH themes' nav-client.blade.php.
 * Previously this logic was copy-pasted between the two navs and the copies
 * had already drifted: the old-theme nav hid Billing and Find-a-consultant
 * from consultant-managed workspaces and gated Reporting Settings behind
 * isAdmin, while the new-theme nav did neither. That is risk R-1 in
 * documentation/redesign.md — a lower tier seeing a paid feature.
 *
 * The gating below follows the OLD nav (the stricter of the two), so
 * nothing widens.
 *
 * This lives in PHP rather than a Blade partial on purpose. A partial would
 * have to be @include-d, and @include renders in a CHILD scope — variables
 * it defines are discarded when it returns, so the including view never
 * sees them. Returning an array from a static method has no such problem.
 *
 * Gating here is PERMISSION-based, not plan-based. App\Support\PlanGate
 * (shared separately as $gate by PlanGateComposer) handles plan
 * entitlements; the two are deliberately independent.
 */
class NavigationGates
{
    /**
     * Resolve every gate key referenced by config/navigation.php.
     *
     * @return array<string, bool>
     */
    public static function forUser(?User $user = null): array
    {
        // Always the web guard, so admin users (App\Models\Admin) never hit
        // client-specific methods.
        $user ??= auth('web')->user();

        // Works for both owners and staff members, if the method exists.
        $activeCompany = ($user && method_exists($user, 'getActiveCompany'))
            ? $user->getActiveCompany()
            : null;

        $hasCompany = $activeCompany !== null;
        $companyId = $activeCompany?->id;

        // Super admin and company admin hold all module permissions.
        $isAdmin = (bool) ($user && ($user->isAdmin() || ($companyId && $user->isCompanyAdmin($companyId))));

        $canViewLocations = $isAdmin || ($hasCompany && (
            $user->hasPermission('locations.*', $companyId) ||
            $user->hasPermission('manage_locations', $companyId) ||
            $user->hasModulePermission('locations', 'view', $companyId)
        ));

        $canViewQuickInput = $isAdmin || ($hasCompany && (
            $user->hasPermission('measurements.view', $companyId) ||
            $user->hasPermission('measurements.*', $companyId) ||
            $user->hasPermission('manage_measurements', $companyId) ||
            $user->hasModulePermission('measurements', 'view', $companyId)
        ));

        $canViewReports = $isAdmin || ($hasCompany && (
            $user->hasPermission('reports.view', $companyId) ||
            $user->hasPermission('reports.*', $companyId) ||
            $user->hasModulePermission('reports', 'view', $companyId)
        ));

        $canViewDisclosures = $isAdmin || ($hasCompany && (
            $user->hasPermission('disclosures.view', $companyId) ||
            $user->hasPermission('disclosures.*', $companyId) ||
            $user->hasModulePermission('disclosures', 'view', $companyId) ||
            $canViewReports
        ));

        $canViewStaff = $isAdmin || ($hasCompany && $user->hasModulePermission('staff_management', 'view', $companyId));
        $canViewRoles = $isAdmin || ($hasCompany && $user->hasModulePermission('roles_permissions', 'view', $companyId));

        // A consultant-managed client does not handle its own billing —
        // the agency does.
        $isManagedClientWorkspace = $activeCompany
            && method_exists($activeCompany, 'isManagedClient')
            && $activeCompany->isManagedClient();

        return [
            // Gate keys referenced by config/navigation.php. An unknown key
            // resolves to false in NavigationMap — fail CLOSED, never open.
            'always'      => true,
            'company'     => $hasCompany,
            'locations'   => (bool) $canViewLocations,
            'quick_input' => (bool) $canViewQuickInput,
            'reports'     => (bool) $canViewReports,
            'disclosures' => (bool) $canViewDisclosures,
            'team'        => (bool) ($canViewStaff || $canViewRoles),
            'billing'     => $isAdmin && ! $isManagedClientWorkspace,
            'admin'       => $isAdmin,
        ];
    }
}
