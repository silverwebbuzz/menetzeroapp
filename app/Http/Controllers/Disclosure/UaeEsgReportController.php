<?php

namespace App\Http\Controllers\Disclosure;

use App\Services\AssuranceDocumentService;
use App\Services\EnterpriseCoverService;
use App\Services\ExportReadinessService;
use App\Services\PlanEntitlementService;
use App\Services\UaeEsgReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UaeEsgReportController extends DisclosureBaseController
{
    public function __construct(
        protected UaeEsgReportService $reportService,
        protected ExportReadinessService $exportReadiness,
        protected AssuranceDocumentService $assuranceService,
        protected EnterpriseCoverService $coverService,
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

    public function exportPdfEnterprise(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);
        $this->requirePermission('disclosures', 'export', [['reports', 'view']]);
        $this->requireDisclosureExport($company->id, PlanEntitlementService::EXPORT_UAE_ESG_PDF_ENTERPRISE, $fiscalYear);

        $report = $this->reportService->build($company, $fiscalYear);
        $enterpriseCover = $this->coverService->build($company, $fiscalYear, $report);

        $pdf = Pdf::loadView('reports.uae-esg-pdf', [
            'report' => $report,
            'companyLogo' => $company->logoDataUri(),
            'platformLogo' => null,
            'enterpriseCover' => $enterpriseCover,
        ])->setPaper('a4', 'portrait')->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'dejavu sans',
        ]);

        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($company->name ?? 'company'));

        return $pdf->download("uae-esg-report-enterprise-{$fiscalYear}-{$slug}.pdf");
    }

    public function uploadAssurance(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);
        $this->requireDisclosureExport($company->id, PlanEntitlementService::FEATURE_ASSURANCE_UPLOAD, $fiscalYear);

        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240',
        ]);

        $this->assuranceService->store($company->id, $fiscalYear, $request->file('file'));

        return $this->fiscalRedirect('disclosures.uae-esg.overview', $fiscalYear, 'Assurance statement uploaded.');
    }

    public function downloadAssurance(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request);
        $this->requireDisclosureExport($company->id, PlanEntitlementService::FEATURE_ASSURANCE_UPLOAD, $fiscalYear);

        $resolved = $this->assuranceService->resolveDownload($company->id, $fiscalYear);

        return Storage::disk('local')->download(
            $resolved['path'],
            $resolved['downloadName'],
            ['Content-Type' => $resolved['mime']]
        );
    }

    public function deleteAssurance(Request $request)
    {
        ['company' => $company, 'fiscalYear' => $fiscalYear] = $this->resolveContext($request, true);
        $this->requireDisclosureExport($company->id, PlanEntitlementService::FEATURE_ASSURANCE_UPLOAD, $fiscalYear);

        $this->assuranceService->delete($company->id, $fiscalYear);

        return $this->fiscalRedirect('disclosures.uae-esg.overview', $fiscalYear, 'Assurance statement removed.');
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
