<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    /**
     * Check if user has permission, abort if not.
     * Supports multiple permission formats for backward compatibility.
     */
    protected function requirePermission($permission, $alternativePermissions = [])
    {
        $user = Auth::user();
        $company = $user->getActiveCompany();
        $companyId = $company ? $company->id : null;

        // Super admin and company admin bypass permission checks
        if ($user->isAdmin() || $user->isCompanyAdmin()) {
            return;
        }

        // Check primary permission
        if ($user->hasPermission($permission, $companyId)) {
            return;
        }

        // Check alternative permissions if provided
        foreach ($alternativePermissions as $altPermission) {
            if ($user->hasPermission($altPermission, $companyId)) {
                return;
            }
        }

        abort(403, 'You do not have permission to perform this action.');
    }
}
