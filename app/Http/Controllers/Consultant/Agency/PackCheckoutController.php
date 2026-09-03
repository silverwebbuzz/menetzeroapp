<?php

namespace App\Http\Controllers\Consultant\Agency;

use App\Data\ConsultantAgencyPlanMatrix;
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
        // Self-serve purchase. Enterprise is filtered out: it has no list price
        // (negotiated per deal), so a Buy-now button there would post a plan
        // resolvePackPurchase() cannot quote. It stays reachable through the
        // request form below the grid.
        $buyablePacks = collect(\App\Data\ConsultantAgencyPlanMatrix::selectablePacks())
            ->filter(fn (array $pack) => isset(
                \App\Data\ConsultantAgencyPlanMatrix::SLOT_PRICING[$pack['plan_code'] ?? '']
            ))
            ->values();

        // Only rows that actually exist in subscription_plans can be bought --
        // the form posts plan_id, not plan_code.
        $planIds = SubscriptionPlan::where('plan_category', 'consultant_agency')
            ->where('is_active', true)
            ->pluck('id', 'plan_code');

        // Invoices issued to this agency. Shown on this page because it is
        // where an agency buys -- there is no separate consultant billing
        // screen, and an invoice the buyer cannot find is not delivered.
        $invoices = \App\Models\Invoice::where('company_id', $consultantOrg->id)
            ->orderByDesc('issued_at')
            ->limit(24)
            ->get();

        $checkoutAvailable = PaymentGateway::checkoutAvailable();
        $minSlots = \App\Data\ConsultantAgencyPlanMatrix::MIN_SLOTS;

        // Default the quantity to what the agency actually needs. An agency
        // already managing more clients than MIN_SLOTS would otherwise land on
        // a pre-filled 5, submit it, and be told to archive clients -- the
        // form would be steering them into its own validation error.
        $suggestedSlots = max($minSlots, (int) ($slotSummary['used'] ?? 0));
        $slotPricing = \App\Data\ConsultantAgencyPlanMatrix::SLOT_PRICING;

        return view('consultant.agency.packs.index', compact(
            'subscription',
            'slotSummary',
            'contractYear',
            'buyablePacks',
            'suggestedSlots',
            'invoices',
            'planIds',
            'checkoutAvailable',
            'minSlots',
            'slotPricing',
        ));
    }

    /**
     * Buy an agency pack (slots at Carbon or ESG depth).
     *
     * Razorpay only, charged in the display currency. If AED is not yet
     * activated on the Razorpay account, ConsultantAgencyPaymentService
     * re-prices through the closure passed as the last argument and charges
     * the INR equivalent, telling the buyer before they pay.
     */
    public function processCheckout(Request $request)
    {
        if (!PaymentGateway::checkoutAvailable()) {
            return redirect()->route('consultant.packs.index')
                ->with('error', 'Online payments are not available yet. Agency pack checkout is coming soon.');
        }

        $consultantOrg = $this->consultantCompany();

        $data = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            // Minimum enforced here as well as in the form: the field is a
            // number input and nothing stops a smaller value being posted.
            // Cap raised from 50: an agency with more than 50 active clients
            // could not buy enough slots to satisfy validatePackChange(), so
            // self-serve was impossible for exactly the largest customers.
            'quantity' => 'required|integer|min:' . ConsultantAgencyPlanMatrix::MIN_SLOTS . '|max:500',
        ]);

        $slots = (int) $data['quantity'];

        // is_active keeps a retired pack from being sold; an agency already on
        // one keeps it, but nobody new can buy it.
        $plan = SubscriptionPlan::where('id', $data['plan_id'])
            ->where('plan_category', 'consultant_agency')
            ->where('is_active', true)
            ->firstOrFail();

        $chargeCurrency = \App\Services\CurrencyService::displayCurrency();
        $current = $this->consultantSubscriptions->getActiveSubscription($consultantOrg->id);

        try {
            $this->consultantSubscriptions->validatePackChange($consultantOrg, $plan, $current, $slots);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $quote = $this->consultantSubscriptions->resolvePackPurchase($consultantOrg, $plan, null, $chargeCurrency, $slots);

        if (!$quote['requires_payment'] || $quote['charge_amount'] <= 0) {
            return back()->with('error', 'This pack is not available for online checkout.');
        }

        return $this->checkout->start(
            $consultantOrg,
            'consultant_agency_pack',
            'razorpay',
            $quote['charge_amount'],
            $quote['charge_currency'],
            'Agency pack: ' . $plan->plan_name . ' x' . $quote['slots'] . ' (' . $quote['contract_year'] . ')',
            [
                'plan_id' => $plan->id,
                'plan_code' => $plan->plan_code,
                'contract_year' => $quote['contract_year'],
                'pro_rata' => $quote['pro_rata'],
                // Read at activation to set slot_limit on the subscription row.
                'slot_limit' => $quote['slots'],
            ],
            fn () => $this->consultantSubscriptions->resolvePackPurchase($consultantOrg, $plan, $quote['contract_year'], 'INR', $slots),
        );
    }

    /**
     * Add slots to an existing pack. Priced at the depth of that pack, in
     * volume bands -- blocks of five cost less per slot than singles, and both
     * cost less than the entry rate, so expanding is never dearer than
     * starting.
     */
    public function processExtraSlots(Request $request)
    {
        if (!PaymentGateway::checkoutAvailable()) {
            return redirect()->route('consultant.packs.index')
                ->with('error', 'Online payments are not available yet. Extra slot purchases are coming soon.');
        }

        $consultantOrg = $this->consultantCompany();
        $subscription = $this->consultantSubscriptions->getActiveSubscription($consultantOrg->id);

        if (!$subscription) {
            return back()->with('error', 'Purchase an agency pack before adding extra slots.');
        }

        $data = $request->validate([
            'quantity' => 'required|integer|min:1|max:50',
        ]);

        $chargeCurrency = \App\Services\CurrencyService::displayCurrency();

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
            'razorpay',
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
