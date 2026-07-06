<?php

namespace App\Http\Controllers\Disclosure;

use App\Services\PlanEntitlementService;
use App\Services\SasbIndexService;
use Illuminate\Http\Request;

class SasbIndexController extends DisclosureBaseController
{
    public function __construct(
        protected SasbIndexService $sasbService,
    ) {
    }

    public function index(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);
        $index = $this->sasbService->build($company, $fiscalYear);

        return view('disclosures.sasb.index', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'index' => $index,
            'sectors' => config('sasb.sectors', []),
            'selectedSector' => $this->sasbService->sectorForCompany($company, $fiscalYear),
        ]);
    }

    public function updateSector(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);

        $this->sasbService->saveSector(
            $company->id,
            $fiscalYear,
            $request->input('sasb_sector') ?: null
        );

        return $this->fiscalRedirect('disclosures.sasb.index', $fiscalYear, 'SASB sector saved.');
    }

    public function exportCsv(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);
        $this->requirePermission('disclosures', 'export', [['reports', 'view']]);
        $this->requireDisclosureExport($company->id, PlanEntitlementService::EXPORT_SASB_INDEX, $fiscalYear);

        $index = $this->sasbService->build($company, $fiscalYear);
        if (!$index['sector']) {
            abort(422, 'Select a SASB sector before exporting.');
        }

        $csv = $this->sasbService->toCsv($index);
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($company->name ?? 'company'));

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sasb-index-' . $index['sector'] . '-' . $fiscalYear . '-' . $slug . '.csv"',
        ]);
    }
}
