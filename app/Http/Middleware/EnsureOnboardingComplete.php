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
            // Billing must stay reachable before the location step. The paywall
            // (EnsureSubscribed) sits between the business profile and the first
            // location, so without these a company with no location yet would be
            // sent here to pay and immediately bounced back to add a location.
            'subscriptions.index',
            'subscriptions.upgrade',
            'subscriptions.process-upgrade',
            'subscriptions.checkout',
            'subscriptions.payment.razorpay',
            'subscriptions.billing',
            'subscriptions.coupon.preview',
            'subscriptions.current-plan',
            'subscriptions.payment-history',
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
