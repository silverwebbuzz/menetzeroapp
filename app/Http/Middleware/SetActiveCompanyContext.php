<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\UserActiveContext;

class SetActiveCompanyContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get user from web guard
        $user = auth('web')->user();
        
        if (!$user) {
            return $next($request);
        }

        // Skip for super admin
        if ($user->isAdmin()) {
            return $next($request);
        }

        if ($user->hasMultipleCompanyAccess()) {
            // Check if user has selected a company
            $context = $user->activeContext;
            $activeCompanyId = $context ? $context->active_company_id : null;
            
            // If no active company selected, redirect to account selector
            if (!$activeCompanyId) {
                // Check if already on account selector page
                if (!$request->routeIs('account.selector')) {
                    return redirect()->route('account.selector');
                }
            } else {
                // Set active company in request
                $request->merge(['active_company_id' => $activeCompanyId]);
            }
        } else {
            // Single company access - use their company
            $company = $user->getActiveCompany();
            if ($company) {
                $request->merge(['active_company_id' => $company->id]);
            }
        }
        
        return $next($request);
    }
}

