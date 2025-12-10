<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PartnerExternalClientService;
use App\Models\PartnerExternalClient;

class ExternalClientController extends Controller
{
    protected $service;

    public function __construct(PartnerExternalClientService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of external clients.
     */
    public function index()
    {
        $user = auth()->user();
        $partnerId = $user->getActiveCompany()->id;
        
        $clients = $this->service->getExternalClients($partnerId);
        $clientLimit = $this->service->getClientLimit($partnerId);
        $clientCount = $this->service->getClientCount($partnerId);
        $canAddMore = $this->service->canAddMoreClients($partnerId);

        return view('partner.clients.index', compact('clients', 'clientLimit', 'clientCount', 'canAddMore'));
    }

    /**
     * Show the form for creating a new external client.
     */
    public function create()
    {
        $user = auth()->user();
        $partnerId = $user->getActiveCompany()->id;
        
        $canAddMore = $this->service->canAddMoreClients($partnerId);
        
        if (!$canAddMore) {
            return redirect()->route('partner.clients.index')
                ->withErrors(['limit' => 'Client limit reached for your subscription plan']);
        }

        return view('partner.clients.create');
    }

    /**
     * Store a newly created external client.
     */
    public function store(Request $request)
    {
        $request->validate([
            'client_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'sector' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            $user = auth()->user();
            $partnerId = $user->getActiveCompany()->id;
            
            $client = $this->service->addExternalClient($partnerId, $request->all());

            return redirect()->route('partner.clients.show', $client->id)
                ->with('success', 'External client added successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified external client.
     */
    public function show($id)
    {
        $client = PartnerExternalClient::with(['locations', 'documents', 'reports'])->findOrFail($id);
        
        // Verify ownership
        $user = auth()->user();
        $partnerId = $user->getActiveCompany()->id;
        
        if ($client->partner_company_id != $partnerId) {
            abort(403, 'Unauthorized');
        }

        return view('partner.clients.show', compact('client'));
    }

    /**
     * Show the form for editing the specified external client.
     */
    public function edit($id)
    {
        $client = PartnerExternalClient::findOrFail($id);
        
        // Verify ownership
        $user = auth()->user();
        $partnerId = $user->getActiveCompany()->id;
        
        if ($client->partner_company_id != $partnerId) {
            abort(403, 'Unauthorized');
        }

        return view('partner.clients.edit', compact('client'));
    }

    /**
     * Update the specified external client.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'client_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'sector' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,archived',
            'notes' => 'nullable|string',
        ]);

        $client = PartnerExternalClient::findOrFail($id);
        
        // Verify ownership
        $user = auth()->user();
        $partnerId = $user->getActiveCompany()->id;
        
        if ($client->partner_company_id != $partnerId) {
            abort(403, 'Unauthorized');
        }

        try {
            $this->service->updateExternalClient($id, $request->all());

            return redirect()->route('partner.clients.show', $id)
                ->with('success', 'External client updated successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Remove the specified external client.
     */
    public function destroy($id)
    {
        $client = PartnerExternalClient::findOrFail($id);
        
        // Verify ownership
        $user = auth()->user();
        $partnerId = $user->getActiveCompany()->id;
        
        if ($client->partner_company_id != $partnerId) {
            abort(403, 'Unauthorized');
        }

        try {
            $this->service->deleteExternalClient($id);

            return redirect()->route('partner.clients.index')
                ->with('success', 'External client deleted successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}

