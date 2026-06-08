<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SubscriptionService;
use App\Services\PaymentService;
use App\Models\SubscriptionPlan;
use App\Models\ClientSubscription;
use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Data\SubscriptionPlanMatrix;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    protected $subscriptionService;
    protected $paymentService;

    public function __construct(SubscriptionService $subscriptionService, PaymentService $paymentService)
    {
        $this->subscriptionService = $subscriptionService;
        $this->paymentService = $paymentService;
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
            return redirect()->route('subscriptions.index')
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
            ->get()
            ->keyBy('plan_code');

        $planMeta = SubscriptionPlanMatrix::plans();
        $comparisonColumns = SubscriptionPlanMatrix::columns();
        $featureRows = SubscriptionPlanMatrix::featureRows();
        $scope3AddOns = SubscriptionPlanMatrix::scope3AddOns();
        $enabledGateways = PaymentGateway::enabled();

        return view('client.subscriptions.upgrade', compact(
            'currentSubscription',
            'availablePlans',
            'company',
            'planMeta',
            'comparisonColumns',
            'featureRows',
            'scope3AddOns',
            'enabledGateways'
        ));
    }

    /**
     * Process subscription selection. Free plans activate immediately; paid
     * plans are routed through the selected payment gateway.
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
        ]);

        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        if ($plan->plan_category !== 'client') {
            return back()->withErrors(['plan_id' => 'Invalid plan selected.'])->withInput();
        }

        // Free plan (or zero-priced): no payment required, activate now.
        if ((float) $plan->price_annual <= 0) {
            try {
                $this->subscriptionService->subscribeClient($company->id, $plan->id, [
                    'billing_cycle' => 'annual',
                    'payment_method' => 'free',
                    'auto_renew' => $request->has('auto_renew'),
                ]);

                return redirect()->route('subscriptions.current-plan')
                    ->with('success', 'Subscription updated successfully!');
            } catch (\Exception $e) {
                return back()->withErrors(['error' => $e->getMessage()])->withInput();
            }
        }

        // Paid plan: a payment gateway must be selected and configured.
        $request->validate([
            'gateway' => 'required|in:razorpay,cashfree',
        ]);

        $gateway = PaymentGateway::forGateway($request->gateway);
        if (!$gateway || !$gateway->is_enabled || !$gateway->isConfigured()) {
            return redirect()->route('subscriptions.upgrade')
                ->with('error', 'The selected payment method is not available right now. Please try another.');
        }

        // Create a pending transaction we can reconcile after the gateway returns.
        $transaction = PaymentTransaction::create([
            'company_id' => $company->id,
            'transaction_type' => 'subscription',
            'amount' => $plan->price_annual,
            'currency' => $plan->currency ?? 'AED',
            'status' => 'pending',
            'payment_method' => $gateway->gateway,
            'description' => 'Subscription: ' . $plan->plan_name . ' (annual)',
            'metadata' => [
                'plan_id' => $plan->id,
                'auto_renew' => $request->has('auto_renew'),
            ],
        ]);

        try {
            $user = Auth::user();
            $metadata = $transaction->metadata;

            if ($gateway->gateway === 'razorpay') {
                $order = $this->paymentService->createRazorpayOrder(
                    $gateway,
                    $plan->price_annual,
                    $transaction->currency,
                    'txn_' . $transaction->id,
                    ['plan' => $plan->plan_code, 'company_id' => (string) $company->id]
                );
                $metadata['razorpay_order_id'] = $order['id'];
            } else {
                $cfOrderId = 'txn_' . $transaction->id . '_' . Str::lower(Str::random(6));
                $returnUrl = route('subscriptions.payment.cashfree') . '?order_id={order_id}';
                $order = $this->paymentService->createCashfreeOrder(
                    $gateway,
                    $cfOrderId,
                    $plan->price_annual,
                    $transaction->currency,
                    [
                        'id' => 'cust_' . $company->id,
                        'name' => $user->name ?? $company->name,
                        'email' => $user->email ?? '',
                        'phone' => $user->phone ?? '0000000000',
                    ],
                    $returnUrl
                );
                $metadata['cashfree_order_id'] = $cfOrderId;
                $metadata['cashfree_payment_session_id'] = $order['payment_session_id'] ?? null;
            }

            $transaction->metadata = $metadata;
            $transaction->save();

            return redirect()->route('subscriptions.checkout', $transaction->id);
        } catch (\Throwable $e) {
            $transaction->update(['status' => 'failed']);
            return redirect()->route('subscriptions.upgrade')
                ->with('error', 'Unable to start payment: ' . $e->getMessage());
        }
    }

    /**
     * Render the gateway checkout page for a pending transaction.
     */
    public function checkout($id)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isClient()) {
            return redirect()->route('client.dashboard')->with('error', 'Access denied.');
        }

        $transaction = PaymentTransaction::where('id', $id)
            ->where('company_id', $company->id)
            ->firstOrFail();

        if ($transaction->status !== 'pending') {
            return redirect()->route('subscriptions.current-plan')
                ->with('info', 'This payment has already been processed.');
        }

        $gateway = PaymentGateway::forGateway($transaction->payment_method);
        if (!$gateway) {
            return redirect()->route('subscriptions.upgrade')->with('error', 'Payment method unavailable.');
        }

        $plan = SubscriptionPlan::find($transaction->metadata['plan_id'] ?? null);
        $user = Auth::user();

        return view('client.subscriptions.checkout', compact('transaction', 'gateway', 'plan', 'company', 'user'));
    }

    /**
     * Razorpay checkout success handler (posted from the checkout page JS).
     */
    public function razorpayCallback(Request $request)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isClient()) {
            return redirect()->route('client.dashboard')->with('error', 'Access denied.');
        }

        $request->validate([
            'transaction_id' => 'required',
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $transaction = PaymentTransaction::where('id', $request->transaction_id)
            ->where('company_id', $company->id)
            ->firstOrFail();

        $gateway = PaymentGateway::forGateway('razorpay');
        $valid = $gateway && $this->paymentService->verifyRazorpaySignature(
            $gateway,
            $request->razorpay_order_id,
            $request->razorpay_payment_id,
            $request->razorpay_signature
        );

        if (!$valid) {
            $transaction->update(['status' => 'failed']);
            return redirect()->route('subscriptions.upgrade')
                ->with('error', 'Payment verification failed. You were not charged for a subscription change.');
        }

        return $this->completePaidSubscription($transaction, [
            'razorpay_payment_id' => $request->razorpay_payment_id,
            'razorpay_order_id' => $request->razorpay_order_id,
        ], $request->razorpay_payment_id);
    }

    /**
     * Cashfree return-URL handler. Verifies the order status server-side.
     */
    public function cashfreeCallback(Request $request)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isClient()) {
            return redirect()->route('client.dashboard')->with('error', 'Access denied.');
        }

        $orderId = (string) $request->query('order_id');
        if ($orderId === '') {
            return redirect()->route('subscriptions.upgrade')->with('error', 'Missing payment reference.');
        }

        // order_id is "txn_{id}_{rand}".
        $txnId = explode('_', $orderId)[1] ?? null;
        $transaction = PaymentTransaction::where('id', $txnId)
            ->where('company_id', $company->id)
            ->firstOrFail();

        if (($transaction->metadata['cashfree_order_id'] ?? null) !== $orderId) {
            return redirect()->route('subscriptions.upgrade')->with('error', 'Payment reference mismatch.');
        }

        if ($transaction->status === 'completed') {
            return redirect()->route('subscriptions.current-plan')
                ->with('success', 'Subscription is already active.');
        }

        $gateway = PaymentGateway::forGateway('cashfree');
        $status = $gateway ? $this->paymentService->getCashfreeOrderStatus($gateway, $orderId) : null;

        if ($status !== 'PAID') {
            $transaction->update(['status' => 'failed']);
            return redirect()->route('subscriptions.upgrade')
                ->with('error', 'Payment not completed (status: ' . ($status ?? 'unknown') . ').');
        }

        return $this->completePaidSubscription($transaction, ['cashfree_order_id' => $orderId], $orderId);
    }

    /**
     * Mark a transaction paid and activate the subscription.
     */
    private function completePaidSubscription(PaymentTransaction $transaction, array $gatewayRefs, ?string $reference)
    {
        try {
            $this->subscriptionService->completeTransaction($transaction, $gatewayRefs);
        } catch (\Throwable $e) {
            return redirect()->route('subscriptions.upgrade')
                ->with('error', 'Payment received but the subscription could not be activated. Please contact support.');
        }

        $planName = optional(SubscriptionPlan::find($transaction->metadata['plan_id'] ?? null))->plan_name ?? 'subscription';

        return redirect()->route('subscriptions.current-plan')
            ->with('success', 'Payment successful — your ' . $planName . ' plan is now active!');
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
            return redirect()->route('subscriptions.index')
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

        return redirect()->route('subscriptions.current-plan')
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

