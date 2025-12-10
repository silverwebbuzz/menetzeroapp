<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PartnerExternalClient;
use App\Models\PartnerExternalClientLocation;

class ExternalClientLocationController extends Controller
{
    /**
     * Display locations for an external client.
     */
    public function index($clientId)
    {
        $client = PartnerExternalClient::findOrFail($clientId);
        
        // Verify ownership
        $user = auth()->user();
        $partnerId = $user->getActiveCompany()->id;
        
        if ($client->partner_company_id != $partnerId) {
            abort(403, 'Unauthorized');
        }

        $locations = $client->locations()->with('measurements')->get();

        return view('partner.clients.locations.index', compact('client', 'locations'));
    }

    /**
     * Store a newly created location.
     */
    public function store(Request $request, $clientId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'location_type' => 'nullable|string|max:255',
            'staff_count' => 'nullable|integer',
            'staff_work_from_home' => 'nullable|boolean',
            'work_from_home_percentage' => 'nullable|numeric|min:0|max:100',
            'fiscal_year_start' => 'nullable|string',
            'is_head_office' => 'nullable|boolean',
            'measurement_frequency' => 'nullable|in:Annually,Half Yearly,Quarterly,Monthly',
        ]);

        $client = PartnerExternalClient::findOrFail($clientId);
        
        // Verify ownership
        $user = auth()->user();
        $partnerId = $user->getActiveCompany()->id;
        
        if ($client->partner_company_id != $partnerId) {
            abort(403, 'Unauthorized');
        }

        $location = PartnerExternalClientLocation::create([
            'partner_external_client_id' => $clientId,
            'name' => $request->name,
            'address' => $request->address,
            'city' => $request->city,
            'country' => $request->country,
            'location_type' => $request->location_type,
            'staff_count' => $request->staff_count,
            'staff_work_from_home' => $request->staff_work_from_home ?? false,
            'work_from_home_percentage' => $request->work_from_home_percentage,
            'fiscal_year_start' => $request->fiscal_year_start ?? 'January',
            'is_head_office' => $request->is_head_office ?? false,
            'is_active' => true,
            'receives_utility_bills' => $request->receives_utility_bills ?? false,
            'pays_electricity_proportion' => $request->pays_electricity_proportion ?? false,
            'shared_building_services' => $request->shared_building_services ?? false,
            'measurement_frequency' => $request->measurement_frequency ?? 'Annually',
        ]);

        return redirect()->route('partner.clients.locations.show', [$clientId, $location->id])
            ->with('success', 'Location added successfully');
    }

    /**
     * Display the specified location.
     */
    public function show($clientId, $locationId)
    {
        $client = PartnerExternalClient::findOrFail($clientId);
        $location = PartnerExternalClientLocation::with(['measurements', 'emissionBoundaries'])->findOrFail($locationId);
        
        // Verify ownership
        $user = auth()->user();
        $partnerId = $user->getActiveCompany()->id;
        
        if ($client->partner_company_id != $partnerId || $location->partner_external_client_id != $clientId) {
            abort(403, 'Unauthorized');
        }

        return view('partner.clients.locations.show', compact('client', 'location'));
    }

    /**
     * Update the specified location.
     */
    public function update(Request $request, $clientId, $locationId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'location_type' => 'nullable|string|max:255',
            'staff_count' => 'nullable|integer',
            'staff_work_from_home' => 'nullable|boolean',
            'work_from_home_percentage' => 'nullable|numeric|min:0|max:100',
            'fiscal_year_start' => 'nullable|string',
            'is_head_office' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'measurement_frequency' => 'nullable|in:Annually,Half Yearly,Quarterly,Monthly',
        ]);

        $client = PartnerExternalClient::findOrFail($clientId);
        $location = PartnerExternalClientLocation::findOrFail($locationId);
        
        // Verify ownership
        $user = auth()->user();
        $partnerId = $user->getActiveCompany()->id;
        
        if ($client->partner_company_id != $partnerId || $location->partner_external_client_id != $clientId) {
            abort(403, 'Unauthorized');
        }

        $location->update($request->all());

        return redirect()->route('partner.clients.locations.show', [$clientId, $locationId])
            ->with('success', 'Location updated successfully');
    }

    /**
     * Remove the specified location.
     */
    public function destroy($clientId, $locationId)
    {
        $client = PartnerExternalClient::findOrFail($clientId);
        $location = PartnerExternalClientLocation::findOrFail($locationId);
        
        // Verify ownership
        $user = auth()->user();
        $partnerId = $user->getActiveCompany()->id;
        
        if ($client->partner_company_id != $partnerId || $location->partner_external_client_id != $clientId) {
            abort(403, 'Unauthorized');
        }

        $location->delete();

        return redirect()->route('partner.clients.locations.index', $clientId)
            ->with('success', 'Location deleted successfully');
    }
}

