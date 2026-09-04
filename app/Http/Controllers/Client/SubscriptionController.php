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
     * Self-serve upgrade catalog removed (Phase 3). Upgrade your package via billing / support.
     */
    public function upgrade()
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isClient()) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Access denied.');
        }

        $currentSubscription = $this->subscriptionService->getActiveSubscription($company->id, 'client');

        // is_active filters retired plans out, so nobody can select one.
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
        $cardHighlights = CommercialPlanComparison::cardHighlights();
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
            'cardHighlights',
            'enabledGateways',
            'planChanges',
            'downgradeWarnings',
            'displayCurrency'
        ));
    }

    /**
     * Plan change: schedule a downgrade, apply a free upgrade, or start payment.
     *
     * Razorpay is the only gateway and AED is the only currency. This needs
     * International Payments active on the Razorpay account; if it is not, the
     * order fails rather than falling back to INR (see the note at the call).
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

        // A retired plan still resolves for the subscriber already on it, but
        // must never be a destination -- nobody can be sold a plan that is no
        // longer in the catalogue.
        if (!$plan->is_active) {
            return back()->withErrors(['plan_id' => 'That plan is no longer available.'])->withInput();
        }

        $currentSubscription = $this->subscriptionService->getActiveSubscription($company->id, 'client');
        $displayCurrency = \App\Services\CurrencyService::displayCurrency();
        $change = $this->subscriptionService->resolvePlanChange($currentSubscription, $plan, $displayCurrency);

        if ($change['type'] === 'same') {
            return redirect()->route('subscriptions.upgrade')
                ->with('info', $change['message']);
        }

        // Downgrade: scheduled at renewal — no payment, and limits are not
        // reduced today, so nobody loses data mid-period.
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

                return redirect()->route('subscriptions.billing')->with('success', $message);
            } catch (\Exception $e) {
                return back()->withErrors(['error' => $e->getMessage()])->withInput();
            }
        }

        // Upgrade whose prorated amount is zero — apply immediately, no payment.
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

        if (!PaymentGateway::checkoutAvailable()) {
            return redirect()->route('subscriptions.upgrade')
                ->with('error', 'Online payments are not available yet. Paid upgrades will open when checkout goes live.');
        }

        $gateway = PaymentGateway::forGateway('razorpay');
        if (!$gateway || !$gateway->is_enabled || !$gateway->isConfigured()) {
            return redirect()->route('subscriptions.upgrade')
                ->with('error', 'Online payment is unavailable right now. Please request a package instead.');
        }

        $charge = [
            'currency' => $change['charge_currency'],
            'amount' => (float) $change['charge_amount'],
            'display_currency' => $displayCurrency,
        ];

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
            'payment_method' => 'razorpay',
            'description' => $description,
            'metadata' => array_merge([
                'plan_id' => $plan->id,
                'auto_renew' => $request->has('auto_renew'),
                'change_type' => $change['type'],
                'preserve_expiry' => $change['preserve_expiry'],
                'from_plan_id' => $currentSubscription?->subscription_plan_id,
                'display_currency' => $displayCurrency,
            ], $couponMeta),
        ]);

        try {
            $metadata = $transaction->metadata;

            // AED only. The former INR fallback (re-price and retry when AED
            // was rejected) is deliberately gone: the seller is an Indian
            // entity exporting services to UAE buyers, and an INR charge would
            // recharacterise a zero-rated export as a domestic supply. If AED
            // is rejected the sale now fails visibly and is retried after the
            // gateway is fixed, which is recoverable; a silent INR charge is
            // not.
            $order = $this->paymentService->createRazorpayOrder(
                $gateway,
                $transaction->amount,
                $transaction->currency,
                'txn_' . $transaction->id,
                ['plan' => $plan->plan_code, 'company_id' => (string) $company->id]
            );

            $metadata['razorpay_order_id'] = $order['id'] ?? null;
            $transaction->metadata = $metadata;
            $transaction->save();

            return redirect()->route('subscriptions.checkout', $transaction->id);
        } catch (\Throwable $e) {
            $transaction->update(['status' => 'failed']);

            return redirect()->route('subscriptions.upgrade')
                ->with('error', 'Unable to start payment: ' . $e->getMessage());
        }
    }

    public function checkout($id)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$company->isClient()) {
            return redirect()->route('client.dashboard')->with('error', 'Access denied.');
        }

        // Scoped to the company, so a transaction id cannot be guessed to read
        // another tenant's payment.
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
     * Mark a transaction paid and activate the subscription.
     */
    private function completePaidSubscription(PaymentTransaction $transaction, array $gatewayRefs, ?string $reference)
    {
        try {
            app(\App\Services\PaymentCompletionService::class)->complete($transaction, $gatewayRefs);
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

            try {
                app(\App\Services\EmailTemplateService::class)->sendSystemAlert(
                    'Subscription activation failed after payment',
                    'Payment was captured but subscription activation threw an exception.',
                    [
                        'transaction_id' => $transaction->id,
                        'company_id' => $transaction->company_id,
                        'reference' => $reference,
                        'error' => $e->getMessage(),
                    ]
                );
            } catch (\Throwable $mailError) {
                \Illuminate\Support\Facades\Log::error('Failed to send system alert email', [
                    'error' => $mailError->getMessage(),
                ]);
            }

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

}

