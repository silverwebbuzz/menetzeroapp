<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SubscriptionService;
use App\Models\SubscriptionPlan;
use App\Models\PartnerSubscription;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Display subscription plans and current subscription.
     */
    public function index()
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isPartner()) {
            return redirect()->route('partner.dashboard')
                ->with('error', 'Access denied.');
        }

        $currentSubscription = $this->subscriptionService->getActiveSubscription($company->id, 'partner');
        $availablePlans = SubscriptionPlan::where('plan_category', 'partner')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('partner.subscriptions.index', compact('currentSubscription', 'availablePlans', 'company'));
    }

    /**
     * Show current subscription details.
     */
    public function currentPlan()
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isPartner()) {
            return redirect()->route('partner.dashboard')
                ->with('error', 'Access denied.');
        }

        $subscription = $this->subscriptionService->getActiveSubscription($company->id, 'partner');
        
        if (!$subscription) {
            return redirect()->route('partner.subscriptions.index')
                ->with('info', 'You do not have an active subscription. Please choose a plan.');
        }

        // Get usage stats
        $clientLimit = $this->subscriptionService->getClientLimit($company->id);
        $currentClientCount = $this->subscriptionService->getClientCount($company->id);

        return view('partner.subscriptions.current-plan', compact('subscription', 'company', 'clientLimit', 'currentClientCount'));
    }

    /**
     * Show upgrade/change plan page.
     */
    public function upgrade()
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isPartner()) {
            return redirect()->route('partner.dashboard')
                ->with('error', 'Access denied.');
        }

        $currentSubscription = $this->subscriptionService->getActiveSubscription($company->id, 'partner');
        $availablePlans = SubscriptionPlan::where('plan_category', 'partner')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('partner.subscriptions.upgrade', compact('currentSubscription', 'availablePlans', 'company'));
    }

    /**
     * Process subscription upgrade/change.
     */
    public function processUpgrade(Request $request)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isPartner()) {
            return redirect()->route('partner.dashboard')
                ->with('error', 'Access denied.');
        }

        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:annual,monthly',
        ]);

        $plan = SubscriptionPlan::findOrFail($request->plan_id);
        
        if ($plan->plan_category !== 'partner') {
            return back()->withErrors(['plan_id' => 'Invalid plan selected.'])->withInput();
        }

        try {
            $this->subscriptionService->subscribePartner($company->id, $plan->id, [
                'billing_cycle' => $request->billing_cycle,
                'payment_method' => $request->payment_method ?? 'manual',
                'auto_renew' => $request->has('auto_renew'),
            ]);

            return redirect()->route('partner.subscriptions.current-plan')
                ->with('success', 'Subscription updated successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Show billing information.
     */
    public function billing()
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isPartner()) {
            return redirect()->route('partner.dashboard')
                ->with('error', 'Access denied.');
        }

        $subscription = $this->subscriptionService->getActiveSubscription($company->id, 'partner');
        
        if (!$subscription) {
            return redirect()->route('partner.subscriptions.index')
                ->with('info', 'You do not have an active subscription.');
        }

        return view('partner.subscriptions.billing', compact('subscription', 'company'));
    }

    /**
     * Show payment history.
     */
    public function paymentHistory()
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isPartner()) {
            return redirect()->route('partner.dashboard')
                ->with('error', 'Access denied.');
        }

        $subscriptions = PartnerSubscription::where('company_id', $company->id)
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('partner.subscriptions.payment-history', compact('subscriptions', 'company'));
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isPartner()) {
            return redirect()->route('partner.dashboard')
                ->with('error', 'Access denied.');
        }

        $subscription = $this->subscriptionService->getActiveSubscription($company->id, 'partner');
        
        if (!$subscription) {
            return back()->with('error', 'No active subscription found.');
        }

        $subscription->update([
            'status' => 'cancelled',
            'auto_renew' => false,
        ]);

        return redirect()->route('partner.subscriptions.current-plan')
            ->with('success', 'Subscription cancelled successfully.');
    }
}

