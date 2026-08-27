{{--
    MENetZero — shared navigation permission gates.

    SINGLE SOURCE OF TRUTH for company-portal nav gating, included by BOTH
    themes' nav-client.blade.php. Previously this block was copy-pasted
    between the two navs, and the copies could drift — a link the old nav
    hid could appear in the new one, exposing a paid feature (risk R-1 in
    documentation/redesign.md). One copy makes that impossible.

    The logic below is reproduced VERBATIM from the pre-existing block in
    layouts/partials/nav-client.blade.php. It is permission-based, not
    plan-based: PlanGateComposer separately shares $gate (a PlanGate) and
    $companyRenewalNudge. There is no planGate variable and no allows()
    method.

    Defines, for the including view:
      $user, $activeCompany, $hasCompany, $companyId, $isAdmin
      $canViewLocations, $canViewQuickInput, $canViewReports,
      $canViewDisclosures, $canViewStaff, $canViewRoles
      $navGates  — the keyed map config/navigation.php 'gate' values resolve
                   against.
--}}
@php
    // Always use the web guard here so admin users (App\Models\Admin) don't
    // hit client-specific methods.
    $user = auth('web')->user();

    // Works for both owners and staff members, only if the method exists.
    $activeCompany = ($user && method_exists($user, 'getActiveCompany'))
        ? $user->getActiveCompany()
        : null;
    $hasCompany = $activeCompany !== null;
    $companyId = $activeCompany ? $activeCompany->id : null;

    // Super admin and company admin have all module permissions.
    $isAdmin = $user && ($user->isAdmin() || ($companyId && $user->isCompanyAdmin($companyId)));

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

    // A consultant-managed client does not handle its own billing — the
    // agency does. The pre-2.0 old-theme nav hid Billing and Consultants in
    // this case; the new-theme nav did NOT, which is exactly the kind of
    // drift this shared file exists to prevent. Gating below follows the
    // OLD nav (the stricter of the two) so nothing widens.
    $isManagedClientWorkspace = $activeCompany && $activeCompany->isManagedClient();

    // Keys referenced by 'gate' in config/navigation.php. An unknown key
    // resolves to false in the renderer — fail CLOSED, never open.
    $navGates = [
        'always'      => true,
        'company'     => $hasCompany,
        'locations'   => $canViewLocations,
        'quick_input' => $canViewQuickInput,
        'reports'     => $canViewReports,
        'disclosures' => $canViewDisclosures,
        'team'        => $canViewStaff || $canViewRoles,
        // Admin-only, and never for a workspace the agency bills for.
        'billing'     => $isAdmin && ! $isManagedClientWorkspace,
        // Reporting methodology settings were admin-only in the old nav.
        'admin'       => $isAdmin,
    ];
@endphp
