<?php

namespace App\Http\Middleware;

use App\Services\OnboardingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks data-entry and reporting routes until business profile + location exist.
 */
class EnsureOnboardingComplete
{
    public function __construct(protected OnboardingService $onboarding)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('web')->user();

        if (!$user || $user->isAdmin()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        $alwaysAllowed = [
            'client.dashboard',
            'company.setup.store',
            'locations.create',
            'locations.store',
            'locations.store-step',
            'logout',
            'account.selector',
            'account.switch',
            'client.profile',
            'profile.update.personal',
            'profile.update.password',
            'profile.update.company',
            'consultant.workspace.exit',
        ];

        if ($routeName && in_array($routeName, $alwaysAllowed, true)) {
            return $next($request);
        }

        $step = $this->onboarding->currentStep($user);

        if ($step === 'complete') {
            return $next($request);
        }

        if ($step === 'business') {
            return redirect()->route('client.dashboard')
                ->with('error', 'Please complete your business profile before continuing.');
        }

        return redirect()->route('locations.create', ['onboarding' => 1])
            ->with('error', 'Please add at least one business location before entering emission data.');
    }
}
