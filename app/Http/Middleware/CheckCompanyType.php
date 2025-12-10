<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCompanyType
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $type): Response
    {
        $user = auth()->user();
        
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        // Skip for super admin
        if ($user->isAdmin()) {
            return $next($request);
        }

        $company = $user->getActiveCompany();
        
        if (!$company) {
            abort(403, 'No company access');
        }

        // Check company type
        if ($type === 'client' && !$company->isClient()) {
            abort(403, 'This route is for clients only');
        }

        if ($type === 'partner' && !$company->isPartner()) {
            abort(403, 'This route is for partners only');
        }

        return $next($request);
    }
}

