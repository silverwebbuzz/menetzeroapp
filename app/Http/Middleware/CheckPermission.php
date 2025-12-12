<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = auth('web')->user();
        
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        $company = $user->getActiveCompany();
        $companyId = $company ? $company->id : null;

        // Super admin and company admin bypass permission checks
        if ($user->isAdmin() || ($companyId && $user->isCompanyAdmin($companyId))) {
            return $next($request);
        }

        // Check permission using both name format and module/action format
        $hasPermission = $user->hasPermission($permission, $companyId);
        
        // Also try to parse as module.action format if permission name check failed
        if (!$hasPermission && str_contains($permission, '.')) {
            $parts = explode('.', $permission, 2);
            if (count($parts) === 2) {
                $module = $parts[0];
                $action = $parts[1];
                $hasPermission = $user->hasModulePermission($module, $action, $companyId);
            }
        }

        if (!$hasPermission) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}

