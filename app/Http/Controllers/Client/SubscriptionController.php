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

    /**
     * Store a new billing method.
     */
    public function storeBillingMethod(Request $request)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isClient()) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Access denied.');
        }

        $request->validate([
            'card_number' => 'required|string|min:13|max:19',
            'cardholder_name' => 'required|string|max:255',
            'card_exp_month' => 'required|string|size:2',
            'card_exp_year' => 'required|string|size:4',
            'card_brand' => 'nullable|string|max:50',
            'billing_address_line1' => 'nullable|string|max:255',
            'billing_address_line2' => 'nullable|string|max:255',
            'billing_city' => 'nullable|string|max:100',
            'billing_state' => 'nullable|string|max:100',
            'billing_postal_code' => 'nullable|string|max:20',
            'billing_country' => 'nullable|string|max:100',
            'is_default' => 'boolean',
        ]);

        // Extract last 4 digits
        $cardNumber = preg_replace('/\s+/', '', $request->card_number);
        $cardLast4 = substr($cardNumber, -4);

        // If this is set as default, unset other defaults
        if ($request->has('is_default') && $request->is_default) {
            \App\Models\ClientBillingMethod::where('company_id', $company->id)
                ->update(['is_default' => false]);
        }

        $billingMethod = \App\Models\ClientBillingMethod::create([
            'company_id' => $company->id,
            'payment_method_type' => 'card',
            'card_brand' => $request->card_brand ?? $this->detectCardBrand($cardNumber),
            'card_last4' => $cardLast4,
            'card_exp_month' => $request->card_exp_month,
            'card_exp_year' => $request->card_exp_year,
            'cardholder_name' => $request->cardholder_name,
            'is_default' => $request->has('is_default') && $request->is_default,
            'is_active' => true,
            'billing_address_line1' => $request->billing_address_line1,
            'billing_address_line2' => $request->billing_address_line2,
            'billing_city' => $request->billing_city,
            'billing_state' => $request->billing_state,
            'billing_postal_code' => $request->billing_postal_code,
            'billing_country' => $request->billing_country,
            'added_by' => Auth::id(),
        ]);

        return redirect()->route('subscriptions.billing')
            ->with('success', 'Billing method added successfully.')
            ->with('active_tab', 'billing-methods');
    }

    /**
     * Update a billing method.
     */
    public function updateBillingMethod(Request $request, \App\Models\ClientBillingMethod $billingMethod)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isClient() || $billingMethod->company_id !== $company->id) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Access denied.');
        }

        $request->validate([
            'cardholder_name' => 'required|string|max:255',
            'card_exp_month' => 'required|string|size:2',
            'card_exp_year' => 'required|string|size:4',
            'billing_address_line1' => 'nullable|string|max:255',
            'billing_address_line2' => 'nullable|string|max:255',
            'billing_city' => 'nullable|string|max:100',
            'billing_state' => 'nullable|string|max:100',
            'billing_postal_code' => 'nullable|string|max:20',
            'billing_country' => 'nullable|string|max:100',
        ]);

        $billingMethod->update([
            'card_exp_month' => $request->card_exp_month,
            'card_exp_year' => $request->card_exp_year,
            'cardholder_name' => $request->cardholder_name,
            'billing_address_line1' => $request->billing_address_line1,
            'billing_address_line2' => $request->billing_address_line2,
            'billing_city' => $request->billing_city,
            'billing_state' => $request->billing_state,
            'billing_postal_code' => $request->billing_postal_code,
            'billing_country' => $request->billing_country,
        ]);

        return redirect()->route('subscriptions.billing')
            ->with('success', 'Billing method updated successfully.')
            ->with('active_tab', 'billing-methods');
    }

    /**
     * Delete a billing method.
     */
    public function destroyBillingMethod(\App\Models\ClientBillingMethod $billingMethod)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isClient() || $billingMethod->company_id !== $company->id) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Access denied.');
        }

        // Don't allow deleting if it's the only billing method
        $totalMethods = \App\Models\ClientBillingMethod::where('company_id', $company->id)
            ->where('is_active', true)
            ->count();

        if ($totalMethods <= 1) {
            return redirect()->route('subscriptions.billing')
                ->with('error', 'Cannot delete the last billing method. Please add another one first.')
                ->with('active_tab', 'billing-methods');
        }

        $billingMethod->update(['is_active' => false]);

        return redirect()->route('subscriptions.billing')
            ->with('success', 'Billing method deleted successfully.')
            ->with('active_tab', 'billing-methods');
    }

    /**
     * Set a billing method as default.
     */
    public function setDefaultBillingMethod(\App\Models\ClientBillingMethod $billingMethod)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isClient() || $billingMethod->company_id !== $company->id) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Access denied.');
        }

        // Unset all other defaults
        \App\Models\ClientBillingMethod::where('company_id', $company->id)
            ->where('id', '!=', $billingMethod->id)
            ->update(['is_default' => false]);

        $billingMethod->update(['is_default' => true]);

        return redirect()->route('subscriptions.billing')
            ->with('success', 'Default billing method updated successfully.')
            ->with('active_tab', 'billing-methods');
    }

    /**
     * Detect card brand from card number.
     */
    private function detectCardBrand($cardNumber)
    {
        $cardNumber = preg_replace('/\s+/', '', $cardNumber);
        
        if (preg_match('/^4/', $cardNumber)) {
            return 'Visa';
        } elseif (preg_match('/^5[1-5]/', $cardNumber)) {
            return 'Mastercard';
        } elseif (preg_match('/^3[47]/', $cardNumber)) {
            return 'American Express';
        } elseif (preg_match('/^6/', $cardNumber)) {
            return 'Discover';
        }
        
        return 'Card';
    }
}

