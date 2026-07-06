<?php

namespace App\Http\Controllers\Disclosure;

use App\Services\ExportReadinessService;
use App\Services\PlanEntitlementService;
use App\Services\UaeEsgReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class UaeEsgReportController extends DisclosureBaseController
{
    public function __construct(
        protected UaeEsgReportService $reportService,
        protected ExportReadinessService $exportReadiness,
    ) {
    }

    public function preview(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);

        return view('disclosures.uae-esg-report-preview', [
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
        $this->requireDisclosureExport($company->id, PlanEntitlementService::EXPORT_UAE_ESG_PDF, $fiscalYear);

        $report = $this->reportService->build($company, $fiscalYear);

        $pdf = Pdf::loadView('reports.uae-esg-pdf', [
            'report' => $report,
            'companyLogo' => $company->logoDataUri(),
            'platformLogo' => $this->platformLogoDataUri(),
        ])->setPaper('a4', 'portrait')->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'dejavu sans',
        ]);

        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($company->name ?? 'company'));

        return $pdf->download("uae-esg-report-{$fiscalYear}-{$slug}.pdf");
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
