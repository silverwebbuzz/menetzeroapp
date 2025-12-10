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

        // Super admin and company admin bypass permission checks
        if ($user->isAdmin() || $user->isCompanyAdmin()) {
            return $next($request);
        }

        $company = $user->getActiveCompany();
        $companyId = $company ? $company->id : null;

        if (!$user->hasPermission($permission, $companyId)) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}

