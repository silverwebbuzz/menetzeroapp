<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

abstract class Controller
{
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
        if ($user->isAdmin() || $user->isCompanyAdmin()) {
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
            }
        }

        abort(403, 'You do not have permission to perform this action.');
    }
}
