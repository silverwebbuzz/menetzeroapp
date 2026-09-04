<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Services\PaymentCompletionService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Repair payments that were captured but never activated.
 *
 * Activation normally happens twice over: the browser callback after checkout,
 * and Razorpay's payment.captured webhook. When both miss -- the customer closes
 * the tab AND the webhook is unconfigured or failing -- the money is ours and
 * the customer has nothing. Until now the only fix was a CLI command, which is
 * not something support can run mid-conversation.
 *
 * The safety property that matters: this NEVER activates on an admin's word.
 * Every repair re-fetches the payment from Razorpay and refuses unless it is
 * genuinely captured for the right amount, currency and order. An admin decides
 * WHEN to repair, never WHETHER the money arrived.
 */
class PaymentRecoveryController extends Controller
{
    public function __construct(
        protected PaymentService $payments,
        protected PaymentCompletionService $completion,
    ) {
    }

    /**
     * Stuck transactions across companies and consultants.
     */
    public function index(Request $request)
    {
        $query = PaymentTransaction::query()
            ->where('status', 'pending')
            // Rows billed as 'invoice' are raised BY us (scheduled plan changes)
            // and are legitimately unpaid -- they are not stuck gateway payments.
            ->where(function ($q) {
                $q->whereNull('payment_method')->orWhere('payment_method', '!=', 'invoice');
            })
            ->with('company')
            ->orderByDesc('id');

        if ($companyId = $request->integer('company_id')) {
            $query->where('company_id', $companyId);
        }

        return view('admin.payment-recovery.index', [
            'transactions' => $query->paginate(30)->withQueryString(),
            'companyId' => $request->integer('company_id') ?: null,
            'gatewayReady' => (bool) PaymentGateway::forGateway('razorpay'),
        ]);
    }

    /**
     * Ask Razorpay what actually happened to this payment. Read-only.
     */
    public function verify(Request $request, PaymentTransaction $transaction)
    {
        $data = $request->validate([
            'payment_id' => 'required|string|max:255',
        ]);

        $gateway = PaymentGateway::forGateway('razorpay');

        if (!$gateway) {
            return back()->with('error', 'Razorpay is not configured, so payments cannot be verified.');
        }

        try {
            $payment = $this->payments->fetchRazorpayPayment($gateway, trim($data['payment_id']));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        if (!$payment) {
            return back()->with('error', 'Razorpay does not recognise that payment reference.');
        }

        $verdict = $this->payments->razorpayPaymentSettles($payment, $transaction);

        return back()->with('verified_payment', [
            'transaction_id' => $transaction->id,
            'payment_id' => $payment['id'] ?? null,
            'status' => $payment['status'] ?? null,
            'amount' => isset($payment['amount']) ? $payment['amount'] / 100 : null,
            'currency' => $payment['currency'] ?? null,
            'order_id' => $payment['order_id'] ?? null,
            'method' => $payment['method'] ?? null,
            'email' => $payment['email'] ?? null,
            'ok' => $verdict['ok'],
            'reason' => $verdict['reason'],
        ]);
    }

    /**
     * Verify once more, then activate: subscription, invoice and receipt email.
     */
    public function activate(Request $request, PaymentTransaction $transaction)
    {
        $data = $request->validate([
            'payment_id' => 'required|string|max:255',
        ]);

        if ($transaction->status === 'completed') {
            return back()->with('error', 'This transaction is already completed.');
        }

        $gateway = PaymentGateway::forGateway('razorpay');

        if (!$gateway) {
            return back()->with('error', 'Razorpay is not configured, so payments cannot be verified.');
        }

        $paymentId = trim($data['payment_id']);

        // Re-fetched rather than trusting the verify step's result: the two are
        // separate requests, and this is the one that moves money's worth of
        // entitlement.
        try {
            $payment = $this->payments->fetchRazorpayPayment($gateway, $paymentId);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        if (!$payment) {
            return back()->with('error', 'Razorpay does not recognise that payment reference.');
        }

        $verdict = $this->payments->razorpayPaymentSettles($payment, $transaction);

        if (!$verdict['ok']) {
            return back()->with('error', 'Not activated. ' . $verdict['reason']);
        }

        $refs = array_filter([
            'razorpay_payment_id' => $payment['id'] ?? $paymentId,
            'razorpay_order_id' => $payment['order_id'] ?? null,
            // Recorded on the subscription so a later audit can tell an
            // admin-repaired activation from a normal one.
            'recovered_by_admin_id' => Auth::guard('admin')->id(),
            'recovered_at' => now()->toIso8601String(),
        ]);

        try {
            // complete() routes by transaction type, so this covers company
            // subscriptions and every consultant order type alike, and issues
            // the invoice and sends the receipt as part of the same path a
            // normal checkout takes. Both are idempotent.
            $this->completion->complete($transaction, $refs);
        } catch (\Throwable $e) {
            Log::error('Admin payment recovery failed', [
                'transaction_id' => $transaction->id,
                'admin_id' => Auth::guard('admin')->id(),
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);

            return back()->with('error', 'Activation failed: ' . $e->getMessage());
        }

        $fresh = $transaction->fresh();

        Log::info('Admin recovered a captured payment', [
            'transaction_id' => $transaction->id,
            'admin_id' => Auth::guard('admin')->id(),
            'payment_id' => $paymentId,
            'resulting_status' => $fresh?->status,
        ]);

        return back()->with('success', sprintf(
            'Transaction %d activated. Invoice issued and the receipt email sent to the customer.',
            $transaction->id
        ));
    }
}
