<?php

namespace App\Http\Controllers\Disclosure;

use App\Services\ExportReadinessService;
use App\Services\GriContentIndexService;
use App\Services\GriReportService;
use App\Services\PlanEntitlementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class GriReportController extends DisclosureBaseController
{
    public function __construct(
        protected GriReportService $reportService,
        protected GriContentIndexService $contentIndexService,
        protected ExportReadinessService $exportReadiness,
    ) {
    }

    public function preview(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);

        return view('disclosures.gri-report-preview', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'report' => $this->reportService->build($company, $fiscalYear),
            'dataReadiness' => $this->exportReadiness->assessCompany($company, $fiscalYear, false),
        ]);
    }

    public function exportPdf(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);
        $this->requirePermission('disclosures', 'export', [['reports', 'view']]);
        $this->requireDisclosureExport($company->id, PlanEntitlementService::EXPORT_GRI_PDF, $fiscalYear);

        $report = $this->reportService->build($company, $fiscalYear);

        $pdf = Pdf::loadView('reports.gri-pdf', [
            'report' => $report,
            'companyLogo' => $company->logoDataUri(),
            'platformLogo' => $this->platformLogoDataUri(),
        ])->setPaper('a4', 'portrait')->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'dejavu sans',
        ]);

        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($company->name ?? 'company'));

        return $pdf->download("gri-sustainability-report-{$fiscalYear}-{$slug}.pdf");
    }

    public function exportContentIndex(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);
        $this->requirePermission('disclosures', 'export', [['reports', 'view']]);
        $this->requireDisclosureExport($company->id, PlanEntitlementService::EXPORT_GRI_CONTENT_INDEX, $fiscalYear);

        $csv = $this->contentIndexService->toCsv($company, $fiscalYear, false);
        $filename = 'gri-content-index-' . $fiscalYear . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportContentIndexExtended(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);
        $this->requirePermission('disclosures', 'export', [['reports', 'view']]);
        $this->requireDisclosureExport($company->id, PlanEntitlementService::EXPORT_GRI_CONTENT_INDEX, $fiscalYear);

        $csv = $this->contentIndexService->toCsv($company, $fiscalYear, true);
        $filename = 'gri-content-index-full-' . $fiscalYear . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportContentIndexEnterprise(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);
        $this->requirePermission('disclosures', 'export', [['reports', 'view']]);
        $this->requireDisclosureExport($company->id, PlanEntitlementService::EXPORT_GRI_CONTENT_INDEX_EXTENDED, $fiscalYear);

        $csv = $this->contentIndexService->toEnterpriseCsv($company, $fiscalYear, true);
        $filename = 'gri-content-index-enterprise-' . $fiscalYear . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    protected function platformLogoDataUri(): ?string
    {
        foreach ([public_path('images/menetzero.png'), public_path('images/menetzero.jpg')] as $path) {
            if (!is_file($path)) {
                continue;
            }
            $mime = mime_content_type($path) ?: 'image/png';

            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
        }

        return null;
    }
}
