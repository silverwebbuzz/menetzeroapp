<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
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
    public function __construct(
        protected PartnerSubscriptionService $partnerSubscriptions,
        protected PartnerWorkspaceService $workspace,
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

        return view('partner.packs.index', compact('partner', 'subscription', 'slotSummary', 'plans', 'contractYear'));
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

        $gateway = PaymentGateway::forGateway($data['gateway']);
        if (!$gateway || !$gateway->is_enabled || !$gateway->isConfigured()) {
            return back()->with('error', 'The selected payment method is not available.');
        }

        $displayCurrency = \App\Services\CurrencyService::displayCurrency();
        $chargeCurrency = $gateway->gateway === 'razorpay' ? 'INR' : $displayCurrency;
        $quote = $this->partnerSubscriptions->resolvePackPurchase($partner, $plan, null, $chargeCurrency);

        if (!$quote['requires_payment'] || $quote['charge_amount'] <= 0) {
            return back()->with('error', 'This pack is not available for online checkout.');
        }

        $charge = [
            'currency' => $quote['charge_currency'],
            'amount' => (float) $quote['charge_amount'],
        ];

        $transaction = PaymentTransaction::create([
            'company_id' => $partner->id,
            'transaction_type' => 'partner_pack',
            'amount' => $charge['amount'],
            'currency' => $charge['currency'],
            'status' => 'pending',
            'payment_method' => $gateway->gateway,
            'description' => 'Agency pack: ' . $plan->plan_name . ' (' . $quote['contract_year'] . ')',
            'metadata' => [
                'transaction_type' => 'partner_pack',
                'plan_id' => $plan->id,
                'plan_code' => $plan->plan_code,
                'contract_year' => $quote['contract_year'],
                'pro_rata' => $quote['pro_rata'],
                'display_currency' => $displayCurrency,
            ],
        ]);

        try {
            $user = Auth::user();
            $metadata = $transaction->metadata;

            if ($gateway->gateway === 'razorpay') {
                $rzOrder = $this->paymentService->createRazorpayOrder(
                    $gateway,
                    $transaction->amount,
                    $transaction->currency,
                    'partner_' . $transaction->id,
                    ['plan' => $plan->plan_code, 'partner_id' => (string) $partner->id]
                );
                $metadata['razorpay_order_id'] = $rzOrder['id'] ?? null;
            } else {
                $cfOrderId = 'partner_' . $transaction->id . '_' . Str::lower(Str::random(6));
                $returnUrl = route('partner.packs.payment.cashfree') . '?order_id={order_id}';
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
                        $inrQuote = $this->partnerSubscriptions->resolvePackPurchase($partner, $plan, $quote['contract_year'], 'INR');
                        $transaction->update([
                            'amount' => $inrQuote['charge_amount'],
                            'currency' => 'INR',
                        ]);
                        $metadata['charged_in_inr_fallback'] = true;
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

                $metadata['cashfree_order_id'] = $cfOrderId;
                $metadata['cashfree_payment_session_id'] = $cfOrder['payment_session_id'] ?? null;
            }

            $transaction->metadata = $metadata;
            $transaction->save();

            return redirect()->route('partner.packs.payment.checkout', $transaction->id);
        } catch (\Throwable $e) {
            $transaction->update(['status' => 'failed']);

            return back()->with('error', 'Unable to start payment: ' . $e->getMessage());
        }
    }

    public function paymentCheckout(int $transaction)
    {
        $partner = $this->partnerCompany();
        $record = PaymentTransaction::where('id', $transaction)
            ->where('company_id', $partner->id)
            ->where('transaction_type', 'partner_pack')
            ->firstOrFail();

        if ($record->status !== 'pending') {
            return redirect()->route('partner.dashboard')
                ->with('info', 'This payment has already been processed.');
        }

        $gateway = PaymentGateway::forGateway($record->payment_method);
        $plan = SubscriptionPlan::find($record->metadata['plan_id'] ?? null);
        $user = Auth::user();

        return view('partner.packs.checkout', [
            'transaction' => $record,
            'gateway' => $gateway,
            'plan' => $plan,
            'partner' => $partner,
            'user' => $user,
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

        $transaction = PaymentTransaction::where('id', $request->transaction_id)
            ->where('company_id', $partner->id)
            ->where('transaction_type', 'partner_pack')
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

            return redirect()->route('partner.packs.index')
                ->with('error', 'Payment verification failed.');
        }

        return $this->completePaidPack($transaction, [
            'razorpay_payment_id' => $request->razorpay_payment_id,
            'razorpay_order_id' => $request->razorpay_order_id,
        ]);
    }

    public function cashfreeCallback(Request $request)
    {
        $partner = $this->partnerCompany();
        $orderId = (string) $request->query('order_id');

        if ($orderId === '') {
            return redirect()->route('partner.packs.index')->with('error', 'Missing payment reference.');
        }

        $txnId = explode('_', $orderId)[1] ?? null;
        $transaction = PaymentTransaction::where('id', $txnId)
            ->where('company_id', $partner->id)
            ->where('transaction_type', 'partner_pack')
            ->firstOrFail();

        if (($transaction->metadata['cashfree_order_id'] ?? null) !== $orderId) {
            return redirect()->route('partner.packs.index')->with('error', 'Payment reference mismatch.');
        }

        if ($transaction->status === 'completed') {
            return redirect()->route('partner.dashboard')->with('success', 'Agency pack is already active.');
        }

        $gateway = PaymentGateway::forGateway('cashfree');
        if (!$gateway) {
            return redirect()->route('partner.packs.index')->with('error', 'Payment method unavailable.');
        }

        $orderStatus = $this->paymentService->getCashfreeOrderStatus($gateway, $orderId);

        if ($orderStatus === 'PAID') {
            return $this->completePaidPack($transaction, ['cashfree_order_id' => $orderId]);
        }

        $paymentStatus = $this->paymentService->getCashfreePaymentStatus($gateway, $orderId);

        if ($paymentStatus === 'SUCCESS') {
            return $this->completePaidPack($transaction, ['cashfree_order_id' => $orderId]);
        }

        if ($paymentStatus === 'PENDING' || ($orderStatus === 'ACTIVE' && $paymentStatus === null)) {
            return redirect()->route('partner.dashboard')
                ->with('info', 'Payment processing — your pack will activate automatically once confirmed.');
        }

        if (in_array($paymentStatus, ['USER_DROPPED', 'CANCELLED'], true)) {
            $transaction->update(['status' => 'cancelled']);

            return redirect()->route('partner.packs.index')
                ->with('error', 'Payment cancelled. You were not charged.');
        }

        $transaction->update(['status' => 'failed']);

        return redirect()->route('partner.packs.index')->with('error', 'Payment failed. Please try again.');
    }

    protected function completePaidPack(PaymentTransaction $transaction, array $gatewayRefs)
    {
        try {
            $this->paymentCompletion->complete($transaction, $gatewayRefs);
        } catch (\Throwable $e) {
            Log::error('Partner pack activation failed after payment', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('partner.packs.index')
                ->with('error', 'Payment received but pack activation failed. Contact support.');
        }

        $planName = SubscriptionPlan::find($transaction->metadata['plan_id'] ?? null)?->plan_name ?? 'Agency pack';

        return redirect()->route('partner.dashboard')
            ->with('success', "{$planName} is now active — you can add managed clients.");
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
