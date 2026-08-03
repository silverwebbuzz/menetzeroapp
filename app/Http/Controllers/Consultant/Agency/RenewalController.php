<?php

namespace App\Http\Controllers\Consultant\Agency;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Consultant\Agency\Concerns\ResolvesConsultantAgency;
use App\Services\ConsultantAgencyRenewalService;
use Illuminate\Http\Request;

class RenewalController extends Controller
{
    use ResolvesConsultantAgency;

    public function __construct(
        protected ConsultantAgencyRenewalService $renewals,
    ) {
    }

    public function index()
    {
        $consultantOrg = $this->consultantCompany();

        if (!$this->renewals->needsRenewalFlow($consultantOrg->id)) {
            return redirect()->route('consultant.dashboard')
                ->with('info', 'Renewal opens within 45 days of capacity expiry (typically 31 Dec).');
        }

        $subscription = $this->renewals->getRenewableSubscription($consultantOrg->id);

        if (!$subscription) {
            return redirect()->route('consultant.packs.index')
                ->with('info', 'Request managed-client capacity to get started.');
        }

        $nextYear = (int) $subscription->contract_year + 1;
        $engagements = $this->renewals->expiringEngagements($subscription);

        return view('consultant.agency.renewal.index', compact(
            'subscription',
            'nextYear',
            'engagements',
        ));
    }

    public function process(Request $request)
    {
        return redirect()->route('consultant.packs.index')
            ->with('info', 'Renewals are handled offline. Submit Request clients for the next year — MENetZero will quote and activate after payment.');
    }
}
