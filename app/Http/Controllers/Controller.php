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
        if ($user->isAdmin() || ($companyId && $user->isCompanyAdmin($companyId))) {
            return;
        }

        // DEBUG: Log permission check details
        \Log::info('Permission Check', [
            'user_id' => $user->id,
            'company_id' => $companyId,
            'permission' => $permissionOrModule,
            'action' => $action,
            'alternatives' => $alternativePermissions,
            'is_admin' => $user->isAdmin(),
            'is_company_admin' => $companyId ? $user->isCompanyAdmin($companyId) : false,
            'custom_role' => $user->getCustomRoleForCompany($companyId) ? $user->getCustomRoleForCompany($companyId)->role_name : null,
            'user_permissions' => $user->getPermissions($companyId),
        ]);

        // New format: module and action
        if ($action !== null) {
            $hasPermission = $user->hasModulePermission($permissionOrModule, $action, $companyId);
            \Log::info('Module Permission Check', [
                'module' => $permissionOrModule,
                'action' => $action,
                'result' => $hasPermission,
            ]);
            if ($hasPermission) {
                return;
            }
        } else {
            // Old format: single permission string
            $hasPermission = $user->hasPermission($permissionOrModule, $companyId);
            \Log::info('Permission Name Check', [
                'permission' => $permissionOrModule,
                'result' => $hasPermission,
            ]);
            if ($hasPermission) {
                return;
            }
            
            // Also try to parse as module.action format and check module/action
            // e.g., "measurements.view" -> module="measurements", action="view"
            if (str_contains($permissionOrModule, '.')) {
                $parts = explode('.', $permissionOrModule, 2);
                if (count($parts) === 2) {
                    $module = $parts[0];
                    $actionPart = $parts[1];
                    $hasModulePermission = $user->hasModulePermission($module, $actionPart, $companyId);
                    \Log::info('Parsed Module Permission Check', [
                        'parsed_module' => $module,
                        'parsed_action' => $actionPart,
                        'result' => $hasModulePermission,
                    ]);
                    if ($hasModulePermission) {
                        return;
                    }
                }
            }
        }

        // Check alternative permissions if provided
        foreach ($alternativePermissions as $altPermission) {
            if (is_array($altPermission) && count($altPermission) === 2) {
                // New format: [module, action]
                $hasAltPermission = $user->hasModulePermission($altPermission[0], $altPermission[1], $companyId);
                \Log::info('Alternative Module Permission Check', [
                    'module' => $altPermission[0],
                    'action' => $altPermission[1],
                    'result' => $hasAltPermission,
                ]);
                if ($hasAltPermission) {
                    return;
                }
            } else {
                // Old format: string
                $hasAltPermission = $user->hasPermission($altPermission, $companyId);
                \Log::info('Alternative Permission Check', [
                    'permission' => $altPermission,
                    'result' => $hasAltPermission,
                ]);
                if ($hasAltPermission) {
                    return;
                }
                
                // Also try to parse as module.action format
                if (str_contains($altPermission, '.')) {
                    $parts = explode('.', $altPermission, 2);
                    if (count($parts) === 2) {
                        $module = $parts[0];
                        $actionPart = $parts[1];
                        $hasParsedAltPermission = $user->hasModulePermission($module, $actionPart, $companyId);
                        \Log::info('Parsed Alternative Permission Check', [
                            'parsed_module' => $module,
                            'parsed_action' => $actionPart,
                            'result' => $hasParsedAltPermission,
                        ]);
                        if ($hasParsedAltPermission) {
                            return;
                        }
                    }
                }
            }
        }

        \Log::warning('Permission Denied', [
            'user_id' => $user->id,
            'company_id' => $companyId,
            'permission' => $permissionOrModule,
            'action' => $action,
            'alternatives' => $alternativePermissions,
        ]);

        abort(403, 'You do not have permission to perform this action.');
    }
}
