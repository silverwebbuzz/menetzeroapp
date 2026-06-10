<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Services\PartnerManagedClientService;
use App\Services\PartnerSubscriptionService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        protected PartnerSubscriptionService $subscriptions,
        protected PartnerManagedClientService $managedClients,
    ) {
    }

    public function index()
    {
        $partner = Auth::user()->getActiveCompany();

        if (!$partner || !$partner->isPartner()) {
            abort(403, 'Partner organisation required.');
        }

        $subscription = $this->subscriptions->getActiveSubscription($partner->id);
        $slotSummary = $this->subscriptions->slotSummary($partner->id);
        $activeClients = $this->managedClients->listForPartner($partner->id, false);

        return view('partner.dashboard', [
            'partner' => $partner,
            'subscription' => $subscription,
            'slotSummary' => $slotSummary,
            'activeClients' => $activeClients,
        ]);
    }
}
