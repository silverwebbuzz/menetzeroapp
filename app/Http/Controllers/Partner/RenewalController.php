<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Models\SubscriptionPlan;
use App\Services\PartnerRenewalService;
use App\Services\PartnerSubscriptionService;
use App\Services\PartnerWorkspaceService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RenewalController extends Controller
{
    public function __construct(
        protected PartnerRenewalService $renewals,
        protected PartnerSubscriptionService $subscriptions,
        protected PartnerWorkspaceService $workspace,
        protected PaymentService $paymentService,
    ) {
    }

    public function index()
    {
        $partner = $this->partnerCompany();
        $subscription = $this->renewals->getRenewableSubscription($partner->id);

        if (!$subscription) {
            return redirect()->route('consultant.packs.index')
                ->with('info', 'Purchase an agency pack to get started.');
        }

        $nextYear = (int) $subscription->contract_year + 1;
        $engagements = $this->renewals->expiringEngagements($subscription);
        $plans = SubscriptionPlan::forPartners()->active()->orderBy('sort_order')->get();

        return view('partner.renewal.index', compact(
            'partner',
            'subscription',
            'nextYear',
            'engagements',
            'plans',
        ));
    }

    public function process(Request $request)
    {
        $partner = $this->partnerCompany();
        $subscription = $this->renewals->getRenewableSubscription($partner->id);

        if (!$subscription) {
            return redirect()->route('consultant.packs.index')->with('error', 'No expiring pack found for renewal.');
        }

        $data = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'gateway' => 'required|in:razorpay,cashfree',
            'carry' => 'nullable|array',
            'carry.*.engagement_id' => 'required|integer',
            'carry.*.primary_reporting_year' => 'required|integer|min:2000|max:2100',
            'carry.*.selected' => 'nullable|boolean',
        ]);

        $plan = SubscriptionPlan::where('id', $data['plan_id'])
            ->where('plan_category', 'partner')
            ->firstOrFail();

        $carried = collect($data['carry'] ?? [])
            ->filter(fn ($row) => !empty($row['selected']))
            ->map(fn ($row) => [
                'engagement_id' => (int) $row['engagement_id'],
                'primary_reporting_year' => (int) $row['primary_reporting_year'],
            ])
            ->values()
            ->all();

        $nextYear = (int) $subscription->contract_year + 1;
        $displayCurrency = \App\Services\CurrencyService::displayCurrency();
        $chargeCurrency = $data['gateway'] === 'razorpay' ? 'INR' : $displayCurrency;

        try {
            $this->renewals->validateCarryForward($partner, $subscription, $plan, $carried);
            $quote = $this->renewals->resolveRenewalPurchase(
                $partner,
                $plan,
                $subscription,
                $carried,
                $chargeCurrency,
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $gateway = PaymentGateway::forGateway($data['gateway']);
        if (!$gateway || !$gateway->is_enabled || !$gateway->isConfigured()) {
            return back()->with('error', 'The selected payment method is not available.');
        }

        $transaction = PaymentTransaction::create([
            'company_id' => $partner->id,
            'transaction_type' => 'partner_renewal',
            'amount' => $quote['charge_amount'],
            'currency' => $quote['charge_currency'],
            'status' => 'pending',
            'payment_method' => $gateway->gateway,
            'description' => "Renewal {$plan->plan_name} for {$nextYear} (" . count($carried) . ' clients)',
            'metadata' => [
                'transaction_type' => 'partner_renewal',
                'plan_id' => $plan->id,
                'plan_code' => $plan->plan_code,
                'contract_year' => $nextYear,
                'previous_subscription_id' => $subscription->id,
                'carried_engagements' => $carried,
                'pro_rata' => $quote['pro_rata'],
                'display_currency' => $displayCurrency,
            ],
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
                    ['type' => 'partner_renewal', 'partner_id' => (string) $partner->id]
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
                        $inrQuote = $this->renewals->resolveRenewalPurchase(
                            $partner,
                            $plan,
                            $subscription,
                            $carried,
                            'INR',
                        );
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

            return back()->withInput()->with('error', 'Unable to start payment: ' . $e->getMessage());
        }
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
