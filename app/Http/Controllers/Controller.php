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
            \Log::info('Permission Bypassed - Admin', [
                'user_id' => $user->id,
                'is_admin' => $user->isAdmin(),
                'is_company_admin' => $companyId ? $user->isCompanyAdmin($companyId) : false,
            ]);
            return;
        }

        // DEBUG: Log permission check details
        \Log::info('=== Permission Check Start ===', [
            'user_id' => $user->id,
            'company_id' => $companyId,
            'permission' => $permissionOrModule,
            'action' => $action,
            'alternatives' => $alternativePermissions,
            'is_admin' => $user->isAdmin(),
            'is_company_admin' => $companyId ? $user->isCompanyAdmin($companyId) : false,
            'custom_role' => $user->getCustomRoleForCompany($companyId) ? $user->getCustomRoleForCompany($companyId)->role_name : null,
            'user_permissions' => $user->getPermissions($companyId),
            'permission_names' => $user->getCustomRoleForCompany($companyId) ? $user->getCustomRoleForCompany($companyId)->getPermissionNames() : [],
        ]);

        $permissionGranted = false;
        $grantedBy = null;

        // New format: module and action
        if ($action !== null) {
            $hasPermission = $user->hasModulePermission($permissionOrModule, $action, $companyId);
            \Log::info('Module Permission Check', [
                'module' => $permissionOrModule,
                'action' => $action,
                'result' => $hasPermission,
            ]);
            if ($hasPermission) {
                $permissionGranted = true;
                $grantedBy = 'module_action';
            }
        } else {
            // Old format: single permission string
            $hasPermission = $user->hasPermission($permissionOrModule, $companyId);
            \Log::info('Permission Name Check', [
                'permission' => $permissionOrModule,
                'result' => $hasPermission,
                'user_permissions_array' => $user->getPermissions($companyId),
            ]);
            if ($hasPermission) {
                $permissionGranted = true;
                $grantedBy = 'permission_name';
            } else {
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
                            'custom_role_id' => $user->getCustomRoleForCompany($companyId) ? $user->getCustomRoleForCompany($companyId)->id : null,
                        ]);
                        if ($hasModulePermission) {
                            $permissionGranted = true;
                            $grantedBy = 'parsed_module_action';
                        }
                    }
                }
            }
        }

        // Check alternative permissions if provided and main check failed
        if (!$permissionGranted) {
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
                        $permissionGranted = true;
                        $grantedBy = 'alternative_module_action';
                        break;
                    }
                } else {
                    // Old format: string
                    $hasAltPermission = $user->hasPermission($altPermission, $companyId);
                    \Log::info('Alternative Permission Check', [
                        'permission' => $altPermission,
                        'result' => $hasAltPermission,
                    ]);
                    if ($hasAltPermission) {
                        $permissionGranted = true;
                        $grantedBy = 'alternative_permission_name';
                        break;
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
                                $permissionGranted = true;
                                $grantedBy = 'parsed_alternative_module_action';
                                break;
                            }
                        }
                    }
                }
            }
        }

        if ($permissionGranted) {
            \Log::info('=== Permission GRANTED ===', [
                'user_id' => $user->id,
                'permission' => $permissionOrModule,
                'granted_by' => $grantedBy,
            ]);
            return;
        }

        \Log::error('=== Permission DENIED ===', [
            'user_id' => $user->id,
            'company_id' => $companyId,
            'permission' => $permissionOrModule,
            'action' => $action,
            'alternatives' => $alternativePermissions,
            'user_permissions' => $user->getPermissions($companyId),
            'permission_names' => $user->getCustomRoleForCompany($companyId) ? $user->getCustomRoleForCompany($companyId)->getPermissionNames() : [],
            'custom_role' => $user->getCustomRoleForCompany($companyId) ? [
                'id' => $user->getCustomRoleForCompany($companyId)->id,
                'name' => $user->getCustomRoleForCompany($companyId)->role_name,
            ] : null,
        ]);

        abort(403, 'You do not have permission to perform this action.');
    }
}
