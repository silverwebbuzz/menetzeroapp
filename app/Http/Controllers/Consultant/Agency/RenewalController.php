<?php

namespace App\Http\Controllers\Consultant\Agency;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Consultant\Agency\Concerns\ResolvesConsultantAgency;
use App\Models\SubscriptionPlan;
use App\Services\ConsultantAgencyPaymentService;
use App\Services\ConsultantAgencyRenewalService;
use App\Services\ConsultantAgencySubscriptionService;
use Illuminate\Http\Request;

class RenewalController extends Controller
{
    use ResolvesConsultantAgency;

    public function __construct(
        protected ConsultantAgencyRenewalService $renewals,
        protected ConsultantAgencySubscriptionService $subscriptions,
        protected ConsultantAgencyPaymentService $checkout,
    ) {
    }

    public function index()
    {
        $consultantOrg = $this->consultantCompany();

        if (!$this->renewals->needsRenewalFlow($consultantOrg->id)) {
            return redirect()->route('consultant.dashboard')
                ->with('info', 'Renewal opens within 45 days of your pack expiry (31 Dec).');
        }

        $subscription = $this->renewals->getRenewableSubscription($consultantOrg->id);

        if (!$subscription) {
            return redirect()->route('consultant.packs.index')
                ->with('info', 'Purchase an agency pack to get started.');
        }

        $nextYear = (int) $subscription->contract_year + 1;
        $engagements = $this->renewals->expiringEngagements($subscription);
        $plans = SubscriptionPlan::forConsultantAgency()->active()->orderBy('sort_order')->get();
        $planQuotes = $this->subscriptions->planQuotes($consultantOrg, $plans, $nextYear);

        return view('consultant.agency.renewal.index', compact(
            'subscription',
            'nextYear',
            'engagements',
            'plans',
            'planQuotes',
        ));
    }

    public function process(Request $request)
    {
        if (!\App\Models\PaymentGateway::checkoutAvailable()) {
            return redirect()->route('consultant.renewal.index')
                ->with('error', 'Online payments are not available yet. Pack renewal checkout is coming soon.');
        }

        $consultantOrg = $this->consultantCompany();

        if (!$this->renewals->needsRenewalFlow($consultantOrg->id)) {
            return redirect()->route('consultant.dashboard')
                ->with('error', 'Renewal is not open yet for your current contract.');
        }

        $subscription = $this->renewals->getRenewableSubscription($consultantOrg->id);

        if (!$subscription) {
            return redirect()->route('consultant.packs.index')->with('error', 'No expiring pack found for renewal.');
        }

        $data = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'gateway' => 'required|in:razorpay,cashfree,stripe',
            'carry' => 'nullable|array',
            'carry.*.engagement_id' => 'required|integer',
            'carry.*.primary_reporting_year' => 'required|integer|min:2000|max:2100',
            'carry.*.selected' => 'nullable|boolean',
        ]);

        $plan = SubscriptionPlan::where('id', $data['plan_id'])
            ->where('plan_category', 'consultant_agency')
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
            $quote = $this->renewals->resolveRenewalPurchase(
                $consultantOrg,
                $plan,
                $subscription,
                $carried,
                $chargeCurrency,
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return $this->checkout->start(
            $consultantOrg,
            'consultant_agency_renewal',
            $data['gateway'],
            $quote['charge_amount'],
            $quote['charge_currency'],
            "Renewal {$plan->plan_name} for {$nextYear} (" . count($carried) . ' clients)',
            [
                'plan_id' => $plan->id,
                'plan_code' => $plan->plan_code,
                'contract_year' => $nextYear,
                'previous_subscription_id' => $subscription->id,
                'carried_engagements' => $carried,
                'pro_rata' => $quote['pro_rata'],
            ],
            fn () => $this->renewals->resolveRenewalPurchase($consultantOrg, $plan, $subscription, $carried, 'INR'),
        );
    }
}
