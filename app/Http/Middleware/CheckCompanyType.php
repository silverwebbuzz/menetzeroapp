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
        // Get user from web guard
        $user = auth('web')->user();
        
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
            $allowedRoutes = $type === 'partner'
                ? ['partner.dashboard', 'company.setup.store', 'logout', 'account.selector', 'account.switch']
                : ['client.dashboard', 'company.setup.store', 'logout', 'account.selector', 'account.switch'];
            $routeName = $request->route() ? $request->route()->getName() : null;
            
            // If route name exists and is not in allowed list, redirect to dashboard with message
            if ($routeName && !in_array($routeName, $allowedRoutes)) {
                $fallback = $type === 'partner' ? 'partner.dashboard' : 'client.dashboard';

                return redirect()->route($fallback)
                    ->with('error', 'Please complete your company setup first to access this feature.');
            }
            return $next($request);
        }

        if ($type === 'client') {
            if ($company->isPartner()) {
                abort(403, 'Use the agency hub at /partner/dashboard for partner organisations.');
            }

            if ($company->isManagedClient()) {
                $workspace = app(\App\Services\PartnerWorkspaceService::class);
                if (!$workspace->canActOnManagedClient($user, $company)) {
                    abort(403, 'Invalid managed client workspace. Open the client from your agency hub.');
                }

                return $next($request);
            }

            if (!$company->isClient()) {
                abort(403, 'This route is for direct client organisations only.');
            }
        }

        if ($type === 'partner') {
            $partnerHome = app(\App\Services\PartnerWorkspaceService::class)->getPartnerHomeCompany($user);
            if (!$partnerHome) {
                abort(403, 'This route is for partner organisations only.');
            }

            return $next($request);
        }

        return $next($request);
    }
}

