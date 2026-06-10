<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Managed clients have no direct subscription — billing lives on the partner account.
 */
class RestrictManagedClientBilling
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('web')->user();
        $company = $user?->getActiveCompany();

        if ($company?->isManagedClient()) {
            return redirect()
                ->route('partner.dashboard')
                ->with('error', 'Subscriptions and billing are managed on your agency partner account.');
        }

        return $next($request);
    }
}
