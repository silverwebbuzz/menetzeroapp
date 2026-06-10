<?php

namespace App\Http\Controllers\Consultant\Agency;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Consultant\Agency\Concerns\ResolvesConsultantAgency;
use App\Services\ConsultantAgencyEntitlementService;
use App\Services\ConsultantAgencyClientService;
use App\Services\ConsultantAgencySubscriptionService;
use Illuminate\Http\Request;
use RuntimeException;

class ManagedClientController extends Controller
{
    use ResolvesConsultantAgency;

    public function __construct(
        protected ConsultantAgencyClientService $managedClients,
        protected ConsultantAgencySubscriptionService $subscriptions,
        protected ConsultantAgencyEntitlementService $entitlements,
    ) {
    }

    public function index()
    {
        $consultantOrg = $this->consultantCompany();
        $engagements = $this->managedClients->listForConsultant($consultantOrg->id);
        $subscription = $this->subscriptions->getActiveSubscription($consultantOrg->id);
        $slotSummary = $this->subscriptions->slotSummary($consultantOrg->id, $subscription);

        return view('consultant.agency.clients.index', compact('engagements', 'slotSummary'));
    }

    public function create()
    {
        $consultantOrg = $this->consultantCompany();
        $subscription = $this->subscriptions->getActiveSubscription($consultantOrg->id);
        $slotSummary = $this->subscriptions->slotSummary($consultantOrg->id, $subscription);
        $defaultPry = $subscription?->contract_year ?? (int) now()->year;

        return view('consultant.agency.clients.create', compact('subscription', 'slotSummary', 'defaultPry'));
    }

    public function store(Request $request)
    {
        $consultantOrg = $this->consultantCompany();

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
            $engagement = $this->managedClients->create($consultantOrg, $validated);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('consultant.clients.show', $engagement)
            ->with('success', 'Managed client added — 1 slot consumed.');
    }

    public function show(int $client)
    {
        $consultantOrg = $this->consultantCompany();
        $engagement = $this->managedClients->findForConsultant($consultantOrg->id, $client);
        $yearUnlockTarget = null;
        $yearUnlockQuote = null;

        if ($engagement->isActive()) {
            $candidateYear = (int) $engagement->primary_reporting_year + 1;
            $mode = $this->entitlements->reportingYearMode($engagement, $candidateYear);

            if ($mode === ConsultantAgencyEntitlementService::MODE_PREVIEW) {
                $yearUnlockTarget = $candidateYear;

                try {
                    $yearUnlockQuote = $this->subscriptions->resolveYearUnlockPurchase(
                        $engagement,
                        $candidateYear,
                    );
                } catch (\Throwable) {
                    $yearUnlockQuote = null;
                }
            }
        }

        return view('consultant.agency.clients.show', compact(
            'engagement',
            'yearUnlockTarget',
            'yearUnlockQuote',
        ));
    }

    public function edit(int $client)
    {
        $consultantOrg = $this->consultantCompany();
        $engagement = $this->managedClients->findForConsultant($consultantOrg->id, $client);

        return view('consultant.agency.clients.edit', compact('engagement'));
    }

    public function update(Request $request, int $client)
    {
        $consultantOrg = $this->consultantCompany();
        $engagement = $this->managedClients->findForConsultant($consultantOrg->id, $client);

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
            ->route('consultant.clients.show', $engagement)
            ->with('success', 'Client details updated.');
    }

    public function destroy(int $client)
    {
        $consultantOrg = $this->consultantCompany();
        $engagement = $this->managedClients->findForConsultant($consultantOrg->id, $client);

        if ($engagement->isActive()) {
            $this->managedClients->archive($engagement);

            return redirect()
                ->route('consultant.clients.index')
                ->with('success', 'Client archived — slot freed for this contract year.');
        }

        return redirect()
            ->route('consultant.clients.index')
            ->with('info', 'Client was already archived.');
    }

}
