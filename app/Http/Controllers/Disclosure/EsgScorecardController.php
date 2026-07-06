<?php

namespace App\Http\Controllers\Disclosure;

use App\Exports\EsgScorecardExport;
use App\Services\EsgScorecardImportService;
use App\Services\EsgScorecardService;
use App\Services\PlanEntitlementService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EsgScorecardController extends DisclosureBaseController
{
    public function __construct(
        protected EsgScorecardService $scorecardService,
        protected EsgScorecardImportService $importService,
    ) {
    }

    public function index(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);
        $scorecard = $this->scorecardService->build($company, $fiscalYear);

        return view('disclosures.esg-scorecard', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'scorecard' => $scorecard,
        ]);
    }

    public function update(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);

        $year = (int) $request->input('metric_year', $fiscalYear);
        $category = $request->input('category', '');

        if (!isset(config('esg_scorecard.categories')[$category])) {
            abort(422, 'Invalid scorecard category.');
        }

        $this->requireReportingYearWrite($company->id, $year);

        $this->scorecardService->saveManual(
            $company->id,
            $year,
            $category,
            $request->input('metrics', [])
        );

        return $this->fiscalRedirect(
            'disclosures.esg-scorecard',
            $fiscalYear,
            'Scorecard metrics saved.',
            ['category' => $category]
        );
    }

    public function sync(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);

        $count = $this->scorecardService->syncAutoSnapshots($company, $fiscalYear);

        return $this->fiscalRedirect(
            'disclosures.esg-scorecard',
            $fiscalYear,
            "Synced {$count} auto KPI snapshot(s) from GHG and GRI data."
        );
    }

    public function exportExcel(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);
        $this->requirePermission('disclosures', 'export', [['reports', 'view']]);
        $this->requireDisclosureExport($company->id, PlanEntitlementService::EXPORT_ESG_SCORECARD, $fiscalYear);

        $scorecard = $this->scorecardService->build($company, $fiscalYear);
        $rows = $this->scorecardService->flattenForExport($scorecard);
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($company->name ?? 'company'));

        return Excel::download(
            new EsgScorecardExport($rows, $scorecard['years'], $company->name),
            "esg-scorecard-{$fiscalYear}-{$slug}.xlsx"
        );
    }

    public function downloadImportTemplate()
    {
        $csv = $this->importService->templateCsv();

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="esg-scorecard-import-template.csv"',
        ]);
    }

    public function importCsv(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);

        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);

        $result = $this->importService->importFromCsv($company, $request->file('file'));

        $message = "Imported {$result['imported']} row(s).";
        if ($result['skipped'] > 0) {
            $message .= " Skipped {$result['skipped']}.";
        }
        if (!empty($result['errors'])) {
            return redirect()
                ->route('disclosures.esg-scorecard.index', ['fiscal_year' => $fiscalYear])
                ->with('success', $message)
                ->with('import_errors', $result['errors']);
        }

        return $this->fiscalRedirect('disclosures.esg-scorecard.index', $fiscalYear, $message);
    }
}
