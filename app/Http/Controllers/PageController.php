<?php

namespace App\Http\Controllers;

use App\Models\SitePage;
use App\Models\SiteSetting;
use App\Models\SubscriptionPlan;
use App\Services\CurrencyService;
use Illuminate\Http\Request;

/**
 * Public marketing + policy pages (Contact, Terms, Refunds, Privacy) and the
 * public pricing page. Required for payment gateway website whitelisting.
 */
class PageController extends Controller
{
    /** Render an editable policy page by slug. */
    public function show(string $slug)
    {
        $page = SitePage::where('slug', $slug)->where('is_published', true)->firstOrFail();
        $settings = SiteSetting::allSettings();

        return view('public.page', compact('page', 'settings'));
    }

    /** Contact page renders the editable intro + structured contact details. */
    public function contact()
    {
        $page = SitePage::where('slug', 'contact')->first();
        $settings = SiteSetting::allSettings();

        return view('public.contact', compact('page', 'settings'));
    }

    /** Public pricing page with geo-aware currency. */
    public function pricing()
    {
        $plans = SubscriptionPlan::where('plan_category', 'client')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $currency = CurrencyService::displayCurrency();
        $settings = SiteSetting::allSettings();

        return view('public.pricing', compact('plans', 'currency', 'settings'));
    }

    /** Switch the display currency and return to the previous page. */
    public function switchCurrency(Request $request, string $code)
    {
        CurrencyService::setDisplayCurrency($code);

        return redirect()->back();
    }
}
