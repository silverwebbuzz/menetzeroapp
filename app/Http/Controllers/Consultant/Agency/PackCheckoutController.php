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
        $plans = SubscriptionPlan::forConsultantAgency()->active()->orderBy('sort_order')->get();
        $contractYear = (int) now()->year;
        $planQuotes = $this->consultantSubscriptions->planQuotes($consultantOrg, $plans, $contractYear);
        $extraSlotQuote = $subscription
            ? $this->consultantSubscriptions->resolveExtraSlotPurchase($subscription, 1)
            : null;

        return view('consultant.agency.packs.index', compact(
            'subscription',
            'slotSummary',
            'plans',
            'contractYear',
            'extraSlotQuote',
            'planQuotes',
        ));
    }

    public function processCheckout(Request $request)
    {
        $consultantOrg = $this->consultantCompany();

        $data = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'gateway' => 'required|in:razorpay,cashfree',
        ]);

        $plan = SubscriptionPlan::where('id', $data['plan_id'])
            ->where('plan_category', 'consultant_agency')
            ->firstOrFail();

        $displayCurrency = \App\Services\CurrencyService::displayCurrency();
        $chargeCurrency = $data['gateway'] === 'razorpay' ? 'INR' : $displayCurrency;
        $current = $this->consultantSubscriptions->getActiveSubscription($consultantOrg->id);

        try {
            $this->consultantSubscriptions->validatePackChange($consultantOrg, $plan, $current);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $quote = $this->consultantSubscriptions->resolvePackPurchase($consultantOrg, $plan, null, $chargeCurrency);

        if (!$quote['requires_payment'] || $quote['charge_amount'] <= 0) {
            return back()->with('error', 'This pack is not available for online checkout.');
        }

        return $this->checkout->start(
            $consultantOrg,
            'consultant_agency_pack',
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
            fn () => $this->consultantSubscriptions->resolvePackPurchase($consultantOrg, $plan, $quote['contract_year'], 'INR'),
        );
    }

    public function processExtraSlots(Request $request)
    {
        $consultantOrg = $this->consultantCompany();
        $subscription = $this->consultantSubscriptions->getActiveSubscription($consultantOrg->id);

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
            $quote = $this->consultantSubscriptions->resolveExtraSlotPurchase(
                $subscription,
                (int) $data['quantity'],
                $chargeCurrency,
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return $this->checkout->start(
            $consultantOrg,
            'consultant_agency_extra_slot',
            $data['gateway'],
            $quote['charge_amount'],
            $quote['charge_currency'],
            "Extra slots (×{$quote['quantity']}) through 31 Dec {$quote['contract_year']}",
            [
                'consultant_subscription_id' => $subscription->id,
                'quantity' => $quote['quantity'],
                'contract_year' => $quote['contract_year'],
                'pro_rata' => $quote['pro_rata'],
            ],
            fn () => $this->consultantSubscriptions->resolveExtraSlotPurchase(
                $subscription,
                (int) $data['quantity'],
                'INR',
            ),
        );
    }

    public function processYearUnlock(Request $request)
    {
        $consultantOrg = $this->consultantCompany();

        $data = $request->validate([
            'engagement_id' => 'required|integer',
            'reporting_year' => 'required|integer|min:2000|max:2100',
            'gateway' => 'required|in:razorpay,cashfree',
        ]);

        $engagement = $this->managedClients->findForConsultant($consultantOrg->id, (int) $data['engagement_id']);

        $displayCurrency = \App\Services\CurrencyService::displayCurrency();
        $chargeCurrency = $data['gateway'] === 'razorpay' ? 'INR' : $displayCurrency;

        try {
            $quote = $this->consultantSubscriptions->resolveYearUnlockPurchase(
                $engagement,
                (int) $data['reporting_year'],
                $chargeCurrency,
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $clientName = $engagement->display_name ?: $engagement->managedCompany?->name ?: 'Client';

        return $this->checkout->start(
            $consultantOrg,
            'consultant_agency_year_unlock',
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
            fn () => $this->consultantSubscriptions->resolveYearUnlockPurchase(
                $engagement,
                (int) $data['reporting_year'],
                'INR',
            ),
        );
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
