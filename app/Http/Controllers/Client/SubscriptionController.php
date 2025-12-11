<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SubscriptionService;
use App\Models\SubscriptionPlan;
use App\Models\ClientSubscription;
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
        if (!$company || !$company->isClient()) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Access denied.');
        }

        $currentSubscription = $this->subscriptionService->getActiveSubscription($company->id, 'client');
        $availablePlans = SubscriptionPlan::where('plan_category', 'client')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('client.subscriptions.index', compact('currentSubscription', 'availablePlans', 'company'));
    }

    /**
     * Show current subscription details.
     */
    public function currentPlan()
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isClient()) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Access denied.');
        }

        $subscription = $this->subscriptionService->getActiveSubscription($company->id, 'client');
        
        if (!$subscription) {
            return redirect()->route('client.subscriptions.index')
                ->with('info', 'You do not have an active subscription. Please choose a plan.');
        }

        return view('client.subscriptions.current-plan', compact('subscription', 'company'));
    }

    /**
     * Show upgrade/change plan page.
     */
    public function upgrade()
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isClient()) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Access denied.');
        }

        $currentSubscription = $this->subscriptionService->getActiveSubscription($company->id, 'client');
        $availablePlans = SubscriptionPlan::where('plan_category', 'client')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('client.subscriptions.upgrade', compact('currentSubscription', 'availablePlans', 'company'));
    }

    /**
     * Process subscription upgrade/change.
     */
    public function processUpgrade(Request $request)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isClient()) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Access denied.');
        }

        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:annual,monthly',
        ]);

        $plan = SubscriptionPlan::findOrFail($request->plan_id);
        
        if ($plan->plan_category !== 'client') {
            return back()->withErrors(['plan_id' => 'Invalid plan selected.'])->withInput();
        }

        try {
            $this->subscriptionService->subscribeClient($company->id, $plan->id, [
                'billing_cycle' => $request->billing_cycle,
                'payment_method' => $request->payment_method ?? 'manual',
                'auto_renew' => $request->has('auto_renew'),
            ]);

            return redirect()->route('client.subscriptions.current-plan')
                ->with('success', 'Subscription updated successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Show billing information with tabs.
     */
    public function billing()
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isClient()) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Access denied.');
        }

        $subscription = $this->subscriptionService->getActiveSubscription($company->id, 'client');
        
        if (!$subscription) {
            return redirect()->route('client.subscriptions.index')
                ->with('info', 'You do not have an active subscription.');
        }

        // Get payment history (if table exists)
        $paymentHistory = collect([]);
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('client_payment_transactions')) {
                $paymentHistory = \App\Models\PaymentTransaction::where('company_id', $company->id)
                    ->with('billingMethod')
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
        } catch (\Exception $e) {
            // Table doesn't exist, use empty collection
        }

        // Get billing methods
        $billingMethods = \App\Models\ClientBillingMethod::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('client.subscriptions.billing', compact('subscription', 'company', 'paymentHistory', 'billingMethods'));
    }

    /**
     * Show payment history - Redirect to billing page with transactions tab.
     */
    public function paymentHistory()
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isClient()) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Access denied.');
        }

        // Redirect to billing page with transactions tab active
        return redirect()->route('subscriptions.billing')->with('active_tab', 'transactions');
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isClient()) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Access denied.');
        }

        $subscription = $this->subscriptionService->getActiveSubscription($company->id, 'client');
        
        if (!$subscription) {
            return back()->with('error', 'No active subscription found.');
        }

        $subscription->update([
            'status' => 'cancelled',
            'auto_renew' => false,
        ]);

        return redirect()->route('client.subscriptions.current-plan')
            ->with('success', 'Subscription cancelled successfully.');
    }
}

