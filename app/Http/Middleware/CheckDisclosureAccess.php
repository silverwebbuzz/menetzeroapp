<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckDisclosureAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('web')->user();
        $company = $user?->getActiveCompany();

        if (!$company) {
            abort(403, 'No active company found.');
        }

        $access = app(SubscriptionService::class)->canAccessIfrsS2($company->id);
        if (!$access['allowed']) {
            return redirect()
                ->route('subscriptions.upgrade')
                ->with('error', $access['message']);
        }

        if ($request->has('fiscal_year')) {
            session(['disclosure_fiscal_year' => (int) $request->input('fiscal_year')]);
        }

        return $next($request);
    }
}
