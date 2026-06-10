<?php

namespace App\Http\Middleware;

use App\Services\ConsultantAgencyWorkspaceService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * P15 — Agency routes: consultant session (primary) or linked web user.
 */
class EnsureConsultantAgencyAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('consultant')->check()) {
            return $next($request);
        }

        $user = Auth::guard('web')->user();

        if ($user && app(ConsultantAgencyWorkspaceService::class)->isConsultantOrgUser($user)) {
            return $next($request);
        }

        return redirect()->route('consultant.login')
            ->with('error', 'Sign in to your consultant account to manage clients and agency packs.');
    }
}
