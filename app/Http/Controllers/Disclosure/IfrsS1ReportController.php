<?php

namespace App\Http\Controllers\Disclosure;

use App\Services\IfrsS1ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class IfrsS1ReportController extends DisclosureBaseController
{
    public function __construct(
        protected IfrsS1ReportService $reportService,
    ) {
    }

    public function preview(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);
        $includeS2 = $request->boolean('include_s2', true);
        $report = $this->reportService->build($company, $fiscalYear, $includeS2);

        return view('disclosures.s1-report-preview', [
            'company' => $company,
            'fiscalYear' => $fiscalYear,
            'report' => $report,
            'includeS2' => $includeS2,
        ]);
    }

    public function exportPdf(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);
        $this->requirePermission('disclosures', 'export', [['reports', 'view']]);

        $includeS2 = $request->boolean('include_s2', true);
        $report = $this->reportService->build($company, $fiscalYear, $includeS2);

        $pdf = Pdf::loadView('reports.ifrs-s1-pdf', [
            'report' => $report,
            'companyLogo' => $company->logoDataUri(),
            'platformLogo' => $this->platformLogoDataUri(),
        ])->setPaper('a4', 'portrait')->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'dejavu sans',
        ]);

        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($company->name ?? 'company'));

        return $pdf->download("ifrs-s1-sustainability-report-{$fiscalYear}-{$slug}.pdf");
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
