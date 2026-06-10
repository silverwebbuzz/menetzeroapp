<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SubscriptionService;
use App\Services\PaymentService;
use App\Services\CouponService;
use App\Models\SubscriptionPlan;
use App\Models\ClientSubscription;
use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Data\CommercialPlanComparison;
use App\Data\SubscriptionPlanMatrix;
use App\Support\PlanGate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    protected $subscriptionService;
    protected $paymentService;
    protected $couponService;

    public function __construct(
        SubscriptionService $subscriptionService,
        PaymentService $paymentService,
        CouponService $couponService
    ) {
        $this->subscriptionService = $subscriptionService;
        $this->paymentService = $paymentService;
        $this->couponService = $couponService;
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
        return redirect()->route('subscriptions.billing');
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
        $comparisonColumns = CommercialPlanComparison::PLAN_COLUMNS;
        $comparisonLabels = CommercialPlanComparison::planLabels();
        $operationsRows = CommercialPlanComparison::operationsRows();
        $downloadRows = CommercialPlanComparison::downloadRows();
        $consultantAddOns = CommercialPlanComparison::consultantAddOns();
        $enabledGateways = PaymentGateway::enabled();
        $displayCurrency = \App\Services\CurrencyService::displayCurrency();

        $planChanges = [];
        $downgradeWarnings = [];
        foreach ($availablePlans as $code => $availablePlan) {
            $planChanges[$code] = $this->subscriptionService->resolvePlanChange(
                $currentSubscription,
                $availablePlan,
                $displayCurrency
            );

            if (in_array($planChanges[$code]['type'], ['downgrade', 'downgrade_to_free'], true)) {
                $downgradeWarnings[$code] = $this->subscriptionService->getDowngradeWarnings(
                    $company->id,
                    $availablePlan
                );
            }
        }

        return view('client.subscriptions.upgrade', compact(
            'currentSubscription',
            'availablePlans',
            'company',
            'planMeta',
            'comparisonColumns',
            'comparisonLabels',
            'operationsRows',
            'downloadRows',
            'consultantAddOns',
            'enabledGateways',
            'planChanges',
            'downgradeWarnings',
            'displayCurrency'
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

        $currentSubscription = $this->subscriptionService->getActiveSubscription($company->id, 'client');
        $displayCurrency = \App\Services\CurrencyService::displayCurrency();
        $change = $this->subscriptionService->resolvePlanChange($currentSubscription, $plan, $displayCurrency);

        if ($change['type'] === 'same') {
            return redirect()->route('subscriptions.upgrade')
                ->with('info', $change['message']);
        }

        // Downgrade: schedule at renewal — no payment, no immediate limit reduction.
        if (in_array($change['type'], ['downgrade', 'downgrade_to_free'], true)) {
            if (!$currentSubscription) {
                return redirect()->route('subscriptions.upgrade')->with('error', 'No active subscription to change.');
            }

            try {
                $this->subscriptionService->scheduleDowngrade($currentSubscription, $plan);

                $message = $change['message'];
                $warnings = $this->subscriptionService->getDowngradeWarnings($company->id, $plan);
                if (!empty($warnings)) {
                    $message .= ' ' . implode(' ', $warnings);
                }

                return redirect()->route('subscriptions.billing')
                    ->with('success', $message);
            } catch (\Exception $e) {
                return back()->withErrors(['error' => $e->getMessage()])->withInput();
            }
        }

        // Upgrade with zero prorated amount — apply immediately without payment.
        if ($change['type'] === 'upgrade' && !$change['requires_payment']) {
            try {
                $this->subscriptionService->subscribeClient($company->id, $plan->id, [
                    'billing_cycle' => 'annual',
                    'payment_method' => $currentSubscription?->payment_method ?? 'free',
                    'auto_renew' => $request->has('auto_renew'),
                    'preserve_expiry' => $change['preserve_expiry'],
                ]);

                return redirect()->route('subscriptions.billing')
                    ->with('success', 'Plan upgraded successfully!');
            } catch (\Exception $e) {
                return back()->withErrors(['error' => $e->getMessage()])->withInput();
            }
        }

        // Paid change: payment gateway required.
        if (!PaymentGateway::checkoutAvailable()) {
            return redirect()->route('subscriptions.upgrade')
                ->with('error', 'Online payments are not available yet. Paid upgrades will open when checkout goes live.');
        }

        $request->validate([
            'gateway' => 'required|in:razorpay,cashfree',
        ]);

        $gateway = PaymentGateway::forGateway($request->gateway);
        if (!$gateway || !$gateway->is_enabled || !$gateway->isConfigured()) {
            return redirect()->route('subscriptions.upgrade')
                ->with('error', 'The selected payment method is not available right now. Please try another.');
        }

        $charge = [
            'currency' => $change['charge_currency'],
            'amount' => (float) $change['charge_amount'],
            'display_currency' => $displayCurrency,
        ];

        // Razorpay: INR only — recalculate in INR.
        if ($gateway->gateway === 'razorpay') {
            $inrChange = $this->subscriptionService->resolvePlanChange($currentSubscription, $plan, 'INR');
            $charge = [
                'currency' => 'INR',
                'amount' => (float) $inrChange['charge_amount'],
                'display_currency' => $displayCurrency,
            ];
        }

        $couponMeta = [];
        $couponCode = trim((string) $request->input('coupon_code', ''));

        if ($couponCode !== '') {
            try {
                $applied = $this->couponService->validateForCheckout(
                    $couponCode,
                    $company->id,
                    $plan,
                    $charge['amount'],
                    $charge['currency']
                );

                if ($applied['is_free']) {
                    $subscription = $this->subscriptionService->activateWithCoupon(
                        $company->id,
                        $plan->id,
                        [
                            'coupon_code' => $applied['coupon']->code,
                            'coupon_id' => $applied['coupon']->id,
                        ],
                        $change['preserve_expiry']
                    );

                    $this->couponService->recordRedemption(
                        $applied['coupon'],
                        $company->id,
                        $applied['discount'],
                        $charge['currency'],
                        $subscription
                    );

                    return redirect()->route('subscriptions.billing')
                        ->with('success', 'Coupon applied — your ' . $plan->plan_name . ' plan is now active!');
                }

                $couponMeta = [
                    'coupon_id' => $applied['coupon']->id,
                    'coupon_code' => $applied['coupon']->code,
                    'discount_applied' => $applied['discount'],
                    'original_amount' => $charge['amount'] + $applied['discount'],
                ];
                $charge['amount'] = $applied['final_amount'];
            } catch (\RuntimeException $e) {
                return back()->withErrors(['coupon_code' => $e->getMessage()])->withInput();
            }
        }

        if ($charge['amount'] <= 0) {
            return redirect()->route('subscriptions.upgrade')
                ->with('error', 'This plan is not available for online payment yet. Please contact support.');
        }

        $description = $change['type'] === 'upgrade' && $currentSubscription
            ? 'Plan upgrade: ' . ($currentSubscription->plan->plan_name ?? '') . ' → ' . $plan->plan_name
            : 'Subscription: ' . $plan->plan_name . ' (annual)';

        $transaction = PaymentTransaction::create([
            'company_id' => $company->id,
            'transaction_type' => 'subscription',
            'amount' => $charge['amount'],
            'currency' => $charge['currency'],
            'status' => 'pending',
            'payment_method' => $gateway->gateway,
            'description' => $description,
            'metadata' => array_merge([
                'plan_id' => $plan->id,
                'auto_renew' => $request->has('auto_renew'),
                'change_type' => $change['type'],
                'preserve_expiry' => $change['preserve_expiry'],
                'from_plan_id' => $currentSubscription?->subscription_plan_id,
            ], $couponMeta),
        ]);

        try {
            $user = Auth::user();
            $metadata = $transaction->metadata;

            if ($gateway->gateway === 'razorpay') {
                $order = $this->paymentService->createRazorpayOrder(
                    $gateway,
                    $transaction->amount,
                    $transaction->currency,
                    'txn_' . $transaction->id,
                    ['plan' => $plan->plan_code, 'company_id' => (string) $company->id]
                );
                $metadata['razorpay_order_id'] = $order['id'];
            } else {
                $cfOrderId = 'txn_' . $transaction->id . '_' . Str::lower(Str::random(6));
                $returnUrl = route('subscriptions.payment.cashfree') . '?order_id={order_id}';

                // Cashfree needs a valid 10-digit phone and a non-empty name/email.
                $phone = PaymentService::normalizePhone($user->phone ?? null)
                    ?? PaymentService::normalizePhone($company->phone ?? null)
                    ?? PaymentService::normalizePhone(\App\Models\SiteSetting::get('support_phone'))
                    ?? '9999999999';

                $customer = [
                    'id' => 'cust_' . $company->id,
                    'name' => $user->name ?: $company->name,
                    'email' => $user->email ?: ($company->email ?: 'billing@menetzero.com'),
                    'phone' => $phone,
                ];

                try {
                    $order = $this->paymentService->createCashfreeOrder(
                        $gateway,
                        $cfOrderId,
                        $transaction->amount,
                        $transaction->currency,
                        $customer,
                        $returnUrl
                    );
                } catch (\RuntimeException $e) {
                    // AED (or other currency) not approved on Cashfree yet — fall back
                    // to INR so payments still work while merchant activation is pending.
                    if ($transaction->currency !== 'INR'
                        && $this->paymentService->isCashfreeCurrencyDisabledError($e->getMessage())) {
                        $inrAmount = (float) $plan->price_inr;
                        $transaction->update(['amount' => $inrAmount, 'currency' => 'INR']);
                        $metadata['charged_in_inr_fallback'] = true;
                        $metadata['display_currency'] = $charge['display_currency'] ?? 'AED';

                        $order = $this->paymentService->createCashfreeOrder(
                            $gateway,
                            $cfOrderId,
                            $inrAmount,
                            'INR',
                            $customer,
                            $returnUrl
                        );

                        session()->flash(
                            'info',
                            'AED checkout is being activated on Cashfree (pending approval). '
                            . 'You will be charged the INR equivalent (₹' . number_format($inrAmount, 0) . ') for now.'
                        );
                    } else {
                        throw $e;
                    }
                }

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
            return redirect()->route('subscriptions.billing')
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
            return redirect()->route('subscriptions.billing')
                ->with('success', 'Subscription is already active.');
        }

        $gateway = PaymentGateway::forGateway('cashfree');
        if (!$gateway) {
            return redirect()->route('subscriptions.upgrade')->with('error', 'Payment method unavailable.');
        }

        $orderStatus = $this->paymentService->getCashfreeOrderStatus($gateway, $orderId);

        // Order is settled — activate immediately.
        if ($orderStatus === 'PAID') {
            return $this->completePaidSubscription($transaction, ['cashfree_order_id' => $orderId], $orderId);
        }

        // Not PAID yet: inspect the latest payment attempt to decide what to tell
        // the customer (pending / dropped / failed).
        $paymentStatus = $this->paymentService->getCashfreePaymentStatus($gateway, $orderId);

        if ($paymentStatus === 'SUCCESS') {
            // Payment captured but order not flipped to PAID yet — safe to activate.
            return $this->completePaidSubscription($transaction, ['cashfree_order_id' => $orderId], $orderId);
        }

        // Still processing — leave the transaction pending and let the webhook
        // activate it once the bank confirms. Do NOT mark it failed.
        if ($paymentStatus === 'PENDING' || ($orderStatus === 'ACTIVE' && $paymentStatus === null)) {
            $transaction->update(['status' => 'pending']);
            return redirect()->route('subscriptions.index')
                ->with('info', 'Your payment is being processed. We\'ll activate your plan automatically once your bank confirms it — please don\'t pay again.');
        }

        // Customer abandoned the payment page.
        if (in_array($paymentStatus, ['USER_DROPPED', 'CANCELLED'], true)) {
            $transaction->update(['status' => 'cancelled']);
            return redirect()->route('subscriptions.upgrade')
                ->with('error', 'Payment was cancelled. You can try again whenever you\'re ready — you were not charged.');
        }

        // FAILED / EXPIRED / TERMINATED / anything else.
        $transaction->update(['status' => 'failed']);
        return redirect()->route('subscriptions.upgrade')
            ->with('error', 'Payment failed (status: ' . ($paymentStatus ?? $orderStatus ?? 'unknown') . '). You were not charged. Please try again.');
    }

    /**
     * Mark a transaction paid and activate the subscription.
     */
    private function completePaidSubscription(PaymentTransaction $transaction, array $gatewayRefs, ?string $reference)
    {
        try {
            $this->subscriptionService->completeTransaction($transaction, $gatewayRefs);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Subscription activation failed after payment', [
                'transaction_id' => $transaction->id,
                'company_id' => $transaction->company_id,
                'reference' => $reference,
                'gateway_refs' => $gatewayRefs,
                'metadata' => $transaction->metadata,
                'exception' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $message = 'Payment received but the subscription could not be activated. Please contact support.';
            if (config('app.debug')) {
                $message .= ' [debug: ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine() . ']';
            }

            return redirect()->route('subscriptions.upgrade')->with('error', $message);
        }

        $planName = optional(SubscriptionPlan::find($transaction->metadata['plan_id'] ?? null))->plan_name ?? 'subscription';

        return redirect()->route('subscriptions.billing')
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

        $scheduledPlan = $this->subscriptionService->getScheduledRenewalPlan($subscription);
        $scheduledDowngradeWarnings = $scheduledPlan
            ? $this->subscriptionService->getDowngradeWarnings($company->id, $scheduledPlan)
            : [];

        $isPaidPlan = $this->subscriptionService->isPaidSubscription($subscription);
        $cancellationScheduled = $this->subscriptionService->isCancellationScheduled($subscription);
        $isComplimentary = $this->subscriptionService->isComplimentary($subscription);
        $provisionLabel = $this->subscriptionService->getProvisionLabel($subscription);

        $gate = PlanGate::forCompany($company->id);
        $usageMeters = $gate->usageMeters();
        $dataEntitlements = $gate->dataEntitlementsList();
        $downloadEntitlements = $gate->downloadEntitlementsList();
        $consultantDirectoryLabel = $gate->consultantDirectoryLabel();
        $daysRemaining = max(0, (int) now()->diffInDays($subscription->expires_at, false));

        return view('client.subscriptions.billing', compact(
            'subscription',
            'company',
            'paymentHistory',
            'billingMethods',
            'scheduledPlan',
            'scheduledDowngradeWarnings',
            'isPaidPlan',
            'cancellationScheduled',
            'isComplimentary',
            'provisionLabel',
            'gate',
            'usageMeters',
            'dataEntitlements',
            'downloadEntitlements',
            'consultantDirectoryLabel',
            'daysRemaining'
        ));
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
     * Schedule cancellation at the end of the current paid term.
     * Free plans have nothing to cancel. Paid plans stay active until expiry.
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

        if (!$this->subscriptionService->isPaidSubscription($subscription)) {
            return back()->with('info', 'You are on the Free plan — there is nothing to cancel. Your access continues at no charge.');
        }

        if ($this->subscriptionService->isCancellationScheduled($subscription)) {
            return back()->with('info', 'Cancellation is already scheduled for '
                . $subscription->expires_at->format('F d, Y') . '.');
        }

        $this->subscriptionService->scheduleCancellation($subscription);

        return redirect()->route('subscriptions.billing')
            ->with('success', 'Cancellation scheduled. Your '
                . ($subscription->plan->plan_name ?? 'paid')
                . ' plan stays fully active until '
                . $subscription->expires_at->format('F d, Y')
                . ' and will not renew after that.');
    }

    /**
     * Undo a scheduled end-of-term cancellation.
     */
    public function resume(Request $request)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isClient()) {
            return redirect()->route('client.dashboard')->with('error', 'Access denied.');
        }

        $subscription = $this->subscriptionService->getActiveSubscription($company->id, 'client');

        if (!$subscription || !$this->subscriptionService->isCancellationScheduled($subscription)) {
            return back()->with('error', 'No scheduled cancellation to resume.');
        }

        $this->subscriptionService->resumeSubscription($subscription);

        return redirect()->route('subscriptions.billing')
            ->with('success', 'Cancellation withdrawn. Your plan will continue and you will be reminded to renew before '
                . $subscription->expires_at->format('F d, Y') . '.');
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

