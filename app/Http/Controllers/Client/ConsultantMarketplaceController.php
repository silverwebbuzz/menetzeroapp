<?php

namespace App\Http\Controllers\Client;

use App\Data\CommercialPlanComparison;
use App\Data\ConsultantOptions;
use App\Http\Controllers\Controller;
use App\Models\Consultant;
use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Services\ConsultantMarketplaceService;
use App\Services\CurrencyService;
use App\Services\PaymentCompletionService;
use App\Services\PaymentService;
use App\Support\PlanGate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ConsultantMarketplaceController extends Controller
{
    public function __construct(
        protected ConsultantMarketplaceService $marketplace,
        protected PaymentService $paymentService,
        protected PaymentCompletionService $paymentCompletion,
    ) {}

    public function checkout(PlanGate $gate, Consultant $consultant, Request $request)
    {
        $companyId = $gate->companyId();
        if (!$companyId || !$consultant->isListed()) {
            abort(404);
        }

        if (!$this->marketplaceDirectoryCanPay($gate)) {
            return redirect()->route('client.consultants.show', $consultant)
                ->with('error', 'Upgrade to Starter or above to book a consultant review pack.');
        }

        $packType = $request->query('pack', 'starter_consultant');
        if (!array_key_exists($packType, ConsultantOptions::PACK_TYPES) || $packType === 'custom') {
            $packType = 'starter_consultant';
        }

        $pack = CommercialPlanComparison::consultantPackByType($packType);
        if (!$pack) {
            abort(404);
        }

        $enabledGateways = PaymentGateway::enabled();

        return view('client.consultants.checkout', [
            'consultant' => $consultant,
            'pack' => $pack,
            'packType' => $packType,
            'enabledGateways' => $enabledGateways,
            'commissionRate' => $this->marketplace->commissionRate(),
            'displayCurrency' => CurrencyService::displayCurrency(),
        ]);
    }

    public function processCheckout(Request $request, PlanGate $gate, Consultant $consultant)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company || !$consultant->isListed()) {
            abort(404);
        }

        if (!$this->marketplaceDirectoryCanPay($gate)) {
            return back()->with('error', 'Upgrade to Starter or above to book consultant packs.');
        }

        $data = $request->validate([
            'pack_type' => 'required|in:starter_consultant,growth_consultant',
            'gateway' => 'required|in:razorpay,cashfree,stripe',
        ]);

        $pack = CommercialPlanComparison::consultantPackByType($data['pack_type']);
        if (!$pack) {
            return back()->with('error', 'Invalid pack selected.');
        }

        $gateway = PaymentGateway::forGateway($data['gateway']);
        if (!$gateway || !$gateway->is_enabled || !$gateway->isConfigured()) {
            return back()->with('error', 'The selected payment method is not available.');
        }

        $order = $this->marketplace->createPendingOrder($company->id, $consultant, $data['pack_type']);

        $amountAed = (float) $pack['price_aed'];
        $charge = $this->marketplace->chargeAmount($amountAed, $gateway->gateway);
        $currency = $charge['currency'];
        $amount = $charge['amount'];

        $transaction = PaymentTransaction::create([
            'company_id' => $company->id,
            'transaction_type' => 'consultant_pack',
            'amount' => $amount,
            'currency' => $currency,
            'status' => 'pending',
            'payment_method' => $gateway->gateway,
            'description' => $pack['name'] . ' — ' . $consultant->company_name,
            'metadata' => [
                'transaction_type' => 'consultant_pack',
                'consultant_order_id' => $order->id,
                'consultant_id' => $consultant->id,
                'pack_type' => $data['pack_type'],
                'pack_name' => $pack['name'],
                'amount_aed' => $amountAed,
            ],
        ]);

        try {
            $metadata = $transaction->metadata;

            if ($gateway->gateway === 'razorpay') {
                $rzOrder = $this->paymentService->createRazorpayOrder(
                    $gateway,
                    $transaction->amount,
                    $transaction->currency,
                    'consultant_' . $order->id,
                    ['consultant_order_id' => (string) $order->id]
                );
                $metadata['razorpay_order_id'] = $rzOrder['id'] ?? null;
            } elseif ($gateway->gateway === 'stripe') {
                $user = Auth::user();
                $session = $this->paymentService->createStripeCheckoutSession(
                    $gateway,
                    $transaction,
                    route('client.consultants.payment.stripe') . '?session_id={CHECKOUT_SESSION_ID}&transaction_id=' . $transaction->id,
                    route('client.consultants.payment.checkout', $transaction->id),
                    [
                        'name' => $user->name ?: $company->name,
                        'email' => $user->email ?: ($company->email ?: null),
                    ]
                );
                $metadata['stripe_session_id'] = $session['id'] ?? null;
                $metadata['stripe_session_url'] = $session['url'] ?? null;
            } else {
                $user = Auth::user();
                $cfOrderId = 'consultant_' . $transaction->id . '_' . Str::lower(Str::random(6));
                $returnUrl = route('client.consultants.payment.cashfree') . '?order_id={order_id}';
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
                $cfOrder = $this->paymentService->createCashfreeOrder(
                    $gateway,
                    $cfOrderId,
                    $transaction->amount,
                    $transaction->currency,
                    $customer,
                    $returnUrl
                );
                $metadata['cashfree_order_id'] = $cfOrderId;
                $metadata['cashfree_payment_session_id'] = $cfOrder['payment_session_id'] ?? null;
            }

            $transaction->metadata = $metadata;
            $transaction->save();

            return redirect()->route('client.consultants.payment.checkout', $transaction->id);
        } catch (\Throwable $e) {
            $transaction->update(['status' => 'failed']);
            $order->update(['order_status' => 'cancelled']);

            return back()->with('error', 'Unable to start payment: ' . $e->getMessage());
        }
    }

    public function paymentCheckout($id)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company) {
            return redirect()->route('client.dashboard');
        }

        $transaction = PaymentTransaction::where('id', $id)
            ->where('company_id', $company->id)
            ->where('transaction_type', 'consultant_pack')
            ->firstOrFail();

        if ($transaction->status !== 'pending') {
            return redirect()->route('client.consultants.orders')
                ->with('info', 'This payment has already been processed.');
        }

        $gateway = PaymentGateway::forGateway($transaction->payment_method);
        $consultant = Consultant::find($transaction->metadata['consultant_id'] ?? null);
        $packName = $transaction->metadata['pack_name'] ?? 'Consultant pack';

        return view('client.consultants.payment-checkout', compact('transaction', 'gateway', 'consultant', 'packName', 'company'));
    }

    public function razorpayCallback(Request $request)
    {
        return $this->handlePaymentCallback($request, 'razorpay');
    }

    public function cashfreeCallback(Request $request)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company) {
            return redirect()->route('client.dashboard');
        }

        $orderId = (string) $request->query('order_id', '');
        if (!str_starts_with($orderId, 'consultant_')) {
            return redirect()->route('client.consultants.index')->with('error', 'Invalid payment reference.');
        }

        $txnId = explode('_', $orderId)[1] ?? null;
        $transaction = PaymentTransaction::where('id', $txnId)
            ->where('company_id', $company->id)
            ->where('transaction_type', 'consultant_pack')
            ->firstOrFail();

        if ($transaction->status === 'completed') {
            return redirect()->route('client.consultants.orders')->with('success', 'Payment already completed.');
        }

        $gateway = PaymentGateway::forGateway('cashfree');
        $orderStatus = $this->paymentService->getCashfreeOrderStatus($gateway, $orderId);

        if ($orderStatus === 'PAID') {
            return $this->finalizePayment($transaction, ['cashfree_order_id' => $orderId], $orderId);
        }

        $paymentStatus = $this->paymentService->getCashfreePaymentStatus($gateway, $orderId);
        if ($paymentStatus === 'SUCCESS') {
            return $this->finalizePayment($transaction, ['cashfree_order_id' => $orderId], $orderId);
        }

        if (in_array($paymentStatus, ['USER_DROPPED', 'CANCELLED'], true)) {
            $transaction->update(['status' => 'cancelled']);

            return redirect()->route('client.consultants.checkout', $transaction->metadata['consultant_id'] ?? 1)
                ->with('error', 'Payment cancelled.');
        }

        $transaction->update(['status' => 'failed']);

        return redirect()->route('client.consultants.index')->with('error', 'Payment failed. Please try again.');
    }

    public function stripeCallback(Request $request)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company) {
            return redirect()->route('client.dashboard');
        }

        $request->validate([
            'transaction_id' => 'required|integer',
            'session_id' => 'required|string',
        ]);

        $transaction = PaymentTransaction::where('id', $request->integer('transaction_id'))
            ->where('company_id', $company->id)
            ->where('transaction_type', 'consultant_pack')
            ->firstOrFail();

        if ($transaction->status === 'completed') {
            return redirect()->route('client.consultants.orders')->with('success', 'Payment already completed.');
        }

        $gateway = PaymentGateway::forGateway('stripe');
        if (!$gateway || !$gateway->is_enabled || !$gateway->isConfigured()) {
            return redirect()->route('client.consultants.index')->with('error', 'Payment method unavailable.');
        }

        $sessionId = (string) $request->query('session_id');
        $session = $this->paymentService->getStripeCheckoutSession($gateway, $sessionId);

        if (!$session || ($session['id'] ?? null) !== $sessionId) {
            return redirect()->route('client.consultants.index')->with('error', 'Could not verify Stripe payment.');
        }

        $expectedTxn = (string) $transaction->id;
        $sessionTxn = (string) ($session['metadata']['transaction_id'] ?? $session['client_reference_id'] ?? '');
        if ($sessionTxn !== '' && $sessionTxn !== $expectedTxn) {
            return redirect()->route('client.consultants.index')->with('error', 'Payment reference mismatch.');
        }

        if (($session['payment_status'] ?? null) === 'paid') {
            return $this->finalizePayment($transaction, [
                'stripe_session_id' => $sessionId,
                'stripe_payment_intent_id' => $session['payment_intent'] ?? null,
            ], (string) ($session['payment_intent'] ?? $sessionId));
        }

        return redirect()->route('client.consultants.orders')
            ->with('info', 'Stripe payment is processing. Your order will activate automatically once confirmed.');
    }

    public function orders(PlanGate $gate)
    {
        $companyId = $gate->companyId();
        if (!$companyId) {
            return redirect()->route('client.dashboard');
        }

        $orders = \App\Models\ConsultantOrder::query()
            ->where('company_id', $companyId)
            ->with('consultant')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('client.consultants.orders', compact('orders'));
    }

    protected function handlePaymentCallback(Request $request, string $gatewayName)
    {
        $company = Auth::user()->getActiveCompany();
        if (!$company) {
            return redirect()->route('client.dashboard');
        }

        $request->validate([
            'transaction_id' => 'required',
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $transaction = PaymentTransaction::where('id', $request->transaction_id)
            ->where('company_id', $company->id)
            ->where('transaction_type', 'consultant_pack')
            ->firstOrFail();

        if ($gatewayName === 'razorpay') {
            $gateway = PaymentGateway::forGateway('razorpay');
            $valid = $gateway && $this->paymentService->verifyRazorpaySignature(
                $gateway,
                $request->razorpay_order_id,
                $request->razorpay_payment_id,
                $request->razorpay_signature
            );

            if (!$valid) {
                $transaction->update(['status' => 'failed']);

                return redirect()->route('client.consultants.index')->with('error', 'Payment verification failed.');
            }

            return $this->finalizePayment($transaction, [
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_order_id' => $request->razorpay_order_id,
            ], $request->razorpay_payment_id);
        }

        return redirect()->route('client.consultants.index');
    }

    protected function finalizePayment(PaymentTransaction $transaction, array $refs, ?string $reference)
    {
        try {
            $this->paymentCompletion->complete($transaction, $refs);
        } catch (\Throwable $e) {
            return redirect()->route('client.consultants.index')
                ->with('error', 'Payment received but order could not be activated. Contact support.');
        }

        return redirect()->route('client.consultants.orders')
            ->with('success', 'Payment successful — funds are held in escrow until the consultant completes your review pack.');
    }

    protected function marketplaceDirectoryCanPay(PlanGate $gate): bool
    {
        $companyId = $gate->companyId();
        if (!$companyId) {
            return false;
        }

        return in_array(
            app(\App\Services\ConsultantDirectoryService::class)->directoryLevel($companyId),
            ['partial', 'full', 'priority'],
            true
        );
    }
}
