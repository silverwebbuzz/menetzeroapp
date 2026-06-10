<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * P18 — Routes that do not apply to partner-managed client workspaces.
 */
class RestrictManagedClientWorkspace
{
    /** @var list<string> */
    protected array $blockedPrefixes = [
        'roles.',
        'staff.',
        'client.consultants.',
        'subscriptions.',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('web')->user();
        $company = $user?->getActiveCompany();

        if (!$company?->isManagedClient()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';

        foreach ($this->blockedPrefixes as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return redirect()
                    ->route('client.dashboard')
                    ->with('error', 'This feature is not available in a managed client workspace.');
            }
        }

        return $next($request);
    }
}
