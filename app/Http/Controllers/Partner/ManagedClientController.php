<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Services\PartnerManagedClientService;
use App\Services\PartnerSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class ManagedClientController extends Controller
{
    public function __construct(
        protected PartnerManagedClientService $managedClients,
        protected PartnerSubscriptionService $subscriptions,
    ) {
    }

    public function index()
    {
        $partner = $this->partnerCompany();
        $engagements = $this->managedClients->listForPartner($partner->id);
        $slotSummary = $this->subscriptions->slotSummary($partner->id);

        return view('partner.clients.index', compact('partner', 'engagements', 'slotSummary'));
    }

    public function create()
    {
        $partner = $this->partnerCompany();
        $subscription = $this->subscriptions->getActiveSubscription($partner->id);
        $slotSummary = $this->subscriptions->slotSummary($partner->id);
        $defaultPry = $subscription?->contract_year ?? (int) now()->year;

        return view('partner.clients.create', compact('partner', 'subscription', 'slotSummary', 'defaultPry'));
    }

    public function store(Request $request)
    {
        $partner = $this->partnerCompany();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'primary_reporting_year' => 'required|integer|min:2000|max:2100',
            'country' => 'nullable|string|max:100',
            'emirate' => 'nullable|string|max:100',
            'sector' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            $engagement = $this->managedClients->create($partner, $validated);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('partner.clients.show', $engagement)
            ->with('success', 'Managed client added — 1 slot consumed.');
    }

    public function show(int $client)
    {
        $partner = $this->partnerCompany();
        $engagement = $this->managedClients->findForPartner($partner->id, $client);

        return view('partner.clients.show', compact('partner', 'engagement'));
    }

    public function edit(int $client)
    {
        $partner = $this->partnerCompany();
        $engagement = $this->managedClients->findForPartner($partner->id, $client);

        return view('partner.clients.edit', compact('partner', 'engagement'));
    }

    public function update(Request $request, int $client)
    {
        $partner = $this->partnerCompany();
        $engagement = $this->managedClients->findForPartner($partner->id, $client);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'emirate' => 'nullable|string|max:100',
            'sector' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $this->managedClients->update($engagement, $validated);

        return redirect()
            ->route('partner.clients.show', $engagement)
            ->with('success', 'Client details updated.');
    }

    public function destroy(int $client)
    {
        $partner = $this->partnerCompany();
        $engagement = $this->managedClients->findForPartner($partner->id, $client);

        if ($engagement->isActive()) {
            $this->managedClients->archive($engagement);

            return redirect()
                ->route('partner.clients.index')
                ->with('success', 'Client archived — slot freed for this contract year.');
        }

        return redirect()
            ->route('partner.clients.index')
            ->with('info', 'Client was already archived.');
    }

    protected function partnerCompany()
    {
        $company = Auth::user()->getActiveCompany();

        if (!$company || !$company->isPartner()) {
            abort(403, 'Partner organisation required.');
        }

        return $company;
    }
}
