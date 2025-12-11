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

        // 1 user = 1 company - get the single company
        $company = $user->getActiveCompany();
        if ($company) {
            $request->merge(['active_company_id' => $company->id]);
        }
        
        return $next($request);
    }
}

