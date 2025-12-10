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
        
        // If no company, allow access to dashboard for company setup
        // Dashboard controller will handle showing company setup prompt
        if (!$company) {
            // Only allow dashboard and company setup routes
            $allowedRoutes = ['client.dashboard', 'partner.dashboard', 'company.setup', 'company.setup.store'];
            $routeName = $request->route() ? $request->route()->getName() : null;
            if ($routeName && !in_array($routeName, $allowedRoutes)) {
                abort(403, 'No company access. Please complete company setup first.');
            }
            return $next($request);
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

