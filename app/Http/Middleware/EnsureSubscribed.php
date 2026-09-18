<?php

namespace App\Http\Middleware;

use App\Services\ConsultantAgencyEntitlementService;
use App\Services\OnboardingService;
use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Holds a company at plan selection until it has paid.
 *
 * The paywall sits AFTER business setup, not at registration: every billing
 * path keys off company_id, and the company does not exist until the profile
 * is saved. So a new signup completes its business profile, lands here, and
 * reaches the dashboard only once a subscription is active.
 *
 * Abandoning checkout costs nothing -- the company and its details are kept,
 * and signing back in returns the user to this same page. There is no pending
 * or half-paid state to reconcile, because access is decided by reading the
 * subscription, never by a flag written during checkout.
 *
 * Who is NOT paywalled:
 *   - super admins, who never hold a client subscription;
 *   - staff invited into a company that has already paid, since the owner
 *     bought the seats (OnboardingService treats them the same way);
 *   - consultant-managed clients, whose agency is billed instead -- their
 *     entitlements come from the agency pack, so a client subscription would
 *     be the wrong thing to ask for.
 */
class EnsureSubscribed
{
    /**
     * Routes that must stay reachable while unpaid, or the paywall would trap
     * the user on a page they cannot leave or pay from.
     */
    private const ALWAYS_ALLOWED = [
        // Billing and checkout — the way out of the paywall.
        'subscriptions.index',
        'subscriptions.upgrade',
        'subscriptions.process-upgrade',
        'subscriptions.checkout',
        'subscriptions.payment.razorpay',
        'subscriptions.billing',
        'subscriptions.coupon.preview',
        'subscriptions.current-plan',
        'subscriptions.payment-history',

        // Onboarding itself: the paywall comes after these, and a user whose
        // profile is incomplete must be able to finish it.
        'client.dashboard',
        'company.setup.store',

        // Account controls that must never be locked behind payment.
        'logout',
        'account.selector',
        'account.switch',
        'client.profile',
        'profile.update.personal',
        'profile.update.password',
        'profile.update.company',
        'consultant.workspace.exit',

        // Support and guidance — someone deciding whether to buy needs these.
        'client.help',
        'client.support',
        'client.support.submit',
        'contact',
        'pricing',
    ];

    public function __construct(
        protected SubscriptionService $subscriptions,
        protected OnboardingService $onboarding,
        protected ConsultantAgencyEntitlementService $agencyEntitlements,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('portals.require_subscription')) {
            return $next($request);
        }

        $user = auth('web')->user();

        if (! $user || $user->isAdmin()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($routeName && in_array($routeName, self::ALWAYS_ALLOWED, true)) {
            return $next($request);
        }

        // Staff joining a company they do not own are covered by the owner's
        // subscription. Mirrors OnboardingService::currentStep().
        if ($user->isStaffInAnyCompany() && ! $user->ownsCompany()) {
            return $next($request);
        }

        $company = $user->getActiveCompany();

        // No company yet means business setup is still ahead, and
        // ensureOnboardingComplete already routes that case.
        if (! $company) {
            return $next($request);
        }

        // The agency pays for these, not the client.
        if ($this->agencyEntitlements->isManagedClient($company->id)) {
            return $next($request);
        }

        if ($this->subscriptions->getActiveSubscription($company->id, 'client')) {
            return $next($request);
        }

        return redirect()->route('subscriptions.upgrade')->with(
            'error',
            'Choose a package to activate your workspace. Your business details are saved.'
        );
    }
}
