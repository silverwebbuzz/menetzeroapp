<?php

namespace App\Http\Controllers\Consultant\Agency;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Consultant\Agency\Concerns\ResolvesConsultantAgency;
use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use App\Services\ConsultantAgencyClientService;
use App\Services\ConsultantAgencyPaymentService;
use App\Services\ConsultantAgencySubscriptionService;
use App\Services\PaymentCompletionService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PackCheckoutController extends Controller
{
    use ResolvesConsultantAgency;

    private const CONSULTANT_TX_TYPES = [
        'consultant_agency_pack',
        'consultant_agency_extra_slot',
        'consultant_agency_year_unlock',
        'consultant_agency_renewal',
    ];

    public function __construct(
        protected ConsultantAgencySubscriptionService $consultantSubscriptions,
        protected ConsultantAgencyClientService $managedClients,
        protected ConsultantAgencyPaymentService $checkout,
        protected PaymentService $paymentService,
        protected PaymentCompletionService $paymentCompletion,
    ) {
    }

    public function index()
    {
        $consultantOrg = $this->consultantCompany();
        $subscription = $this->consultantSubscriptions->getActiveSubscription($consultantOrg->id);
        $slotSummary = $this->consultantSubscriptions->slotSummary($consultantOrg->id, $subscription);
        $contractYear = (int) now()->year;
        $recentRequests = \App\Models\ConsultantEntityRequest::query()
            ->where('consultant_company_id', $consultantOrg->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Phase 3–5: self-serve pack grid/checkout hidden — request entities offline.
        return view('consultant.agency.packs.index', compact(
            'subscription',
            'slotSummary',
            'contractYear',
            'recentRequests',
        ));
    }

    public function processCheckout(Request $request)
    {
        return redirect()->route('consultant.packs.index')
            ->with('info', 'Self-serve agency pack checkout is unavailable. Request managed-client entities from this page or contact MENetZero — pricing is confirmed offline.');
    }

    public function processExtraSlots(Request $request)
    {
        return redirect()->route('consultant.packs.index')
            ->with('info', 'Self-serve extra slot checkout is unavailable. Request more entities via MENetZero — activation is offline.');
    }

    public function processYearUnlock(Request $request)
    {
        return redirect()->route('consultant.packs.index')
            ->with('info', 'Self-serve year unlock checkout is unavailable. Contact MENetZero to unlock a reporting year after offline payment.');
    }

    public function paymentCheckout(int $transaction)
    {
        $consultantOrg = $this->consultantCompany();
        $record = $this->findConsultantTransaction($consultantOrg->id, $transaction);

        if ($record->status !== 'pending') {
            return redirect()->route('consultant.dashboard')
                ->with('info', 'This payment has already been processed.');
        }

        $gateway = PaymentGateway::forGateway($record->payment_method);
        $meta = $record->metadata ?? [];
        $plan = isset($meta['plan_id']) ? SubscriptionPlan::find($meta['plan_id']) : null;

        return view('consultant.agency.packs.checkout', [
            'transaction' => $record,
            'gateway' => $gateway,
            'plan' => $plan,
            'user' => Auth::user(),
        ]);
    }

    public function razorpayCallback(Request $request)
    {
        $consultantOrg = $this->consultantCompany();

        $request->validate([
            'transaction_id' => 'required',
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $transaction = $this->findConsultantTransaction($consultantOrg->id, (int) $request->transaction_id);

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
        $consultantOrg = $this->consultantCompany();
        $orderId = (string) $request->query('order_id');

        if ($orderId === '') {
            return redirect()->route('consultant.packs.index')->with('error', 'Missing payment reference.');
        }

        $transaction = PaymentTransaction::query()
            ->where('company_id', $consultantOrg->id)
            ->whereIn('transaction_type', self::CONSULTANT_TX_TYPES)
            ->where('metadata->cashfree_order_id', $orderId)
            ->first();

        if (!$transaction && preg_match('/^consultant_(\d+)_/', $orderId, $matches)) {
            $transaction = $this->findConsultantTransaction($consultantOrg->id, (int) $matches[1]);
        }

        if (!$transaction) {
            return redirect()->route('consultant.packs.index')->with('error', 'Payment reference not found.');
        }

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

    public function stripeCallback(Request $request)
    {
        $consultantOrg = $this->consultantCompany();

        $request->validate([
            'transaction_id' => 'required|integer',
            'session_id' => 'required|string',
        ]);

        $transaction = $this->findConsultantTransaction($consultantOrg->id, (int) $request->transaction_id);

        if ($transaction->status === 'completed') {
            return redirect()->route('consultant.dashboard')->with('success', 'Payment already processed.');
        }

        $gateway = PaymentGateway::forGateway('stripe');
        if (!$gateway || !$gateway->is_enabled || !$gateway->isConfigured()) {
            return redirect()->route('consultant.packs.index')->with('error', 'Payment method unavailable.');
        }

        $sessionId = (string) $request->query('session_id');
        $session = $this->paymentService->getStripeCheckoutSession($gateway, $sessionId);

        if (!$session || ($session['id'] ?? null) !== $sessionId) {
            return redirect()->route('consultant.packs.index')->with('error', 'Could not verify Stripe payment.');
        }

        $expectedTxn = (string) $transaction->id;
        $sessionTxn = (string) ($session['metadata']['transaction_id'] ?? $session['client_reference_id'] ?? '');
        if ($sessionTxn !== '' && $sessionTxn !== $expectedTxn) {
            return redirect()->route('consultant.packs.index')->with('error', 'Payment reference mismatch.');
        }

        if (($session['payment_status'] ?? null) === 'paid') {
            return $this->completePaid($transaction, [
                'stripe_session_id' => $sessionId,
                'stripe_payment_intent_id' => $session['payment_intent'] ?? null,
            ]);
        }

        return redirect()->route('consultant.dashboard')
            ->with('info', 'Stripe payment is still processing. Your purchase will activate automatically once confirmed.');
    }

    protected function completePaid(PaymentTransaction $transaction, array $gatewayRefs)
    {
        try {
            $this->paymentCompletion->complete($transaction, $gatewayRefs);
        } catch (\Throwable $e) {
            Log::error('Consultant agency payment activation failed', [
                'transaction_id' => $transaction->id,
                'type' => $transaction->transaction_type,
                'error' => $e->getMessage(),
            ]);

            return $this->redirectAfterFailure($transaction)
                ->with('error', 'Payment received but activation failed. Contact support.');
        }

        $message = match ($transaction->transaction_type) {
            'consultant_agency_extra_slot' => 'Extra slots added — you can add more managed clients.',
            'consultant_agency_year_unlock' => 'Reporting year unlocked — full exports are now available for that year.',
            'consultant_agency_renewal' => 'Renewal complete — selected clients are active for the new contract year.',
            default => (SubscriptionPlan::find($transaction->metadata['plan_id'] ?? null)?->plan_name ?? 'Agency pack')
                . ' is now active — you can add managed clients.',
        };

        if ($transaction->transaction_type === 'consultant_agency_year_unlock') {
            $engagementId = $transaction->metadata['engagement_id'] ?? null;

            if ($engagementId) {
                return redirect()->route('consultant.clients.show', $engagementId)->with('success', $message);
            }
        }

        return redirect()->route('consultant.dashboard')->with('success', $message);
    }

    protected function redirectAfterFailure(PaymentTransaction $transaction)
    {
        if ($transaction->transaction_type === 'consultant_agency_year_unlock') {
            $engagementId = $transaction->metadata['engagement_id'] ?? null;

            if ($engagementId) {
                return redirect()->route('consultant.clients.show', $engagementId);
            }
        }

        if ($transaction->transaction_type === 'consultant_agency_renewal') {
            return redirect()->route('consultant.renewal.index');
        }

        return redirect()->route('consultant.packs.index');
    }

    protected function findConsultantTransaction(int $consultantOrgId, int $transactionId): PaymentTransaction
    {
        return PaymentTransaction::where('id', $transactionId)
            ->where('company_id', $consultantOrgId)
            ->whereIn('transaction_type', self::CONSULTANT_TX_TYPES)
            ->firstOrFail();
    }
}
