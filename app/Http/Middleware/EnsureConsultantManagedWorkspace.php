<?php

namespace App\Http\Middleware;

use App\Services\ConsultantAgencyWorkspaceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * P18 — Validates consultant ↔ managed client workspace session on client routes.
 */
class EnsureConsultantManagedWorkspace
{
    public function __construct(
        protected ConsultantAgencyWorkspaceService $workspace,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('web')->user();

        if (!$user || $user->isAdmin()) {
            return $next($request);
        }

        $this->workspace->purgeInvalidActingSession($user);

        $company = $user->getActiveCompany();

        if ($company?->isConsultantOrg()) {
            return redirect()
                ->route('consultant.dashboard')
                ->with('info', 'Open a managed client workspace from the agency hub to use client tools.');
        }

        if ($company?->isManagedClient() && !$this->workspace->canActOnManagedClient($user, $company)) {
            return redirect()
                ->route('consultant.workspace.switcher')
                ->with('error', 'Select a managed client workspace from your agency hub.');
        }

        if ($company?->isManagedClient()) {
            $request->attributes->set('consultant_engagement', $this->workspace->engagementForActing($user));
            $request->attributes->set('consultant_workspace_read_only', $this->workspace->isReadOnlyWorkspace());
        }

        return $next($request);
    }
}
