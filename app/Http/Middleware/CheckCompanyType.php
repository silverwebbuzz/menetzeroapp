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
        
        // If no company, allow access to dashboard and company setup only
        // Dashboard controller will handle showing company setup prompt
        if (!$company) {
            // Only allow dashboard and company setup routes
            $allowedRoutes = [
                'client.dashboard', 
                'partner.dashboard', 
                'company.setup', 
                'company.setup.store',
                'logout', // Allow logout
            ];
            $routeName = $request->route() ? $request->route()->getName() : null;
            
            // If route name exists and is not in allowed list, redirect to dashboard with message
            if ($routeName && !in_array($routeName, $allowedRoutes)) {
                // Redirect to dashboard with a message instead of showing 403
                return redirect()->route('client.dashboard')
                    ->with('error', 'Please complete your company setup first to access this feature.');
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

