<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use App\Services\PartnerManagedClientService;
use App\Services\PartnerSubscriptionService;
use App\Services\PartnerWorkspaceService;
use App\Services\PaymentCompletionService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PackCheckoutController extends Controller
{
    private const PARTNER_TX_TYPES = [
        'partner_pack',
        'partner_extra_slot',
        'partner_year_unlock',
        'partner_renewal',
    ];

    public function __construct(
        protected PartnerSubscriptionService $partnerSubscriptions,
        protected PartnerWorkspaceService $workspace,
        protected PartnerManagedClientService $managedClients,
        protected PaymentService $paymentService,
        protected PaymentCompletionService $paymentCompletion,
    ) {
    }

    public function index()
    {
        $partner = $this->partnerCompany();
        $subscription = $this->partnerSubscriptions->getActiveSubscription($partner->id);
        $slotSummary = $this->partnerSubscriptions->slotSummary($partner->id);
        $plans = SubscriptionPlan::forPartners()->active()->orderBy('sort_order')->get();
        $contractYear = (int) now()->year;
        $extraSlotQuote = null;

        if ($subscription) {
            $extraSlotQuote = $this->partnerSubscriptions->resolveExtraSlotPurchase($subscription, 1);
        }

        return view('partner.packs.index', compact(
            'partner',
            'subscription',
            'slotSummary',
            'plans',
            'contractYear',
            'extraSlotQuote',
        ));
    }

    public function processCheckout(Request $request)
    {
        $partner = $this->partnerCompany();

        $data = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'gateway' => 'required|in:razorpay,cashfree',
        ]);

        $plan = SubscriptionPlan::where('id', $data['plan_id'])
            ->where('plan_category', 'partner')
            ->firstOrFail();

        $displayCurrency = \App\Services\CurrencyService::displayCurrency();
        $chargeCurrency = $data['gateway'] === 'razorpay' ? 'INR' : $displayCurrency;
        $quote = $this->partnerSubscriptions->resolvePackPurchase($partner, $plan, null, $chargeCurrency);

        if (!$quote['requires_payment'] || $quote['charge_amount'] <= 0) {
            return back()->with('error', 'This pack is not available for online checkout.');
        }

        return $this->startPayment(
            $partner,
            'partner_pack',
            $data['gateway'],
            $quote['charge_amount'],
            $quote['charge_currency'],
            'Agency pack: ' . $plan->plan_name . ' (' . $quote['contract_year'] . ')',
            [
                'plan_id' => $plan->id,
                'plan_code' => $plan->plan_code,
                'contract_year' => $quote['contract_year'],
                'pro_rata' => $quote['pro_rata'],
            ],
            fn () => $this->partnerSubscriptions->resolvePackPurchase($partner, $plan, $quote['contract_year'], 'INR'),
        );
    }

    public function processExtraSlots(Request $request)
    {
        $partner = $this->partnerCompany();
        $subscription = $this->partnerSubscriptions->getActiveSubscription($partner->id);

        if (!$subscription) {
            return back()->with('error', 'Purchase an agency pack before adding extra slots.');
        }

        $data = $request->validate([
            'quantity' => 'required|integer|min:1|max:50',
            'gateway' => 'required|in:razorpay,cashfree',
        ]);

        $displayCurrency = \App\Services\CurrencyService::displayCurrency();
        $chargeCurrency = $data['gateway'] === 'razorpay' ? 'INR' : $displayCurrency;

        try {
            $quote = $this->partnerSubscriptions->resolveExtraSlotPurchase(
                $subscription,
                (int) $data['quantity'],
                $chargeCurrency,
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return $this->startPayment(
            $partner,
            'partner_extra_slot',
            $data['gateway'],
            $quote['charge_amount'],
            $quote['charge_currency'],
            "Extra slots (×{$quote['quantity']}) through 31 Dec {$quote['contract_year']}",
            [
                'partner_subscription_id' => $subscription->id,
                'quantity' => $quote['quantity'],
                'contract_year' => $quote['contract_year'],
                'pro_rata' => $quote['pro_rata'],
            ],
            fn () => $this->partnerSubscriptions->resolveExtraSlotPurchase(
                $subscription,
                (int) $data['quantity'],
                'INR',
            ),
        );
    }

    public function processYearUnlock(Request $request)
    {
        $partner = $this->partnerCompany();

        $data = $request->validate([
            'engagement_id' => 'required|integer',
            'reporting_year' => 'required|integer|min:2000|max:2100',
            'gateway' => 'required|in:razorpay,cashfree',
        ]);

        $engagement = $this->managedClients->findForPartner($partner->id, (int) $data['engagement_id']);

        $displayCurrency = \App\Services\CurrencyService::displayCurrency();
        $chargeCurrency = $data['gateway'] === 'razorpay' ? 'INR' : $displayCurrency;

        try {
            $quote = $this->partnerSubscriptions->resolveYearUnlockPurchase(
                $engagement,
                (int) $data['reporting_year'],
                $chargeCurrency,
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $clientName = $engagement->display_name ?: $engagement->managedCompany?->name ?: 'Client';

        return $this->startPayment(
            $partner,
            'partner_year_unlock',
            $data['gateway'],
            $quote['charge_amount'],
            $quote['charge_currency'],
            "Year unlock {$quote['reporting_year']}: {$clientName}",
            [
                'engagement_id' => $engagement->id,
                'managed_company_id' => $engagement->managed_company_id,
                'reporting_year' => $quote['reporting_year'],
                'contract_year' => $quote['contract_year'],
                'pro_rata' => $quote['pro_rata'],
            ],
            fn () => $this->partnerSubscriptions->resolveYearUnlockPurchase(
                $engagement,
                (int) $data['reporting_year'],
                'INR',
            ),
        );
    }

    public function paymentCheckout(int $transaction)
    {
        $partner = $this->partnerCompany();
        $record = $this->findPartnerTransaction($partner->id, $transaction);

        if ($record->status !== 'pending') {
            return redirect()->route('consultant.dashboard')
                ->with('info', 'This payment has already been processed.');
        }

        $gateway = PaymentGateway::forGateway($record->payment_method);
        $meta = $record->metadata ?? [];
        $plan = isset($meta['plan_id']) ? SubscriptionPlan::find($meta['plan_id']) : null;

        return view('partner.packs.checkout', [
            'transaction' => $record,
            'gateway' => $gateway,
            'plan' => $plan,
            'partner' => $partner,
            'user' => Auth::user(),
        ]);
    }

    public function razorpayCallback(Request $request)
    {
        $partner = $this->partnerCompany();

        $request->validate([
            'transaction_id' => 'required',
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $transaction = $this->findPartnerTransaction($partner->id, (int) $request->transaction_id);

        $gateway = PaymentGateway::forGateway('razorpay');
        $valid = $gateway && $this->paymentService->verifyRazorpaySignature(
            $gateway,
            $request->razorpay_order_id,
            $request->razorpay_payment_id,
            $request->razorpay_signature
        );

        if (!$valid) {
            $transaction->update(['status' => 'failed']);

            return $this->redirectAfterFailure($transaction);
        }

        return $this->completePaid($transaction, [
            'razorpay_payment_id' => $request->razorpay_payment_id,
            'razorpay_order_id' => $request->razorpay_order_id,
        ]);
    }

    public function cashfreeCallback(Request $request)
    {
        $partner = $this->partnerCompany();
        $orderId = (string) $request->query('order_id');

        if ($orderId === '') {
            return redirect()->route('consultant.packs.index')->with('error', 'Missing payment reference.');
        }

        $txnId = explode('_', $orderId)[1] ?? null;
        $transaction = $this->findPartnerTransaction($partner->id, (int) $txnId);

        if (($transaction->metadata['cashfree_order_id'] ?? null) !== $orderId) {
            return redirect()->route('consultant.packs.index')->with('error', 'Payment reference mismatch.');
        }

        if ($transaction->status === 'completed') {
            return redirect()->route('consultant.dashboard')->with('success', 'Payment already processed.');
        }

        $gateway = PaymentGateway::forGateway('cashfree');
        if (!$gateway) {
            return redirect()->route('consultant.packs.index')->with('error', 'Payment method unavailable.');
        }

        $orderStatus = $this->paymentService->getCashfreeOrderStatus($gateway, $orderId);

        if ($orderStatus === 'PAID') {
            return $this->completePaid($transaction, ['cashfree_order_id' => $orderId]);
        }

        $paymentStatus = $this->paymentService->getCashfreePaymentStatus($gateway, $orderId);

        if ($paymentStatus === 'SUCCESS') {
            return $this->completePaid($transaction, ['cashfree_order_id' => $orderId]);
        }

        if ($paymentStatus === 'PENDING' || ($orderStatus === 'ACTIVE' && $paymentStatus === null)) {
            return redirect()->route('consultant.dashboard')
                ->with('info', 'Payment processing — your purchase will activate automatically once confirmed.');
        }

        if (in_array($paymentStatus, ['USER_DROPPED', 'CANCELLED'], true)) {
            $transaction->update(['status' => 'cancelled']);

            return $this->redirectAfterFailure($transaction)
                ->with('error', 'Payment cancelled. You were not charged.');
        }

        $transaction->update(['status' => 'failed']);

        return $this->redirectAfterFailure($transaction)->with('error', 'Payment failed. Please try again.');
    }

    /**
     * @param  callable(): array  $inrFallbackQuote
     */
    protected function startPayment(
        $partner,
        string $transactionType,
        string $gatewayCode,
        float $amount,
        string $currency,
        string $description,
        array $metadata,
        callable $inrFallbackQuote,
    ) {
        $gateway = PaymentGateway::forGateway($gatewayCode);
        if (!$gateway || !$gateway->is_enabled || !$gateway->isConfigured()) {
            return back()->with('error', 'The selected payment method is not available.');
        }

        $displayCurrency = \App\Services\CurrencyService::displayCurrency();

        $transaction = PaymentTransaction::create([
            'company_id' => $partner->id,
            'transaction_type' => $transactionType,
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'payment_method' => $gateway->gateway,
            'description' => $description,
            'metadata' => array_merge($metadata, [
                'transaction_type' => $transactionType,
                'display_currency' => $displayCurrency,
            ]),
        ]);

        try {
            $user = Auth::user();
            $meta = $transaction->metadata;

            if ($gateway->gateway === 'razorpay') {
                $rzOrder = $this->paymentService->createRazorpayOrder(
                    $gateway,
                    $transaction->amount,
                    $transaction->currency,
                    'partner_' . $transaction->id,
                    ['type' => $transactionType, 'partner_id' => (string) $partner->id]
                );
                $meta['razorpay_order_id'] = $rzOrder['id'] ?? null;
            } else {
                $cfOrderId = 'partner_' . $transaction->id . '_' . Str::lower(Str::random(6));
                $returnUrl = route('consultant.packs.payment.cashfree') . '?order_id={order_id}';
                $phone = PaymentService::normalizePhone($user->phone ?? null)
                    ?? PaymentService::normalizePhone($partner->phone ?? null)
                    ?? PaymentService::normalizePhone(\App\Models\SiteSetting::get('support_phone'))
                    ?? '9999999999';
                $customer = [
                    'id' => 'partner_' . $partner->id,
                    'name' => $user->name ?: $partner->name,
                    'email' => $user->email ?: ($partner->email ?: 'billing@menetzero.com'),
                    'phone' => $phone,
                ];

                try {
                    $cfOrder = $this->paymentService->createCashfreeOrder(
                        $gateway,
                        $cfOrderId,
                        $transaction->amount,
                        $transaction->currency,
                        $customer,
                        $returnUrl
                    );
                } catch (\RuntimeException $e) {
                    if ($transaction->currency !== 'INR'
                        && $this->paymentService->isCashfreeCurrencyDisabledError($e->getMessage())) {
                        $inrQuote = $inrFallbackQuote();
                        $transaction->update([
                            'amount' => $inrQuote['charge_amount'],
                            'currency' => 'INR',
                        ]);
                        $meta['charged_in_inr_fallback'] = true;
                        $cfOrder = $this->paymentService->createCashfreeOrder(
                            $gateway,
                            $cfOrderId,
                            $inrQuote['charge_amount'],
                            'INR',
                            $customer,
                            $returnUrl
                        );
                        session()->flash('info', 'Charged in INR equivalent while AED activation is pending on Cashfree.');
                    } else {
                        throw $e;
                    }
                }

                $meta['cashfree_order_id'] = $cfOrderId;
                $meta['cashfree_payment_session_id'] = $cfOrder['payment_session_id'] ?? null;
            }

            $transaction->metadata = $meta;
            $transaction->save();

            return redirect()->route('consultant.packs.payment.checkout', $transaction->id);
        } catch (\Throwable $e) {
            $transaction->update(['status' => 'failed']);

            return back()->with('error', 'Unable to start payment: ' . $e->getMessage());
        }
    }

    protected function completePaid(PaymentTransaction $transaction, array $gatewayRefs)
    {
        try {
            $this->paymentCompletion->complete($transaction, $gatewayRefs);
        } catch (\Throwable $e) {
            Log::error('Partner payment activation failed', [
                'transaction_id' => $transaction->id,
                'type' => $transaction->transaction_type,
                'error' => $e->getMessage(),
            ]);

            return $this->redirectAfterFailure($transaction)
                ->with('error', 'Payment received but activation failed. Contact support.');
        }

        $message = match ($transaction->transaction_type) {
            'partner_extra_slot' => 'Extra slots added — you can add more managed clients.',
            'partner_year_unlock' => 'Reporting year unlocked — full exports are now available for that year.',
            'partner_renewal' => 'Renewal complete — selected clients are active for the new contract year.',
            default => (SubscriptionPlan::find($transaction->metadata['plan_id'] ?? null)?->plan_name ?? 'Agency pack')
                . ' is now active — you can add managed clients.',
        };

        if ($transaction->transaction_type === 'partner_year_unlock') {
            $engagementId = $transaction->metadata['engagement_id'] ?? null;

            if ($engagementId) {
                return redirect()->route('consultant.clients.show', $engagementId)->with('success', $message);
            }
        }

        return redirect()->route('consultant.dashboard')->with('success', $message);
    }

    protected function redirectAfterFailure(PaymentTransaction $transaction)
    {
        if ($transaction->transaction_type === 'partner_year_unlock') {
            $engagementId = $transaction->metadata['engagement_id'] ?? null;

            if ($engagementId) {
                return redirect()->route('consultant.clients.show', $engagementId);
            }
        }

        if ($transaction->transaction_type === 'partner_renewal') {
            return redirect()->route('consultant.renewal.index');
        }

        return redirect()->route('consultant.packs.index');
    }

    protected function findPartnerTransaction(int $partnerId, int $transactionId): PaymentTransaction
    {
        return PaymentTransaction::where('id', $transactionId)
            ->where('company_id', $partnerId)
            ->whereIn('transaction_type', self::PARTNER_TX_TYPES)
            ->firstOrFail();
    }

    protected function partnerCompany()
    {
        $company = $this->workspace->getPartnerHomeCompany(Auth::user());

        if (!$company) {
            abort(403, 'Partner organisation required.');
        }

        return $company;
    }
}
