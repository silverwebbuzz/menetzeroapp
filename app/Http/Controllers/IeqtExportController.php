<?php

namespace App\Http\Controllers;

use App\Models\Measurement;
use App\Services\ExportReadinessService;
use App\Services\IeqtExportService;
use App\Services\PlanEntitlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IeqtExportController extends Controller
{
    public function __construct(
        protected IeqtExportService $exportService,
        protected ExportReadinessService $exportReadiness,
    ) {
    }

    public function export(Request $request)
    {
        $this->requirePermission('reports.view', null, ['reports.*']);

        $company = Auth::user()->getActiveCompany();
        if (!$company) {
            abort(403, 'No active company found.');
        }

        $request->validate([
            'location_id' => 'required|exists:locations,id',
            'fiscal_year' => 'required|integer|min:2000|max:2100',
        ]);

        $location = $company->locations()->where('id', $request->location_id)->firstOrFail();
        $fiscalYear = (int) $request->fiscal_year;

        $this->requirePlanExport($company->id, PlanEntitlementService::EXPORT_IEQT, $fiscalYear);

        $measurement = Measurement::query()
            ->where('location_id', $location->id)
            ->where('fiscal_year', $fiscalYear)
            ->firstOrFail();

        $readiness = $this->exportReadiness->assess($measurement, true);
        if (!$readiness['is_ready']) {
            abort(422, implode(' ', $readiness['errors']));
        }

        return $this->exportService->downloadCsv($company, $location->id, $fiscalYear);
    }
}
