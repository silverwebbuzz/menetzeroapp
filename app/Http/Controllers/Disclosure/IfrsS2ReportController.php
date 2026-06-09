<?php

namespace App\Http\Controllers\Disclosure;

use App\Services\IfrsS2ReportService;
use App\Services\PlanEntitlementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class IfrsS2ReportController extends DisclosureBaseController
{
    public function __construct(
        protected IfrsS2ReportService $reportService,
    ) {
    }

    public function preview(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);
        $report = $this->reportService->build($company, $fiscalYear);

        return view('disclosures.report-preview', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'report' => $report,
        ]);
    }

    public function exportPdf(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);
        $this->requirePermission('disclosures', 'export', [['reports', 'view']]);
        $this->requireDisclosureExport($company->id, PlanEntitlementService::EXPORT_IFRS_S2_PDF, $fiscalYear);

        $report = $this->reportService->build($company, $fiscalYear);

        $pdf = Pdf::loadView('reports.ifrs-s2-pdf', [
            'report' => $report,
            'companyLogo' => $company->logoDataUri(),
            'platformLogo' => $this->platformLogoDataUri(),
        ])->setPaper('a4', 'portrait')->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'dejavu sans',
        ]);

        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($company->name ?? 'company'));

        return $pdf->download("ifrs-s2-climate-report-{$fiscalYear}-{$slug}.pdf");
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
