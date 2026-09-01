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

        $packages = \App\Data\CompanyPackageOptions::packages();
        $extraOptions = \App\Data\CompanyPackageOptions::extraOptions();
        $matrix = \App\Data\CompanyPackageOptions::comparisonMatrix();

        // Phase 3–5: self-serve pack grid/checkout hidden — request clients offline.
        return view('consultant.agency.packs.index', compact(
            'subscription',
            'slotSummary',
            'contractYear',
            'recentRequests',
            'packages',
            'extraOptions',
            'matrix',
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
