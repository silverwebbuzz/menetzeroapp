<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PartnerExternalClient;
use App\Models\PartnerExternalClientReport;

class ExternalClientReportController extends Controller
{
    /**
     * Display reports for an external client.
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

        $reports = $client->reports()->orderBy('created_at', 'desc')->get();

        return view('partner.clients.reports.index', compact('client', 'reports'));
    }

    /**
     * Generate a new report.
     */
    public function generate(Request $request, $clientId)
    {
        $request->validate([
            'report_type' => 'required|string|max:100',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
        ]);

        $client = PartnerExternalClient::findOrFail($clientId);
        
        // Verify ownership
        $user = auth()->user();
        $partnerId = $user->getActiveCompany()->id;
        
        if ($client->partner_company_id != $partnerId) {
            abort(403, 'Unauthorized');
        }

        // TODO: Implement report generation logic
        // For now, just create the report record
        $report = PartnerExternalClientReport::create([
            'partner_external_client_id' => $clientId,
            'report_type' => $request->report_type,
            'period_start' => $request->period_start,
            'period_end' => $request->period_end,
            'generated_at' => now(),
            'generated_by' => auth()->id(),
        ]);

        return redirect()->route('partner.clients.reports.index', $clientId)
            ->with('success', 'Report generated successfully');
    }
}

