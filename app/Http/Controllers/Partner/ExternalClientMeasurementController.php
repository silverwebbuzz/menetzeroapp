<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PartnerExternalClient;
use App\Models\PartnerExternalClientLocation;
use App\Models\PartnerExternalClientMeasurement;
use App\Models\EmissionSourceMaster;

class ExternalClientMeasurementController extends Controller
{
    /**
     * Display measurements for a location.
     */
    public function index($clientId, $locationId)
    {
        $client = PartnerExternalClient::findOrFail($clientId);
        $location = PartnerExternalClientLocation::findOrFail($locationId);
        
        // Verify ownership
        $user = auth()->user();
        $partnerId = $user->getActiveCompany()->id;
        
        if ($client->partner_company_id != $partnerId || $location->partner_external_client_id != $clientId) {
            abort(403, 'Unauthorized');
        }

        $measurements = $location->measurements()->with('creator')->orderBy('period_start', 'desc')->get();

        return view('partner.clients.measurements.index', compact('client', 'location', 'measurements'));
    }

    /**
     * Store a newly created measurement.
     */
    public function store(Request $request, $clientId, $locationId)
    {
        $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'frequency' => 'required|in:monthly,quarterly,half_yearly,annually',
            'fiscal_year' => 'required|integer',
            'fiscal_year_start_month' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $client = PartnerExternalClient::findOrFail($clientId);
        $location = PartnerExternalClientLocation::findOrFail($locationId);
        
        // Verify ownership
        $user = auth()->user();
        $partnerId = $user->getActiveCompany()->id;
        
        if ($client->partner_company_id != $partnerId || $location->partner_external_client_id != $clientId) {
            abort(403, 'Unauthorized');
        }

        $measurement = PartnerExternalClientMeasurement::create([
            'partner_external_client_location_id' => $locationId,
            'period_start' => $request->period_start,
            'period_end' => $request->period_end,
            'frequency' => $request->frequency,
            'status' => 'draft',
            'fiscal_year' => $request->fiscal_year,
            'fiscal_year_start_month' => $request->fiscal_year_start_month,
            'created_by' => auth()->id(),
            'notes' => $request->notes,
            'staff_count' => $location->staff_count,
            'staff_work_from_home' => $location->staff_work_from_home,
            'work_from_home_percentage' => $location->work_from_home_percentage,
        ]);

        return redirect()->route('partner.clients.measurements.show', [$clientId, $measurement->id])
            ->with('success', 'Measurement created successfully');
    }

    /**
     * Display the specified measurement.
     */
    public function show($clientId, $measurementId)
    {
        $client = PartnerExternalClient::findOrFail($clientId);
        $measurement = PartnerExternalClientMeasurement::with(['location', 'measurementData.emissionSource', 'creator'])->findOrFail($measurementId);
        
        // Verify ownership
        $user = auth()->user();
        $partnerId = $user->getActiveCompany()->id;
        
        if ($client->partner_company_id != $partnerId || $measurement->location->partner_external_client_id != $clientId) {
            abort(403, 'Unauthorized');
        }

        // Get available emission sources
        $emissionSources = EmissionSourceMaster::active()->get();

        return view('partner.clients.measurements.show', compact('client', 'measurement', 'emissionSources'));
    }

    /**
     * Update the specified measurement.
     */
    public function update(Request $request, $clientId, $measurementId)
    {
        $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'frequency' => 'required|in:monthly,quarterly,half_yearly,annually',
            'status' => 'required|in:draft,submitted,under_review,not_verified,verified',
            'notes' => 'nullable|string',
        ]);

        $client = PartnerExternalClient::findOrFail($clientId);
        $measurement = PartnerExternalClientMeasurement::findOrFail($measurementId);
        
        // Verify ownership
        $user = auth()->user();
        $partnerId = $user->getActiveCompany()->id;
        
        if ($client->partner_company_id != $partnerId || $measurement->location->partner_external_client_id != $clientId) {
            abort(403, 'Unauthorized');
        }

        $measurement->update($request->all());

        return redirect()->route('partner.clients.measurements.show', [$clientId, $measurementId])
            ->with('success', 'Measurement updated successfully');
    }

    /**
     * Remove the specified measurement.
     */
    public function destroy($clientId, $measurementId)
    {
        $client = PartnerExternalClient::findOrFail($clientId);
        $measurement = PartnerExternalClientMeasurement::findOrFail($measurementId);
        
        // Verify ownership
        $user = auth()->user();
        $partnerId = $user->getActiveCompany()->id;
        
        if ($client->partner_company_id != $partnerId || $measurement->location->partner_external_client_id != $clientId) {
            abort(403, 'Unauthorized');
        }

        $locationId = $measurement->partner_external_client_location_id;
        $measurement->delete();

        return redirect()->route('partner.clients.measurements.index', [$clientId, $locationId])
            ->with('success', 'Measurement deleted successfully');
    }
}

